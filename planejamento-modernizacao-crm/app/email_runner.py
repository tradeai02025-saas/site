#!/usr/bin/env python3
"""Bounded email dispatcher for the local Revenue OS.

Uses the approved fixed HTML frame and Zoho SMTP settings from the canonical
sender. Editable copy is snapshotted at batch start; recipients are reserved
before SMTP is contacted.
"""

import argparse
import fcntl
import hashlib
import importlib.util
import json
import os
import random
import re
import smtplib
import sqlite3
import sys
import time
import unicodedata
import uuid
import email_model
from datetime import datetime, timedelta
from schedule_clock import sao_paulo_local, sao_paulo_now
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from pathlib import Path
from contact_suppressions import load_suppressed_cnpis


HERE = Path(__file__).resolve().parent
DATA = HERE / 'data'
DATA.mkdir(parents=True, exist_ok=True)
AGENCY_DB = Path('/mnt/d/Eternity-Signals/COMERCIAL/AGENCIAS-PUB/data/agencias_publicidade.db')
CENTRAL_DB = Path('/mnt/c/Users/user/iarandu_crm_central/central.db')
LEGACY_SENDER = Path('/mnt/d/site-iarandu/disparar_emails_30.py')
LEGACY_LOCK = Path('/mnt/d/site-iarandu/last_email_dispatch_date.txt')
RUN_LOCK = Path('/mnt/d/site-iarandu/email_dispatch.lock')
STATUS_FILE = DATA / 'email_status.json'
MESSAGE_CONFIG = DATA / 'channel_messages.json'
STOP_FILE = DATA / 'email_stop.txt'
EXCLUDED_DOMAINS = {'gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com', 'icloud.com', 'live.com'}
RESERVATION_STALE_AFTER_SECONDS = 15 * 60
UNCERTAIN_REVIEW_LIMIT = 100

spec = importlib.util.spec_from_file_location('iarandu_approved_email', LEGACY_SENDER)
email_engine = importlib.util.module_from_spec(spec)
spec.loader.exec_module(email_engine)


def render_model_email(snapshot, company, choice=None):
    """The preview and dispatcher use the same fixed approved HTML frame."""
    return email_model.compose(snapshot, company, email_engine.render_standard_email, choice=choice)


def _write_effective_email(path, record):
    with path.open('a', encoding='utf-8') as stream:
        stream.write(json.dumps(record, ensure_ascii=False, sort_keys=True) + '\n')
        stream.flush()
        os.fsync(stream.fileno())


def _write_status(**values):
    current = {'updated_at': datetime.now().isoformat(timespec='seconds'), **values}
    temp = STATUS_FILE.with_suffix('.tmp')
    temp.write_text(json.dumps(current, ensure_ascii=False), encoding='utf-8')
    temp.replace(STATUS_FILE)


def _agency_connection(readonly=False):
    if readonly:
        conn = sqlite3.connect(f'file:{AGENCY_DB}?mode=ro', uri=True, timeout=10)
    else:
        conn = sqlite3.connect(AGENCY_DB, timeout=15)
    conn.row_factory = sqlite3.Row
    conn.execute('PRAGMA busy_timeout=10000')
    return conn


def _sinapro_agency_id(row):
    sinapro_id = row.get('sinapro_id')
    if sinapro_id is not None and str(sinapro_id).isdigit():
        return 'sina:' + str(int(sinapro_id))
    def slug(value):
        text = unicodedata.normalize('NFKD', str(value or ''))
        clean = ''.join(char for char in text if not unicodedata.combining(char)).lower()
        return re.sub(r'[^a-z0-9]', '', clean)
    raw_handle = str(row.get('instagram') or '').strip()
    if '://' in raw_handle:
        from urllib.parse import urlparse
        raw_handle = urlparse(raw_handle).path.strip('/').split('/', 1)[0]
    raw_handle = raw_handle[1:] if raw_handle.startswith('@') else raw_handle
    handle = raw_handle.strip().lower()
    handle = handle if re.fullmatch(r'[a-z0-9._]{1,30}', handle) else ''
    fingerprint = '|'.join((
        slug(row.get('nome')), str(row.get('uf') or 'XX').strip().upper(),
        slug(row.get('municipio')), slug(row.get('website')),
        str(row.get('email') or '').strip().casefold(), handle,
        re.sub(r'\D', '', str(row.get('telefone') or '')),
    ))
    return 'sina:' + hashlib.sha256(fingerprint.encode('utf-8')).hexdigest()


