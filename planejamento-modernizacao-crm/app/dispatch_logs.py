"""Read-only, evidence-preserving dispatch log adapter.

No source is opened for writing. Missing sources are reported as partial rather
than silently counted as zero. A log is never evidence of recipient delivery
unless the source contains a delivery receipt.
"""

import json
import re
import sqlite3
from datetime import datetime
from pathlib import Path


STATUSES = {'queued', 'smtp_accepted', 'gateway_accepted', 'sent_unconfirmed',
            'delivery_confirmed', 'uncertain', 'errors', 'exclusions'}
CHANNELS = {'email', 'whatsapp', 'instagram_direct'}


def _rows(path, table):
    """Return rows from a single existing SQLite table without creating files."""
    path = Path(path)
    if not path.is_file():
        raise FileNotFoundError(str(path))
    connection = sqlite3.connect(path.as_uri() + '?mode=ro', uri=True, timeout=5)
    connection.row_factory = sqlite3.Row
    try:
        if not connection.execute("SELECT 1 FROM sqlite_master WHERE type='table' AND name=?", (table,)).fetchone():
            raise RuntimeError(f'{table} table missing in {path.name}')
        return [dict(row) for row in connection.execute(f'SELECT * FROM "{table}"')]
    finally:
        connection.close()


def _identity(value):
    value = str(value or '').strip()
    digits = re.sub(r'\D', '', value)
    if len(digits) == 14:
        return digits, 'cnpj'
    if re.fullmatch(r'sina:\d+', value):
        return value, 'sinapro'
    return None, None


def _timestamp(raw):
    raw = str(raw or '').strip()
    if not raw:
        return None, None, None
    try:
        parsed = datetime.fromisoformat(raw.replace('Z', '+00:00'))
    except ValueError:
        return raw, None, None
    if parsed.tzinfo is None:
        return raw, None, None
    return raw, parsed.isoformat(), 'persisted_offset'


def _payload(value):
    try:
        result = json.loads(value or '{}')
        return result if isinstance(result, dict) else {}
    except (TypeError, ValueError):
        return {}


def _status(channel, raw, detail='', receipt=None):
    raw = str(raw or '').lower().strip()
    detail = str(detail or '').lower()
    if raw in {'incerto', 'uncertain'} or ('timeout' in detail and raw in {'erro', 'error', 'falha'}):
        return 'uncertain'
    if raw in {'enviando', 'queued', 'pending'}:
        return 'queued'
    if raw in {'erro', 'error', 'falha', 'erro_envio', 'dm_fechada', 'perfil_inexistente'}:
        return 'errors'
    if raw in {'excluido', 'excluded', 'do_not_contact'}:
        return 'exclusions'
    if channel == 'email' and raw in {'enviado', 'sent', 'accepted', 'concluido'}:
        return 'smtp_accepted'
    if channel == 'whatsapp' and raw in {'enviado', 'sent', 'accepted'}:
        if isinstance(receipt, dict) and isinstance(receipt.get('ack'), int) and receipt['ack'] >= 2:
            return 'delivery_confirmed'
        return 'gateway_accepted' if receipt else 'uncertain'
    if channel == 'instagram_direct' and raw in {'enviado', 'sent', 'concluido'}:
        return 'sent_unconfirmed'
    return 'uncertain'


def _entry(source, source_id, key, agency_id, channel, account, instance,
           recipient, body, timestamp, raw_status, detail='', receipt=None,
           agency_name=None, proof='unknown'):
    lead_id, lead_type = _identity(agency_id)
    timestamp_raw, timestamp_tz, timezone_provenance = _timestamp(timestamp)
    canonical_status = _status(channel, raw_status, detail, receipt)
    event_key = str(key or source_id)
    return {
        'log_id': f'{source}:{source_id}', 'source_system': source,
        'source_record_id': str(source_id), 'source_event_key': event_key,
        'lead_id': lead_id, 'lead_id_type': lead_type, 'agency_name': agency_name,
        'channel': channel, 'sender_account': account,
        'sender_instance_id': instance, 'sender_proof_status': proof,
        'recipient_address': str(recipient).strip() if recipient else None,
        'body_status': 'presente' if body else 'desconhecido', 'body_text': body or None,
        'body_html': None, 'template_id': None,
        'timestamp_raw': timestamp_raw, 'timestamp_tz': timestamp_tz,
        'timezone_provenance': timezone_provenance,
        'status': canonical_status, 'status_raw': str(raw_status or ''),
        'reason_code': ('TIMEOUT_AFTER_ATTEMPT' if canonical_status == 'uncertain'
                        and 'timeout' in str(detail or '').lower() else
                        canonical_status.upper()),
        'reason_detail': str(detail or '')[:500],
        'delivery_receipt': receipt if canonical_status == 'delivery_confirmed' else None,
        'commercial_reply_detected': False,
        'commercial_reply_excerpt': None,
        'recipient_name': None,
    }


