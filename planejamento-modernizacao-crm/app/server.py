#!/usr/bin/env python3
"""Revenue OS local: visual novo, APIs legadas intactas, prévia sem ações.

Executar no WSL. Este arquivo não altera o cockpit legado nem os bancos.
"""

import argparse
import base64
import concurrent.futures
import hashlib
import importlib.util
import json
import mimetypes
import ntpath
import os
import re
import sqlite3
import subprocess
import sys
import threading
import time
import urllib.error
import urllib.request
import email_model
from datetime import datetime, timedelta
from http.server import ThreadingHTTPServer, SimpleHTTPRequestHandler
from pathlib import Path
from urllib.parse import parse_qs, unquote, urlparse
import unicodedata
from contact_suppressions import load_suppressed_cnpis, load_suppressed_contacts
from schedule_clock import sao_paulo_at, sao_paulo_local, sao_paulo_now
import dispatch_logs

mimetypes.add_type('image/webp', '.webp')
mimetypes.add_type('video/webm', '.webm')
mimetypes.add_type('video/quicktime', '.mov')


HERE = Path(__file__).resolve().parent
DATA_DIR = HERE / 'data'
DATA_DIR.mkdir(parents=True, exist_ok=True)
LEGACY_PATH = Path('/mnt/c/Users/user/iarandu_crm_cockpit.py')
spec = importlib.util.spec_from_file_location('iarandu_cockpit_legacy', LEGACY_PATH)
legacy = importlib.util.module_from_spec(spec)
spec.loader.exec_module(legacy)
email_spec = importlib.util.spec_from_file_location('revenue_os_email_runner', HERE / 'email_runner.py')
email_runner = importlib.util.module_from_spec(email_spec)
email_spec.loader.exec_module(email_runner)
wa_spec = importlib.util.spec_from_file_location('revenue_os_whatsapp_runner', HERE / 'whatsapp_runner.py')
wa_runner = importlib.util.module_from_spec(wa_spec)
wa_spec.loader.exec_module(wa_runner)

EXCLUDED = {'para_exclusao', 'duplicata_consolidada'}
IG_CAMPAIGN_START_LOCK = threading.Lock()
DB_PATH = legacy.DB_PATH
CENTRAL_DB = legacy.CENTRAL_DB

# The onedir ArticulaPR distribution is installed under this directory by
# default. Override it with IARTICULE_BOT_INSTALL_DIR when choosing another
# install location; the packaged Firefox lives below _internal/playwright_browsers.
BOT_INSTALL_DIR_FALLBACK = r'C:\Users\user\AppData\Local\Programs\ArticulaPR'
BOT_CANONICAL_DB_PATH = r'D:\Eternity-Signals\COMERCIAL\AGENCIAS-PUB\data\agencias_publicidade.db'
BOT_CENTRAL_DB_PATH = r'C:\Users\user\iarandu_crm_central\central.db'
BOT_TEMPLATE_ID = 'custom_1792432503883'
BOT_TEMPLATE_SHA256 = '69af57b0b6142310c6e5ed6eb05a3e0af626700825ec97cc9c3af0ec8146b0d8'
BOT_TEMPLATE_PATHS = {
    5050: r'C:\Users\user\AppData\Local\iArticuleBot_Comercial\custom_templates.json',
    5051: r'C:\Users\user\AppData\Local\iArticuleBot_Oficial\iArticuleBot_Comercial\custom_templates.json',
}
BOT_DATABASE_PATHS = {
    5050: r'C:\Users\user\AppData\Local\iArticuleBot_Comercial\comercial.db',
    5051: r'C:\Users\user\AppData\Local\iArticuleBot_Oficial\iArticuleBot_Comercial\comercial.db',
}
LOGIN_FIREFOX_PROFILES = {
    5050: r'C:\Users\user\AppData\Roaming\Mozilla\Firefox\Profiles\1nz82aud.default-release',
    5051: r'C:\Users\user\AppData\Roaming\Mozilla\Firefox\Profiles\k7852du9.default-release-1',
}
BOT_RUNTIME_PROFILES = {
    5050: r'C:\Users\user\AppData\Local\iArticuleBot_Comercial\ArticulaPR\firefox_runtime_profile_guarded',
    5051: r'C:\Users\user\AppData\Local\iArticuleBot_Oficial\ArticulaPR\firefox_runtime_profile_guarded',
}
BOT_DAILY_CAP = 50
VALID_ROUTES = {'/', '/inicio', '/campanhas', '/prospeccao', '/equipe', '/metas', '/ciclo-comercial', '/prioridades', '/documentacao'}
CONFIG_PATH = DATA_DIR / 'campaign_config.json'
SELECTION_PATH = DATA_DIR / 'selected_agencies.json'
MESSAGES_PATH = DATA_DIR / 'channel_messages.json'
MESSAGE_CHANNELS = ('instagram_5050', 'instagram_5051', 'whatsapp')
VALID_ROUTES.add('/mensagens')
SCHEDULE_STATE_PATH = DATA_DIR / 'schedule_state.json'
SCHEDULE_WINDOW_PROTOCOL = 'saopaulo-weekday-08-18-v1'
DEFAULT_CONFIG = {
    'schedule': {'enabled': False, 'weekdays': [0, 1, 2, 3, 4],
                 'times': {'email': '08:00', 'instagram': '08:05', 'whatsapp': '08:02'}},
    'daily_targets': {'email': 60, 'whatsapp': 30, 'instagram_5050': 50, 'instagram_5051': 50},
    'delays': {'email_min': 120, 'email_max': 180, 'whatsapp_min': 120, 'whatsapp_max': 240,
               'instagram_5050_min': 90, 'instagram_5050_max': 180,
               'instagram_5051_min': 150, 'instagram_5051_max': 300},
}


def _message_config_path(path_value):
    raw = str(path_value)
    match = re.fullmatch(r'([A-Za-z]):\\(.*)', raw)
    if os.name != 'nt' and match:
        return Path('/mnt') / match.group(1).lower() / match.group(2).replace('\\', '/')
    return Path(raw)


def _validate_channel_messages(messages):
    if not isinstance(messages, dict):
        raise ValueError('As mensagens precisam ser um objeto por canal.')
    clean = {}
    for channel in MESSAGE_CHANNELS:
        text = messages.get(channel)
        if not isinstance(text, str) or not text.strip() or len(text) > 6000:
            raise ValueError(f'Modelo {channel} vazio ou acima de 6000 caracteres.')
        if '{empresa}' not in text:
            raise ValueError(f'Modelo {channel} deve conter a variável {{empresa}}.')
        tokens = re.findall(r'\{([^{}]*)\}', text)
        if text.count('{') != text.count('}') or any(
            token != 'empresa' and ('|' not in token or any(not choice.strip() for choice in token.split('|')))
            for token in tokens
        ):
            raise ValueError(f'Modelo {channel} contém uma variável ou variação inválida.')
        clean[channel] = text.strip()
    clean['email'], clean['email_subject'] = email_model.validate_email_fields(
        messages.get('email'), messages.get('email_subject'))
    return clean


def load_channel_messages():
    try:
        payload = json.loads(MESSAGES_PATH.read_text(encoding='utf-8'))
    except (OSError, ValueError, TypeError) as error:
        raise RuntimeError(f'Configuração oficial de mensagens indisponível: {error}') from error
    if not isinstance(payload, dict) or payload.get('template_id') != BOT_TEMPLATE_ID:
        raise RuntimeError('ID da configuração oficial de mensagens inválido.')
    messages = _validate_channel_messages(payload)
    return {'template_id': BOT_TEMPLATE_ID, **messages}


def instagram_message_hash(port):
    key = 'instagram_5050' if int(port) == 5050 else 'instagram_5051' if int(port) == 5051 else None
    if key is None:
        raise ValueError('Porta Instagram inválida para resolução do modelo.')
    return hashlib.sha256(load_channel_messages()[key].encode('utf-8')).hexdigest()


def _atomic_write(path, content):
    temp = path.with_name(path.name + '.tmp')
    temp.write_bytes(content)
    os.replace(str(temp), str(path))


def save_channel_messages(payload):
    incoming = payload.get('messages')
    if isinstance(incoming, dict) and ('email' not in incoming or 'email_subject' not in incoming):
        # Older open CRM tabs may still save only IG/WhatsApp. Preserve the
        # persisted email model; never derive a fallback from hardcoded copy.
        existing = load_channel_messages()
        incoming = {**existing, **incoming}
    messages = _validate_channel_messages(incoming)
    account_updates = []
    for port, channel in ((5050, 'instagram_5050'), (5051, 'instagram_5051')):
        path = _message_config_path(BOT_TEMPLATE_PATHS[port])
        original = path.read_bytes()
        document = json.loads(original.decode('utf-8-sig'))
        template = document.get(BOT_TEMPLATE_ID) if isinstance(document, dict) else None
        if not isinstance(template, dict) or not isinstance(template.get('conteudo'), str):
            raise RuntimeError(f'Template oficial ausente ou inválido na conta IG {port}.')
        if template['conteudo'] != messages[channel]:
            template['conteudo'] = messages[channel]
            updated = json.dumps(document, ensure_ascii=False, indent=2).encode('utf-8') + b'\n'
            account_updates.append((path, original, updated))

    old_config = MESSAGES_PATH.read_bytes() if MESSAGES_PATH.exists() else None
    config_bytes = json.dumps({'template_id': BOT_TEMPLATE_ID, **messages},
                              ensure_ascii=False, indent=2).encode('utf-8') + b'\n'
    stamp = datetime.now().strftime('%Y%m%d-%H%M%S')
    written = []
    try:
        for path, original, updated in account_updates:
            backup = path.with_name(path.name + '.bak_messages_' + stamp)
            if not backup.exists():
                backup.write_bytes(original)
            _atomic_write(path, updated)
            written.append((path, original))
        _atomic_write(MESSAGES_PATH, config_bytes)
    except Exception:
        for path, original in reversed(written):
            _atomic_write(path, original)
        if old_config is None:
            MESSAGES_PATH.unlink(missing_ok=True)
        else:
            _atomic_write(MESSAGES_PATH, old_config)
        raise
    return load_channel_messages()


def _sina_fingerprint(row):
    """Internal content signature used only to deduplicate CNPJ-less rows."""
    fields = (
        _slug(row.get('nome')), str(row.get('uf') or 'XX').strip().upper(),
        _slug(row.get('municipio')), _slug(row.get('site')),
        str(row.get('email') or '').strip().casefold(),
        normalize_instagram_handle(row.get('instagram')) or '',
        re.sub(r'\D', '', str(row.get('telefone') or '')),
    )
    return '|'.join(fields)


def _slug(name):
    text = unicodedata.normalize('NFKD', str(name or ''))
    clean = ''.join(char for char in text if not unicodedata.combining(char)).lower()
    return re.sub(r'[^a-z0-9]', '', clean)


def agency_id(row, collision_fingerprint=None):
    """Return a stable selection ID for every canonical agency row."""
    digits = re.sub(r'\D', '', str(row.get('cnpj') or ''))
    if re.fullmatch(r'\d{14}', digits):
        return digits
    sinapro_id = row.get('sinapro_id')
    if sinapro_id is not None and str(sinapro_id).isdigit():
        return 'sina:' + str(int(sinapro_id))
    fingerprint = _sina_fingerprint(row)
    return 'sina:' + hashlib.sha256(fingerprint.encode('utf-8')).hexdigest()


def is_valid_agency_id(value):
    """Only canonical 14-digit CNPJs can enter selection and campaigns."""
    return bool(re.fullmatch(r'\d{14}', str(value or '')))


def load_campaign_config():
    try:
        data = json.loads(CONFIG_PATH.read_text(encoding='utf-8'))
        merged = json.loads(json.dumps(DEFAULT_CONFIG))
        for key in ('schedule', 'daily_targets', 'delays'):
            if isinstance(data.get(key), dict):
                merged[key].update(data[key])
        maxima = {'email': 60, 'whatsapp': 30, 'instagram_5050': 50, 'instagram_5051': 50}
        for key, maximum in maxima.items():
            merged['daily_targets'][key] = min(max(int(merged['daily_targets'][key]), 1), maximum)
        return merged
    except (OSError, ValueError, TypeError):
        return json.loads(json.dumps(DEFAULT_CONFIG))


def save_campaign_config(data):
    if not isinstance(data, dict):
        raise ValueError('Configuração inválida.')
    current = load_campaign_config()
    for section in ('daily_targets',):
        values = data.get(section, current[section])
        if not isinstance(values, dict):
            raise ValueError('Metas inválidas.')
        for key, maximum in {'email': 60, 'whatsapp': 30, 'instagram_5050': 50, 'instagram_5051': 50}.items():
            value = int(values.get(key, current[section][key]))
            floor = 1
            if not floor <= value <= maximum:
                raise ValueError(f'Meta fora da faixa para {key}.')
            current[section][key] = value
    schedule = data.get('schedule', current['schedule'])
    days = schedule.get('weekdays', current['schedule']['weekdays'])
    if not isinstance(days, list) or any(not isinstance(day, int) or day < 0 or day > 4 for day in days):
        raise ValueError('A programação aceita somente segunda a sexta-feira.')
    times = schedule.get('times', current['schedule']['times'])
    for key in ('email', 'instagram', 'whatsapp'):
        parsed_time = datetime.strptime(str(times.get(key, current['schedule']['times'][key])), '%H:%M')
        if parsed_time.hour < 8 or parsed_time.hour >= 18:
            raise ValueError('Os inícios automáticos precisam ficar entre 08:00 e 17:59.')
    enabled = schedule.get('enabled', current['schedule']['enabled'])
    if not isinstance(enabled, bool):
        raise ValueError('O estado da automação deve ser booleano.')
    current['schedule'] = {'enabled': enabled,
                           'weekdays': sorted(set(days)), 'times': {key: str(times.get(key, current['schedule']['times'][key])) for key in ('email', 'instagram', 'whatsapp')}}
    delays = data.get('delays', current['delays'])
    for key, value in delays.items():
        if key in current['delays']:
            current['delays'][key] = min(max(int(value), 30), 600)
    CONFIG_PATH.write_text(json.dumps(current, ensure_ascii=False, indent=2), encoding='utf-8')
    return current


