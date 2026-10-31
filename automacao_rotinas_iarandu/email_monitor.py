#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
IArandú — Monitor de E-mails & Agendador Inteligente (Zoho Mail: contato@iarandu.com.br)
Integração com Google Agenda, geração de convites .ics e envio seguro via Zoho SMTP SSL
"""

import subprocess
import json
import re
import smtplib
import sqlite3
from datetime import datetime, timedelta
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from email.mime.base import MIMEBase
from email import encoders
from typing import Dict, List, Optional, Tuple

from config import (
    MAESTRI_CLI,
    PORTAL_EMAIL,
    ZOHO_EMAIL,
    SMTP_HOST,
    SMTP_PORT,
    SMTP_USER,
    SMTP_PASS,
    CENTRAL_DB,
    DEFAULT_MEETING_TIME,
    render_standard_email
)
from agenda_manager import AgendaManager

class EmailMonitor:
    def __init__(self):
        self.cli_path = MAESTRI_CLI
        self.portal_name = PORTAL_EMAIL
        self.agenda_manager = AgendaManager()

    def snapshot_inbox(self) -> str:
        """Captura o snapshot atual da caixa de entrada do Zoho Mail via Maestri."""
        cmd = [self.cli_path, "portal", "snapshot", self.portal_name]
        try:
            res = subprocess.run(cmd, stdin=subprocess.DEVNULL, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=12)
            if res.returncode == 0:
                return res.stdout
        except Exception as e:
            print(f"[EmailMonitor] Erro no snapshot do Zoho Mail: {e}")
        return ""

    def parse_inbox_items(self) -> List[Dict]:
        """Extrai os e-mails visíveis na listagem de entrada."""
        raw = self.snapshot_inbox()
        items = []
        # Exemplo de linha do snapshot:
        # @e91 option "Do Lia Pinheiro, Assunto Eficiência operacional..., Horário de recebimento do QUI. OUT. 29 3:22 PM..."
        pattern = re.compile(r'(@e\d+)\s+option\s+"Do\s+([^,]+),\s+Assunto\s+([^,]+),\s+Horário de recebimento do\s+([^",]+)', re.IGNORECASE)
        for line in raw.split('\n'):
            m = pattern.search(line)
            if m:
                ref = m.group(1)
                sender = m.group(2).strip()
                subject = m.group(3).strip()
                time_str = m.group(4).strip()

                # Ignora bounces e alertas do sistema
                if "mailer-daemon" in sender.lower() or "notification" in sender.lower():
                    continue

                items.append({
                    "ref": ref,
                    "sender": sender,
                    "subject": subject,
                    "time_str": time_str
                })
        return items

    def is_already_responded(self, email_address: str, subject_keyword: str) -> bool:
        """Verifica no central.db se já existe resposta enviada por Diego ou pela automação."""
        try:
            conn = sqlite3.connect(CENTRAL_DB)
            c = conn.cursor()
            c.execute("""
                SELECT id FROM interactions 
                WHERE direction = 'outbound' 
                  AND (identity_value LIKE ? OR summary LIKE ?)
                LIMIT 1
            """, (f"%{email_address}%", f"%{subject_keyword}%"))
            row = c.fetchone()
            conn.close()
            return row is not None
        except Exception as e:
            print(f"[EmailMonitor] Erro checando resposta anterior: {e}")
            return False

    def send_email_with_ics(self, to_email: str, subject: str, body_html: str, start_dt: datetime, duration_min: int, agency_name: str, meet_link: str = "https://meet.google.com/iarandu-apresentacao") -> bool:
        """Envia e-mail oficial com layout institucional e convite .ics interativo."""
        ics_text = self.agenda_manager.generate_ics(
            summary=f"Reunião: IArandú Tecnologia & {agency_name}",
            start_dt=start_dt,
            duration_minutes=duration_min,
            guest_email=to_email,
            meet_url=meet_link
        )

        full_html = render_standard_email(body_html)

        msg = MIMEMultipart("mixed")
        msg["Subject"] = subject
        msg["From"] = f"Diego Maldonado <{ZOHO_EMAIL}>"
        msg["To"] = to_email
        msg["Reply-To"] = ZOHO_EMAIL

        # Parte alternativa (HTML + Texto)
        alt_part = MIMEMultipart("alternative")
        plain_text = re.sub(r'<[^>]+>', '', body_html)
        alt_part.attach(MIMEText(plain_text, "plain", "utf-8"))
        alt_part.attach(MIMEText(full_html, "html", "utf-8"))
        msg.attach(alt_part)

        # Anexo interativo de calendário (ICS)
        ics_part = MIMEBase("text", "calendar", method="REQUEST", name="invite.ics")
        ics_part.set_payload(ics_text.encode("utf-8"))
        encoders.encode_base64(ics_part)
        ics_part.add_header("Content-Disposition", "attachment; filename=invite.ics")
        ics_part.add_header("Content-Class", "urn:content-classes:calendarmessage")
        msg.attach(ics_part)

        try:
            server = smtplib.SMTP_SSL(SMTP_HOST, SMTP_PORT, timeout=20)
            server.login(SMTP_USER, SMTP_PASS)
            server.sendmail(ZOHO_EMAIL, [to_email], msg.as_string())
            server.quit()

            # Registra no central.db
            self._log_interaction(to_email, agency_name, subject)
            return True
        except Exception as e:
            print(f"[EmailMonitor] Erro no envio SMTP: {e}")
            return False

    def _log_interaction(self, to_email: str, agency: str, subject: str):
        try:
            conn = sqlite3.connect(CENTRAL_DB)
            c = conn.cursor()
            ts = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
            c.execute("""
                INSERT INTO interactions (
                    source_system, source_event_key, channel, direction, status, summary,
                    event_ts, account_operator, identity_value, match_confidence, payload_json, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            """, (
                "zoho_smtp_automation",
                f"reuniao_{to_email}_{datetime.now().strftime('%Y%m%d%H%M%S')}",
                "email",
                "outbound",
                "enviado",
                f"Envio de confirmação de reunião com convite ICS para {agency} ({to_email})",
                ts,
                "sexta_feira_rotina",
                to_email,
                "high",
                json.dumps({"to": to_email, "agency": agency, "subject": subject}),
                ts
            ))
            conn.commit()
            conn.close()
        except Exception as e:
            print(f"[EmailMonitor] Erro registrando no central.db: {e}")
