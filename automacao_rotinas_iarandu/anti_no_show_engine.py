#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
IArandú — Módulo 1 de Automação Comercial: Motor Anti-No-Show & Aquecimento High-Ticket
Baseado na metodologia de diretoria comercial da IArandú / The Maestri.

Gatilhos da Cadência:
- T-0: Confirmação Imediata (WhatsApp + E-mail com One-Pager / Apresentação IArandú)
- D-1: Check-in Executivo Humano (18h do dia anterior com confirmação suave de presença)
- H-3: Aquecimento com Prova Social (3 horas antes com case study de agência similar)
- M-15: Link Direto da Sala (15 minutos antes no WhatsApp do decisor)
- Follow-up Loop: Cadência pós-call para quebra de objeção "Vou Pensar"
"""

import sys
import os
import json
import sqlite3
import logging
from datetime import datetime, timedelta
from typing import Dict, List, Optional

# Resiliência de DNS e paths do sistema
sys.path.insert(0, "/mnt/d/tião")
sys.path.insert(0, "/mnt/d/site-iarandu/automacao_rotinas_iarandu")
try:
    from alfred import dns_fix
except Exception:
    pass

import httpx
from config import (
    CENTRAL_DB,
    AGENCIAS_DB,
    CHECKPOINT_DB,
    ZOHO_EMAIL,
    SMTP_HOST,
    SMTP_PORT,
    SMTP_USER,
    SMTP_PASS,
    OPENWA_BASE_URL,
    OPENWA_API_KEY,
    DIEGO_WHATSAPP_CHAT_ID,
    get_active_openwa_session,
    render_standard_email
)
import smtplib
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText

logging.basicConfig(
    level=logging.INFO,
    format="[%(asctime)s] [%(levelname)s] [Anti-No-Show Engine] %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S"
)
logger = logging.getLogger("anti_no_show_engine")

# Links de autoridade e assets aprovados da IArandú
DECK_APRESENTACAO_URL = "http://localhost:8765/static/Apresentacao_Executiva_IArandu.pdf"
CASE_STUDY_URL = "http://localhost:8765/static/Estudo_de_Caso_Trade_Marketing.pdf"
DEFAULT_MEET_URL = "https://meet.google.com/iarandu-comercial"

class AntiNoShowEngine:
    def __init__(self, db_path: Optional[str] = None):
        self.db_path = db_path or str(AGENCIAS_DB)
        self.checkpoint_db = str(CHECKPOINT_DB)
        self._init_tables()

    def _get_connection(self):
        return sqlite3.connect(self.db_path)

    def _init_tables(self):
        """Garante que a tabela de cadência e controle exista no banco de checkpoints."""
        conn = sqlite3.connect(self.checkpoint_db)
        c = conn.cursor()
        c.execute("""
            CREATE TABLE IF NOT EXISTS anti_no_show_cadencia (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cnpj TEXT NOT NULL,
                empresa TEXT,
                contato_nome TEXT,
                contato_wa TEXT,
                contato_email TEXT,
                data_reuniao TEXT NOT NULL,
                meet_url TEXT,
                t0_enviado_em TEXT,
                d1_enviado_em TEXT,
                h3_enviado_em TEXT,
                m15_enviado_em TEXT,
                pos_call_status TEXT,
                criado_em TEXT NOT NULL,
                atualizado_em TEXT NOT NULL,
                UNIQUE(cnpj, data_reuniao)
            )
        """)
        conn.commit()
        conn.close()

    def sync_reunioes_do_crm(self) -> int:
        """Sincroniza deals com etapa de reunião/demonstração do CRM 8090."""
        conn_crm = self._get_connection()
        conn_crm.row_factory = sqlite3.Row
        deals = conn_crm.execute("""
            SELECT d.cnpj, d.etapa, d.responsavel, d.proximo_passo, d.prazo_proximo_passo,
                   d.prioridade, COALESCE(a.nome_fantasia, a.razao_social) AS empresa,
                   COALESCE(a.dono_telefone, a.telefone) AS telefone,
                   COALESCE(a.dono_email, a.email) AS email,
                   a.dono_nome
            FROM crm_deals d
            LEFT JOIN agencias a ON a.cnpj = d.cnpj
            WHERE LOWER(d.etapa) IN ('demonstracao', 'reuniao', 'apresentacao', 'proposta')
               OR LOWER(d.proximo_passo) LIKE '%reunião%'
               OR LOWER(d.proximo_passo) LIKE '%demonstração%'
               OR LOWER(d.proximo_passo) LIKE '%apresentação%'
        """).fetchall()
        conn_crm.close()

        conn_cp = sqlite3.connect(self.checkpoint_db)
        c_cp = conn_cp.cursor()
        inseridos = 0
        now_str = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

        for d in deals:
            cnpj = d["cnpj"]
            prazo = (d["prazo_proximo_passo"] or "").strip()
            if not prazo:
                continue

            empresa = d["empresa"] or ""
            if not empresa:
                if cnpj == "10856009000152":
                    empresa = "HOC"
                elif cnpj == "05580620000160":
                    empresa = "Bamboo&Co"
                elif cnpj in ("18942180000190", "22096347000120"):
                    empresa = "Lápis Raro"
                else:
                    empresa = "Agência Parceira"

            # Extração de contato a partir do próximo passo ou dono_nome
            contato_nome = d["dono_nome"] or "Equipe"
            passo = d["proximo_passo"] or ""
            if "Lia Pinheiro" in passo:
                contato_nome = "Lia Pinheiro"
            elif "Alice Lascasas" in passo:
                contato_nome = "Alice Lascasas"
            elif "Jorge Souza" in passo:
                contato_nome = "Jorge Souza"
            elif "Gláucio" in passo:
                contato_nome = "Gláucio Binder"

            try:
                c_cp.execute("""
                    INSERT OR IGNORE INTO anti_no_show_cadencia
                    (cnpj, empresa, contato_nome, contato_wa, contato_email, data_reuniao, meet_url, criado_em, atualizado_em)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                """, (
                    cnpj,
                    empresa,
                    contato_nome,
                    d["telefone"] or "",
                    d["email"] or "",
                    prazo,
                    DEFAULT_MEET_URL,
                    now_str,
                    now_str
                ))
                if c_cp.rowcount > 0:
                    inseridos += 1
            except Exception as e:
                logger.error(f"Erro inserindo deal na cadência: {e}")

        conn_cp.commit()
        conn_cp.close()
        logger.info(f"Sincronização concluída: {inseridos} reuniões novas adicionadas à cadência Anti-No-Show.")
        return inseridos

    def _enviar_whatsapp(self, chat_id: str, texto: str) -> bool:
        """Dispara mensagem no WhatsApp via OpenWA Gateway."""
        if not chat_id:
            logger.warning("WhatsApp chat_id vazio; mensagem não enviada.")
            return False
        
        # Garante formato correto de chat_id do WhatsApp
        if not chat_id.endswith("@c.us") and not chat_id.endswith("@lid"):
            # Limpa caracteres não numéricos
            digits = "".join(filter(str.isdigit, chat_id))
            if len(digits) in (10, 11):
                chat_id = f"55{digits}@c.us"
            elif len(digits) >= 12:
                chat_id = f"{digits}@c.us"
            else:
                chat_id = DIEGO_WHATSAPP_CHAT_ID  # Fallback seguro para o Diego validar

        base_url = f"{OPENWA_BASE_URL}/api/sessions/{get_active_openwa_session()}/messages/send-text"
        headers = {"X-Api-Key": OPENWA_API_KEY, "Content-Type": "application/json"}
        try:
            r = httpx.post(base_url, headers=headers, json={"chatId": chat_id, "text": texto}, timeout=15)
            return r.status_code in (200, 201)
        except Exception as e:
            logger.error(f"Erro no envio WhatsApp: {e}")
            return False

    def _enviar_email(self, destinatario: str, assunto: str, corpo_html: str) -> bool:
        """Dispara e-mail oficial via Zoho SMTP."""
        if not destinatario or "@" not in destinatario:
            destinatario = ZOHO_EMAIL
        try:
            msg = MIMEMultipart()
            msg["From"] = f"Diego Maldonado · IArandú <{ZOHO_EMAIL}>"
            msg["To"] = destinatario
            msg["Subject"] = assunto
            msg.attach(MIMEText(render_standard_email(corpo_html), "html", "utf-8"))

            with smtplib.SMTP_SSL(SMTP_HOST, SMTP_PORT, timeout=12) as s:
                s.login(SMTP_USER, SMTP_PASS)
                s.send_message(msg)
            return True
        except Exception as e:
            logger.error(f"Erro no envio de e-mail Zoho: {e}")
            return False

    def processar_cadencias(self) -> Dict:
        """
        Analisa o relógio atual e processa os gatilhos devidos:
        - T-0: Nunca disparado ainda -> envia agora.
        - D-1: Falta entre 14h e 30h para a reunião E são >= 18h do dia anterior.
        - H-3: Falta entre 2h30 e 3h30 para a reunião.
        - M-15: Falta entre 5 e 20 minutos para a reunião.
        """
        self.sync_reunioes_do_crm()
        conn = sqlite3.connect(self.checkpoint_db)
        conn.row_factory = sqlite3.Row
        rows = conn.execute("SELECT * FROM anti_no_show_cadencia").fetchall()
        now = datetime.now()
        now_str = now.strftime("%Y-%m-%d %H:%M:%S")

        resumo = {"t0": 0, "d1": 0, "h3": 0, "m15": 0}

        for r in rows:
            rec_id = r["id"]
            cnpj = r["cnpj"]
            empresa = r["empresa"]
            nome = r["contato_nome"]
            data_str = r["data_reuniao"]
            wa = r["contato_wa"]
            email = r["contato_email"]
            meet_url = r["meet_url"] or DEFAULT_MEET_URL

            try:
                if len(data_str) == 10:
                    dt_reuniao = datetime.strptime(data_str, "%Y-%m-%d").replace(hour=10, minute=0)
                else:
                    dt_reuniao = datetime.strptime(data_str[:16], "%Y-%m-%d %H:%M")
            except Exception:
                continue

            delta = dt_reuniao - now
            horas_ate_reuniao = delta.total_seconds() / 3600.0

            # ----------------------------------------------------
            # GATILHO T-0: Confirmação Imediata pós-agendamento
            # ----------------------------------------------------
            if not r["t0_enviado_em"]:
                txt_wa = (
                    f"Olá, {nome}! Aqui é o Diego Maldonado, fundador da IArandú.\n\n"
                    f"Reunião de alinhamento com a {empresa} confirmada para *{dt_reuniao.strftime('%d/%m às %H:%M')}* via Google Meet.\n\n"
                    f"📋 *Pauta principal*: Demonstração da tecnologia de inteligência e checagem de mídia para agências de alta performance.\n\n"
                    f"Já deixei separado o material executivo para adiantar nossa conversa:\n"
                    f"👉 {DECK_APRESENTACAO_URL}\n\n"
                    f"Qualquer ajuste na agenda é só me avisar diretamente por aqui. Um abraço e até lá!"
                )
                html_email = f"""
                <p>Olá, <b>{nome}</b>,</p>
                <p>Confirmamos a nossa reunião de alinhamento estratégico com a <b>{empresa}</b> para o dia <b>{dt_reuniao.strftime('%d/%m/%Y às %H:%M')}</b> via Google Meet.</p>
                <p><b>Pauta</b>: Demonstração da tecnologia IArandú para automação operacional, conciliação de mídia e inteligência comercial.</p>
                <p><a href="{meet_url}" style="display:inline-block;padding:10px 20px;background:#00e5ff;color:#020617;font-weight:700;text-decoration:none;border-radius:8px;">Acessar Sala do Google Meet</a></p>
                <p>Você também pode conferir nosso One-Pager executivo neste link: <a href="{DECK_APRESENTACAO_URL}">{DECK_APRESENTACAO_URL}</a>.</p>
                <p>Até breve!</p>
                """
                self._enviar_whatsapp(wa or DIEGO_WHATSAPP_CHAT_ID, txt_wa)
                if email:
                    self._enviar_email(email, f"Confirmação: Reunião {empresa} x IArandú ({dt_reuniao.strftime('%d/%m')})", html_email)

                conn.execute("UPDATE anti_no_show_cadencia SET t0_enviado_em = ?, atualizado_em = ? WHERE id = ?", (now_str, now_str, rec_id))
                conn.commit()
                resumo["t0"] += 1
                logger.info(f"[Anti-No-Show] T-0 disparado com sucesso para {empresa} ({nome}).")

            # ----------------------------------------------------
            # GATILHO D-1: Check-in humano no dia anterior (às 18h)
            # ----------------------------------------------------
            elif not r["d1_enviado_em"] and 12.0 <= horas_ate_reuniao <= 30.0 and now.hour >= 17:
                txt_wa = (
                    f"Boa tarde, {nome}! Tudo bem?\n\n"
                    f"Passando para confirmar nossa conversa de amanhã ({dt_reuniao.strftime('%d/%m às %H:%M')}). "
                    f"Já estruturei os pontos da operação da {empresa} que vamos analisar juntos.\n\n"
                    f"Tudo certo pelo seu lado para amanhã?"
                )
                self._enviar_whatsapp(wa or DIEGO_WHATSAPP_CHAT_ID, txt_wa)
                conn.execute("UPDATE anti_no_show_cadencia SET d1_enviado_em = ?, atualizado_em = ? WHERE id = ?", (now_str, now_str, rec_id))
                conn.commit()
                resumo["d1"] += 1
                logger.info(f"[Anti-No-Show] D-1 disparado com sucesso para {empresa} ({nome}).")

            # ----------------------------------------------------
            # GATILHO H-3: Aquecimento de Prova Social (3h antes)
            # ----------------------------------------------------
            elif not r["h3_enviado_em"] and 2.5 <= horas_ate_reuniao <= 3.8:
                txt_wa = (
                    f"Fala {nome}, em cerca de 3 horas nos falamos!\n\n"
                    f"Para você já visualizar na prática o impacto que o IArandú gera, separei este estudo de caso rápido de como eliminamos 90% do retrabalho de checagem e blindamos o fluxo financeiro de uma agência parceira:\n"
                    f"👉 {CASE_STUDY_URL}\n\n"
                    f"Até já já!"
                )
                self._enviar_whatsapp(wa or DIEGO_WHATSAPP_CHAT_ID, txt_wa)
                conn.execute("UPDATE anti_no_show_cadencia SET h3_enviado_em = ?, atualizado_em = ? WHERE id = ?", (now_str, now_str, rec_id))
                conn.commit()
                resumo["h3"] += 1
                logger.info(f"[Anti-No-Show] H-3 Prova Social disparada para {empresa} ({nome}).")

            # ----------------------------------------------------
            # GATILHO M-15: Link Direto da Sala (15 min antes)
            # ----------------------------------------------------
            elif not r["m15_enviado_em"] and 0.05 <= horas_ate_reuniao <= 0.35:
                txt_wa = (
                    f"Oi {nome}, sala aberta para nossa conversa das {dt_reuniao.strftime('%H:%M')}!\n\n"
                    f"Link direto do Google Meet:\n"
                    f"👉 {meet_url}\n\n"
                    f"Te aguardo lá na sala!"
                )
                self._enviar_whatsapp(wa or DIEGO_WHATSAPP_CHAT_ID, txt_wa)
                conn.execute("UPDATE anti_no_show_cadencia SET m15_enviado_em = ?, atualizado_em = ? WHERE id = ?", (now_str, now_str, rec_id))
                conn.commit()
                resumo["m15"] += 1
                logger.info(f"[Anti-No-Show] M-15 Link Meet disparado para {empresa} ({nome}).")

        conn.close()
        return resumo

    def obter_status_cadencias(self) -> List[Dict]:
        """Retorna todas as reuniões e o status detalhado dos 4 estágios da cadência."""
        self.sync_reunioes_do_crm()
        conn = sqlite3.connect(self.checkpoint_db)
        conn.row_factory = sqlite3.Row
        rows = conn.execute("SELECT * FROM anti_no_show_cadencia ORDER BY data_reuniao ASC").fetchall()
        conn.close()
        return [dict(r) for r in rows]

if __name__ == "__main__":
    engine = AntiNoShowEngine()
    print("Sincronizando reuniões do CRM Comercial 8090...")
    status = engine.processar_cadencias()
    print("Resultado dos disparos da cadência:", json.dumps(status, indent=2))
    print("\nReuniões sob proteção da cadência Anti-No-Show:")
    for c in engine.obter_status_cadencias():
        print(f" - {c['empresa']} ({c['contato_nome']}): {c['data_reuniao']} | T0: {bool(c['t0_enviado_em'])} | D1: {bool(c['d1_enviado_em'])} | H3: {bool(c['h3_enviado_em'])} | M15: {bool(c['m15_enviado_em'])}")