def _daily_remaining_budget(daily_target, sent_today, eligible):
    """Compute the only operational send limit: remaining quota bounded by eligibility."""
    target = max(0, int(daily_target))
    sent = max(0, int(sent_today))
    ready = max(0, int(eligible))
    remaining = max(0, target - sent)
    return {'daily_target': target, 'sent_today': sent, 'daily_remaining': remaining,
            'eligible': ready, 'limit': min(remaining, ready)}


def load_agency_selection():
    try:
        data = json.loads(SELECTION_PATH.read_text(encoding='utf-8'))
        return data if isinstance(data, dict) else {'cnpjs': [], 'mode': 'manual'}
    except (OSError, ValueError, TypeError):
        return {'cnpjs': [], 'mode': 'manual'}


def save_agency_selection(data):
    raw = data.get('agency_ids', data.get('cnpjs', [])) if isinstance(data, dict) else []
    if not isinstance(raw, list) or len(raw) > 846:
        raise ValueError('Selecao precisa conter ate 846 agencias da base canonica.')
    submitted = {str(value).strip() for value in raw if str(value).strip()}
    canonical_rows = agency_rows()
    canonical_ids = {row['agency_id'] for row in canonical_rows if row.get('agency_id')}
    ids = sorted(submitted)
    unknown = [value for value in ids if value not in canonical_ids]
    if unknown:
        raise ValueError(f'{len(unknown)} identificador(es) nao pertencem a base canonica atual.')
    cnpjs = sorted({str(row.get('cnpj') or '') for row in canonical_rows
                    if row.get('agency_id') in submitted and re.fullmatch(r'\d{14}', str(row.get('cnpj') or ''))})
    selected_rows = [row for row in canonical_rows if row.get('agency_id') in submitted]
    sinapro_row_ids = sorted({int(value) for row in selected_rows
                              if not re.fullmatch(r'\d{14}', str(row.get('cnpj') or ''))
                              for value in row.get('sinapro_ids', [row.get('sinapro_id')])
                              if value is not None and str(value).isdigit()})
    selection = {'mode': 'manual', 'agency_ids': ids, 'cnpjs': cnpjs,
                 'sinapro_row_ids': sinapro_row_ids,
                 'updated_at': datetime.now().isoformat(timespec='seconds')}
    temp = SELECTION_PATH.with_suffix('.tmp')
    temp.write_text(json.dumps(selection, ensure_ascii=False, indent=2), encoding='utf-8')
    temp.replace(SELECTION_PATH)
    return selection


def selected_agency_ids(required=True):
    """Return selected stable agency IDs from the canonical prospect universe."""
    selection = load_agency_selection()
    raw_ids = {str(value).strip() for value in selection.get('agency_ids', selection.get('cnpjs', []))}
    ids = raw_ids
    if required and not ids:
        raise RuntimeError('Selecione ao menos uma agência da base canônica antes do pré-voo.')
    canonical = {row['agency_id'] for row in agency_rows() if row.get('agency_id')}
    invalid = ids - canonical
    if invalid:
        raise RuntimeError('A seleção contém identificadores que não pertencem mais à base canônica; salve a lista novamente.')
    return ids


def selected_agency_cnpjs(required=True):
    """Backward-compatible wrapper: returns only 14-digit CNPJs from selection."""
    ids = selected_agency_ids(required)
    return {v for v in ids if re.fullmatch(r'\d{14}', v)}


def selected_whatsapp_candidates(limit=2000, selected_ids=None, selected_cnpis=None):
    raw = selected_ids if selected_ids is not None else selected_cnpis
    if raw is None:
        raw = selected_agency_ids()
    cnpjs = {value for value in raw if re.fullmatch(r'\d{14}', str(value))}
    suppressed = load_suppressed_cnpis(DB_PATH, CENTRAL_DB)
    cnpjs.difference_update(suppressed)
    if not cnpjs:
        return []
    return wa_runner.engine.get_next_batch(limit, selected_cnpjs=cnpjs)


def normalize_instagram_handle(value):
    raw = str(value or '').strip()
    if not raw:
        return None
    if '://' in raw:
        parsed = urlparse(raw)
        if (parsed.hostname or '').lower().removeprefix('www.') not in {'instagram.com'}:
            return None
        raw = parsed.path.strip('/').split('/', 1)[0]
        if raw.lower() in {'p', 'reel', 'reels', 'stories', 'explore', 'accounts'}:
            return None
    raw = raw[1:] if raw.startswith('@') else raw
    handle = raw.strip().lower()
    return handle if re.fullmatch(r'[a-z0-9._]{1,30}', handle) else None


def selected_instagram_handles(port, selected_ids=None):
    if int(port) not in {5050, 5051}:
        raise ValueError('Porta Instagram nao reconhecida para cobertura por conta.')
    raw_selected = set(selected_ids if selected_ids is not None else selected_agency_ids())
    suppressed = load_suppressed_cnpis(DB_PATH, CENTRAL_DB)
    suppressed_contacts = load_suppressed_contacts(CENTRAL_DB, suppressed)
    suppressed_selected = raw_selected & set(suppressed)
    selected = raw_selected - set(suppressed)
    all_by_handle, selected_by_handle = {}, {}
    handles_by_id = {}
    canonical_rows = agency_rows()
    suppressed_handles = {
        normalize_instagram_handle(row.get('instagram')) for row in canonical_rows
        if re.fullmatch(r'\d{14}', re.sub(r'\D', '', str(row.get('cnpj') or '')))
        and re.sub(r'\D', '', str(row.get('cnpj') or '')) in suppressed
    }
    suppressed_handles.update(suppressed_contacts.get('instagram', set()))
    suppressed_handles.discard(None)
    for agency in canonical_rows:
        aid = agency.get('agency_id')
        handle = normalize_instagram_handle(agency.get('instagram'))
        if not aid or not handle:
            continue
        all_by_handle.setdefault(handle, set()).add(aid)
        agency_cnpj = re.sub(r'\D', '', str(agency.get('cnpj') or ''))
        # Apply global company suppression when this row has a CNPJ. A valid,
        # unique Instagram handle remains selectable even when CNPJ is absent.
        if (aid in selected and handle not in suppressed_handles
                and (not re.fullmatch(r'\d{14}', agency_cnpj) or agency_cnpj not in suppressed)):
            selected_by_handle.setdefault(handle, set()).add(aid)
            handles_by_id.setdefault(aid, set()).add(handle)

    # Assign a stable owner before applying either account's session capacity.
    # Both manual and scheduled starts call this mapper, so neither can borrow
    # a handle from the other account when its campaign is idle or running.
    owner_bit = 0 if int(port) == 5050 else 1
    historically_sent = set()
    for account_port in (5050, 5051):
        path = _message_config_path(BOT_DATABASE_PATHS[account_port])
        account_db = read_connection(path)
        try:
            historically_sent.update(
                str(row[0] or '').strip().lstrip('@').lower()
                for row in account_db.execute(
                    "SELECT handle FROM candidatos WHERE handle IS NOT NULL AND "
                    "(coalesce(msg1_enviada_em,msg2_enviada_em,msg3_enviada_em,"
                    "msg4_enviada_em,msg5_enviada_em,msg6_enviada_em,msg7_enviada_em,"
                    "msg8_enviada_em,msg9_enviada_em,msg10_enviada_em) IS NOT NULL OR "
                    "lower(coalesce(status_geral,'')) "
                    "IN ('enviado','concluido','em_envio','resultado_incerto','incerto') OR "
                    "lower(coalesce(ultimo_erro,'')) LIKE '%resultado_incerto%')"
                )
            )
        finally:
            account_db.close()
    handles = sorted(handle for handle, ids in selected_by_handle.items()
                     if len(ids) == 1 and all_by_handle.get(handle) == ids
                     and (hashlib.sha256(handle.encode('utf-8')).digest()[0] & 1) == owner_bit
                     and handle not in historically_sent)
    covered_ids = {next(iter(selected_by_handle[handle])) for handle in handles}
    selected_with_handle = set(handles_by_id)
    ambiguous_ids = {aid for handle, ids in selected_by_handle.items()
                     if len(ids) != 1 or len(all_by_handle.get(handle, set())) != 1
                     for aid in ids}
    coverage = {
        'selected_agencies': len(raw_selected),
        'suppressed_agencies': len(suppressed_selected),
        'eligible_selected_agencies': len(selected),
        'selected_agencies_without_verified_cnpj': sum(
            1 for row in canonical_rows if row.get('agency_id') in selected
            and not re.fullmatch(r'\d{14}', re.sub(r'\D', '', str(row.get('cnpj') or '')))),
        'selected_agencies_with_verified_cnpj': sum(
            1 for row in canonical_rows if row.get('agency_id') in selected
            and re.fullmatch(r'\d{14}', re.sub(r'\D', '', str(row.get('cnpj') or '')))),
        'suppressed_handles_matched_to_selected_agencies': sum(
            1 for row in canonical_rows if row.get('agency_id') in selected
            and normalize_instagram_handle(row.get('instagram')) in suppressed_handles),
        'selected_agencies_with_handle': len(selected_with_handle),
        'selected_agencies_with_unique_handle': len(covered_ids),
        'ambiguous_handle_agencies': len(ambiguous_ids),
        'selected_agencies_without_handle': len(selected - selected_with_handle),
        'handles_sent_to_bot': len(handles),
    }
    handle_to_agency_id = {handle: next(iter(selected_by_handle[handle])) for handle in handles}
    return {'port': int(port), 'handles': handles, 'handle_to_cnpj': handle_to_agency_id,
            'coverage': coverage}


def _instagram_runtime_contract(port):
    """Require a ready exact-filter build for the selected account only."""
    port = int(port)
    if port not in {5050, 5051}:
        raise ValueError('Invalid Instagram port.')
    other = 5051 if port == 5050 else 5050
    for left, right, label in (
        (BOT_DATABASE_PATHS[port], BOT_DATABASE_PATHS[other], 'database'),
        (LOGIN_FIREFOX_PROFILES[port], LOGIN_FIREFOX_PROFILES[other], 'source profile'),
        (BOT_RUNTIME_PROFILES[port], BOT_RUNTIME_PROFILES[other], 'runtime profile'),
    ):
        if ntpath.normcase(ntpath.normpath(left)) == ntpath.normcase(ntpath.normpath(right)):
            raise RuntimeError(f'Instagram {label} is shared between account ports.')
    infos = {}
    for account_port in (port,):
        info = _local_json(account_port, '/api/bot/build-info')
        if info.get('status') != 'ok' or info.get('filter_protocol') != 'exact-handles-v1':
            raise RuntimeError(f'Instagram {account_port}: exact handle protocol unavailable.')
        if info.get('daily_cap') != BOT_DAILY_CAP:
            raise RuntimeError(
                f'Instagram {account_port}: cap efetivo do bot ({info.get("daily_cap")}) '
                f'diverge do limite operacional revisado ({BOT_DAILY_CAP}).'
            )
        if info.get('composer_guard_protocol') != 'cancel-on-interaction-v1':
            raise RuntimeError(f'Instagram {account_port}: runtime sem proteção contra interferência durante a digitação.')
        if info.get('recipient_guard_protocol') != 'exact-recipient-before-submit-v1':
            raise RuntimeError(f'Instagram {account_port}: runtime não confirma o destinatário antes do Enter.')
        identity = info.get('identity_guard') or {}
        expected = '@maldonadodiego55' if account_port == 5050 else '@iarandu.br'
        if (identity.get('protocol') != 'account-identity-v1'
                or identity.get('expected_handle') != expected
                or not identity.get('source_profile')
                or not identity.get('runtime_profile')
                or (account_port == int(port) and not identity.get('can_start'))):
            raise RuntimeError(
                f'Instagram {account_port}: identidade não liberada. '
                f'{identity.get("last_error") or identity.get("error") or "Build sem guard de identidade."}'
            )
        for field, expected_path in (
            ('source_profile', LOGIN_FIREFOX_PROFILES[account_port]),
            ('runtime_profile', BOT_RUNTIME_PROFILES[account_port]),
        ):
            if ntpath.normcase(ntpath.normpath(str(identity.get(field)))) != ntpath.normcase(ntpath.normpath(expected_path)):
                raise RuntimeError(f'Instagram {account_port}: {field} não corresponde à conta configurada.')
        database = info.get('database') or {}
        if (not database.get('path')
                or not re.fullmatch(r'[0-9a-fA-F]{64}', str(database.get('sha256') or ''))
                or database.get('device') is None or database.get('file_id') is None):
            raise RuntimeError(f'Instagram {account_port}: database path/hash unverified.')
        if ntpath.normcase(ntpath.normpath(database['path'])) != ntpath.normcase(ntpath.normpath(BOT_DATABASE_PATHS[account_port])):
            raise RuntimeError(f'Instagram {account_port}: database path does not match its mapped account.')
        template = info.get('template') or {}
        if (template.get('available') is not True
                or template.get('template_id') != BOT_TEMPLATE_ID
                or template.get('content_sha256') != instagram_message_hash(account_port)
                or ntpath.normcase(ntpath.normpath(str(template.get('path') or ''))) !=
                   ntpath.normcase(ntpath.normpath(BOT_TEMPLATE_PATHS[account_port]))):
            raise RuntimeError(f'Instagram {account_port}: legacy template path/content is not verified.')
        return info


