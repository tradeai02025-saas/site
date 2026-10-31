#!/usr/bin/env python3
"""Executor WhatsApp com parada cooperativa, usando as funções do motor existente.

Não contém nem duplica credenciais. Só é acionado por clique explícito no CRM.
"""

import argparse
import importlib.util
import json
import os
import random
import re
import sqlite3
import sys
import time
from datetime import datetime
from schedule_clock import sao_paulo_local, sao_paulo_now
from pathlib import Path
from contact_suppressions import load_suppressed_cnpis


ORIGINAL = Path('/mnt/d/site-iarandu/executar_lote_whatsapp_seguro.py')
spec = importlib.util.spec_from_file_location('iarandu_wa_original', ORIGINAL)
engine = importlib.util.module_from_spec(spec)
spec.loader.exec_module(engine)
LOCK = Path(engine.LOCK_FILE)
def _scheduled_window_open(now=None):
    local = sao_paulo_local(now or sao_paulo_now())
    return local.weekday() < 5 and 8 <= local.hour < 18
MESSAGE_CONFIG = Path(__file__).resolve().parent / 'data' / 'channel_messages.json'


def load_whatsapp_message_template():
    try:
        payload = json.loads(MESSAGE_CONFIG.read_text(encoding='utf-8'))
    except (OSError, ValueError, TypeError) as error:
        raise RuntimeError(f'Não foi possível carregar o modelo oficial de WhatsApp: {error}') from error
    template = payload.get('whatsapp') if isinstance(payload, dict) else None
    if not isinstance(template, str) or not template.strip() or '{empresa}' not in template:
        raise RuntimeError('Modelo oficial de WhatsApp ausente ou inválido; envio bloqueado.')
    return template


def stop_requested():
    try:
        return LOCK.read_text(encoding='utf-8').strip().upper() == 'STOP'
    except FileNotFoundError:
        return False


def acquire_lock():
    for _ in range(2):
        try:
            fd = os.open(str(LOCK), os.O_CREAT | os.O_EXCL | os.O_WRONLY, 0o600)
            with os.fdopen(fd, 'w', encoding='utf-8') as stream:
                stream.write(f'{os.getpid()} {datetime.now().isoformat()}')
            return True
        except FileExistsError:
            content = LOCK.read_text(encoding='utf-8').strip()
            first = content.split(' ', 1)[0]
            if first.isdigit():
                try:
                    os.kill(int(first), 0)
                    return False
                except ProcessLookupError:
                    pass
            try:
                LOCK.unlink()
            except FileNotFoundError:
                pass
    return False


def sent_today():
    """Count confirmed WhatsApp sends across the central log and campaign DB."""
    keys = set()
    for path, query in (
        (engine.CENTRAL_DB, "SELECT DISTINCT coalesce(cnpj,identity_value) FROM interactions WHERE lower(channel)='whatsapp' AND lower(direction)='outbound' AND lower(status) IN ('enviado','sent','accepted') AND date(event_ts)=date('now','localtime')"),
        (engine.AGEN_DB, "SELECT DISTINCT coalesce(cnpj,whatsapp_id,telefone) FROM disparos_whatsapp WHERE lower(status)='enviado' AND date(enviado_em)=date('now','localtime')"),
    ):
        try:
            connection = sqlite3.connect(f'file:{path}?mode=ro', uri=True, timeout=5)
            keys.update(str(row[0]) for row in connection.execute(query) if row[0])
            connection.close()
        except sqlite3.Error:
            # If a history source is unavailable, fail closed instead of guessing a quota.
            raise RuntimeError('Não foi possível validar a meta diária do WhatsApp.')
    return len(keys)


def selected_candidates(selected_cnpjs, limit):
    if not selected_cnpjs:
        raise RuntimeError('Selecione agências antes de preparar a fila WhatsApp.')
    selected = {re.sub(r'\D', '', str(value)) for value in selected_cnpjs}
    selected.discard('')
    suppressed = load_suppressed_cnpis(engine.AGEN_DB, engine.CENTRAL_DB)
    selected.difference_update(suppressed)
    if not selected:
        raise RuntimeError('A seleção não contém CNPJs válidos.')
    return engine.get_next_batch(2000, selected_cnpjs=selected)[:limit]


