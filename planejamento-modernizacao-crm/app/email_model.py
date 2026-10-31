"""Editable email body inside the approved, fixed HTML frame."""

import hashlib
import html
import json
import random
import re
from pathlib import Path


MODEL_VERSION = 'email-v1'
TOKEN = re.compile(r'\{([^{}]+)\}')
SUBJECT_TOKEN = re.compile(r'\{empresa\}')


def validate_email_fields(body, subject):
    if not isinstance(body, str) or not body.strip() or len(body) > 12000:
        raise ValueError('Corpo do e-mail vazio ou acima de 12000 caracteres.')
    if not isinstance(subject, str) or not subject.strip() or len(subject) > 250:
        raise ValueError('Assunto do e-mail vazio ou acima de 250 caracteres.')
    if '\n' in subject or '\r' in subject:
        raise ValueError('Assunto do e-mail não pode conter quebra de linha.')
    if '{empresa}' not in body or '{empresa}' not in subject:
        raise ValueError('Corpo e assunto devem conter {empresa}.')
    for value, allow_variations in ((body, True), (subject, False)):
        if value.count('{') != value.count('}'):
            raise ValueError('Chaves de variável ou variação não balanceadas.')
        for match in TOKEN.finditer(value):
            token = match.group(1)
            if token == 'empresa':
                continue
            if not allow_variations or '|' not in token or any(not part.strip() for part in token.split('|')):
                raise ValueError('Variável ou variação inválida no e-mail.')
        if '{' in TOKEN.sub('', value) or '}' in TOKEN.sub('', value):
            raise ValueError('Chaves inválidas no e-mail.')
    return body.strip(), subject.strip()


def snapshot_from_document(document):
    if not isinstance(document, dict):
        raise ValueError('Configuração do e-mail inválida.')
    body, subject = validate_email_fields(document.get('email'), document.get('email_subject'))
    canonical = json.dumps({'version': MODEL_VERSION, 'email': body, 'email_subject': subject},
                           ensure_ascii=False, sort_keys=True, separators=(',', ':'))
    return {'version': MODEL_VERSION, 'email': body, 'email_subject': subject,
            'model_hash': hashlib.sha256(canonical.encode('utf-8')).hexdigest()}


def load_snapshot(path):
    """Fail closed: dispatch never substitutes a hardcoded body for missing config."""
    try:
        document = json.loads(Path(path).read_text(encoding='utf-8'))
    except (OSError, ValueError) as error:
        raise RuntimeError(f'Modelo oficial de e-mail indisponível: {error}') from error
    return snapshot_from_document(document)


def variant_groups(snapshot):
    return [match.group(1).split('|') for match in TOKEN.finditer(snapshot['email'])
            if match.group(1) != 'empresa']


def compose(snapshot, company, standard_frame, choice=None):
    """Render one effective body; only standard_frame supplies header/signature."""
    company = re.sub(r'[\r\n]+', ' ', str(company or 'sua agência')).strip()
    choose = choice or random.choice
    selected = []

    def replace(match):
        token = match.group(1)
        if token == 'empresa':
            return company
        picked = choose(token.split('|'))
        selected.append({'options': token.split('|'), 'selected': picked})
        return picked

    effective_body = TOKEN.sub(replace, snapshot['email'])
    subject = SUBJECT_TOKEN.sub(lambda _match: company, snapshot['email_subject'])
    paragraphs = [part.strip() for part in re.split(r'\n\s*\n', effective_body) if part.strip()]
    rendered = []
    for index, paragraph in enumerate(paragraphs):
        safe = html.escape(paragraph).replace('\n', '<br>')
        if index == 0:
            safe = safe.replace(html.escape(company), '<strong>' + html.escape(company) + '</strong>', 1)
        if index == 1:
            safe = safe.replace('www.iarandu.com.br',
                                '<a href="https://www.iarandu.com.br" style="color:#3b82c4;">www.iarandu.com.br</a>')
        weight = 'font-weight:600;' if index == 3 else ''
        rendered.append(f'<p style="margin:0 0 16px;{weight}">{safe}</p>')
    body_html = ''.join(rendered)
    return {'subject': subject, 'html': standard_frame(body_html),
            'effective_body': effective_body, 'body_html': body_html,
            'selected_variants': selected, 'model_hash': snapshot['model_hash'],
            'model_version': snapshot['version']}