def _dispatch_log_ig_sources():
    """Resolve each live bot DB from build-info; never guess from a legacy path."""
    sources, warnings, file_ids = {}, [], set()
    for port in (5050, 5051):
        try:
            info = _local_json(port, '/api/bot/build-info', timeout=5)
            database = info.get('database') or {}
            raw = str(database.get('path') or '')
            expected = BOT_DATABASE_PATHS[port]
            if (info.get('status') != 'ok' or
                    ntpath.normcase(ntpath.normpath(raw)) != ntpath.normcase(ntpath.normpath(expected)) or
                    database.get('device') is None or database.get('file_id') is None):
                raise RuntimeError('build-info database identity differs from configured account')
            file_id = (str(database['device']), str(database['file_id']))
            if file_id in file_ids:
                raise RuntimeError('Instagram database file is shared between accounts')
            file_ids.add(file_id)
            drive, tail = ntpath.splitdrive(raw)
            if drive and os.name != 'nt':
                if drive.lower() not in {'c:', 'd:'}:
                    raise RuntimeError('unsupported database drive')
                path = Path('/mnt') / drive[0].lower() / tail.lstrip('\\/').replace('\\', '/')
            else:
                path = Path(raw)
            if not path.is_file():
                raise RuntimeError('resolved Instagram database unavailable')
            sources[port] = path
        except (OSError, RuntimeError, ValueError, KeyError) as error:
            warnings.append(f'Instagram {port} logs unavailable: {error}')
    if len(sources) == 2 and sources[5050].resolve() == sources[5051].resolve():
        return {}, warnings + ['Instagram account databases resolve to the same file']
    return sources, warnings


def _dispatch_logs_response(params):
    filters = {key: (params.get(key) or [None])[0] for key in
               ('page', 'limit', 'channel', 'status', 'sender_account', 'sender_instance_id',
                'search', 'date_from', 'date_to')}
    rows = agency_rows()
    handles = {}
    ambiguous = set()
    for row in rows:
        handle = (normalize_instagram_handle(row.get('instagram')) or '').lstrip('@')
        agency_id = row.get('agency_id')
        if not handle or not agency_id:
            continue
        if handle in handles and handles[handle] != agency_id:
            ambiguous.add(handle)
        else:
            handles[handle] = agency_id
    for handle in ambiguous:
        handles.pop(handle, None)
    ig_sources, warnings = _dispatch_log_ig_sources()
    entries, source_warnings = dispatch_logs.collect(
        agency_db=email_runner.AGENCY_DB, central_db=email_runner.CENTRAL_DB,
        instagram_dbs=ig_sources, handle_to_agency_id=handles)
    return dispatch_logs.page(entries, warnings + source_warnings, filters)


def _instagram_campaign_preflight(port, selected_ids, daily_target, requested_limit, config):
    runtime_info = _instagram_runtime_contract(port)
    bot_daily_cap = int(runtime_info['daily_cap'])
    mapping = selected_instagram_handles(port, selected_ids)
    if not mapping['handles']:
        raise RuntimeError(f'Instagram {port}: no canonical handles in the selection.')
    key = f'instagram_{port}'
    payload = {
        'filter_protocol': 'exact-handles-v1', 'template_id': BOT_TEMPLATE_ID,
        'handles': mapping['handles'], 'daily_target': int(daily_target),
        'requested_limit': int(requested_limit), 'limite_diario': int(requested_limit),
        'etapa': 1,
        'delay_min': int(config['delays'][key + '_min']),
        'delay_max': int(config['delays'][key + '_max']),
    }
    response = _local_json(port, '/api/bot/preflight', 'POST', payload, timeout=15)
    coverage = response.get('coverage') or {}
    template = response.get('template') or {}
    if (response.get('status') not in {'ok', 'blocked'}
            or coverage.get('protocol') != 'exact-handles-v1'
            or not isinstance(coverage.get('eligible_pending'), int)
            or not isinstance(coverage.get('allowed_session_limit'), int)
            or coverage.get('reason_contract') != 'selected-handle-reasons-v1'
            or not isinstance(coverage.get('reason_counts'), dict)
            or not isinstance(coverage.get('handle_statuses'), list)
            or template.get('template_id') != BOT_TEMPLATE_ID
            or template.get('content_sha256') != instagram_message_hash(port)
            or ntpath.normcase(ntpath.normpath(str(template.get('path') or ''))) !=
               ntpath.normcase(ntpath.normpath(BOT_TEMPLATE_PATHS[int(port)]))):
        raise RuntimeError(f'Instagram {port}: invalid exact preflight response.')
    reported_handles = {str(row.get('handle') or '').lower()
                        for row in coverage.get('handle_statuses', []) if isinstance(row, dict)}
    if reported_handles != set(mapping['handles']):
        raise RuntimeError(f'Instagram {port}: exact preflight reason coverage does not match the selected handles.')
    return {'mapping': mapping, 'coverage': coverage, 'payload': payload,
            'bot_daily_cap': bot_daily_cap,
            'effective_daily_target': min(int(daily_target), bot_daily_cap),
            'can_start': bool(coverage.get('can_start') and coverage['allowed_session_limit'] > 0)}


def read_connection(path):
    connection = sqlite3.connect(f'file:{path}?mode=ro', uri=True, timeout=5)
    connection.row_factory = sqlite3.Row
    return connection


def clean(value):
    return (value or '').strip() if isinstance(value, str) else value


def agency_rows():
    """Merge das duas origens reais; não trata CSV exportado como banco mestre."""
    connection = read_connection(DB_PATH)
    try:
        a = connection.execute('''
            SELECT cnpj, COALESCE(NULLIF(nome_fantasia,''),razao_social) AS nome,
                   municipio,uf,website_oficial AS site,email,telefone,
                   whatsapp_agencia AS whatsapp,instagram_handle AS instagram,
                   dono_nome AS dono,total_valor_contratos AS contratos,
                   status_enriquecimento AS estado
            FROM agencias
        ''').fetchall()
        s = connection.execute('''
            SELECT id AS sinapro_id,cnpj,nome,municipio,uf,website AS site,email,telefone,
                   NULL AS whatsapp,instagram,dono_nome AS dono,
                   valor_contratos AS contratos,status AS estado
            FROM agencias_sinapro_master
        ''').fetchall()
    finally:
        connection.close()
    merged = {}
    # Mesma ordem e chave de deduplicação do exportador canônico.
    for source, rows in [('sinapro', s), ('agencias', a)]:
        for raw in rows:
            row = {key: clean(raw[key]) for key in raw.keys()}
            state = (row.get('estado') or '').lower()
            if state in EXCLUDED or state.startswith('duplicada_unificada'):
                continue
            cnpj = row.get('cnpj') or ''
            if cnpj.startswith('DUP_'):
                continue
            name = row.get('nome') or ''
            if not name:
                continue
            digits = re.sub(r'\D', '', cnpj)
            if source == 'agencias' and not digits:
                # O exportador canônico ignora chaves governamentais sem CNPJ.
                continue
            if digits:
                key = 'cnpj:' + digits
            else:
                fingerprint = _sina_fingerprint(row)
                key = f'sina:{hashlib.sha256(fingerprint.encode("utf-8")).hexdigest()}'
            if not key:
                continue
            row['source'] = source
            row['cnpj'] = digits or cnpj
            if source == 'sinapro' and row.get('sinapro_id') is not None:
                row['sinapro_ids'] = [int(row['sinapro_id'])]
            if key not in merged:
                merged[key] = row
            else:
                target = merged[key]
                if source == 'sinapro' and row.get('sinapro_id') is not None:
                    existing_id = target.get('sinapro_id')
                    target['sinapro_id'] = min(int(existing_id), int(row['sinapro_id'])) if existing_id is not None else int(row['sinapro_id'])
                    target['sinapro_ids'] = sorted(set(target.get('sinapro_ids', [])) | {int(row['sinapro_id'])})
                for field in ('site', 'email', 'telefone', 'whatsapp', 'instagram', 'dono', 'municipio', 'uf', 'contratos'):
                    if not target.get(field) and row.get(field):
                        target[field] = row[field]
                if source == 'agencias':
                    target['source'] = 'sinapro + agencias'
    result = list(merged.values())
    for row in result:
        digits = re.sub(r'\D', '', str(row.get('cnpj') or ''))
        if re.fullmatch(r'\d{14}', digits):
            row['agency_id'] = digits
            row['agency_identity_confidence'] = 'cnpj'
        else:
            row['agency_id'] = agency_id(row)
            row['agency_identity_confidence'] = 'sinapro_row_id' if row.get('sinapro_id') is not None else 'sinapro_record_fingerprint'
    return result


def filter_agency_rows(q='', uf='', channel='', source_rows=None):
    rows = list(source_rows) if source_rows is not None else agency_rows()
    query = str(q or '').strip().casefold()
    state = str(uf or '').strip().upper()
    channel = str(channel or '').strip().lower()
    if query:
        rows = [row for row in rows if query in ' '.join(
            str(row.get(key) or '') for key in ('nome', 'municipio', 'uf', 'dono', 'cnpj', 'agency_id')
        ).casefold()]
    if state:
        rows = [row for row in rows if (row.get('uf') or '').upper() == state]
    if channel in {'instagram', 'whatsapp', 'email'}:
        rows = [row for row in rows if row.get(channel)]
    rows.sort(key=lambda row: (
        not bool(re.fullmatch(r'\d{14}', re.sub(r'\D', '', str(row.get('cnpj') or '')))),
        (row.get('nome') or '').casefold()))
    return rows


def canonical_base_metadata(rows=None):
    """Describe the complete canonical universe without reading channel queues."""
    rows = agency_rows() if rows is None else rows
    cnpj_count = sum(bool(re.fullmatch(r'\d{14}', re.sub(r'\D', '', str(row.get('cnpj') or '')))) for row in rows)
    connection = read_connection(DB_PATH)
    try:
        agencies_updated = connection.execute('SELECT max(ultima_atualizacao) FROM agencias').fetchone()[0]
        sinapro_updated = connection.execute('SELECT max(atualizado_em) FROM agencias_sinapro_master').fetchone()[0]
    finally:
        connection.close()
    updates = [value for value in (agencies_updated, sinapro_updated) if value]
    return {'source': Path(DB_PATH).name, 'canonical_total': len(rows),
            'canonical_cnpj_count': cnpj_count,
            'canonical_cnpjless_count': len(rows) - cnpj_count,
            'source_updated_at': max(updates) if updates else None}


def get_replies():
    """Só respostas confirmadas com registro de evidência pelo operador."""
    connection = read_connection(DB_PATH)
    try:
        rows = connection.execute('''
            SELECT h.cnpj,h.descricao,h.metadata_json,h.criado_em,
                   d.etapa,d.proximo_passo,d.prazo_proximo_passo,d.responsavel,d.prioridade
            FROM crm_history h LEFT JOIN crm_deals d ON d.cnpj=h.cnpj
            WHERE h.tipo='resposta_confirmada'
            ORDER BY h.criado_em DESC
        ''').fetchall()
        agency_index = {row['cnpj']: row for row in agency_rows() if row.get('cnpj')}
        result = []
        seen = set()
        for raw in rows:
            row = dict(raw)
            agency = agency_index.get(row['cnpj'])
            if not agency:
                continue
            if row['cnpj'] in seen:
                continue
            seen.add(row['cnpj'])
            try:
                metadata = json.loads(row.pop('metadata_json') or '{}')
            except json.JSONDecodeError:
                metadata = {}
            row.update({'nome': agency['nome'], 'municipio': agency['municipio'], 'uf': agency['uf']})
            row['canal'] = metadata.get('canal') or 'não informado'
            row['recebido_em'] = metadata.get('recebido_em') or row['criado_em']
            row['etapa'] = row.get('etapa') or 'resposta'
            result.append(row)
        return result
    finally:
        connection.close()


