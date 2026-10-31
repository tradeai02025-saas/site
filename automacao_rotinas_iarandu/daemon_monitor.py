#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
IArandú — Daemon Contínuo de Monitoramento
Executa a cada 3 horas de forma autônoma sem necessidade de acionamento manual.
"""

import time
import sys
import os
from datetime import datetime

# Garante path
DIR = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, DIR)

from rotina_orquestrador import RotinaOrquestrador

INTERVALO_MONITORAMENTO_SEGUNDOS = 3 * 3600  # 3 horas (10.800s)

def main():
    print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] Iniciando Daemon de Monitoramento Autônomo IArandú (Ciclo de 3 horas)...")
    orchestrator = RotinaOrquestrador()

    last_briefing_date = None
    last_wrapup_date = None
    last_monitor_time = 0
    last_cadence_check = 0

    while True:
        now = datetime.now()
        today_str = now.strftime("%Y-%m-%d")
        current_hour = now.hour
        current_minute = now.minute
        now_ts = time.time()

        # 1. Briefing Matinal (09h00 - 09h30)
        if current_hour == 9 and current_minute <= 30 and last_briefing_date != today_str:
            try:
                orchestrator.run_morning_briefing()
                last_briefing_date = today_str
            except Exception as e:
                print(f"[Daemon] Erro no briefing: {e}")

        # 2. Fechamento Diário (18h00 - 18h30)
        elif current_hour == 18 and current_minute <= 30 and last_wrapup_date != today_str:
            try:
                orchestrator.run_evening_wrapup()
                last_wrapup_date = today_str
            except Exception as e:
                print(f"[Daemon] Erro no fechamento: {e}")

        # 3. Ciclo Periódico de Monitoramento (a cada 3 horas em horário comercial 08:00 - 20:00)
        if 8 <= current_hour <= 20:
            if now_ts - last_monitor_time >= INTERVALO_MONITORAMENTO_SEGUNDOS:
                try:
                    res = orchestrator.run_cycle_monitoring()
                    last_monitor_time = now_ts
                    print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] Ciclo executado com sucesso: {res}. Próximo em 3 horas.")
                except Exception as e:
                    print(f"[Daemon] Erro no ciclo de monitoramento: {e}")

        # 4. Verificação de Cadência Anti-No-Show (a cada 10 minutos para precisão de H-3 e M-15)
        if 8 <= current_hour <= 20 and (current_minute % 10 == 0) and (now_ts - last_cadence_check >= 500):
            try:
                from anti_no_show_engine import AntiNoShowEngine
                c_res = AntiNoShowEngine().processar_cadencias()
                last_cadence_check = now_ts
                if any(c_res.values()):
                    print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] Disparos Anti-No-Show realizados: {c_res}")
            except Exception as e:
                print(f"[Daemon] Erro na cadência Anti-No-Show: {e}")

        # Verificação a cada 30 segundos para precisão nos agendamentos
        time.sleep(30)

if __name__ == "__main__":
    main()
