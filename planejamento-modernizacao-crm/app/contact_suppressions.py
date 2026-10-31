"""Read-only, fail-closed contact suppression shared by Revenue OS channels."""

import re
from urllib.parse import urlparse
import sqlite3
import unicodedata
from pathlib import Path


# Auditoria pendente de duplicidade apontou estes dois CNPJs da Binder.
# Bloqueio DNC preventivo até resolver a identidade; a base revisada atualmente
# não os marca em crm_exclusions/central. Sem editar os bancos.
MANUAL_DNC_CNPIS = frozenset({'72190242000104', '72190242000368'})


def _digits(value):
    return re.sub(r'\D', '', str(value or ''))


def _normalized_tag(value):
    text = unicodedata.normalize('NFKD', str(value or ''))
    return ''.join(char for char in text if not unicodedata.combining(char)).upper()


def _read_only(path):
    db_path = Path(path)
    if not db_path.is_file():
        raise RuntimeError(f'Fonte de supressao indisponivel: {db_path.name}.')
    connection = sqlite3.connect(db_path.resolve().as_uri() + '?mode=ro', uri=True, timeout=5)
    connection.row_factory = sqlite3.Row
    return connection


def load_suppressed_cnpis(canonical_db, central_db):
    """Return CNPJ -> suppression reasons; fail closed if any authoritative source is unreadable."""
    suppressed = {}
    canonical = _read_only(canonical_db)
    try:
        for row in canonical.execute('SELECT cnpj,is_cliente,tag_cliente FROM agencias'):
            cnpj = _digits(row['cnpj'])
            if not cnpj:
                continue
            tag = _normalized_tag(row['tag_cliente'])
            reasons = set()
            if bool(row['is_cliente']):
                reasons.add('cliente_canonico')
            if 'NAO PROSPECTAR' in tag:
                reasons.add('nao_prospectar')
            if 'CLIENTE ATIVO IARANDU' in tag:
                reasons.add('cliente_ativo_iarandu')
            if reasons:
                suppressed.setdefault(cnpj, set()).update(reasons)

        for row in canonical.execute('SELECT cnpj FROM crm_exclusions WHERE bloquear_disparos=1'):
            cnpj = _digits(row['cnpj'])
            if cnpj:
                suppressed.setdefault(cnpj, set()).add('crm_exclusion')
    except sqlite3.Error as error:
        raise RuntimeError('Nao foi possivel auditar supressoes na base canonica; preflight bloqueado.') from error
    finally:
        canonical.close()

    central = _read_only(central_db)
    try:
        for row in central.execute('SELECT cnpj,is_customer,do_not_contact FROM agencies'):
            cnpj = _digits(row['cnpj'])
            if not cnpj:
                continue
            reasons = set()
            if bool(row['is_customer']):
                reasons.add('cliente_central')
            if bool(row['do_not_contact']):
                reasons.add('do_not_contact')
            if reasons:
                suppressed.setdefault(cnpj, set()).update(reasons)
    except sqlite3.Error as error:
        raise RuntimeError('Nao foi possivel auditar supressoes no central.db; preflight bloqueado.') from error
    finally:
        central.close()
    for cnpj in MANUAL_DNC_CNPIS:
        suppressed.setdefault(cnpj, set()).add('dnc_audit_preventivo')
    return suppressed


def load_suppressed_contacts(central_db, suppressed_cnpis):
    """Return exact channel identities already linked to a globally suppressed CNPJ."""
    wanted = {_digits(value) for value in suppressed_cnpis if _digits(value)}
    result = {'email': set(), 'instagram': set(), 'whatsapp': set()}
    if not wanted:
        return result
    central = _read_only(central_db)
    try:
        rows = central.execute('SELECT cnpj,channel,value_norm FROM identities').fetchall()
    except sqlite3.Error as error:
        raise RuntimeError('Nao foi possivel auditar supressoes por identidade de contato.') from error
    finally:
        central.close()
    for row in rows:
        if _digits(row['cnpj']) not in wanted:
            continue
        channel = str(row['channel'] or '').strip().casefold()
        value = str(row['value_norm'] or '').strip()
        if channel in {'email', 'e-mail'}:
            value = value.casefold()
            target = 'email'
        elif channel in {'instagram', 'ig'}:
            if '://' in value:
                parsed = urlparse(value)
                if (parsed.hostname or '').lower().removeprefix('www.') != 'instagram.com':
                    continue
                value = parsed.path.strip('/').split('/', 1)[0]
            value = value[1:] if value.startswith('@') else value
            value = value.casefold()
            if not re.fullmatch(r'[a-z0-9._]{1,30}', value):
                continue
            target = 'instagram'
        elif channel in {'whatsapp', 'phone', 'telephone', 'telefone'}:
            value = _digits(value)
            target = 'whatsapp'
        else:
            continue
        if value:
            result[target].add(value)
    return result