def get_priority_deals():
    """Read actual CRM deals separately from explicitly confirmed replies."""
    connection = read_connection(DB_PATH)
    try:
        history_exists = connection.execute(
            "SELECT 1 FROM sqlite_master WHERE type='table' AND name='crm_history'"
        ).fetchone() is not None
        reply_expr = "EXISTS (SELECT 1 FROM crm_history h WHERE h.cnpj=d.cnpj AND h.tipo='resposta_confirmada')" if history_exists else "0"
        rows = connection.execute(f'''
            SELECT d.cnpj,d.etapa,d.valor_proposta,d.responsavel,d.proximo_passo,
                   d.prazo_proximo_passo,d.prioridade,d.criado_em,d.atualizado_em,
                   {reply_expr} AS has_confirmed_reply
            FROM crm_deals d
            ORDER BY CASE WHEN upper(COALESCE(d.prioridade,''))='ALTA' THEN 0
                          WHEN upper(COALESCE(d.prioridade,''))='MEDIA' THEN 1 ELSE 2 END,
                     d.atualizado_em DESC,d.cnpj
        ''').fetchall()
        agencies = {str(row.get('cnpj') or ''): row for row in agency_rows() if row.get('cnpj')}
        result = []
        for raw in rows:
            row = dict(raw)
            agency = agencies.get(str(row.get('cnpj') or ''), {})
            row.update({'nome': agency.get('nome') or '', 'municipio': agency.get('municipio') or '',
                        'uf': agency.get('uf') or '', 'contratos': agency.get('contratos'),
                        'has_confirmed_reply': bool(row.get('has_confirmed_reply'))})
            result.append(row)
        return result
    finally:
        connection.close()


def summary():
    agencies = agency_rows()
    replies = get_replies()
    connection = read_connection(DB_PATH)
    try:
        pending = connection.execute("SELECT count(*) FROM crm_tasks WHERE status='PENDENTE'").fetchone()[0]
        meetings = connection.execute("SELECT count(*) FROM crm_deals WHERE etapa IN ('diagnostico','demonstracao')").fetchone()[0]
    finally:
        connection.close()
    return {
        'updated_at': datetime.now().isoformat(timespec='seconds'),
        'agencies': len(agencies),
        'sites': sum(bool(a.get('site') and str(a['site']).startswith(('http://','https://'))) for a in agencies),
        'with_email': sum(bool(a.get('email')) for a in agencies),
        'with_instagram': sum(bool(a.get('instagram')) for a in agencies),
        'with_phone': sum(bool(a.get('telefone') or a.get('whatsapp')) for a in agencies),
        'replies': len(replies),
        'pending_tasks': pending,
        'meetings': meetings,
    }


def prospecting_summary():
    """Keep the canonical prospect universe, legacy CRM rows, and channel queues distinct."""
    prospects = agency_rows()
    connection = read_connection(DB_PATH)
    try:
        legacy_rows = connection.execute('SELECT count(*) FROM agencias').fetchone()[0]
        base_rows = connection.execute('SELECT count(*) FROM agencias').fetchone()[0]
        sinapro_rows = connection.execute('SELECT count(*) FROM agencias_sinapro_master').fetchone()[0]
        last_agencies = connection.execute('SELECT max(ultima_atualizacao) FROM agencias').fetchone()[0]
        last_sinapro = connection.execute('SELECT max(atualizado_em) FROM agencias_sinapro_master').fetchone()[0]
    finally:
        connection.close()
    updates = [value for value in (last_agencies, last_sinapro) if value]
    instagram = {}
    for port in (5050, 5051):
        try:
            status = _local_json(port)
            kpi = status.get('kpi') or {}
            instagram[str(port)] = {
                'pending_in_motor': int(kpi.get('pendentes') or 0),
                'sent_today_in_motor': int(kpi.get('enviados_hoje') or 0),
                'running': bool(status.get('is_running')),
            }
        except Exception as error:
            instagram[str(port)] = {'error': str(error)}
    try:
        email_eligible = len(email_runner.eligible_emails())
    except Exception as error:
        email_eligible = None
        email_error = str(error)
    try:
        whatsapp_eligible = len(wa_runner.engine.get_next_batch(2000))
    except Exception as error:
        whatsapp_eligible = None
        whatsapp_error = str(error)
    return {
        'canonical': {
            'total': len(prospects),
            'database': Path(DB_PATH).name,
            'tables': {'agencias': base_rows, 'agencias_sinapro_master': sinapro_rows},
            'updated_at': max(updates) if updates else None,
        },
        'legacy_crm': {'agency_table_rows': legacy_rows, 'source': 'agencias'},
        'channel_eligibility': {
            'email': {'eligible': email_eligible, **({'error': email_error} if 'email_error' in locals() else {})},
            'whatsapp': {'eligible': whatsapp_eligible, **({'error': whatsapp_error} if 'whatsapp_error' in locals() else {})},
            'instagram_5050': instagram.get('5050', {}),
            'instagram_5051': instagram.get('5051', {}),
        },
        'updated_at': datetime.now().isoformat(timespec='seconds'),
    }


def _local_json(port, path='/api/bot/status', method='GET', payload=None, timeout=9):
    url = f'http://127.0.0.1:{port}{path}'
    body = None if payload is None else json.dumps(payload).encode('utf-8')
    request = urllib.request.Request(url, data=body, method=method,
                                     headers={'Content-Type': 'application/json'} if body is not None else {})
    try:
        with urllib.request.urlopen(request, timeout=timeout) as response:
            raw = response.read().decode('utf-8', errors='replace')
    except urllib.error.URLError as linux_error:
        # This Revenue OS runs in WSL while the Instagram bots run as Windows
        # loopback services. Linux localhost is isolated from Windows localhost,
        # so bridge only this local request through Windows PowerShell.
        powershell = Path('/mnt/c/Windows/System32/WindowsPowerShell/v1.0/powershell.exe')
        if not powershell.is_file():
            raise linux_error
        encoded_body = '' if body is None else base64.b64encode(body).decode('ascii')
        encoded_url = base64.b64encode(url.encode('utf-8')).decode('ascii')
        ps = ("$ProgressPreference='SilentlyContinue'; "
              "$ErrorActionPreference='Stop'; "
              f"$uri=[System.Text.Encoding]::UTF8.GetString([Convert]::FromBase64String('{encoded_url}')); "
              f"$method='{method.upper()}'; "
              f"$body64='{encoded_body}'; "
              f"$timeout={int(timeout)}; "
              "try { "
              "if([string]::IsNullOrEmpty($body64)){ "
              "$result=Invoke-RestMethod -Uri $uri -Method $method -TimeoutSec $timeout "
              "} else { "
              "$bodyText=[System.Text.Encoding]::UTF8.GetString([Convert]::FromBase64String($body64)); "
              "$result=Invoke-RestMethod -Uri $uri -Method $method -ContentType 'application/json' -Body $bodyText -TimeoutSec $timeout "
              "}; "
              "if($null -eq $result){$result=@{}}; "
              "$json=ConvertTo-Json -InputObject $result -Depth 100 -Compress; "
              "$json64=[Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($json)); "
              "[Console]::Out.WriteLine($json64); exit 0 "
              "} catch { [Console]::Error.WriteLine($_.Exception.Message); exit 1 }")
        try:
            bridged = subprocess.run([str(powershell), '-NoProfile', '-NonInteractive', '-Command', ps],
                                     stdin=subprocess.DEVNULL, capture_output=True, timeout=timeout + 3)
        except (OSError, subprocess.SubprocessError) as bridge_error:
            raise RuntimeError(f'Bot {port} inacessível pelo WSL e pela ponte Windows: {bridge_error}') from bridge_error
        if bridged.returncode != 0:
            detail = bridged.stderr.decode('utf-8', errors='replace').strip()
            raise RuntimeError(f'Bot {port} não respondeu via Windows PowerShell: {detail or "erro HTTP"}')
        encoded_response = bridged.stdout.decode('ascii', errors='strict').strip()
        try:
            raw = base64.b64decode(encoded_response, validate=True).decode('utf-8')
        except (ValueError, UnicodeDecodeError) as bridge_json_error:
            raise RuntimeError(f'Bot {port}: resposta JSON invalida recebida pela ponte PowerShell.') from bridge_json_error
    return json.loads(raw) if raw else {}


def _powershell_literal(value):
    return "'" + str(value).replace("'", "''") + "'"


def _open_login_firefox(payload):
    """Request Firefox from the onedir distribution with the selected account profile."""
    if not isinstance(payload, dict):
        raise ValueError('O corpo precisa informar a porta do bot.')
    port = payload.get('port')
    if isinstance(port, bool) or not isinstance(port, int) or port not in LOGIN_FIREFOX_PROFILES:
        raise ValueError('Porta inválida; use 5050 (pessoal) ou 5051 (oficial).')

    install_dir = os.environ.get('IARTICULE_BOT_INSTALL_DIR') or BOT_INSTALL_DIR_FALLBACK
    install_dir = ntpath.normpath(install_dir)
    if not ntpath.isabs(install_dir):
        raise ValueError('IARTICULE_BOT_INSTALL_DIR precisa ser um caminho absoluto do Windows.')
    browser = ntpath.join(
        install_dir, '_internal', 'playwright_browsers', 'firefox-1543', 'firefox', 'firefox.exe'
    )
    profile_env = f'IARTICULE_FIREFOX_PROFILE_{port}'
    profile = ntpath.normpath(os.environ.get(profile_env) or LOGIN_FIREFOX_PROFILES[port])
    if not ntpath.isabs(profile):
        raise ValueError(f'{profile_env} precisa ser um caminho absoluto do Windows.')

    ps = (
        "$ErrorActionPreference='Stop'; "
        f"$browser={_powershell_literal(browser)}; "
        f"$firefoxProfile={_powershell_literal(profile)}; "
        "if(-not (Test-Path -LiteralPath $browser -PathType Leaf)){throw 'Firefox do pacote onedir nao encontrado.'}; "
        "if(-not (Test-Path -LiteralPath $firefoxProfile -PathType Container)){throw 'Perfil Firefox da conta nao encontrado.'}; "
        "Start-Process -FilePath $browser "
        "-ArgumentList @('-no-remote','-profile',('\"{0}\"' -f $firefoxProfile)) "
        "-WorkingDirectory (Split-Path -LiteralPath $browser -Parent) | Out-Null"
    )
    subprocess.run(
        ['powershell.exe', '-NoProfile', '-NonInteractive', '-Command', ps],
        check=True, stdin=subprocess.DEVNULL, capture_output=True, timeout=15,
    )
    return {'status': 'requested', 'port': port, 'browser': browser, 'profile': profile}


def _ensure_bot_service(port):
    try:
        return _local_json(port)
    except (OSError, ValueError, urllib.error.URLError):
        if port == 5050:
            localapp = r'C:\Users\user\AppData\Local\iArticuleBot_Comercial'
            profile = r'C:\Users\user\AppData\Roaming\Mozilla\Firefox\Profiles\1nz82aud.default-release'
            workdir = r'C:\Users\user\AppData\Local\iArticuleBot_Comercial'
            default_db = r'C:\Users\user\AppData\Local\iArticuleBot_Comercial\comercial.db'
            templates_path = r'C:\Users\user\AppData\Local\iArticuleBot_Comercial\custom_templates.json'
            runtime_profile = BOT_RUNTIME_PROFILES[port]
            edition, daily_cap, expected_handle = 'comercial', BOT_DAILY_CAP, 'maldonadodiego55'
        else:
            localapp = r'C:\Users\user\AppData\Local\iArticuleBot_Oficial'
            profile = r'C:\Users\user\AppData\Roaming\Mozilla\Firefox\Profiles\k7852du9.default-release-1'
            workdir = r'C:\Users\user\AppData\Local\iArticuleBot_Oficial\iArticuleBot_Comercial'
            default_db = r'C:\Users\user\AppData\Local\iArticuleBot_Oficial\iArticuleBot_Comercial\comercial.db'
            templates_path = r'C:\Users\user\AppData\Local\iArticuleBot_Oficial\iArticuleBot_Comercial\custom_templates.json'
            runtime_profile = BOT_RUNTIME_PROFILES[port]
            edition, daily_cap, expected_handle = 'oficial', BOT_DAILY_CAP, 'iarandu.br'
        explicit_db = (os.environ.get(f'IARTICULE_DB_PATH_{port}') or
                       os.environ.get('IARTICULE_DB_PATH'))
        if explicit_db and ntpath.normcase(ntpath.normpath(explicit_db)) != ntpath.normcase(ntpath.normpath(default_db)):
            raise RuntimeError(f'IARTICULE_DB_PATH precisa ser especifico da conta {port}; caminho inesperado bloqueado.')
        db_path = explicit_db or default_db
        install_dir = ntpath.normpath(os.environ.get('IARTICULE_BOT_INSTALL_DIR') or BOT_INSTALL_DIR_FALLBACK)
        exe_name = ('ArticulaPR_official_browserfix_20261029.exe' if port == 5051
                    else 'ArticulaPR.exe')
        exe = ntpath.join(install_dir, exe_name)
        data_dir = ntpath.join(localapp, 'ArticulaPR')
        # Firefox needs the real Windows LOCALAPPDATA on the official account.
        # Its bot data, DB and browser profile remain isolated by explicit paths.
        localapp_env = (f"$env:LOCALAPPDATA={_powershell_literal(localapp)}; "
                        if port == 5050 else "")
        ps = (f"$ErrorActionPreference='Stop'; {localapp_env}"
              f"$env:IARTICULE_DATA_DIR={_powershell_literal(data_dir)}; "
              f"$env:IARTICULE_DB_PATH={_powershell_literal(db_path)}; "
              f"$env:IARTICULE_TEMPLATES_PATH={_powershell_literal(templates_path)}; "
              f"$env:IARTICULE_RUNTIME_PROFILE_DIR={_powershell_literal(runtime_profile)}; "
              f"$env:IARTICULE_TEMPLATE_ID='{BOT_TEMPLATE_ID}'; "
              f"$env:IARANDU_CANONICAL_DB={_powershell_literal(BOT_CANONICAL_DB_PATH)}; "
              f"$env:IARANDU_CENTRAL_DB={_powershell_literal(BOT_CENTRAL_DB_PATH)}; "
              f"$env:IARTICULE_DAILY_CAP='{daily_cap}'; $env:BOT_EDITION='{edition}'; "
              f"$env:IARTICULE_EXPECTED_INSTAGRAM_HANDLE='{expected_handle}'; "
              f"$env:FIREFOX_PROFILE_DIR={_powershell_literal(profile)}; "
              f"if(-not (Get-NetTCPConnection -State Listen -LocalPort {port} -ErrorAction SilentlyContinue)){{"
              f"Start-Process -FilePath {_powershell_literal(exe)} -ArgumentList @('--port','{port}','--edition','{edition}','--no-open-browser') "
              f"-WorkingDirectory {_powershell_literal(install_dir)} -WindowStyle Hidden}}; Start-Sleep -Seconds 4")
        subprocess.run(['powershell.exe', '-NoProfile', '-NonInteractive', '-Command', ps],
                       check=True, stdin=subprocess.DEVNULL, capture_output=True, timeout=12)
        return _local_json(port)