def _sql_cnpj_digits(column):
    """Normalize common CNPJ punctuation in SQLite without changing stored data."""
    return f"replace(replace(replace(replace(replace(trim(coalesce({column},'')),'.',''),'/',''),'-',''),' ',''),char(9),'')"


def eligible_emails(limit=None, selected_cnpjs=None, selected_agency_ids=None):
    """Return qualified, corporate, never-contacted Sinapro email records."""
    suppressed = load_suppressed_cnpis(AGENCY_DB, CENTRAL_DB)
    cnpj_s = _sql_cnpj_digits('s.cnpj')
    cnpj_a = _sql_cnpj_digits('a.cnpj')
    cnpj_x = _sql_cnpj_digits('x.cnpj')
    cnpj_d = _sql_cnpj_digits('d.cnpj')
    cnpj_i = _sql_cnpj_digits('i.cnpj')
    conn = _agency_connection(readonly=True)
    try:
        conn.execute('ATTACH DATABASE ? AS central', (f'file:{CENTRAL_DB}?mode=ro',))
        rows = conn.execute(f'''
            SELECT s.id AS sinapro_id, s.cnpj, s.nome, s.municipio, s.uf, s.email, s.dono_nome,
                   s.website, s.fonte, s.valor_contratos, s.instagram, s.telefone
            FROM agencias_sinapro_master s
            LEFT JOIN agencias a ON {cnpj_a}={cnpj_s}
            LEFT JOIN crm_exclusions x ON {cnpj_x}={cnpj_s} AND x.bloquear_disparos=1
            WHERE lower(coalesce(s.status,''))='qualificada'
              AND ((length({cnpj_s})=14 AND {cnpj_s} NOT GLOB '*[^0-9]*')
                   OR s.cnpj IS NULL OR trim(s.cnpj)='')
              AND s.email IS NOT NULL AND trim(s.email)<>''
              AND instr(trim(s.email),' ')=0
              AND lower(trim(s.email)) GLOB '*@*.*'
              AND lower(substr(trim(s.email), instr(trim(s.email),'@')+1)) NOT IN
                  ('gmail.com','hotmail.com','outlook.com','yahoo.com','icloud.com','live.com')
              AND x.id IS NULL
              AND (a.cnpj IS NULL OR coalesce(a.is_cliente,0)=0)
              AND NOT EXISTS (
                  SELECT 1 FROM agencias customer_by_email
                  WHERE coalesce(customer_by_email.is_cliente,0)=1
                    AND lower(trim(customer_by_email.email))=lower(trim(s.email))
              )
              AND NOT EXISTS (
                  SELECT 1 FROM central.identities ci
                  JOIN central.agencies ca ON ca.cnpj=ci.cnpj
                  WHERE lower(ci.channel)='email'
                    AND lower(trim(ci.value_norm))=lower(trim(s.email))
                    AND (coalesce(ca.is_customer,0)=1 OR coalesce(ca.do_not_contact,0)=1)
              )
              AND NOT EXISTS (
                  SELECT 1 FROM disparos_email d
                  WHERE {cnpj_d}={cnpj_s} OR lower(trim(d.email))=lower(trim(s.email))
              )
              AND NOT EXISTS (
                  SELECT 1 FROM central.interactions i
                  WHERE lower(i.channel)='email' AND lower(i.direction)='outbound'
                    AND ({cnpj_i}={cnpj_s} OR lower(trim(i.identity_value))=lower(trim(s.email)))
              )
            ORDER BY coalesce(s.valor_contratos,0) DESC, s.nome COLLATE NOCASE
        ''').fetchall()
    finally:
        conn.close()

    old_queue = Path('/mnt/c/Users/user/iarandu_crm_central/filas_amanha/fila_emails.json')
    already_queued = set()
    try:
        data = json.loads(old_queue.read_text(encoding='utf-8'))
        for item in data if isinstance(data, list) else []:
            email = str(item.get('email') or '').strip().lower()
            if email:
                already_queued.add(email)
    except (OSError, ValueError, TypeError):
        pass

    output, seen = [], set()
    if selected_agency_ids is not None:
        selected = {str(value).strip() for value in selected_agency_ids if str(value).strip()}
    elif selected_cnpjs is not None:
        selected = {re.sub(r'\D', '', str(value)) for value in selected_cnpjs}
    else:
        selected = None
    for row in rows:
        item = dict(row)
        normalized_cnpj = re.sub(r'\D', '', str(item.get('cnpj') or ''))
        if normalized_cnpj and normalized_cnpj in suppressed:
            continue
        agency_key = normalized_cnpj if re.fullmatch(r'\d{14}', normalized_cnpj) else _sinapro_agency_id(item)
        if selected is not None and agency_key not in selected:
            continue
        email = str(item.get('email') or '').strip()
        key = email.lower()
        if not re.fullmatch(r"[^\s@]+@[^\s@]+\.[^\s@]+", email):
            continue
        if key in seen or key in already_queued:
            continue
        seen.add(key)
        item['email'] = email
        item['nome'] = str(item.get('nome') or '').strip()
        item['dono_nome'] = str(item.get('dono_nome') or '').strip()
        item['agency_id'] = agency_key
        output.append(item)
        if limit and len(output) >= limit:
            break
    return output


