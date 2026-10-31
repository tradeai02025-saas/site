#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
IArandú — Gerenciador de Agenda & Prevenção Anti-Conflito (Google Agenda)
Opera via Portal Maestri 'RCC Agenda' e gera convites nativos (.ics)
"""

import subprocess
import json
import re
import urllib.parse
from datetime import datetime, timedelta
from typing import Dict, List, Optional, Tuple

from config import (
    MAESTRI_CLI,
    PORTAL_AGENDA,
    DEFAULT_MEETING_TIME,
    SAFE_ESCAPE_TIMES,
    ZOHO_EMAIL
)

class AgendaManager:
    def __init__(self):
        self.cli_path = MAESTRI_CLI
        self.portal_name = PORTAL_AGENDA

    def _run_portal_evaluate(self, js_code: str) -> Optional[str]:
        cmd = [self.cli_path, "portal", "evaluate", self.portal_name, js_code]
        try:
            res = subprocess.run(cmd, stdin=subprocess.DEVNULL, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=10)
            if res.returncode == 0:
                return res.stdout.strip()
        except Exception as e:
            print(f"[AgendaManager] Erro no evaluate: {e}")
        return None

    def get_calendar_events_raw(self) -> List[str]:
        """Extrai todos os eventos visíveis do DOM da Google Agenda no portal."""
        js = """
        (() => {
            const elements = Array.from(document.querySelectorAll('[data-eventid]'));
            return elements.map(el => (el.innerText || el.textContent || '').trim());
        })()
        """
        raw = self._run_portal_evaluate(js)
        if not raw:
            return []
        try:
            return json.loads(raw)
        except Exception:
            return []

    def parse_events(self) -> List[Dict]:
        """Converte as strings brutas de eventos do Google Calendar em objetos estruturados."""
        raw_events = self.get_calendar_events_raw()
        parsed = []
        for text in raw_events:
            lines = [l.strip() for l in text.split('\n') if l.strip()]
            if not lines:
                continue
            title = lines[0]
            time_info = lines[1] if len(lines) > 1 else ""
            details = lines[2] if len(lines) > 2 else ""
            parsed.append({
                "title": title,
                "time_info": time_info,
                "details": details,
                "raw": text
            })
        return parsed

    def check_slot_availability(self, target_date: datetime, requested_time: str = DEFAULT_MEETING_TIME) -> Tuple[bool, str]:
        """
        Verifica se o horário (ex: 10:30) no target_date está livre na Google Agenda.
        Retorna (True, requested_time) se livre, ou (False, alternative_time) com sugestão sem conflito.
        """
        events = self.parse_events()
        # Mapeia eventos do dia
        date_keywords = [
            target_date.strftime("%d de %B").lower(),
            f"{target_date.day} de",
            target_date.strftime("%d/%m")
        ]
        
        # Procura conflito no dia
        day_events = []
        for ev in events:
            raw_lower = ev["raw"].lower()
            if any(k in raw_lower for k in date_keywords):
                day_events.append(ev)

        # Checa se o requested_time conflita com algum evento do dia
        conflict = False
        for ev in day_events:
            if requested_time in ev["time_info"] or requested_time in ev["raw"]:
                conflict = True
                break

        if not conflict:
            return True, requested_time

        # Se houver conflito, testa os horários de escape seguros
        for alt_time in SAFE_ESCAPE_TIMES:
            alt_conflict = False
            for ev in day_events:
                if alt_time in ev["time_info"] or alt_time in ev["raw"]:
                    alt_conflict = True
                    break
            if not alt_conflict:
                return False, alt_time

        # Se o dia estiver todo cheio, sugere o dia útil seguinte às 10:30
        next_day = target_date + timedelta(days=1)
        if next_day.weekday() >= 5: # Sábado ou Domingo
            next_day += timedelta(days=(7 - next_day.weekday()))
        return False, f"Próximo dia útil ({next_day.strftime('%d/%m')}) às {DEFAULT_MEETING_TIME}"

    def generate_ics(self, summary: str, start_dt: datetime, duration_minutes: int, guest_email: str, meet_url: str = "https://meet.google.com/iarandu-apresentacao") -> str:
        """Gera arquivo .ics compatível com Google Calendar, Outlook e Apple Calendar com convite interativo."""
        end_dt = start_dt + timedelta(minutes=duration_minutes)
        uid = f"iarandu_{int(start_dt.timestamp())}_{guest_email.split('@')[0]}@iarandu.com.br"
        now_utc = datetime.utcnow().strftime("%Y%m%dT%H%M%SZ")
        dtstart = start_dt.strftime("%Y%m%dT%H%M%S")
        dtend = end_dt.strftime("%Y%m%dT%H%M%S")

        ics_content = f"""BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//IArandu Tecnologia//Operacao de Midia//PT
CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VEVENT
UID:{uid}
DTSTAMP:{now_utc}
DTSTART;TZID=America/Sao_Paulo:{dtstart}
DTEND;TZID=America/Sao_Paulo:{dtend}
SUMMARY:{summary}
DESCRIPTION:Demonstração da plataforma IArandú Tecnologia (checking de mídia, conciliação de PIs e comprovantes).\\n\\nLink da Reunião: {meet_url}
LOCATION:{meet_url}
ORGANIZER;CN=Diego Maldonado - IArandú:mailto:{ZOHO_EMAIL}
ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN={guest_email}:mailto:{guest_email}
STATUS:CONFIRMED
BEGIN:VALARM
TRIGGER:-PT15M
ACTION:DISPLAY
DESCRIPTION:Lembrete de Reunião IArandú Tecnologia
END:VALARM
END:VEVENT
END:VCALENDAR"""
        return ics_content

    def build_google_calendar_url(self, summary: str, start_dt: datetime, duration_minutes: int, guest_email: str, meet_url: str) -> str:
        """Gera URL direta para criação de evento pré-preenchido no Google Agenda do usuário."""
        end_dt = start_dt + timedelta(minutes=duration_minutes)
        fmt = "%Y%m%dT%H%M%S"
        dates = f"{start_dt.strftime(fmt)}/{end_dt.strftime(fmt)}"
        params = {
            "action": "TEMPLATE",
            "text": summary,
            "dates": dates,
            "details": f"Apresentação da Plataforma IArandú Tecnologia.\nLink Google Meet: {meet_url}",
            "location": meet_url,
            "add": guest_email
        }
        return f"https://calendar.google.com/calendar/render?{urllib.parse.urlencode(params)}"
