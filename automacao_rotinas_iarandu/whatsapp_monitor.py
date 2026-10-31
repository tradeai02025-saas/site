#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
IArandú — Monitor de WhatsApp & Alertas Estruturados (OpenWA Gateway)
Rotulagem automática com 'Iarandú' (Label ID 16) e Notificação Pessoal para Diego
"""

import json
import urllib.request
import urllib.error
import sqlite3
from datetime import datetime
from typing import Dict, List, Optional

from config import (
    OPENWA_BASE_URL,
    OPENWA_API_KEY,
    OPENWA_SESSION_ID,
    OPENWA_LABEL_IARANDU_ID,
    DIEGO_WHATSAPP_CHAT_ID,
    CENTRAL_DB,
    get_active_openwa_session
)

class WhatsAppMonitor:
    def __init__(self):
        self.session_id = get_active_openwa_session()
        self.base_url = f"{OPENWA_BASE_URL}/api/sessions/{self.session_id}"
        self.headers = {
            "X-Api-Key": OPENWA_API_KEY,
            "Content-Type": "application/json"
        }

    def _refresh_session(self):
        self.session_id = get_active_openwa_session()
        self.base_url = f"{OPENWA_BASE_URL}/api/sessions/{self.session_id}"

    def _request(self, method: str, endpoint: str, data: Optional[Dict] = None) -> Optional[Dict]:
        url = f"{self.base_url}{endpoint}"
        body = json.dumps(data).encode("utf-8") if data else None
        req = urllib.request.Request(url, data=body, headers=self.headers, method=method)
        try:
            with urllib.request.urlopen(req, timeout=15) as resp:
                raw = resp.read().decode("utf-8")
                return json.loads(raw) if raw else {"success": True}
        except urllib.error.HTTPError as e:
            err_body = e.read().decode('utf-8', errors='ignore')
            if e.code in (400, 404):
                self._refresh_session()
            print(f"[WhatsAppMonitor] HTTPError {e.code} em {endpoint}: {err_body}")
        except Exception as e:
            print(f"[WhatsAppMonitor] Erro em {endpoint}: {e}")
        return None

    def get_chats_with_iarandu_label(self) -> List[Dict]:
        """Obtém todas as conversas rotuladas com a etiqueta 'Iarandú'."""
        endpoint = f"/labels/{OPENWA_LABEL_IARANDU_ID}/chats"
        res = self._request("GET", endpoint)
        if not res or not isinstance(res, list):
            lbls = self._request("GET", "/labels")
            if lbls and isinstance(lbls, list) and len(lbls) > 0:
                first_lid = lbls[0].get("id")
                if first_lid and first_lid != OPENWA_LABEL_IARANDU_ID:
                    res = self._request("GET", f"/labels/{first_lid}/chats")
        return res if isinstance(res, list) else []

    def ensure_chat_labeled(self, chat_id: str) -> bool:
        """Garante que a conversa comercial tenha a etiqueta 'Iarandú' aplicada."""
        endpoint = f"/labels/chat/{chat_id}"
        payload = {"labelId": OPENWA_LABEL_IARANDU_ID}
        res = self._request("POST", endpoint, payload)
        return bool(res and res.get("success", False))

    def send_alert_to_diego(self, origem: str, agencia: str, contato: str, mensagem: str, sugestao_acao: str) -> bool:
        """
        Envia mensagem estruturada para o chat pessoal de Diego no WhatsApp.
        Formato aprovado: '1 Clique para Aprovar'
        """
        texto_alerta = (
            f"🔔 *IArandú Alerta | Lead Respondeu ({origem})*\n\n"
            f"*Agência:* {agencia}\n"
            f"*Contato:* {contato}\n"
            f"*Mensagem:* \"{mensagem}\"\n\n"
            f"*Sugestão de Ação:* {sugestao_acao}\n\n"
            f"━━━━━━━━━━━━━━━━━━\n"
            f"👉 *Responda:* \n"
            f"`1` para aprovar o disparo automático\n"
            f"`2` para você mesmo responder"
        )
        return self.send_text_message(DIEGO_WHATSAPP_CHAT_ID, texto_alerta)

    def send_text_message(self, chat_id: str, text: str) -> bool:
        """Dispara mensagem de texto via OpenWA."""
        endpoint = "/messages/send-text"
        payload = {
            "chatId": chat_id,
            "text": text
        }
        res = self._request("POST", endpoint, payload)
        return bool(res and (res.get("messageId") or res.get("id")))

    def check_new_inbound_messages(self, limit: int = 20) -> List[Dict]:
        """
        Inspeciona chats para detectar respostas recentes que ainda não foram tratadas.
        Cruza com as conversas que possuem etiqueta Iarandú ou registros de disparos.
        """
        chats = self.get_chats_with_iarandu_label()
        inbound_pendentes = []

        for ch in chats[:limit]:
            chat_id = ch.get("id")
            unread = ch.get("unreadCount", 0)
            name = ch.get("name", "Contato")
            
            # Se houver mensagens não lidas ou atividade recente
            if unread > 0:
                history = self._request("GET", f"/messages/{chat_id}/history?limit={min(unread + 1, 5)}")
                if history and isinstance(history, list):
                    for msg in history:
                        if not msg.get("fromMe", False) and msg.get("type") == "text":
                            inbound_pendentes.append({
                                "chat_id": chat_id,
                                "name": name,
                                "body": msg.get("body", ""),
                                "timestamp": msg.get("timestamp", 0),
                                "unread": unread
                            })
                            break
        return inbound_pendentes