def _parse_local_datetime(value):
    if not value:
        return None
    try:
        parsed = datetime.fromisoformat(str(value).replace('Z', '+00:00'))
        return parsed.replace(tzinfo=None) if parsed.tzinfo else parsed
    except (TypeError, ValueError):
        return None


def _recipient_key(cnpj, email):
    normalized_email = str(email or '').strip().casefold()
    if normalized_email:
        return f'email:{normalized_email}'
    normalized_cnpj = re.sub(r'\D', '', str(cnpj or ''))
    return f'cnpj:{normalized_cnpj}' if normalized_cnpj else ''


def _today_acceptance_snapshot(now=None):
    """Count accepted, active reservations and uncertain SMTP outcomes conservatively."""
    now = now or datetime.now()
    today = now.date()
    conn = _agency_connection(readonly=True)
    try:
        conn.execute('ATTACH DATABASE ? AS central', (f'file:{CENTRAL_DB}?mode=ro',))
        agency = conn.execute('''
            SELECT id, cnpj, lower(trim(email)) email, lower(status) status,
                   enviado_em, ultimo_log
            FROM disparos_email
            WHERE lower(status) IN ('enviado','concluido','enviando','incerto')
        ''').fetchall()
        central = conn.execute('''
            SELECT cnpj, lower(trim(identity_value)) email, lower(status) status, event_ts
            FROM central.interactions
            WHERE lower(channel)='email' AND lower(direction)='outbound'
              AND lower(status) IN ('enviado','sent','accepted')
              AND date(event_ts)=date('now','localtime')
        ''').fetchall()
    finally:
        conn.close()

    accepted, reserved, uncertain_today, uncertain_all = set(), set(), set(), set()
    uncertain_records = []
    stale_reservations = 0
    stale_before = now - timedelta(seconds=RESERVATION_STALE_AFTER_SECONDS)
    for row in agency:
        key = _recipient_key(row['cnpj'], row['email'])
        if not key:
            continue
        state = row['status']
        timestamp = _parse_local_datetime(row['enviado_em'])
        on_today = timestamp is None or timestamp.date() == today
        stale = state == 'enviando' and (timestamp is None or timestamp <= stale_before)
        if state in {'enviado', 'concluido'}:
            if on_today:
                accepted.add(key)
        elif state == 'incerto' or stale:
            uncertain_all.add(key)
            if on_today:
                uncertain_today.add(key)
            if stale:
                stale_reservations += 1
            uncertain_records.append({
                'dispatch_id': int(row['id']),
                'cnpj': str(row['cnpj'] or ''),
                'email': str(row['email'] or ''),
                'state': 'incerto',
                'original_status': state,
                'classification': ('reserva enviando sem conclusao; revisar antes de qualquer reenvio'
                                   if stale else 'resultado SMTP incerto; revisar antes de qualquer reenvio'),
                'reserved_at': str(row['enviado_em'] or ''),
                'record_note': str(row['ultimo_log'] or ''),
            })
        elif state == 'enviando' and on_today:
            reserved.add(key)

    for row in central:
        key = _recipient_key(row['cnpj'], row['email'])
        if key:
            accepted.add(key)

    uncertain_today.difference_update(accepted)
    uncertain_all.difference_update(accepted)
    reserved.difference_update(accepted | uncertain_today)
    uncertain_records = [
        item for item in uncertain_records
        if _recipient_key(item['cnpj'], item['email']) not in accepted
    ]
    uncertain_records.sort(key=lambda item: item['reserved_at'], reverse=True)
    return {
        'accepted_smtp_today': len(accepted),
        'reserved_today': len(reserved),
        'uncertain_today': len(uncertain_today),
        'uncertain_count': len(uncertain_all),
        'stale_reservation_count': stale_reservations,
        'uncertain_records': uncertain_records[:UNCERTAIN_REVIEW_LIMIT],
        'uncertain_records_truncated': max(0, len(uncertain_records) - UNCERTAIN_REVIEW_LIMIT),
        'review_required': bool(uncertain_all),
    }


