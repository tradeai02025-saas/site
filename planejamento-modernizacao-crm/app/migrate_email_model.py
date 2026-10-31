"""One-time, reviewable import of the approved sender's existing copy variants."""

import ast
import json
import os
from datetime import datetime
from pathlib import Path


HERE = Path(__file__).resolve().parent
LEGACY = HERE.parent.parent / 'disparar_emails_30.py'
MESSAGES = HERE / 'data' / 'channel_messages.json'


def _literal(node):
    if isinstance(node, ast.Constant) and isinstance(node.value, str):
        return node.value
    if isinstance(node, ast.JoinedStr):
        parts = []
        for item in node.values:
            if isinstance(item, ast.Constant) and isinstance(item.value, str):
                parts.append(item.value)
            elif isinstance(item, ast.FormattedValue) and isinstance(item.value, ast.Name) and item.value.id == 'agencia_nome':
                parts.append('{empresa}')
            else:
                raise ValueError('Unexpected expression in approved email copy.')
        return ''.join(parts)
    raise ValueError('Unexpected approved email variant type.')


def current_approved_model(source=LEGACY):
    module = ast.parse(Path(source).read_text(encoding='utf-8'))
    method = next(node for node in module.body if isinstance(node, ast.FunctionDef)
                  and node.name == 'build_html_content')
    groups = {}
    for node in method.body:
        if not isinstance(node, ast.Assign) or len(node.targets) != 1 or not isinstance(node.targets[0], ast.Name):
            continue
        name = node.targets[0].id
        if name not in ('saudacao', 'desenvolvimento', 'convite', 'conclusao'):
            continue
        call = node.value
        if not isinstance(call, ast.Call) or not isinstance(call.func, ast.Attribute) or call.func.attr != 'choice':
            raise ValueError('Approved sender copy structure changed.')
        options = [_literal(item).replace('<strong>', '').replace('</strong>', '')
                   for item in call.args[0].elts]
        if not options or any('|' in item or '{' in item.replace('{empresa}', '') for item in options):
            raise ValueError('Approved sender variant cannot be imported without changing text.')
        if name == 'saudacao':
            split = [item.split('{empresa}') for item in options]
            if any(len(parts) != 2 or parts[1] != split[0][1] for parts in split):
                raise ValueError('Approved greetings no longer share the company suffix.')
            groups[name] = '{' + '|'.join(parts[0].rstrip() for parts in split) + '} {empresa}' + split[0][1]
        else:
            groups[name] = '{' + '|'.join(options) + '}'
    if len(groups) != 4:
        raise ValueError('Approved sender does not contain the four expected variant groups.')
    return {
        'email': '\n\n'.join((groups['saudacao'],
                             'Somos da IArandú Tecnologia (www.iarandu.com.br).',
                             groups['desenvolvimento'], groups['convite'], groups['conclusao'])),
        'email_subject': 'IArandú Tecnologia — conciliação e comprovação de mídia | {empresa}',
    }


def migrate(path=MESSAGES, source=LEGACY):
    path = Path(path)
    document = json.loads(path.read_text(encoding='utf-8'))
    if 'email' in document or 'email_subject' in document:
        raise RuntimeError('Email model already exists; migration will not overwrite it.')
    document.update(current_approved_model(source))
    backup = path.with_name(path.name + '.before_email_' + datetime.now().strftime('%Y%m%d-%H%M%S'))
    backup.write_bytes(path.read_bytes())
    temp = path.with_suffix('.tmp')
    temp.write_text(json.dumps(document, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    os.replace(temp, path)
    return backup


if __name__ == '__main__':
    print(migrate())