def _start_instagram(port, limit, daily_target, config, selected_ids=None, scheduled=False):
    selected = set(selected_ids if selected_ids is not None else selected_agency_ids())
    _ensure_bot_service(port)
    status = _local_json(port)
    if scheduled:
        info = _local_json(port, '/api/bot/build-info')
        if info.get('scheduled_window_protocol') != SCHEDULE_WINDOW_PROTOCOL:
            raise RuntimeError(f'Instagram {port}: bot sem guarda de horario automatico; inicio bloqueado.')
    if status.get('is_running'):
        raise RuntimeError(f'Instagram {port} already has a running campaign.')
    # Recheck exact handles and daily remaining immediately before starting.
    check = _instagram_campaign_preflight(port, selected, daily_target, limit, config)
    coverage = check['coverage']
    allowed = int(coverage['allowed_session_limit'])
    if not check['can_start'] or allowed < 1 or allowed > int(limit):
        raise RuntimeError(f'Instagram {port} preflight blocked start: eligible={coverage.get("eligible_pending")}, allowed={allowed}.')
    key = f'instagram_{port}'
    payload = {
        'handles': check['mapping']['handles'], 'filter_protocol': 'exact-handles-v1',
        'municipios': None, 'partidos': None,
        'etapa': 1,
        'template_id': BOT_TEMPLATE_ID, 'origem': None,
        'daily_target': int(daily_target), 'requested_limit': allowed,
        'limite_diario': allowed,
        'delay_min': int(config['delays'][key + '_min']),
        'delay_max': int(config['delays'][key + '_max']), 'dry_run': False,
        'scheduled': bool(scheduled),
    }
    result = _local_json(port, '/api/bot/start', 'POST', payload, timeout=75)
    return {'port': port, 'limit': allowed, 'configured_daily_target': daily_target,
            'bot_daily_cap': check['bot_daily_cap'],
            'effective_daily_target': check['effective_daily_target'],
            'allowed_session_limit': allowed, 'coverage': coverage, 'result': result}


def _email_campaign_preflight(daily_target, preview=False):
    selected = selected_agency_ids(required=False)
    selection = load_agency_selection()
    email_scope = set(selected) | {f"sina:{int(value)}" for value in selection.get('sinapro_row_ids', [])
                                  if str(value).isdigit()}
    state = email_runner.preflight(int(daily_target), selected_agency_ids=email_scope)
    budget = _daily_remaining_budget(daily_target, state.get('sent_today', 0), state.get('eligible', 0))
    reason = None
    try:
        email_model.load_snapshot(MESSAGES_PATH)
    except (RuntimeError, ValueError) as error:
        reason = str(error)
    if reason is None and preview:
        reason = 'Preview ativo; nenhum envio e permitido.'
    elif reason is None and not selected:
        reason = 'Selecione agencias da base canonica.'
    elif reason is None and state.get('active'):
        reason = 'Ja existe uma campanha de e-mail em execucao.'
    elif reason is None and not state.get('script_found'):
        reason = 'Motor real de e-mail indisponivel.'
    elif reason is None and budget['limit'] < 1:
        reason = 'Meta diaria restante ou elegibilidade igual a zero.'
    return {**state, **budget, 'selection_count': len(selected),
            'can_start': reason is None, 'blocked_reason': reason,
            'accepted_metric_label': 'aceitos pelo SMTP; entrega nao confirmada'}


def _start_email(limit, daily_target, config, scheduled=False):
    email_model.load_snapshot(MESSAGES_PATH)
    selected = selected_agency_ids()
    selection = load_agency_selection()
    email_scope = set(selected) | {f"sina:{int(value)}" for value in selection.get('sinapro_row_ids', [])
                                  if str(value).isdigit()}
    state = email_runner.preflight(int(daily_target), selected_agency_ids=email_scope)
    budget = _daily_remaining_budget(daily_target, state.get('sent_today', 0), state.get('eligible', 0))
    if state.get('active'):
        raise RuntimeError('Ja existe uma campanha de e-mail em execucao.')
    if not state.get('script_found'):
        raise RuntimeError('Motor real de e-mail indisponivel; campanha nao iniciada.')
    approved = budget['limit']
    if approved < 1:
        raise RuntimeError(f"E-mail sem orcamento elegivel: restante={budget['daily_remaining']}, elegiveis={budget['eligible']}.")
    if limit is not None and int(limit) != approved:
        raise RuntimeError(f"Preflight de e-mail mudou: limite aprovado={approved}; solicitado={limit}.")
    log_path = DATA_DIR / 'email_runner.log'
    log_stream = log_path.open('a', encoding='utf-8')
    try:
        process = subprocess.Popen(['python3', str(HERE / 'email_runner.py'), '--limit', str(approved),
                                     '--daily-target', str(daily_target),
                                     '--cnpjs-file', str(SELECTION_PATH),
                                     '--delay-min', str(config['delays']['email_min']),
                                     '--delay-max', str(config['delays']['email_max'])] +
                                    (['--scheduled'] if scheduled else []),
                                    stdin=subprocess.DEVNULL, stdout=log_stream, stderr=subprocess.STDOUT,
                                    start_new_session=True)
    finally:
        log_stream.close()
    return {'pid': process.pid, 'limit': approved, 'daily_target': int(daily_target),
            'accepted_metric_label': 'aceitos pelo SMTP; entrega nao confirmada',
            'selected_agencies': len(selected)}


def _whatsapp_lock_status():
    """Fail closed when a WhatsApp runner lock exists or cannot be read."""
    lock = Path(wa_runner.LOCK)
    if not lock.exists():
        return False, None
    try:
        content = lock.read_text(encoding='utf-8').strip()
    except OSError as error:
        return True, f'Lock do WhatsApp não pôde ser inspecionado: {error}'
    if content.upper() == 'STOP':
        return True, 'Parada do WhatsApp solicitada; aguarde o lote encerrar.'
    first = content.split(' ', 1)[0]
    if not first.isdigit():
        return True, 'Lock do WhatsApp existe sem PID válido; novo início bloqueado.'
    try:
        os.kill(int(first), 0)
    except ProcessLookupError:
        return False, None
    except PermissionError:
        return True, 'O processo do WhatsApp existe, mas não pode ser inspecionado; novo início bloqueado.'
    except OSError as error:
        return True, f'Estado do processo WhatsApp incerto; novo início bloqueado: {error}'
    return True, 'Já existe um lote de WhatsApp em execução.'


def _start_whatsapp(limit, daily_target, config, scheduled=False):
    selected_agency_ids()
    remaining = max(0, int(daily_target) - wa_runner.sent_today())
    if limit < 1 or limit > remaining:
        raise RuntimeError(f'WhatsApp: restam {remaining} envios na meta diária; lote solicitado {limit}.')
    if len(selected_whatsapp_candidates(2000)) < limit:
        raise RuntimeError(f'WhatsApp: a fila não contém {limit} contatos validados e inéditos.')
    active, lock_reason = _whatsapp_lock_status()
    if active:
        raise RuntimeError(lock_reason or 'Já existe um lote de WhatsApp em execução.')
    log_path = DATA_DIR / 'whatsapp_runner.log'
    log_stream = log_path.open('a', encoding='utf-8')
    try:
        process = subprocess.Popen(['python3', str(HERE / 'whatsapp_runner.py'), '--limit', str(limit),
                                     '--daily-target', str(daily_target), '--cnpjs-file', str(SELECTION_PATH)] +
                                    (['--scheduled'] if scheduled else []),
                                    stdin=subprocess.DEVNULL, stdout=log_stream, stderr=subprocess.STDOUT,
                                    start_new_session=True)
    finally:
        log_stream.close()
    return {'pid': process.pid, 'limit': limit, 'daily_target': daily_target}


def _independent_instagram_assignments(pending_by_port, capacity):
    """Each Instagram account gets its own pending queue, even for the same agency."""
    return {
        port: set(sorted(pending_by_port[port])[:max(0, int(capacity[port]))])
        for port in (5050, 5051)
    }


