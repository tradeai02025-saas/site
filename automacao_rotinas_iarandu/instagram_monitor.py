#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
IArandú — Monitor em Tempo Real de Instagram DMs (@maldonadodiego55 e @iarandu.br)
Extração via CDP/Evaluate no Maestri, detecção precisa de Inbound vs Outbound,
cruzamento de histórico e envio de alertas estruturados no WhatsApp
"""

import subprocess
import json
import sqlite3
import os
import re
from datetime import datetime
from typing import Dict, List, Optional

from config import (
    MAESTRI_CLI,
    PORTAL_IG_COMERCIAL,
    PORTAL_IG_IARANDU,
    CHECKPOINT_DB,
    DIEGO_WHATSAPP_CHAT_ID
)
from whatsapp_monitor import WhatsAppMonitor

DB_TIAO_ATENDIMENTO = "/mnt/d/tião/dados/atendimento_checkpoint.sqlite3"

class InstagramMonitor:
    def __init__(self):
        self.cli_path = MAESTRI_CLI
        self.whatsapp_monitor = WhatsAppMonitor()

    def get_portal_conversations(self, portal_name: str) -> Dict:
        """
        Extrai em tempo real a lista de conversas ativas do Instagram Direct usando JavaScript evaluate.
        Identifica automaticamente se a última mensagem foi enviada pelo contato (Inbound) ou por nós (Outbound).
        """
        js_code = """(() => {
            const rows = document.querySelectorAll('div[role="button"], div[role="listitem"]');
            return JSON.stringify(Array.from(rows).map(r => r.innerText).filter(x => x && x.length > 5));
        })()"""
        cmd = [self.cli_path, "portal", "evaluate", portal_name, js_code]

        try:
            res = subprocess.run(
                cmd,
                stdin=subprocess.DEVNULL,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                text=True,
                timeout=10
            )
            if res.returncode == 0:
                raw_list = json.loads(res.stdout.strip())
                seen_names = set()
                conversations = []

                for item in raw_list:
                    # Filtra por carimbos de tempo característicos do Direct
                    if re.search(r'·\s*\d+\s*(?:min|h|d|sem|s)', item) or re.search(r'há\s*\d+\s*(?:min|h|d|sem|s)', item, re.IGNORECASE):
                        lines = [l.strip() for l in item.split('\n') if l.strip()]
                        if len(lines) >= 2:
                            nome = lines[0]
                            if nome in seen_names or "sua nota" in item.lower():
                                continue
                            seen_names.add(nome)

                            time_line = lines[-1]
                            msg_line = lines[1] if len(lines) > 1 else ""
                            is_inbound = not msg_line.lower().startswith("você:")

                            conversations.append({
                                "nome": nome,
                                "ultima_mensagem": msg_line,
                                "tempo": time_line,
                                "inbound": is_inbound
                            })

                inbound_count = sum(1 for c in conversations if c["inbound"])
                return {
                    "portal": portal_name,
                    "status": "autenticado_online",
                    "total_conversas": len(conversations),
                    "inbound_respostas": inbound_count,
                    "conversas": conversations
                }
        except Exception as e:
            print(f"[InstagramMonitor] Erro avaliando portal {portal_name}: {e}")

        return {
            "portal": portal_name,
            "status": "indisponivel",
            "total_conversas": 0,
            "inbound_respostas": 0,
            "conversas": []
        }

    def get_historical_interactions(self) -> List[Dict]:
        """Recupera histórico auditado de interações no Instagram do banco de dados local."""
        if not os.path.exists(DB_TIAO_ATENDIMENTO):
            return []

        interactions = []
        try:
            conn = sqlite3.connect(DB_TIAO_ATENDIMENTO)
            conn.row_factory = sqlite3.Row
            c = conn.cursor()
            rows = c.execute("""
                SELECT id, canal, chat_id, contato_nome, recebido_em, conteudo_sanitizado,
                       respondido_por_diego, resposta_diego_texto, status_aprovacao
                FROM atendimento_mensagens
                WHERE canal LIKE 'instagram%'
                ORDER BY id DESC
            """).fetchall()
            for r in rows:
                interactions.append(dict(r))
            conn.close()
        except Exception as e:
            print(f"[InstagramMonitor] Erro lendo histórico: {e}")
        return interactions

    def get_full_overview(self) -> Dict:
        """Gera visão completa e consolidada das duas contas do Instagram."""
        conv_comercial = self.get_portal_conversations(PORTAL_IG_COMERCIAL)
        conv_iarandu = self.get_portal_conversations(PORTAL_IG_IARANDU)
        history = self.get_historical_interactions()

        return {
            "accounts": {
                "@maldonadodiego55": conv_comercial,
                "@iarandu.br": conv_iarandu
            },
            "historico_interacoes": history,
            "total_interacoes_registradas": len(history)
        }

    def alert_pending_dms(self) -> int:
        """
        Dispara alertas estruturados para o WhatsApp de Diego para cada resposta recebida de contato/lead.
        Registra no banco rotina_checkpoint.sqlite3 para não duplicar avisos.
        """
        overview = self.get_full_overview()
        alertas_enviados = 0

        conn = sqlite3.connect(CHECKPOINT_DB)
        c = conn.cursor()

        # 1. Alerta sobre respostas em tempo real encontradas nos portais
        for conta, data in overview["accounts"].items():
            for conv in data.get("conversas", []):
                if conv["inbound"]:
                    contato_nome = conv["nome"]
                    msg_texto = conv["ultima_mensagem"]

                    # Ignora mensagens de erro/restrição de DM padrão da Meta
                    if "não pode receber sua mensagem" in msg_texto or "nem todos podem enviar" in msg_texto.lower():
                        continue

                    # Verifica se já alertamos hoje
                    ja_alertado = c.execute(
                        "SELECT 1 FROM pendencias WHERE canal = ? AND identificador = ?",
                        (conta, contato_nome)
                    ).fetchone()

                    if not ja_alertado:
                        print(f"[InstagramMonitor] Nova resposta detectada em {conta}: {contato_nome} -> {msg_texto[:40]}")
                        sugestao = "Responder no Direct propondo envio da apresentação comercial ou verificar interesse."
                        ok = self.whatsapp_monitor.send_alert_to_diego(
                            origem=f"Instagram {conta}",
                            agencia=contato_nome,
                            contato=contato_nome,
                            mensagem=msg_texto,
                            sugestao_acao=sugestao
                        )
                        if ok:
                            alertas_enviados += 1
                            c.execute("""
                                INSERT INTO pendencias (canal, identificador, agencia, mensagem, status, atualizado_em)
                                VALUES (?, ?, ?, ?, ?, ?)
                            """, (
                                conta,
                                contato_nome,
                                contato_nome,
                                msg_texto,
                                "alerta_enviado",
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                            ))
                            conn.commit()

        conn.close()
        return alertas_enviados

if __name__ == "__main__":
    monitor = InstagramMonitor()
    alertas = monitor.alert_pending_dms()
    print(f"Alertas disparados: {alertas}")
    overview = monitor.get_full_overview()
    print(json.dumps(overview, ensure_ascii=False, indent=2))