def _today_acceptance_counts():
    snapshot = _today_acceptance_snapshot()
    return (snapshot['accepted_smtp_today'], snapshot['reserved_today'],
            snapshot['uncertain_today'])


def sent_or_reserved_today():
    accepted, reserved, uncertain = _today_acceptance_counts()
    return accepted + reserved + uncertain


def preflight(daily_target=30, selected_cnpjs=None, selected_agency_ids=None):
    eligible = len(eligible_emails(selected_cnpjs=selected_cnpjs,
                                   selected_agency_ids=selected_agency_ids))
    counts = _today_acceptance_snapshot()
    sent_today = (counts['accepted_smtp_today'] + counts['reserved_today']
                  + counts['uncertain_today'])
    try:
        status = json.loads(STATUS_FILE.read_text(encoding='utf-8'))
        pid = int(status.get('pid') or 0)
        active = status.get('state') == 'running' and pid > 0
        if active:
            try:
                os.kill(pid, 0)
            except OSError:
                active = False
    except (OSError, ValueError, TypeError):
        active = False
    return {'script_found': LEGACY_SENDER.is_file() and Path(__file__).is_file(),
            'eligible': eligible, 'sent_today': sent_today,
            **counts,
            'daily_target': daily_target, 'daily_remaining': max(0, daily_target-sent_today),
            'active': active}


def _reserve(conn, row, subject, campaign_id):
    now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    conn.execute('''
        INSERT INTO disparos_email
            (cnpj,agencia_nome,municipio,uf,email,orgao_referencia,valor_contratos,status,assunto,enviado_em,ultimo_log)
        VALUES (?,?,?,?,?,?,?,'enviando',?,?,?)
    ''', (row['cnpj'], row['nome'], row.get('municipio'), row.get('uf'), row['email'],
          row.get('fonte'), row.get('valor_contratos'), subject, now, campaign_id))
    return conn.execute('SELECT last_insert_rowid()').fetchone()[0], now