def _campaign_preflight(preview=True, include_internal=False):
    config = load_campaign_config()
    targets = config['daily_targets']
    selected_ids = selected_agency_ids(required=False)
    selection_ready = bool(selected_ids)
    try:
        email = _email_campaign_preflight(int(targets['email']), preview=preview)
    except Exception as error:
        email = {'eligible': 0, 'sent_today': None, 'daily_target': targets['email'],
                 'daily_remaining': 0, 'limit': 0, 'can_start': False,
                 'queue_today': False, 'active': False, 'script_found': False,
                 'blocked_reason': f'Preflight de e-mail falhou; inicio bloqueado: {error}',
                 'accepted_metric_label': 'aceitos pelo SMTP; entrega nao confirmada'}
    whatsapp = {'stop_verified': (HERE / 'whatsapp_runner.py').is_file(),
                'session_ready': False, 'sent_today': None, 'eligible': 0, 'daily_target': targets['whatsapp'],
                'daily_remaining': 0, 'can_start': False}
    try:
        whatsapp['active'], whatsapp['active_reason'] = _whatsapp_lock_status()
        whatsapp['session_ready'] = bool(wa_runner.engine.get_active_session())
        whatsapp['sent_today'] = wa_runner.sent_today()
        whatsapp['daily_remaining'] = max(0, targets['whatsapp'] - whatsapp['sent_today'])
        whatsapp['eligible'] = len(selected_whatsapp_candidates(2000, selected_ids=selected_ids)) if selection_ready else 0
        whatsapp['can_start'] = (not preview and selection_ready and whatsapp['stop_verified'] and not whatsapp['active'] and whatsapp['session_ready']
                                 and whatsapp['daily_remaining'] > 0 and whatsapp['eligible'] > 0)
        reasons = []
        if not selection_ready:
            reasons.append('Selecione agencias da base canonica.')
        else:
            if whatsapp['daily_remaining'] <= 0:
                reasons.append(f"Meta diaria atingida: {whatsapp['sent_today']} enviados de {whatsapp['daily_target']}.")
            if whatsapp['eligible'] <= 0:
                reasons.append('Nenhum WhatsApp elegivel na selecao atual.')
        if not whatsapp['session_ready']:
            reasons.append('Sessao OpenWA indisponivel.')
        if whatsapp['active']:
            reasons.append(whatsapp.get('active_reason') or 'Já existe um lote de WhatsApp em execução.')
        if reasons:
            whatsapp['blocked_reason'] = ' '.join(reasons)
    except Exception as error:
        whatsapp['error'] = str(error)
    instagram = {}
    account_coverage = {}
    for port, key in ((5050, 'instagram_5050'), (5051, 'instagram_5051')):
        item = {'port': port, 'available': None, 'can_start': False,
                'status_source': f'GET http://127.0.0.1:{port}/api/bot/status',
                'checked_at': datetime.now().isoformat(timespec='seconds'),
                'sent_today': None, 'queue_pending': None}
        try:
            mapping = selected_instagram_handles(port, selected_ids) if selection_ready else {
                'port': port, 'handles': [], 'coverage': {'selected_agencies': 0, 'handles_sent_to_bot': 0}}
            item['eligible'] = len(mapping['handles'])
            item['coverage'] = mapping['coverage']
            status = _local_json(port)
            item['is_running'] = bool(status.get('is_running'))
            kpi = status.get('kpi') or {}
            item['current_state'] = status.get('current_state') or ('Executando' if item['is_running'] else 'Parado')
            exact = _instagram_campaign_preflight(port, selected_ids, targets[key], targets[key], config) if selection_ready else None
            bot_coverage = exact['coverage'] if exact else None
            item['bot_coverage'] = bot_coverage
            if bot_coverage is not None:
                account_coverage[port] = (mapping.get('handle_to_cnpj', {}), bot_coverage)
            if bot_coverage is not None:
                item['eligible'] = int(bot_coverage['eligible_pending'])
            raw_sent_today = bot_coverage.get('sent_today') if bot_coverage else None
            item['sent_today'] = int(raw_sent_today) if raw_sent_today is not None else None
            raw_pending = kpi.get('pendentes')
            item['queue_pending'] = int(raw_pending) if raw_pending is not None else None
            item['daily_target'] = targets[key]
            bot_daily_cap = (int(exact['bot_daily_cap']) if exact else
                             int(kpi['meta_hoje']) if kpi.get('meta_hoje') is not None else None)
            item['bot_daily_cap'] = bot_daily_cap
            item['effective_daily_target'] = (min(int(item['daily_target']), bot_daily_cap)
                                              if bot_daily_cap is not None else None)
            item['effective_daily_remaining'] = (
                max(0, item['effective_daily_target'] - item['sent_today'])
                if item['effective_daily_target'] is not None and item['sent_today'] is not None else None
            )
            if bot_daily_cap is not None and int(item['daily_target']) > bot_daily_cap:
                item['capacity_notice'] = (
                    f"Meta configurada {item['daily_target']}/dia; cap efetivo do bot {bot_daily_cap}/dia. "
                    f"O preflight libera apenas o menor valor entre cap, saldo e elegiveis."
                )
            else:
                item.pop('capacity_notice', None)
            item['available'] = item['effective_daily_remaining']
            item['daily_cap_source'] = f'GET http://127.0.0.1:{port}/api/bot/build-info -> daily_cap'
            if exact and kpi.get('meta_hoje') is not None and int(kpi['meta_hoje']) != bot_daily_cap:
                raise RuntimeError(f'Instagram {port}: cap diferente entre build-info e status do bot.')
            item['daily_count_source'] = (f'POST http://127.0.0.1:{port}/api/bot/preflight -> coverage.sent_today'
                                          if item['sent_today'] is not None else 'Indisponivel; campo diario ausente no endpoint')
            if item['sent_today'] is None:
                item['metric_error'] = 'Endpoint respondeu sem kpi.enviados_hoje; contagem diaria nao inferida.'
            item['can_start'] = bool(not preview and exact and exact['can_start'] and not item['is_running'])
            item['selection_ready'] = selection_ready
            if preview:
                item['blocked_reason'] = 'Preview ativo; nenhuma acao de envio e permitida.'
            elif not selection_ready:
                item['blocked_reason'] = 'Selecione agencias da base canonica.'
            elif not exact or not exact['can_start']:
                coverage = exact['coverage'] if exact else {}
                reasons = []
                if int(coverage.get('daily_remaining', 0)) <= 0:
                    reasons.append(
                        f"Meta diaria atingida: {coverage.get('sent_today', '?')} enviados de {targets[key]}."
                    )
                if int(coverage.get('eligible_pending', 0)) <= 0:
                    reasons.append('Nenhum contato pendente elegivel entre as agencias selecionadas.')
                if not reasons:
                    reasons.append('O preflight do bot nao liberou a campanha.')
                item['blocked_reason'] = ' '.join(reasons)
        except Exception as error:
            item['current_state'] = 'Indisponivel'
            item['daily_target'] = targets[key]
            item['daily_count_source'] = item['status_source']
            item['available'] = None
            item['sent_today'] = None
            item['queue_pending'] = None
            item['error'] = str(error)
        if preview:
            item['blocked_reason'] = 'Preview ativo; nenhuma acao de envio e permitida.'
        elif not selection_ready:
            item['blocked_reason'] = 'Selecione agencias da base canonica.'
        elif item.get('error'):
            item['blocked_reason'] = str(item['error'])
        elif item.get('is_running'):
            item['blocked_reason'] = 'Bot ja esta executando.'
        elif not (item.get('bot_coverage') or {}).get('can_start'):
            coverage = item.get('bot_coverage') or {}
            reasons = []
            if int(coverage.get('daily_remaining', 0)) <= 0:
                reasons.append(
                    f"Meta diaria atingida: {coverage.get('sent_today', '?')} enviados de {targets[key]}."
                )
            if int(coverage.get('eligible_pending', 0)) <= 0:
                reasons.append('Nenhum contato pendente elegivel entre as agencias selecionadas.')
            item['blocked_reason'] = ' '.join(reasons) or 'O preflight do bot nao liberou a campanha.'
        else:
            item.pop('blocked_reason', None)
        instagram[str(port)] = item
    result = {'selection': {'count': len(selected_ids), 'source': 'base canônica agencias_publicidade.db',
                          'ready': selection_ready},
            'email': email, 'whatsapp': whatsapp, 'instagram': instagram,
            'schedule': config['schedule'], 'daily_targets': targets,
            'preview': preview}


    pending_by_port = {5050: set(), 5051: set()}
    for port, (handle_to_cnpj, coverage) in account_coverage.items():
        for row in coverage.get('handle_statuses', []):
            cnpj = handle_to_cnpj.get(str(row.get('handle') or '').lower())
            if not cnpj:
                continue
            if row.get('reason') == 'pending_eligible':
                pending_by_port[port].add(cnpj)
    # The two bot databases represent separate sending accounts. A history,
    # reservation, or uncertain result in one account must not suppress the other.
    capacity = {
        port: max(0, min(
            int((instagram[str(port)].get('bot_coverage') or {}).get('allowed_session_limit') or 0),
            int((instagram[str(port)].get('bot_coverage') or {}).get('eligible_pending') or 0),
        )) if not instagram[str(port)].get('is_running') else 0 for port in (5050, 5051)
    }
    weights = {5050: max(1, int(targets['instagram_5050'])),
               5051: max(1, int(targets['instagram_5051']))}
    assigned = _independent_instagram_assignments(pending_by_port, capacity)
    shared_pending = pending_by_port[5050] & pending_by_port[5051]
    for port in (5050, 5051):
        item = instagram[str(port)]
        coverage = item.get('bot_coverage') or {}
        item['attempt_count'] = len(assigned[port])
        item['cross_account'] = {
            'shared_pending_total': len(shared_pending),
            'cross_account_suppression': 0,
            'assigned_to_this_account': len(assigned[port]),
            'unassigned_due_to_daily_capacity': max(0, len(pending_by_port[port]) - len(assigned[port])),
        }
        if include_internal:
            item['_startable_agency_ids'] = sorted(assigned[port])
        if item.get('bot_coverage'):
            item['can_start'] = bool(not preview and item.get('can_start') and item['attempt_count'] > 0)
            if not item['attempt_count'] and coverage.get('eligible_pending'):
                item['blocked_reason'] = 'Nenhum contato elegivel restante dentro da meta desta conta.'
        item['assigned_daily_share'] = {
            'configured_target': weights[port],
            'bot_daily_cap': item.get('bot_daily_cap'),
            'effective_daily_target': item.get('effective_daily_target'),
            'effective_daily_remaining': item.get('effective_daily_remaining'),
            'attempt_count': item['attempt_count'],
        }
    if not include_internal:
        for item in instagram.values():
            item.pop('_startable_agency_ids', None)
    return result


def _preflight_test_batch(limits, preview=False):
    if preview:
        raise RuntimeError('A prévia está em modo de leitura; reinicie o Revenue OS em modo operacional.')
    config = load_campaign_config()
    targets = config['daily_targets']
    limits = {key: int(limits.get(key, config['test_limits'][key])) for key in config['test_limits']}
    maxima = {'email': 30, 'whatsapp': 24, 'instagram_5050': 50, 'instagram_5051': 15}
    for key, value in limits.items():
        if not 1 <= value <= maxima[key]:
            raise ValueError(f'Quantidade inválida para {key}.')
    if limits['email'] > 0:
        raise RuntimeError('Email permanece bloqueado ate auditar o motor real.')
    email = email_runner.preflight(targets['email'])
    if email['active'] or email['eligible'] < limits['email'] or email['daily_remaining'] < limits['email']:
        raise RuntimeError(f"E-mail não passou no pré-voo: elegíveis={email['eligible']}, meta restante={email['daily_remaining']}.")
    wa_remaining = max(0, targets['whatsapp'] - wa_runner.sent_today())
    if (not wa_runner.engine.get_active_session() or wa_remaining < limits['whatsapp']
            or len(wa_runner.engine.get_next_batch(limits['whatsapp'])) < limits['whatsapp']):
        raise RuntimeError('WhatsApp não passou no pré-voo: verifique sessão, meta e fila elegível.')
    ig_status = {}
    for port, key in ((5050, 'instagram_5050'), (5051, 'instagram_5051')):
        status = _local_json(port)
        sent = int((status.get('kpi') or {}).get('enviados_hoje') or 0)
        if status.get('is_running') or targets[key] - sent < limits[key]:
            raise RuntimeError(f'Instagram {port} não passou no pré-voo: verifique estado e meta diária.')
        templates = _local_json(port, '/api/templates')
        if not templates.get('sucesso') or 'custom_1792432503883' not in (templates.get('templates') or {}):
            raise RuntimeError(f'Instagram {port} template comercial indisponivel.')
        ig_status[port] = {'status': status, 'sent': sent, 'limit': limits[key], 'target': targets[key]}
    return config, limits, ig_status


def _launch_test_batch(limits, preview=False):
    config, limits, ig_status = _preflight_test_batch(limits, preview)
    targets, delays = config['daily_targets'], config['delays']
    jobs = {}
    with concurrent.futures.ThreadPoolExecutor(max_workers=4) as pool:
        futures = {
            'email': pool.submit(_start_email, limits['email'], targets['email'], config),
            'whatsapp': pool.submit(_start_whatsapp, limits['whatsapp'], targets['whatsapp'], config),
            '5050': pool.submit(_start_instagram, 5050, limits['instagram_5050'], targets['instagram_5050'], config),
            '5051': pool.submit(_start_instagram, 5051, limits['instagram_5051'], targets['instagram_5051'], config),
        }
        for key, future in futures.items():
            jobs[key] = future.result()
    return {'status': 'iniciado', 'message': 'Lote de teste iniciado com os limites configurados.',
            'email': jobs['email'], 'whatsapp': jobs['whatsapp'],
            'instagram': {'5050': jobs['5050'], '5051': jobs['5051']}}


def _read_run_status(path):
    try:
        return json.loads(path.read_text(encoding='utf-8'))
    except (OSError, ValueError, TypeError):
        return {'state': 'idle'}


def _read_schedule_state():
    try:
        state = json.loads(SCHEDULE_STATE_PATH.read_text(encoding='utf-8'))
        return state if isinstance(state, dict) else {}
    except (OSError, ValueError, TypeError):
        return {}


def _write_schedule_state(state):
    temp = SCHEDULE_STATE_PATH.with_suffix('.tmp')
    temp.write_text(json.dumps(state, ensure_ascii=False, indent=2), encoding='utf-8')
    temp.replace(SCHEDULE_STATE_PATH)


def _schedule_window_open(now):
    local = sao_paulo_local(now)
    return local.weekday() < 5 and 8 <= local.hour < 18


def _retryable_ig_prestart_block(event, reason):
    """Recognize only known pre-start IG guards; never retry a possibly sent run."""
    if event not in {'instagram_5050', 'instagram_5051'}:
        return False
    normalized = unicodedata.normalize('NFKD', str(reason or '').lower())
    normalized = ''.join(char for char in normalized if not unicodedata.combining(char))
    safe_gate_phrases = (
        'runtime sem protecao contra interferencia durante a digitacao',
        'runtime nao confirma o destinatario antes do enter',
        'runtime_profile nao corresponde a conta configurada',
        'identidade nao liberada',
        'exact handle protocol unavailable',
    )
    return any(phrase in normalized for phrase in safe_gate_phrases)


def _dispatch_scheduled(event, config):
    targets = config['daily_targets']
    if event == 'email':
        try:
            sent = email_runner.sent_or_reserved_today()
        except Exception as error:
            return {'status': 'deferred', 'reason': f'Email count unavailable: {type(error).__name__}'}
        remaining = max(0, targets['email'] - sent)
        return _start_email(remaining, targets['email'], config, scheduled=True) if remaining else {'status': 'meta_alcancada'}
    if event == 'whatsapp':
        try:
            sent = wa_runner.sent_today()
        except Exception as error:
            return {'status': 'deferred', 'reason': f'WhatsApp count unavailable: {type(error).__name__}'}
        remaining = max(0, targets['whatsapp'] - sent)
        return _start_whatsapp(remaining, targets['whatsapp'], config, scheduled=True) if remaining else {'status': 'meta_alcancada'}
    if event not in ('instagram_5050', 'instagram_5051'):
        raise ValueError('Unknown scheduled channel.')
    port = int(event.rsplit('_', 1)[-1])
    key = event
    try:
        _instagram_runtime_contract(port)
    except (OSError, RuntimeError, ValueError) as error:
        return {'status': 'blocked', 'reason': str(error)[:240]}
    try:
        _ensure_bot_service(port)
        status = _local_json(port)
        raw_sent = (status.get('kpi') or {}).get('enviados_hoje')
        if raw_sent is None:
            raise RuntimeError('Daily count unavailable.')
    except Exception as error:
        return {'status': 'deferred', 'reason': f'Instagram {port} count unavailable: {type(error).__name__}'}
    sent = int(raw_sent)
    remaining = max(0, targets[key] - sent)
    if not remaining:
        return {'status': 'meta_alcancada'}
    return _start_instagram(port, remaining, targets[key], config, scheduled=True)