def collect(agency_db=None, central_db=None, instagram_dbs=None,
            handle_to_agency_id=None):
    """Collect independent source events; never infer cross-source replicas."""
    entries, warnings = [], []
    handle_to_agency_id = handle_to_agency_id or {}
    if agency_db is not None:
        try:
            for row in _rows(agency_db, 'disparos_email'):
                entries.append(_entry('agencias_publicidade.db:disparos_email', row['id'], row['id'],
                    row.get('cnpj'), 'email', None, 'email:agency_db',
                    row.get('email'), None, row.get('enviado_em'), row.get('status'),
                    row.get('ultimo_log'), agency_name=row.get('agencia_nome'), proof='desconhecido'))
        except (OSError, sqlite3.Error, RuntimeError) as error:
            warnings.append(f'email source unavailable: {type(error).__name__}')
        try:
            for row in _rows(agency_db, 'disparos_whatsapp'):
                payload = _payload(row.get('resposta_api'))
                message_id = payload.get('messageId') or payload.get('id')
                receipt = ({'message_id': message_id, 'ack': payload.get('ack')}
                           if message_id and isinstance(payload.get('ack'), int) else message_id)
                entries.append(_entry('agencias_publicidade.db:disparos_whatsapp', row['id'], row['id'],
                    row.get('cnpj'), 'whatsapp', None, 'openwa:2785',
                    row.get('whatsapp_id') or row.get('telefone'), row.get('mensagem'),
                    row.get('enviado_em'), row.get('status'), row.get('resposta_api'),
                    receipt=receipt, agency_name=row.get('nome'), proof='requer_prova_sessao'))
        except (OSError, sqlite3.Error, RuntimeError) as error:
            warnings.append(f'whatsapp source unavailable: {type(error).__name__}')
    if central_db is not None:
        try:
            for row in _rows(central_db, 'interactions'):
                if str(row.get('direction') or '').lower() != 'outbound':
                    continue
                channel = str(row.get('channel') or '').lower()
                if channel == 'instagram':
                    channel = 'instagram_direct'
                if channel not in CHANNELS:
                    continue
                payload = _payload(row.get('payload_json'))
                account = row.get('account_operator')
                if channel == 'instagram_direct':
                    # account_operator describes routing, not the authenticated
                    # browser identity at the historical time of the event.
                    account = None
                if channel == 'whatsapp':
                    account = None  # The session identity must be independently proven.
                if channel == 'email' and account not in {'contato@iarandu.com.br'}:
                    account = None
                operator = row.get('account_operator')
                instance = ('ig:5050' if operator == '@maldonadodiego55' else
                            'ig:5051' if operator == '@iarandu.br' else
                            'openwa:2785' if channel == 'whatsapp' else
                            'zoho:iarandu' if channel == 'email' else 'ig:unknown')
                message_id = payload.get('messageId') or payload.get('id') if channel == 'whatsapp' else None
                receipt = ({'message_id': message_id, 'ack': payload.get('ack')}
                           if message_id and isinstance(payload.get('ack'), int) else message_id)
                entries.append(_entry('central.db:interactions', row['id'],
                    row.get('source_event_key') or row['id'], row.get('cnpj'), channel,
                    account, instance, row.get('identity_value'), None,
                    row.get('event_ts'), row.get('status'), row.get('summary'),
                    receipt=receipt, proof='desconhecido' if channel != 'whatsapp' else 'requer_prova_sessao'))
        except (OSError, sqlite3.Error, RuntimeError) as error:
            warnings.append(f'central source unavailable: {type(error).__name__}')
    for port, path in (instagram_dbs or {}).items():
        if int(port) not in {5050, 5051}:
            continue
        try:
            account = '@maldonadodiego55' if int(port) == 5050 else '@iarandu.br'
            for row in _rows(path, 'candidatos'):
                handle = str(row.get('handle') or '').strip().lower().lstrip('@')
                agency_id = handle_to_agency_id.get(handle)
                sent_at = row.get('msg1_enviada_em')
                if sent_at:
                    later = any(row.get(f'msg{stage}_enviada_em') for stage in range(2, 8))
                    entries.append(_entry(f'ig:{port}:candidatos', f"{row['id']}:msg1",
                        f"{row['id']}:msg1", agency_id, 'instagram_direct', None,
                        f'ig:{port}', '@' + handle if handle else None,
                        None if later else row.get('conteudo_mensagem'), sent_at,
                        'enviado', agency_name=row.get('nome'), proof='desconhecido'))
                elif row.get('status_geral') in {'dm_fechada', 'erro_envio', 'perfil_inexistente'}:
                    entries.append(_entry(f'ig:{port}:candidatos', f"{row['id']}:error",
                        f"{row['id']}:error", agency_id, 'instagram_direct', None,
                        f'ig:{port}', '@' + handle if handle else None, None, None,
                        row.get('status_geral'), row.get('ultimo_erro'),
                        agency_name=row.get('nome'), proof='desconhecido'))
        except (OSError, sqlite3.Error, RuntimeError) as error:
            warnings.append(f'instagram {port} source unavailable: {type(error).__name__}')
    # A central event and a campaign-table event remain distinct unless a
    # separate, audited cross-source link proves they are the same dispatch.
    unique = {}
    for entry in entries:
        # An unknown sender and unknown instance cannot safely identify a
        # replica. Keep its physical source row separate.
        unknown_instance = entry['sender_account'] is None
        key = (entry['source_system'], entry['source_event_key'],
               entry['sender_instance_id'],
               entry['source_record_id'] if unknown_instance else None)
        unique.setdefault(key, entry)
    return list(unique.values()), warnings