def _finish_record(conn, dispatch_id, row, subject, campaign_id, success, error='', uncertain=False):
    now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    previous = conn.execute('SELECT ultimo_log FROM disparos_email WHERE id=?', (dispatch_id,)).fetchone()
    previous_note = str(previous['ultimo_log'] or '') if previous else ''
    status = 'enviado' if success else ('incerto' if uncertain else 'erro')
    if success:
        log = 'SMTP aceitou a mensagem'
    elif uncertain:
        log = f'Resultado SMTP incerto; destinatario mantido bloqueado para revisao manual. {error[:220]}'
    else:
        log = error[:300]
    if previous_note:
        log = f'{previous_note} | {log}'[:500]
    conn.execute('''UPDATE disparos_email SET status=?, enviado_em=?, ultimo_log=? WHERE id=?''',
                 (status, now, log, dispatch_id))
    if success:
        identity_key = str(row.get('cnpj') or row.get('email') or '').strip().casefold()
        source_key = f"revenue_os_email_{campaign_id}_{identity_key}"
        conn.execute('''
            INSERT OR IGNORE INTO central.interactions
                (source_system,source_event_key,cnpj,channel,direction,status,summary,event_ts,
                 account_operator,identity_value,match_confidence,payload_json,updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
        ''', ('revenue_os_email', source_key, row['cnpj'], 'email', 'outbound', 'enviado',
              f"Apresentação institucional enviada para {row['nome']}", now,
              'revenue_os', row['email'], 'high',
              json.dumps({'cnpj': row['cnpj'], 'campaign_id': campaign_id, 'assunto': subject}, ensure_ascii=False), now))


def _is_uncertain_delivery_error(error, send_attempted):
    """Only explicit negative SMTP replies prove that DATA was not accepted."""
    definitive_rejection = isinstance(error, (
        smtplib.SMTPResponseException,
        smtplib.SMTPRecipientsRefused,
    ))
    return bool(send_attempted and not definitive_rejection)


def _scheduled_window_open(now=None):
    local = sao_paulo_local(now or sao_paulo_now())
    return local.weekday() < 5 and 8 <= local.hour < 18


def _wait_between_recipients(delay_seconds, stop_path=None, sleep_fn=None,
                             progress_fn=None, can_continue=None):
    """Wait in one-second slices so a manual pause is noticed without another send."""
    stop_path = Path(stop_path or STOP_FILE)
    sleep_fn = sleep_fn or time.sleep
    total = max(0, int(delay_seconds))
    for elapsed in range(total):
        if stop_path.exists() or (can_continue and not can_continue()):
            print('EMAIL_STOP requested_during_delay', flush=True)
            return False
        if progress_fn and elapsed % 5 == 0:
            progress_fn(total - elapsed)
        sleep_fn(1)
    return not stop_path.exists() and (not can_continue or can_continue())


