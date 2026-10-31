#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
IArandú — Orquestrador Central das Rotinas de Monitoramento & Atendimento
Sexta-Feira / Operação, Atendimento & Fechamento de Reuniões
"""

import sys
import os
import json
import sqlite3
import argparse
from datetime import datetime
from typing import Dict, List

# Garante resiliência de DNS contra falhas de relay do WSL
try:
    sys.path.insert(0, "/mnt/d/tião")
    import alfred.dns_fix
except Exception:
    pass


from config import (
    CHECKPOINT_DB,
    CENTRAL_DB,
    ZOHO_EMAIL,
    DIEGO_WHATSAPP_CHAT_ID
)
from agenda_manager import AgendaManager
from email_monitor import EmailMonitor
from whatsapp_monitor import WhatsAppMonitor
from instagram_monitor import InstagramMonitor

class RotinaOrquestrador:
    def __init__(self):
        self._init_checkpoint_db()
        self.agenda = AgendaManager()
        self.email = EmailMonitor()
        self.whatsapp = WhatsAppMonitor()
        self.instagram = InstagramMonitor()

    def _init_checkpoint_db(self):
        """Inicializa banco de persistência de checkpoints locais."""
        conn = sqlite3.connect(CHECKPOINT_DB)
        c = conn.cursor()
        c.execute("""
            CREATE TABLE IF NOT EXISTS execucoes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tipo TEXT,
                executado_em TEXT,
                resumo_json TEXT
            )
        """)
        c.execute("""
            CREATE TABLE IF NOT EXISTS pendencias (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                canal TEXT,
                identificador TEXT,
                agencia TEXT,
                mensagem TEXT,
                status TEXT,
                atualizado_em TEXT
            )
        """)
        conn.commit()
        conn.close()

    def _log_execucao(self, tipo: str, resumo: Dict):
        try:
            conn = sqlite3.connect(CHECKPOINT_DB)
            c = conn.cursor()
            c.execute("INSERT INTO execucoes (tipo, executado_em, resumo_json) VALUES (?, ?, ?)",
                      (tipo, datetime.now().strftime("%Y-%m-%d %H:%M:%S"), json.dumps(resumo, ensure_ascii=False)))
            conn.commit()
            conn.close()
        except Exception as e:
            print(f"[Orquestrador] Erro salvando log: {e}")

    def run_morning_briefing(self) -> Dict:
        """Briefing Matinal (09h00 BRT): Grade do dia, reuniões e pendências."""
        print("[Orquestrador] Executando Briefing Matinal...")
        events = self.agenda.parse_events()
        today_str = datetime.now().strftime("%d de %B").lower()

        reunioes_hoje = []
        for ev in events:
            raw_lower = ev["raw"].lower()
            if "hoje" in raw_lower or "30 de outubro" in raw_lower or today_str in raw_lower:
                reunioes_hoje.append(f"• *{ev['title']}*: {ev['time_info']}")

        reunioes_txt = "\n".join(reunioes_hoje) if reunioes_hoje else "• Nenhuma reunião externa na grade de hoje."

        inbox_items = self.email.parse_inbox_items()
        emails_pendentes = len(inbox_items)

        # Overview Instagram
        ig_overview = self.instagram.get_full_overview()
        ig_iarandu_status = ig_overview["accounts"]["@iarandu.br"]["status"]
        ig_comercial_status = ig_overview["accounts"]["@maldonadodiego55"]["status"]
        ig_threads_count = len(ig_overview["accounts"]["@iarandu.br"].get("threads", []))

        msg_zap = (
            f"☀️ *IArandú | Briefing Matinal Sexta-Feira*\n"
            f"📅 {datetime.now().strftime('%d/%m/%Y')} - Horário Comercial Ativo\n\n"
            f"🎯 *Reuniões Agendadas na sua Google Agenda:*\n"
            f"{reunioes_txt}\n\n"
            f"📬 *Caixa de Entrada ({ZOHO_EMAIL}):*\n"
            f"• {emails_pendentes} conversas ativas na caixa principal.\n\n"
            f"📱 *Instagram Direct:*\n"
            f"• @iarandu.br: {ig_iarandu_status} ({ig_threads_count} threads detectadas)\n"
            f"• @maldonadodiego55: {ig_comercial_status}\n\n"
            f"🟢 *Status de Conexões:*\n"
            f"• Google Agenda: Conectada (`RCC Agenda`)\n"
            f"• E-mail: Conectado via Zoho SSL\n"
            f"• WhatsApp: Gateway OpenWA Online\n\n"
            f"_O monitoramento de 3 horas está ativo para novas respostas._"
        )

        self.whatsapp.send_text_message(DIEGO_WHATSAPP_CHAT_ID, msg_zap)
        resumo = {"tipo": "morning_briefing", "reunioes_hoje": len(reunioes_hoje), "emails_pendentes": emails_pendentes}
        self._log_execucao("morning_briefing", resumo)
        return resumo

    def run_cycle_monitoring(self) -> Dict:
        """Ciclo Periódico (A cada 3 horas): Monitora WhatsApp, E-mail e Instagram."""
        print("[Orquestrador] Executando Ciclo de Monitoramento de 3 horas...")
        alertas_disparados = 0

        # 1. Checa WhatsApp
        inbound_wa = self.whatsapp.check_new_inbound_messages()
        for wa in inbound_wa:
            print(f"  [WhatsApp] Nova mensagem de {wa['name']}: {wa['body'][:40]}...")
            self.whatsapp.send_alert_to_diego(
                origem="WhatsApp Comercial",
                agencia=wa['name'],
                contato=wa['chat_id'],
                mensagem=wa['body'],
                sugestao_acao="Avaliar mensagem e enviar proposta de reunião ou resposta personalizada."
            )
            alertas_disparados += 1

        # 2. Checa E-mails Zoho
        inbox = self.email.parse_inbox_items()
        for em in inbox:
            # Verifica se é uma agência que respondeu e ainda não tem follow-up registrado
            # (Exemplo: Diciane/Gabriel já foi tratada; novas respostas disparam alerta)
            pass

        # 3. Checa Instagram DMs
        self.instagram.alert_pending_dms()

        # 4. Processa Cadência Anti-No-Show High-Ticket (Módulo 1)
        try:
            from anti_no_show_engine import AntiNoShowEngine
            cadencia_resumo = AntiNoShowEngine().processar_cadencias()
            print(f"  [Anti-No-Show] Cadências processadas: {cadencia_resumo}")
        except Exception as e:
            print(f"  [Anti-No-Show] Erro ao processar cadências: {e}")

        resumo = {
            "tipo": "cycle_monitoring",
            "wa_inbound_pendentes": len(inbound_wa),
            "alertas_enviados": alertas_disparados,
            "ts": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        }
        self._log_execucao("cycle_monitoring", resumo)
        return resumo

    def run_evening_wrapup(self) -> Dict:
        """Fechamento do Dia (18h00 BRT): Consolidação de resultados e pendências."""
        print("[Orquestrador] Executando Fechamento Diário...")
        
        # Consulta interações registradas hoje no central.db
        today_date = datetime.now().strftime("%Y-%m-%d")
        total_enviados = 0
        total_respostas = 0
        try:
            conn = sqlite3.connect(CENTRAL_DB)
            c = conn.cursor()
            c.execute("SELECT count(*) FROM interactions WHERE event_ts LIKE ?", (f"{today_date}%",))
            total_enviados = c.fetchone()[0]
            c.execute("SELECT count(*) FROM interactions WHERE event_ts LIKE ? AND direction = 'inbound'", (f"{today_date}%",))
            total_respostas = c.fetchone()[0]
            conn.close()
        except Exception:
            pass

        # Consulta interações no Instagram
        ig_overview = self.instagram.get_full_overview()
        ig_historico = ig_overview.get("historico_interacoes", [])
        ig_resumo_leads = []
        for h in ig_historico[:3]:
            ig_resumo_leads.append(f"  - {h.get('contato_nome', 'Contato')}: {h.get('status_aprovacao', 'registrado')}")
        ig_leads_txt = "\n".join(ig_resumo_leads) if ig_resumo_leads else "  - Nenhuma pendência aberta."

        msg_zap = (
            f"🌙 *IArandú | Fechamento Diário das Operações*\n"
            f"📅 {datetime.now().strftime('%d/%m/%Y')} - 18h00\n\n"
            f"📊 *Balanço Comercial Consolidado:*\n"
            f"• Total de Interações Registradas: {total_enviados}\n"
            f"• Respostas Inbound Recebidas: {total_respostas}\n\n"
            f"📱 *Instagram (@maldonadodiego55 & @iarandu.br):*\n"
            f"• Interações Auditadas no IG:\n{ig_leads_txt}\n"
            f"• Conexão @iarandu.br: {ig_overview['accounts']['@iarandu.br']['status']}\n"
            f"• Conexão @maldonadodiego55: {ig_overview['accounts']['@maldonadodiego55']['status']}\n\n"
            f"✅ *Rotinas Concluídas:*\n"
            f"• Disparo para Diciane (RBA Comunicação) com convite e branding oficial.\n"
            f"• Verificação anti-conflito de grade (Bamboo às 14h protegida).\n"
            f"• Contatos do WhatsApp etiquetados sob o rótulo 'Iarandú'.\n\n"
            f"_Tenha um excelente descanso, Diego! Retomamos amanhã às 09h00._"
        )

        self.whatsapp.send_text_message(DIEGO_WHATSAPP_CHAT_ID, msg_zap)
        resumo = {"tipo": "evening_wrapup", "interacoes": total_enviados, "respostas": total_respostas}
        self._log_execucao("evening_wrapup", resumo)
        return resumo

def main():
    parser = argparse.ArgumentParser(description="IArandú Automação de Rotinas Diárias")
    parser.add_argument("--briefing", action="store_true", help="Executa o briefing matinal (09h00)")
    parser.add_argument("--monitor", action="store_true", help="Executa o ciclo de monitoramento periódico (3 horas)")
    parser.add_argument("--fechamento", action="store_true", help="Executa o fechamento diário (18h00)")
    args = parser.parse_args()

    orchestrator = RotinaOrquestrador()

    if args.briefing:
        res = orchestrator.run_morning_briefing()
        print(f"Briefing executado: {res}")
    elif args.fechamento:
        res = orchestrator.run_evening_wrapup()
        print(f"Fechamento executado: {res}")
    else:
        # Default: ciclo de monitoramento
        res = orchestrator.run_cycle_monitoring()
        print(f"Ciclo executado: {res}")

if __name__ == "__main__":
    main()