def _close_scheduled_runs(state, now):
    """Cooperatively stop only campaigns this scheduler started, at 18:00."""
    changed = False
    for day_key, record in list(state.items()):
        if not isinstance(record, dict) or record.get('state') != 'started':
            continue
        try:
            run_day, event = day_key.split(':', 1)
            run_date = datetime.strptime(run_day, '%Y-%m-%d').date()
        except (ValueError, AttributeError):
            continue
        if run_date >= now.date() and now.hour < 18:
            continue
        try:
            owned_pid = (record.get('result') or {}).get('pid')
            if event == 'email':
                run_status = _read_run_status(DATA_DIR / 'email_status.json')
                if (owned_pid and run_status.get('state') == 'running'
                        and str(run_status.get('pid')) == str(owned_pid)):
                    email_runner.STOP_FILE.write_text('STOP', encoding='utf-8')
            elif event == 'whatsapp':
                lock = Path(wa_runner.LOCK)
                lock_pid = lock.read_text(encoding='utf-8').split(' ', 1)[0] if lock.exists() else None
                if owned_pid and lock_pid == str(owned_pid):
                    lock.write_text('STOP', encoding='utf-8')
            elif event.startswith('instagram'):
                # The bot's own scheduled-window guard stops its automatic
                # worker. Do not stop a later manual session on the same port.
                pass
            record['state'] = 'closed'
            record['closed_at'] = now.isoformat(timespec='seconds')
            changed = True
        except (OSError, RuntimeError, ValueError, urllib.error.URLError) as error:
            record['close_error'] = str(error)[:240]
            changed = True
    if changed:
        _write_schedule_state(state)


def _scheduler_tick(now, config=None, dispatch_fn=None, clock_fn=None):
    """One deterministic schedule pass; a recorded start is never replayed."""
    if now.tzinfo is None:
        raise ValueError('Scheduled clock must include a timezone.')
    now = sao_paulo_local(now)
    config = config or load_campaign_config()
    dispatch_fn = dispatch_fn or _dispatch_scheduled
    clock_fn = clock_fn or sao_paulo_now
    schedule = config['schedule']
    state = _read_schedule_state()
    _close_scheduled_runs(state, now)
    if not (schedule['enabled'] and _schedule_window_open(now)
            and now.weekday() in schedule['weekdays']):
        return
    state = _read_schedule_state()
    windows = {'email': 599, 'whatsapp': 597,
               'instagram_5050': 594, 'instagram_5051': 594}
    for event in ('email', 'whatsapp', 'instagram_5050', 'instagram_5051'):
        day_key = now.strftime('%Y-%m-%d') + ':' + event
        legacy_official_key = now.strftime('%Y-%m-%d') + ':instagram'
        if event == 'instagram_5051' and legacy_official_key in state:
            continue
        previous = state.get(day_key)
        retryable_blocked = bool(
            previous and previous.get('state') == 'blocked'
            and _retryable_ig_prestart_block(event, previous.get('reason'))
        )
        if previous and previous.get('state') != 'deferred' and not retryable_blocked:
            continue
        if previous and (int(previous.get('attempts', 0)) >= 3
                         or now.isoformat() < str(previous.get('retry_after') or '')):
            continue
        try:
            time_key = 'instagram' if event.startswith('instagram_') else event
            scheduled_at = sao_paulo_at(now.date(), schedule['times'][time_key])
        except (TypeError, ValueError, KeyError):
            continue
        if not (scheduled_at <= now <= scheduled_at + timedelta(minutes=windows[event])):
            continue
        if not _schedule_window_open(clock_fn()):
            return
        attempts = int((previous or {}).get('attempts', 0)) + 1
        state[day_key] = {'state': 'starting', 'at': now.isoformat(timespec='seconds'),
                          'attempts': attempts}
        _write_schedule_state(state)
        try:
            result = dispatch_fn(event, config)
            if isinstance(result, dict) and result.get('status') == 'deferred':
                state[day_key] = {'state': 'deferred', 'at': now.isoformat(timespec='seconds'),
                                  'attempts': attempts, 'reason': str(result.get('reason') or '')[:200],
                                  'retry_after': (now + timedelta(minutes=1)).isoformat()}
            elif isinstance(result, dict) and result.get('status') == 'blocked':
                reason = str(result.get('reason') or '')[:240]
                retryable = _retryable_ig_prestart_block(event, reason)
                state[day_key] = {'state': 'blocked', 'at': now.isoformat(timespec='seconds'),
                                  'attempts': attempts, 'reason': reason,
                                  'retryable_prestart': retryable,
                                  **({'retry_after': (now + timedelta(minutes=1)).isoformat()}
                                     if retryable else {})}
            else:
                state[day_key] = {'state': 'started', 'at': now.isoformat(timespec='seconds'),
                                  'attempts': attempts, 'result': result}
        except Exception as error:
            # The process may have started before a timeout/error was observed.
            # Never retry such an uncertain launch automatically.
            state[day_key] = {'state': 'uncertain', 'at': now.isoformat(timespec='seconds'),
                              'attempts': attempts, 'error': str(error)[:300]}
        _write_schedule_state(state)


def _scheduler_loop():
    # Catch up after a local restart while respecting the Sao Paulo cutoff.
    while True:
        try:
            _scheduler_tick(sao_paulo_now())
        except Exception as error:
            print(f'SCHEDULE_TICK_ERROR {type(error).__name__}: {error}', flush=True)
        time.sleep(20)


def preflight(preview=True):
    return _campaign_preflight(preview)


def agency_detail(cnpj):
    row = next((item for item in agency_rows() if item.get('cnpj') == cnpj), None)
    if not row:
        return None
    official = legacy.get_agencia_detalhe(cnpj)
    if official:
        return official
    # Sinapro-only: o CRM pode registrar notas/tarefas pelo CNPJ, mesmo sem
    # ficha na tabela governamental. Nunca inventar contratos ou aprovação.
    connection = read_connection(DB_PATH)
    try:
        deal_row = connection.execute('SELECT * FROM crm_deals WHERE cnpj=?', (cnpj,)).fetchone()
        tasks = connection.execute('SELECT * FROM crm_tasks WHERE cnpj=? ORDER BY prazo ASC', (cnpj,)).fetchall()
        history = connection.execute('SELECT * FROM crm_history WHERE cnpj=? ORDER BY criado_em DESC', (cnpj,)).fetchall()
        proposals = connection.execute('SELECT * FROM crm_proposals WHERE cnpj=? ORDER BY criado_em DESC', (cnpj,)).fetchall()
    finally:
        connection.close()
    agency = {
        'cnpj': cnpj, 'nome_fantasia': row['nome'], 'razao_social': row['nome'],
        'municipio': row['municipio'], 'uf': row['uf'],
        'dono_nome': row['dono'], 'website_oficial': row['site'],
        'email': row['email'], 'instagram_handle': row['instagram'],
        'telefone': row['telefone'], 'fonte': row['source'],
    }
    return {'agencia': agency, 'contratos': {'total_count': 0, 'itens': []},
            'contatos_enriquecidos': [], 'deal': dict(deal_row) if deal_row else {'etapa': 'pesquisa'},
            'tasks': [dict(item) for item in tasks], 'history': [dict(item) for item in history],
            'proposals': [dict(item) for item in proposals], 'sinapro_only': True}


def agency_detail_by_id(identifier):
    if re.fullmatch(r'\d{14}', str(identifier or '')):
        return agency_detail(identifier)
    row = next((item for item in agency_rows() if item.get('agency_id') == identifier), None)
    if not row:
        return None
    cnpj = re.sub(r'\D', '', str(row.get('cnpj') or ''))
    if re.fullmatch(r'\d{14}', cnpj):
        return agency_detail(cnpj)
    agency = {
        'cnpj': '', 'agency_id': row['agency_id'], 'nome_fantasia': row.get('nome') or '',
        'razao_social': row.get('nome') or '', 'municipio': row.get('municipio') or '',
        'uf': row.get('uf') or '', 'dono_nome': row.get('dono') or '',
        'website_oficial': row.get('site') or '', 'email': row.get('email') or '',
        'instagram_handle': row.get('instagram') or '', 'telefone': row.get('telefone') or '',
        'fonte': row.get('source') or 'sinapro',
    }
    return {'agency_id': row['agency_id'], 'agencia': agency, 'contratos': {'total_count': 0, 'itens': []},
            'contatos_enriquecidos': [], 'deal': {}, 'tasks': [], 'history': [], 'proposals': [],
            'sinapro_only': True, 'crm_actions_available': False,
            'action_block_reason': 'Registro sem CNPJ: ficha exibida para revisão; ações CRM e disparos exigem conciliação.'}