def run(limit=5, daily_target=30, delay_min=120, delay_max=180, dry_run=False,
        selected_cnpjs=None, selected_agency_ids=None, scheduled=False):
    if scheduled and not _scheduled_window_open():
        raise RuntimeError('Automatic email window is closed.')
    model_snapshot = email_model.load_snapshot(MESSAGE_CONFIG)
    if not 1 <= limit <= 60 or not 1 <= daily_target <= 100:
        raise ValueError('Limite fora da faixa permitida.')
    candidates = eligible_emails(selected_cnpjs=selected_cnpjs,
                                 selected_agency_ids=selected_agency_ids)
    sent_today = sent_or_reserved_today()
    remaining = max(0, daily_target-sent_today)
    if limit > remaining:
        raise RuntimeError(f'Meta diária: restam {remaining} envios; lote solicitado: {limit}.')
    if len(candidates) < limit:
        raise RuntimeError(f'Fila elegível insuficiente: {len(candidates)} destinatários para {limit} envios.')
    batch = candidates[:limit]
    if dry_run:
        print(f'DRY RUN: {len(batch)} emails corporativos elegíveis; nenhum envio nem lock criado.', flush=True)
        return 0

    DATA.mkdir(parents=True, exist_ok=True)
    with RUN_LOCK.open('a+', encoding='utf-8') as lock_stream:
        try:
            fcntl.flock(lock_stream, fcntl.LOCK_EX | fcntl.LOCK_NB)
        except BlockingIOError:
            raise RuntimeError('Já existe um lote de email em execução.')

        campaign_id = datetime.now().strftime('%Y%m%d-%H%M%S') + '-' + uuid.uuid4().hex[:6]
        snapshot_file = DATA / f'email_model_{campaign_id}.json'
        snapshot_file.write_text(json.dumps(model_snapshot, ensure_ascii=False, indent=2), encoding='utf-8')
        effective_file = DATA / f'email_effective_{campaign_id}.jsonl'
        try:
            STOP_FILE.unlink()
        except FileNotFoundError:
            pass
        queue_file = DATA / f'email_queue_{campaign_id}.json'
        queue_file.write_text(json.dumps(batch, ensure_ascii=False, indent=2), encoding='utf-8')
        try:
            LEGACY_LOCK.write_text(datetime.now().date().isoformat(), encoding='utf-8')
        except OSError:
            pass
        started_at = datetime.now().isoformat(timespec='seconds')
        _write_status(state='running', phase='preparing', pid=os.getpid(),
                      campaign_id=campaign_id, target=len(batch), sent=0, errors=0,
                      current_index=0, started_at=started_at,
                      model_version=model_snapshot['version'], model_hash=model_snapshot['model_hash'])
        print(f'EMAIL_BATCH_STARTED campaign={campaign_id} target={len(batch)}', flush=True)

        conn = None
        sent = errors = 0
        last_error = ''
        try:
            conn = _agency_connection()
            conn.execute('ATTACH DATABASE ? AS central', (str(CENTRAL_DB),))
            conn.commit()
            for index, row in enumerate(batch, 1):
                if STOP_FILE.exists() or (scheduled and not _scheduled_window_open()):
                    print('EMAIL_STOP requested_before_next_message', flush=True)
                    break
                if conn is None:
                    conn = _agency_connection()
                    conn.execute('ATTACH DATABASE ? AS central', (str(CENTRAL_DB),))
                    conn.commit()
                _write_status(state='running', phase='sending', pid=os.getpid(),
                              campaign_id=campaign_id, target=len(batch), sent=sent,
                              errors=errors, current_index=index, started_at=started_at,
                              last_error=last_error)
                rendered = render_model_email(model_snapshot, row['nome'])
                subject = rendered['subject']
                dispatch_id = None
                send_attempted = False
                try:
                    dispatch_id, _ = _reserve(conn, row, subject, campaign_id)
                    conn.commit()
                    _write_effective_email(effective_file, {
                        'campaign_id': campaign_id, 'dispatch_id': dispatch_id,
                        'agency_id': row.get('agency_id'), 'cnpj': row.get('cnpj'),
                        'email': row['email'], 'model_version': model_snapshot['version'],
                        'model_hash': model_snapshot['model_hash'], 'subject': subject,
                        'effective_body': rendered['effective_body'], 'html': rendered['html'],
                        'selected_variants': rendered['selected_variants'],
                        'prepared_at': datetime.now().isoformat(timespec='seconds'),
                        'status': 'prepared_before_smtp'})
                    msg = MIMEMultipart('alternative')
                    msg['Subject'] = subject
                    msg['From'] = email_engine.SMTP_USER
                    msg['To'] = row['email']
                    msg.attach(MIMEText(rendered['html'], 'html', 'utf-8'))
                    smtp = smtplib.SMTP_SSL(email_engine.SMTP_HOST, int(email_engine.SMTP_PORT), timeout=25)
                    try:
                        smtp.login(email_engine.SMTP_USER, email_engine.SMTP_PASS)
                        if scheduled and not _scheduled_window_open():
                            raise RuntimeError('Automatic email window closed before SMTP send.')
                        send_attempted = True
                        smtp.send_message(msg)
                    finally:
                        try:
                            smtp.quit()
                        except Exception:
                            pass
                    _finish_record(conn, dispatch_id, row, subject, campaign_id, True)
                    conn.commit()
                    sent += 1
                    print(f'EMAIL_SENT {index}/{len(batch)} accepted_by_smtp', flush=True)
                except Exception as error:
                    errors += 1
                    last_error = f'{type(error).__name__}: {str(error)[:240]}'
                    uncertain = _is_uncertain_delivery_error(error, send_attempted)
                    if dispatch_id is not None:
                        _finish_record(conn, dispatch_id, row, subject, campaign_id, False,
                                       str(error), uncertain=uncertain)
                        conn.commit()
                    event = 'EMAIL_UNCERTAIN' if uncertain else 'EMAIL_ERROR'
                    print(f'{event} {index}/{len(batch)} {type(error).__name__}', flush=True)
                    if '550' in str(error) or 'Unusual sending activity' in str(error):
                        print('EMAIL_STOP unusual_sending_activity', flush=True)
                        break
                _write_status(state='running', phase='processed', pid=os.getpid(),
                              campaign_id=campaign_id, target=len(batch), sent=sent,
                              errors=errors, current_index=index, started_at=started_at,
                              last_error=last_error)
                if index < len(batch) and sent + errors < len(batch):
                    # Release SQLite handles while waiting. Other CRM reads must
                    # remain available throughout a long email campaign.
                    conn.close()
                    conn = None
                    delay = random.randint(delay_min, delay_max)
                    wait_until = (datetime.now() + timedelta(seconds=delay)).isoformat(timespec='seconds')
                    def report_wait(remaining):
                        _write_status(state='running', phase='waiting', pid=os.getpid(),
                                      campaign_id=campaign_id, target=len(batch), sent=sent,
                                      errors=errors, current_index=index, started_at=started_at,
                                      wait_until=wait_until, wait_remaining_seconds=remaining,
                                      last_error=last_error)
                    _wait_between_recipients(delay, progress_fn=report_wait,
                                             can_continue=_scheduled_window_open if scheduled else None)
        except Exception as error:
            last_error = f'{type(error).__name__}: {str(error)[:240]}'
            raise
        finally:
            if conn is not None:
                conn.close()
            stopped = STOP_FILE.exists()
            final_state = ('stopped' if stopped else
                           ('complete' if errors == 0 and sent == len(batch) else
                            ('error' if last_error and sent + errors < len(batch) else 'partial')))
            _write_status(state=final_state, phase='finished',
                          pid=os.getpid(), campaign_id=campaign_id, target=len(batch),
                          sent=sent, errors=errors, started_at=started_at,
                          current_index=sent + errors, last_error=last_error,
                          completed_at=datetime.now().isoformat(timespec='seconds'))
            try:
                STOP_FILE.unlink()
            except FileNotFoundError:
                pass
        print(f'EMAIL_BATCH_FINISHED sent={sent} errors={errors} target={len(batch)}', flush=True)
        return 0 if sent == len(batch) and errors == 0 else 1


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--limit', type=int, default=5)
    parser.add_argument('--daily-target', type=int, default=30)
    parser.add_argument('--delay-min', type=int, default=120)
    parser.add_argument('--delay-max', type=int, default=180)
    parser.add_argument('--dry-run', action='store_true')
    parser.add_argument('--scheduled', action='store_true')
    parser.add_argument('--cnpjs-file', required=True)
    args = parser.parse_args()
    try:
        selection = json.loads(Path(args.cnpjs_file).read_text(encoding='utf-8'))
        selected_agency_ids = set(selection.get('agency_ids', selection.get('cnpjs', [])))
        selected_agency_ids.update(
            f"sina:{int(value)}" for value in selection.get('sinapro_row_ids', [])
            if str(value).isdigit()
        )
    except (OSError, ValueError, TypeError, AttributeError) as error:
        parser.error(f'Não foi possível carregar a seleção manual: {error}')
    sys.exit(run(args.limit, args.daily_target, args.delay_min, args.delay_max,
                 args.dry_run, selected_agency_ids=selected_agency_ids,
                 scheduled=args.scheduled))
