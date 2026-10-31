#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
IArandú — Configurações Globais das Rotinas de Automação
Sexta-Feira / Operação & Atendimento
"""

from pathlib import Path

# --- E-MAIL OFICIAL ÚNICO ---
ZOHO_EMAIL = "contato@iarandu.com.br"
SMTP_HOST = "smtp.zoho.com"
SMTP_PORT = 465
SMTP_USER = "contato@iarandu.com.br"
SMTP_PASS = "C@mondongo2026!"

# --- WHATSAPP (OpenWA Gateway) ---
OPENWA_BASE_URL = "http://localhost:2785"
OPENWA_API_KEY = "owa_k1_0915bba3a57a72c1fcd613a536aefe7e0aa275950aa6710eb6cee3ee15537dfa"
OPENWA_SESSION_ID = "1636795e-8cf5-43b0-b982-164c80594493"
OPENWA_LABEL_IARANDU_ID = "16"
DIEGO_WHATSAPP_CHAT_ID = "6562760384672@lid"

def get_active_openwa_session() -> str:
    """Retorna o ID da sessão ativa e conectada no OpenWA dinamicamente."""
    import urllib.request, json
    try:
        req = urllib.request.Request(
            f"{OPENWA_BASE_URL}/api/sessions",
            headers={"X-Api-Key": OPENWA_API_KEY}
        )
        with urllib.request.urlopen(req, timeout=5) as resp:
            sessions = json.loads(resp.read().decode())
            for s in sessions:
                if s.get("status") == "ready":
                    return s.get("id")
    except Exception:
        pass
    return OPENWA_SESSION_ID


# --- MAESTRI PORTALS & CLI ---
MAESTRI_CLI = "/mnt/c/Program Files/Maestri/resources/cli/maestri.exe"
PORTAL_AGENDA = "RCC Agenda"
PORTAL_EMAIL = "Email IArandu"
PORTAL_IG_COMERCIAL = "Instagram Comercial"
PORTAL_IG_IARANDU = "@iarandu.br"

# --- BANCOS DE DADOS ---
CENTRAL_DB = Path("/mnt/c/Users/user/iarandu_crm_central/central.db")
AGENCIAS_DB = Path("/mnt/d/Eternity-Signals/COMERCIAL/AGENCIAS-PUB/data/agencias_publicidade.db")
CHECKPOINT_DB = Path("/mnt/d/site-iarandu/automacao_rotinas_iarandu/rotina_checkpoint.sqlite3")

# --- HORÁRIO PADRÃO DE REUNIÕES ---
DEFAULT_MEETING_TIME = "10:30"
DEFAULT_MEETING_DURATION_MIN = 25
SAFE_ESCAPE_TIMES = ["11:15", "14:30", "15:30"]

# --- TEMPLATE INSTITUCIONAL IARANDÚ ---
def render_standard_email(body_html: str) -> str:
    """Template oficial aprovado IArandú com branding completo."""
    return f'''<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>IArandú Tecnologia</title></head>
<body style="margin:0;padding:24px;background:#eef1f5;font-family:Arial,Helvetica,sans-serif;color:#17202c;">
<table align="center" role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:660px;background:#f4f6f9;border:1px solid #cbd1d8;border-radius:16px;overflow:hidden;">
<tr><td style="padding:30px 34px 22px;border-bottom:1px solid #bfc6ce;">
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"><tr>
<td><img src="https://www.iarandu.com.br/assets/img/logo-dark.png" alt="IArandú" width="135" style="display:block;max-width:135px;height:auto;"></td>
<td style="text-align:right;"><span style="display:inline-block;padding:6px 14px;border-radius:20px;border:1px solid #d9bf98;background:#eee8de;color:#a46a16;font-size:11px;font-weight:700;letter-spacing:0.7px;">TECNOLOGIA &amp; OPERAÇÃO DE MÍDIA</span></td>
</tr></table></td></tr>
<tr><td style="padding:30px 34px;font-size:14px;line-height:1.65;color:#263341;">
{body_html}
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top:28px;border-top:1px solid #858d96;"><tr><td style="padding-top:20px;">
<div style="font-size:15px;font-weight:700;color:#202a36;">Diego Maldonado</div>
<div style="font-size:12px;color:#748091;">Fundador &amp; CEO · IArandú Tecnologia</div>
<div style="font-size:12px;margin-top:5px;color:#3b82c4;"><a href="tel:+5541998136312" style="color:#3b82c4;text-decoration:none;">(41) 99813-6312</a> · <a href="mailto:{ZOHO_EMAIL}" style="color:#3b82c4;text-decoration:none;">{ZOHO_EMAIL}</a> · <a href="https://www.iarandu.com.br" style="color:#3b82c4;text-decoration:none;">www.iarandu.com.br</a> · <a href="https://www.instagram.com/iarandu.br" style="color:#3b82c4;text-decoration:none;">@iarandu.br</a></div>
</td></tr></table>
</td></tr></table></body></html>'''