class CRMHandler(legacy.RevenueOSHandler, SimpleHTTPRequestHandler):
    preview = True

    def __init__(self, *args, **kwargs):
        self.directory = str(HERE)
        super().__init__(*args, **kwargs)

    def end_headers(self):
        self.send_header('Cache-Control', 'no-store')
        super().end_headers()

    def do_GET(self):
        parsed = urlparse(self.path)
        path = parsed.path.rstrip('/') or '/'
        params = parse_qs(parsed.query)
        if path == '/api/local/health':
            self.send_json({'ok': True, 'preview': self.preview, 'legacy': str(LEGACY_PATH)})
            return
        if path == '/api/local/summary':
            self.send_json(summary())
            return
        if path == '/api/local/prospecting-summary':
            self.send_json(prospecting_summary())
            return
        if path == '/api/local/agency-selection':
            selection = load_agency_selection()
            try:
                selected_ids = sorted(selected_agency_ids(required=False))
            except RuntimeError:
                selected_ids = []
            selected_cnpjs = sorted(value for value in selected_ids if re.fullmatch(r'\d{14}', value))
            selected_rows = [row for row in agency_rows() if row.get('agency_id') in selected_ids]
            sinapro_row_ids = sorted({int(value) for row in selected_rows
                                      if not re.fullmatch(r'\d{14}', str(row.get('cnpj') or ''))
                                      for value in row.get('sinapro_ids', [row.get('sinapro_id')])
                                      if value is not None and str(value).isdigit()})
            selection = {**selection, 'agency_ids': selected_ids, 'cnpjs': selected_cnpjs,
                         'sinapro_row_ids': sinapro_row_ids,
                         'excluded_non_cnpj': max(0, len(selection.get('agency_ids', selection.get('cnpjs', []))) - len(selected_ids))}
            self.send_json(selection)
            return
        if path == '/api/local/preflight':
            self.send_json(preflight(self.preview))
            return
        if path == '/api/local/campaign-config':
            self.send_json(load_campaign_config())
            return
        if path == '/api/local/messages':
            try:
                self.send_json({'status': 'ok', 'messages': load_channel_messages()})
            except (RuntimeError, ValueError, OSError) as error:
                self.send_json({'status': 'blocked', 'message': str(error)}, 503)
            return
        if path == '/api/local/messages/email-preview':
            try:
                company = (params.get('empresa') or ['Agência Exemplo'])[0][:160]
                snapshot = email_model.load_snapshot(MESSAGES_PATH)
                rendered = email_runner.render_model_email(snapshot, company)
                self.send_json({'status': 'ok', **rendered,
                                'variant_groups': email_model.variant_groups(snapshot),
                                'notice': 'Prévia apenas; nenhum e-mail enviado.'})
            except (RuntimeError, ValueError, OSError) as error:
                self.send_json({'status': 'blocked', 'message': str(error)}, 503)
            return
        if path == '/api/local/dispatch-logs':
            try:
                self.send_json(_dispatch_logs_response(params))
            except ValueError as error:
                self.send_json({'status': 'blocked', 'message': str(error)}, 400)
            except Exception as error:
                self.send_json({'status': 'error', 'message':
                                f'Could not read dispatch logs: {type(error).__name__}'}, 503)
            return
        if path == '/api/local/campaign-status':
            email_run = _read_run_status(DATA_DIR / 'email_status.json')
            try:
                preflight = _campaign_preflight(self.preview)
            except Exception as error:
                self.send_json({'status': 'unavailable', 'error': type(error).__name__,
                                'message': 'Preflight indisponivel; inicio de campanhas bloqueado.',
                                'email_run': email_run}, 503)
                return
            self.send_json({'preflight': preflight, 'email_run': email_run})
            return
        if path == '/api/local/priorities':
            self.send_json(get_replies())
            return
        if path == '/api/local/priority-deals':
            self.send_json(get_priority_deals())
            return
        if path == '/api/local/anti-no-show/status':
            try:
                sys.path.insert(0, '/mnt/d/site-iarandu/automacao_rotinas_iarandu')
                from anti_no_show_engine import AntiNoShowEngine
                _anti_engine = AntiNoShowEngine()
                self.send_json({'status': 'ok', 'cadencias': _anti_engine.obter_status_cadencias()})
            except Exception as error:
                self.send_json({'status': 'error', 'message': str(error)}, 500)
            return
        if path.startswith('/api/local/agency/'):
            identifier = unquote(path.rsplit('/', 1)[-1])
            detail = agency_detail_by_id(identifier)
            self.send_json(detail if detail else {'error': 'Agência não encontrada'}, 200 if detail else 404)
            return
        if path == '/api/local/agencies':
            q = (params.get('q') or [''])[0].strip().casefold()
            uf = (params.get('uf') or [''])[0].strip().upper()
            channel = (params.get('channel') or [''])[0].strip().lower()
            try:
                limit = min(max(int((params.get('limit') or ['50'])[0]), 1), 100)
                offset = max(int((params.get('offset') or ['0'])[0]), 0)
            except ValueError:
                self.send_json({'error': 'Paginação inválida'}, 400)
                return
            canonical_rows = agency_rows()
            metadata = canonical_base_metadata(canonical_rows)
            rows = filter_agency_rows(q, uf, channel, source_rows=canonical_rows)
            agency_ids = sorted({row['agency_id'] for row in rows if row.get('agency_id')})
            cnpjs = sorted({str(row.get('cnpj') or '') for row in rows
                            if re.fullmatch(r'\d{14}', str(row.get('cnpj') or ''))})
            self.send_json({'total': len(rows), **metadata,
                            'cnpj_count': len(cnpjs), 'cnpjless_count': len(rows) - len(cnpjs),
                            'selectable_agencies': len(agency_ids),
                            'selectable_agency_ids': len(agency_ids),
                            'selectable_cnpjs': len(cnpjs),
                            'offset': offset, 'limit': limit, 'items': rows[offset:offset+limit]})
            return
        if path == '/api/local/agency-selection-candidates':
            q = (params.get('q') or [''])[0].strip()
            uf = (params.get('uf') or [''])[0].strip().upper()
            channel = (params.get('channel') or [''])[0].strip().lower()
            rows = filter_agency_rows(q, uf, channel)
            agency_ids = sorted({row['agency_id'] for row in rows if row.get('agency_id')})
            cnpjs = sorted({str(row.get('cnpj') or '') for row in rows
                            if re.fullmatch(r'\d{14}', str(row.get('cnpj') or ''))})
            self.send_json({'total': len(rows), 'selectable_agencies': len(agency_ids),
                            'selectable_agency_ids': len(agency_ids), 'agency_ids': agency_ids,
                            'selectable_cnpjs': len(cnpjs), 'cnpjs': cnpjs,
                            'filters': {'q': q, 'uf': uf, 'channel': channel}})
            return
        if path == '/legado':
            self.send_html(legacy.HTML_TEMPLATE)
            return
        if path in VALID_ROUTES:
            self._send_file(HERE / 'index.html')
            return
        if path in {'/iarandu.css','/iarandu.js','/crm-local.js','/crm-local.css','/favicon.svg'} or path.startswith('/scene/'):
            file_path = (HERE / path.lstrip('/')).resolve()
            if os.path.commonpath((str(HERE), str(file_path))) != str(HERE) or not file_path.is_file():
                self.send_error(404)
                return
            self._send_file(file_path)
            return
        if path.startswith('/api/'):
            return super().do_GET()
        self.send_error(404)

    def _send_file(self, path):
        size = path.stat().st_size
        content_type = mimetypes.guess_type(str(path))[0] or 'application/octet-stream'
        range_header = self.headers.get('Range')
        matched = re.fullmatch(r'bytes=(\d+)-(\d*)', range_header or '')
        start, end = 0, size - 1
        if matched:
            start = int(matched.group(1))
            end = int(matched.group(2)) if matched.group(2) else size - 1
            if start >= size or start > end:
                self.send_error(416)
                return
            end = min(end, size - 1)
        length = end - start + 1
        self.send_response(206 if matched else 200)
        self.send_header('Content-Type', content_type)
        self.send_header('Content-Length', str(length))
        self.send_header('Accept-Ranges', 'bytes')
        if matched:
            self.send_header('Content-Range', f'bytes {start}-{end}/{size}')
        self.end_headers()
        with path.open('rb') as stream:
            stream.seek(start)
            remaining = length
            while remaining:
                block = stream.read(min(65536, remaining))
                if not block:
                    break
                self.wfile.write(block)
                remaining -= len(block)

    def do_POST(self):
        path = urlparse(self.path).path
        safe_preview_posts = {'/api/local/campaign-config', '/api/local/agency-selection',
                              '/api/local/messages/email-preview'}
        if self.preview and path not in safe_preview_posts:
            self.send_json({'status': 'preview', 'message': 'Prévia somente leitura: nenhuma ação foi executada.'}, 403)
            return
        path = urlparse(self.path).path
        managed_posts = {'/api/local/campaign-config', '/api/local/agency-selection', '/api/local/messages',
                         '/api/local/messages/email-preview', '/api/action/start-test-batch',
                         '/api/action/start-email', '/api/action/start-whatsapp',
                         '/api/action/start-instagram', '/api/action/start-instagram-oficial',
                         '/api/action/open-login-firefox'}
        if path in managed_posts:
            try:
                length = min(max(int(self.headers.get('Content-Length', '0')), 0), 20000)
                payload = json.loads(self.rfile.read(length).decode('utf-8')) if length else {}
                if not isinstance(payload, dict):
                    raise ValueError('Corpo JSON precisa ser um objeto.')
                config = load_campaign_config()
                targets = config['daily_targets']
                if path == '/api/local/messages/email-preview':
                    draft = payload.get('messages', payload)
                    snapshot = email_model.snapshot_from_document(draft)
                    company = str(payload.get('empresa') or 'Agência Exemplo')[:160]
                    rendered = email_runner.render_model_email(snapshot, company)
                    self.send_json({'status': 'ok', **rendered,
                                    'variant_groups': email_model.variant_groups(snapshot),
                                    'notice': 'Prévia apenas; modelo não foi salvo nem enviado.'})
                    return
                if path == '/api/local/campaign-config':
                    self.send_json({'status': 'salvo', 'config': save_campaign_config(payload)})
                    return
                if path == '/api/local/messages':
                    saved = save_channel_messages(payload)
                    active_email = _read_run_status(DATA_DIR / 'email_status.json').get('state') == 'running'
                    self.send_json({'status': 'salvo', 'messages': saved,
                                    'message': ('Modelos salvos; o lote de e-mail em curso usa o snapshot anterior. '
                                                'Este modelo valerá no próximo lote.' if active_email else
                                                'Modelos salvos para os próximos envios. Nenhum envio foi iniciado.')})
                    return
                if path == '/api/local/agency-selection':
                    self.send_json({'status': 'salvo', 'selection': save_agency_selection(payload)})
                    return
                if path == '/api/action/start-test-batch':
                    self.send_json({'status': 'blocked', 'message': 'Lote de teste removido; configure uma meta diaria por canal.'}, 409)
                    return
                if path == '/api/action/open-login-firefox':
                    login = _open_login_firefox(payload)
                    self.send_json({'status': login['status'], 'port': login['port'],
                                    'message': f"Firefox solicitado para a conta do bot {login['port']}."}, 202)
                    return
                if path == '/api/action/start-email':
                    check = _email_campaign_preflight(targets['email'], preview=self.preview)
                    if not check['can_start']:
                        self.send_json({'status': 'blocked', 'message': check['blocked_reason'], 'preflight': check}, 409)
                        return
                    started = _start_email(check['limit'], targets['email'], config)
                    self.send_json({'status': 'iniciado', 'message': 'Campanha iniciada; status mede aceite SMTP, nao entrega.',
                                    'preflight': check, **started}, 202)
                    return
                if path == '/api/action/start-whatsapp':
                    selected = selected_agency_ids()
                    eligible = len(selected_whatsapp_candidates(2000, selected_ids=selected))
                    budget = _daily_remaining_budget(targets['whatsapp'], wa_runner.sent_today(), eligible)
                    limit = budget['limit']
                    if limit < 1:
                        raise RuntimeError(f"WhatsApp sem orcamento elegivel: restante={budget['daily_remaining']}, elegiveis={eligible}.")
                    self.send_json({'status': 'iniciado', **_start_whatsapp(limit, targets['whatsapp'], config)}, 202)
                    return
                if path in {'/api/action/start-instagram', '/api/action/start-instagram-oficial'}:
                    port, key = ((5050, 'instagram_5050') if path == '/api/action/start-instagram' else (5051, 'instagram_5051'))
                    if not IG_CAMPAIGN_START_LOCK.acquire(blocking=False):
                        raise RuntimeError('Ja existe uma decisao de inicio Instagram em andamento.')
                    try:
                        paired = _campaign_preflight(preview=False, include_internal=True)
                        item = paired['instagram'][str(port)]
                        selected = item.get('_startable_agency_ids') or []
                        limit = int(item.get('attempt_count') or 0)
                        if not item.get('can_start') or not selected or limit < 1:
                            raise RuntimeError(f"Instagram {port} preflight bloqueou: tentativa={limit}; {item.get('blocked_reason', '')}")
                        result = _start_instagram(port, limit, targets[key], config, selected_ids=selected)
                    finally:
                        IG_CAMPAIGN_START_LOCK.release()
                    self.send_json({'status': 'iniciado', **result, 'coverage': result['coverage'],
                                    'allowed_session_limit': result['allowed_session_limit']}, 202)
                    return
            except (ValueError, TypeError, KeyError, RuntimeError, OSError, subprocess.SubprocessError, urllib.error.URLError) as error:
                self.send_json({'status': 'blocked', 'message': str(error)}, 409)
                return
        if path in {'/api/action/stop-instagram', '/api/action/stop-instagram-oficial', '/api/action/stop-instagram-both'}:
            ports = [5050, 5051] if path.endswith('both') else ([5050] if path == '/api/action/stop-instagram' else [5051])
            try:
                result = {str(port): _local_json(port, '/api/bot/stop', 'POST', {}) for port in ports}
                self.send_json({'status': 'parando', 'instagram': result})
            except (OSError, ValueError, urllib.error.URLError) as error:
                self.send_json({'status': 'error', 'message': str(error)}, 502)
            return
        if path == '/api/action/stop-email':
            state = _read_run_status(DATA_DIR / 'email_status.json')
            if state.get('state') != 'running':
                self.send_json({'status': 'parado', 'message': 'Não há lote de e-mail ativo.'})
                return
            email_runner.STOP_FILE.write_text('STOP', encoding='utf-8')
            self.send_json({'status': 'parando', 'message': 'Parada solicitada; o envio atual termina antes do próximo.'})
            return
        # Rota legada WhatsApp removida (código morto): o fluxo seguro em
        # managed_posts já trata POST /api/action/start-whatsapp com validação
        # de orçamento e seleção manual. — Claude Opus 4.6, 25/10/2026.
        if path == '/api/action/stop-whatsapp':
            lock = Path('/mnt/d/site-iarandu/whatsapp_dispatch.lock')
            if not lock.exists():
                self.send_json({'status': 'parado', 'message': 'Não há lote WhatsApp ativo.'})
                return
            lock.write_text('STOP', encoding='utf-8')
            self.send_json({'status': 'parando', 'message': 'Parada solicitada; o envio em curso termina antes de pausar o próximo.'})
            return
        if path == '/api/local/replies/confirm':
            try:
                length = int(self.headers.get('Content-Length', '0'))
                payload = json.loads(self.rfile.read(length).decode('utf-8'))
            except (ValueError, UnicodeDecodeError, json.JSONDecodeError):
                self.send_json({'error': 'Dados inválidos'}, 400)
                return
            cnpj = str(payload.get('cnpj') or '')
            channel = str(payload.get('canal') or '').lower()
            evidence = str(payload.get('evidencia') or '').strip()
            received_at = str(payload.get('recebido_em') or '').strip()
            next_step = str(payload.get('proximo_passo') or '').strip()
            if (not re.fullmatch(r'\d{14}', cnpj) or not agency_detail(cnpj) or
                    channel not in {'instagram','whatsapp','email','outro'} or
                    not 15 <= len(evidence) <= 1000 or len(next_step) > 500):
                self.send_json({'error': 'Confirme agência, canal e evidência (15–1000 caracteres).'}, 400)
                return
            try:
                datetime.fromisoformat(received_at)
            except ValueError:
                self.send_json({'error': 'Data da resposta inválida.'}, 400)
                return
            conn = sqlite3.connect(DB_PATH, timeout=5)
            try:
                metadata = json.dumps({'canal': channel, 'recebido_em': received_at}, ensure_ascii=False)
                existing = conn.execute('''SELECT 1 FROM crm_history WHERE cnpj=? AND tipo='resposta_confirmada' AND descricao=? LIMIT 1''',
                                        (cnpj, evidence)).fetchone()
                if existing:
                    self.send_json({'error': 'Esta evidência já foi registrada para a agência.'}, 409)
                    return
                now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                conn.execute('''INSERT INTO crm_history(cnpj,tipo,autor,descricao,metadata_json,criado_em)
                                VALUES(?,?,?,?,?,?)''', (cnpj,'resposta_confirmada','Operador',evidence,metadata,now))
                conn.execute('''INSERT INTO crm_deals(cnpj,etapa,responsavel,proximo_passo,criado_em,atualizado_em)
                                VALUES(?,'resposta','Franciele',?,?,?)
                                ON CONFLICT(cnpj) DO UPDATE SET etapa='resposta',
                                proximo_passo=excluded.proximo_passo,atualizado_em=excluded.atualizado_em''',
                             (cnpj,next_step,now,now))
                conn.commit()
            finally:
                conn.close()
            self.send_json({'success': True, 'message': 'Resposta confirmada com evidência no histórico.'})
            return
        return super().do_POST()


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--port', type=int, default=8090)
    parser.add_argument('--live', action='store_true', help='Habilita ações legadas; usar apenas na troca final para 8051')
    args = parser.parse_args()
    if args.live and args.port not in (8051, 8090):
        parser.error('--live requer a porta 8051 ou 8090')
    CRMHandler.preview = not args.live
    if args.live and args.port == 8090:
        threading.Thread(target=_scheduler_loop, name='revenue-os-campaign-scheduler', daemon=True).start()
    server = ThreadingHTTPServer(('127.0.0.1', args.port), CRMHandler)
    server.daemon_threads = True
    print(f'IArandú CRM visual em 127.0.0.1:{args.port}; preview={CRMHandler.preview}', flush=True)
    server.serve_forever()


if __name__ == '__main__':
    main()
