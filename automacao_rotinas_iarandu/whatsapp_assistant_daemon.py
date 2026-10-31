#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
IArandú — Assistente Pessoal Sexta-Feira via WhatsApp Self-Chat
Escuta mensagens de Diego Maldonado (@maldonadodiego / 6562760384672@lid)
e responde de forma autônoma e inteligente utilizando Groq openai/gpt-oss-120b.
Imune a falhas de rede via dns_fix.
"""

import sys
import os
import time
import json
import socket
import logging
from datetime import datetime

import httpx

# Garante path para dns_fix do tião e config do iarandu
sys.path.insert(0, "/mnt/d/tião")
sys.path.insert(0, "/mnt/d/site-iarandu/automacao_rotinas_iarandu")
from alfred import dns_fix, segredos
from config import get_active_openwa_session, OPENWA_BASE_URL, OPENWA_API_KEY, DIEGO_WHATSAPP_CHAT_ID

logging.basicConfig(
    level=logging.INFO,
    format="[%(asctime)s] [%(levelname)s] [Sexta-Feira WA] %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S"
)
logger = logging.getLogger("whatsapp_assistant")

DIEGO_CHAT_ID = DIEGO_WHATSAPP_CHAT_ID

HEADERS_OPENWA = {
    "X-Api-Key": OPENWA_API_KEY,
    "Content-Type": "application/json"
}

def get_base_session_url():
    session_id = get_active_openwa_session()
    return f"{OPENWA_BASE_URL}/api/sessions/{session_id}"


GROQ_KEY = segredos.obter("groq")

SYSTEM_PROMPT = """Você é a Sexta-Feira, assistente executiva pessoal e de negócios de Diego Maldonado na IArandú Tecnologia (iarandu.com.br).
Seu tom é executivo, direto, perspicaz, cordial e resolutivo.
Você conhece as operações da IArandú:
- Prospecção comercial de agências de publicidade (Revenue OS / Maestri)
- Automação de checking de mídia, conciliação de PIs e faturamento para agências
- Contas oficiais: contato@iarandu.com.br (Zoho Mail), @iarandu.br no Instagram, Diego no WhatsApp
- Reuniões com agências às 10h30 (padrão)
Responda diretamente à mensagem de Diego com clareza, objetividade e foco em resultados. Seja sempre prestativa."""

def get_history(limit=5):
    base = get_base_session_url()
    url = f"{base}/messages/{DIEGO_CHAT_ID}/history?limit={limit}"
    try:
        r = httpx.get(url, headers=HEADERS_OPENWA, timeout=10)
        if r.status_code == 200:
            return r.json()
    except Exception as e:
        logger.error(f"Erro ao buscar histórico: {e}")
    return []

def send_whatsapp_message(text: str) -> bool:
    base = get_base_session_url()
    url = f"{base}/messages/send-text"
    payload = {
        "chatId": DIEGO_CHAT_ID,
        "text": text
    }
    try:
        r = httpx.post(url, headers=HEADERS_OPENWA, json=payload, timeout=15)
        if r.status_code in (200, 201):
            logger.info("Resposta enviada com sucesso no WhatsApp.")
            return True
        logger.error(f"Falha ao enviar mensagem: {r.status_code} - {r.text}")
    except Exception as e:
        logger.error(f"Erro na requisição de envio: {e}")
    return False

def pensar(mensagem_usuario: str) -> str:
    """Invoca Groq openai/gpt-oss-120b para resposta em alta inteligência e baixa latência."""
    url = "https://api.groq.com/openai/v1/chat/completions"
    headers = {
        "Authorization": f"Bearer {GROQ_KEY}",
        "Content-Type": "application/json"
    }
    payload = {
        "model": "openai/gpt-oss-120b",
        "messages": [
            {"role": "system", "content": SYSTEM_PROMPT},
            {"role": "user", "content": mensagem_usuario}
        ],
        "temperature": 0.5,
        "max_tokens": 600
    }
    try:
        r = httpx.post(url, headers=headers, json=payload, timeout=20)
        if r.status_code == 200:
            return r.json()["choices"][0]["message"]["content"].strip()
        logger.warning(f"Groq retornou {r.status_code}: {r.text[:200]}")
    except Exception as e:
        logger.error(f"Erro no cérebro Groq: {e}")
    return "Diego, estou com uma leve oscilação externa no provedor, mas estou operando. O que você precisa que eu execute?"

def run_loop():
    logger.info("Iniciando daemon do assistente pessoal WhatsApp para Diego Maldonado...")
    # Marca as mensagens existentes para não responder o passado
    historico_inicial = get_history(10)
    processadas = set(m.get("id") for m in historico_inicial if m.get("id"))
    logger.info(f"Histórico inicial carregado: {len(processadas)} mensagens indexadas.")

    while True:
        try:
            mensagens = get_history(5)
            for m in mensagens:
                m_id = m.get("id")
                if not m_id or m_id in processadas:
                    continue

                processadas.add(m_id)

                # Identifica se é uma mensagem enviada por Diego do celular
                # Quando o bot envia, m['body'] tem formato de alerta e from='6562760384672@lid'
                # Quando Diego envia do celular, from='554198136312@c.us' ou author='6562760384672@lid' sem prefixos do bot
                origem = m.get("from", "")
                tipo = m.get("type", "text")
                corpo = (m.get("body") or "").strip()

                # Ignora mensagens de alertas enviadas pelo próprio robô
                if "🔔 *IArandú Alerta" in corpo or "Estou aqui, Diego!" in corpo:
                    continue

                # Se a mensagem partiu de Diego (celular ou web cliente)
                if origem == "554198136312@c.us" or (m.get("fromMe") and m.get("author") == DIEGO_CHAT_ID and corpo):
                    logger.info(f"Nova mensagem de Diego recebida [Tipo: {tipo}]: {corpo[:60]}")

                    if tipo == "text" and corpo:
                        # Diego enviou texto
                        resposta = pensar(corpo)
                        send_whatsapp_message(resposta)
                    elif tipo in ("voice", "audio"):
                        # Diego enviou áudio
                        msg_aviso = (
                            "🎙️ *Sexta-Feira:* Diego, vi que você enviou um áudio pelo WhatsApp!\n\n"
                            "Como o WhatsApp Web sincroniza mensagens para si mesmo na nuvem sem armazenar o arquivo bruto no computador, "
                            "no celular prefira mandar por texto ou *usar a digitação por voz do teclado do celular* (o ícone de microfone do teclado do WhatsApp transcreve suas palavras em texto com precisão máxima instantaneamente!).\n\n"
                            "E quando estiver no computador, na nossa central Jarvis (http://localhost:8765), o nosso Whisper Large v3 com voz contínua está 100% ativo."
                        )
                        send_whatsapp_message(msg_aviso)

            time.sleep(4)
        except Exception as e:
            logger.error(f"Erro no loop do daemon WhatsApp: {e}")
            time.sleep(5)

if __name__ == "__main__":
    run_loop()