def run(limit=8, dry_run=False, daily_target=None, selected_cnpjs=None, scheduled=False):
    if scheduled and not _scheduled_window_open():
        raise RuntimeError('Automatic WhatsApp window is closed.')
    if not 1 <= limit <= 30:
        raise ValueError('O lote precisa estar entre 1 e 24 mensagens.')
    if daily_target is not None:
        remaining = max(0, daily_target - sent_today())
        if limit > remaining:
            raise RuntimeError(f'Meta diária WhatsApp: restam {remaining}; lote solicitado: {limit}.')
    if dry_run:
        leads = selected_candidates(selected_cnpjs, limit)
        print(f'DRY RUN: {len(leads)} candidatos; nenhum envio nem lock criado.', flush=True)
        if len(leads) < limit:
            raise RuntimeError(f'Fila WhatsApp elegível: {len(leads)} candidatos para {limit} envios.')
        return 0
    if not acquire_lock():
        engine.log('Lote já em execução; início duplicado recusado.')
        return 2
    sent = 0
    errors = 0
    try:
        leads = selected_candidates(selected_cnpjs, limit)
        message_template = load_whatsapp_message_template()
        engine.log(f'Lote seguro preparado: {len(leads)} agências.')
        for index, lead in enumerate(leads, 1):
            if stop_requested() or (scheduled and not _scheduled_window_open()):
                engine.log('Parada solicitada antes da próxima mensagem.')
                break
            name = lead.get('nome') or lead.get('nome_fantasia') or lead.get('razao_social') or 'Agência'
            wid = lead.get('whatsapp_id') or ''
            telephone = re.sub(r'\D', '', str(lead.get('telefone_normalizado') or lead.get('telefone') or ''))
            owner = lead.get('socio_decisor') or lead.get('dono_diretor') or ''
            tier = lead.get('tier') or lead.get('classificacao_lead') or 'OURO'
            agency = lead.get('orgao') or lead.get('orgao_referencia') or ''
            chat_id = wid if wid and ('@lid' in wid or '@c.us' in wid) else f'{telephone}@c.us'
            message = engine.gerar_mensagem(name, socio_nome=owner, orgao=agency,
                                            template_text=message_template)
            engine.write_live_status(True, {'nome': name, 'dono': owner, 'tier': tier,
                                            'acao_atual': f'Disparando ({index}/{len(leads)})',
                                            'progresso': f'{index}/{len(leads)}'})
            try:
                if scheduled and not _scheduled_window_open():
                    engine.log('Automatic WhatsApp window closed before gateway send.')
                    break
                result = engine.send_message(chat_id, message)
                engine.record_dispatch(lead, message, result)
                sent += 1
                engine.log(f'[{index}/{len(leads)}] Enviado para {name}.')
            except Exception as error:
                errors += 1
                engine.log(f'[{index}/{len(leads)}] Erro para {name}: {error}')
            if index < len(leads):
                for remaining in range(random.randint(120, 240), 0, -5):
                    if stop_requested() or (scheduled and not _scheduled_window_open()):
                        engine.log('Parada solicitada durante o intervalo.')
                        break
                    engine.write_live_status(True, {'nome': name, 'dono': owner, 'tier': tier,
                                                    'acao_atual': f'Intervalo: {remaining}s',
                                                    'progresso': f'{index}/{len(leads)}'})
                    time.sleep(min(5, remaining))
                if stop_requested() or (scheduled and not _scheduled_window_open()):
                    break
        engine.log(f'Lote encerrado: {sent} enviados, {errors} erros.')
        return 0
    finally:
        engine.write_live_status(False, {'nome': 'Motor em Repouso', 'acao_atual': 'Aguardando acionamento no CRM'})
        try:
            content = LOCK.read_text(encoding='utf-8').strip()
            if content.upper() == 'STOP' or content.split(' ', 1)[0] == str(os.getpid()):
                LOCK.unlink()
        except FileNotFoundError:
            pass


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--limit', type=int, default=8)
    parser.add_argument('--daily-target', type=int, default=24)
    parser.add_argument('--dry-run', action='store_true')
    parser.add_argument('--scheduled', action='store_true')
    parser.add_argument('--cnpjs-file', required=True)
    args = parser.parse_args()
    try:
        selected = json.loads(Path(args.cnpjs_file).read_text(encoding='utf-8')).get('cnpjs', [])
    except (OSError, ValueError, TypeError, AttributeError) as error:
        parser.error(f'Não foi possível carregar a seleção manual: {error}')
    sys.exit(run(min(max(args.limit, 1), 30), args.dry_run, args.daily_target, selected,
                 scheduled=args.scheduled))