def page(entries, warnings, filters=None):
    filters = filters or {}
    try:
        number = max(1, int(filters.get('page') or 1))
        limit = int(filters.get('limit') or 25)
    except (TypeError, ValueError):
        raise ValueError('Invalid pagination')
    if limit not in {25, 50, 100}:
        raise ValueError('Invalid page limit')
    channel, status = filters.get('channel'), filters.get('status')
    if channel and channel != 'all' and channel not in CHANNELS:
        raise ValueError('Invalid channel')
    if status and status != 'all' and status not in STATUSES:
        raise ValueError('Invalid status')
    account = filters.get('sender_account')
    instance = filters.get('sender_instance_id')
    search = str(filters.get('search') or '').casefold()
    start, end = filters.get('date_from'), filters.get('date_to')
    for value in (start, end):
        if value and not re.fullmatch(r'\d{4}-\d{2}-\d{2}', value):
            raise ValueError('Invalid date')
    selected = []
    for entry in entries:
        if channel and channel != 'all' and entry['channel'] != channel:
            continue
        if status and status != 'all' and entry['status'] != status:
            continue
        if account and entry['sender_account'] != account:
            continue
        if instance and entry['sender_instance_id'] != instance:
            continue
        if search and search not in ' '.join(str(entry.get(key) or '') for key in
            ('agency_name', 'lead_id', 'recipient_address', 'body_text')).casefold():
            continue
        day = str(entry['timestamp_raw'] or '')[:10]
        if (start or end) and (not day or (start and day < start) or (end and day > end)):
            continue
        selected.append(entry)
    selected.sort(key=lambda row: (row['timestamp_raw'] or '', row['log_id']), reverse=True)
    counts = {name: sum(row['status'] == name for row in selected) for name in STATUSES}
    by_channel = {name: sum(row['channel'] == name for row in selected) for name in CHANNELS}
    by_instance = {name: sum(row['sender_instance_id'] == name for row in selected)
                   for name in sorted({row['sender_instance_id'] for row in selected
                                       if row['sender_instance_id']})}
    total = len(selected)
    return {'status': 'partial' if warnings else 'ok', 'source_warnings': warnings,
            'pagination': {'current_page': number, 'limit': limit,
                           'total_records': total, 'total_pages': (total + limit - 1) // limit,
                           'has_next': number * limit < total, 'has_prev': number > 1},
            'summary': {'total_events': total, **counts,
                        'by_channel': by_channel, 'by_sender_instance': by_instance},
            'data': selected[(number - 1) * limit:number * limit]}
