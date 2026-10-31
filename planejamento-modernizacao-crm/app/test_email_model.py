"""Offline contract tests for editable copy inside the fixed approved HTML frame."""

import json
import importlib.util
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch

import email_model
import migrate_email_model
import server


class TestEmailModel(unittest.TestCase):
    def test_import_preserves_all_approved_copy_variations(self):
        model = migrate_email_model.current_approved_model()
        snapshot = email_model.snapshot_from_document(model)
        groups = email_model.variant_groups(snapshot)
        self.assertEqual([len(group) for group in groups], [3, 3, 3, 3])
        self.assertIn('checking de mídia', model['email'])
        self.assertIn('{empresa}', model['email_subject'])

    def test_fixed_header_signature_and_html_escaping(self):
        snapshot = email_model.snapshot_from_document({
            'email': '{Olá|Oi}, {empresa}.\n\nSomos da IArandú Tecnologia (www.iarandu.com.br).\n\n{Conversa|Apresentação}.',
            'email_subject': 'Convite | {empresa}'})
        frame = lambda body: '<html>FIXED_HEADER' + body + 'FIXED_SIGNATURE</html>'
        one = email_model.compose(snapshot, 'Agência <Exemplo>', frame, choice=lambda options: options[0])
        two = email_model.compose(snapshot, 'Agência <Exemplo>', frame, choice=lambda options: options[-1])
        for rendered in (one, two):
            self.assertIn('FIXED_HEADER', rendered['html'])
            self.assertIn('FIXED_SIGNATURE', rendered['html'])
            self.assertIn('<strong>Agência &lt;Exemplo&gt;</strong>', rendered['html'])
            self.assertNotIn('<Exemplo>', rendered['html'])
            self.assertEqual(rendered['subject'], 'Convite | Agência <Exemplo>')
        self.assertNotEqual(one['effective_body'], two['effective_body'])

    def test_preview_uses_approved_header_and_signature(self):
        source = Path(__file__).resolve().parents[2] / 'disparar_emails_30.py'
        spec = importlib.util.spec_from_file_location('approved_email_frame_test', source)
        module = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(module)
        snapshot = email_model.snapshot_from_document(migrate_email_model.current_approved_model())
        rendered = email_model.compose(snapshot, 'Agência Exemplo', module.render_standard_email,
                                       choice=lambda options: options[0])
        self.assertIn('logo-dark.png', rendered['html'])
        self.assertIn('tel:+5541998136312', rendered['html'])
        self.assertIn('mailto:contato@iarandu.com.br', rendered['html'])
        self.assertIn('Agência Exemplo', rendered['html'])
        with patch.object(module.random, 'choice', side_effect=lambda options: options[0]):
            original_html = module.build_html_content('Agência Exemplo')
        self.assertEqual(rendered['html'], original_html)

    def test_save_reload_preview_and_snapshot_for_next_batch(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            paths = {port: root / str(port) / 'custom_templates.json' for port in (5050, 5051)}
            for path in paths.values():
                path.parent.mkdir()
                path.write_text(json.dumps({server.BOT_TEMPLATE_ID: {'conteudo': 'old'}}), encoding='utf-8')
            config = root / 'channel_messages.json'
            current = {'instagram_5050': 'IG {empresa}', 'instagram_5051': 'Oficial {empresa}',
                       'whatsapp': 'WA {empresa}', 'email': '{Olá|Oi} {empresa}.',
                       'email_subject': 'Assunto | {empresa}'}
            with patch.object(server, 'MESSAGES_PATH', config), \
                 patch.dict(server.BOT_TEMPLATE_PATHS, {port: str(path) for port, path in paths.items()}):
                saved = server.save_channel_messages({'messages': current})
                loaded = server.load_channel_messages()
                snapshot = email_model.load_snapshot(config)
                self.assertEqual(saved, loaded)
                self.assertEqual(snapshot['email'], current['email'])
                legacy_save = server.save_channel_messages({'messages': {
                    key: current[key] for key in ('instagram_5050', 'instagram_5051', 'whatsapp')}})
                self.assertEqual(legacy_save['email'], current['email'])
                self.assertEqual(legacy_save['email_subject'], current['email_subject'])
                current['email'] = '{Boa tarde|Olá} {empresa}.'
                server.save_channel_messages({'messages': current})
                next_snapshot = email_model.load_snapshot(config)
            self.assertNotEqual(snapshot['model_hash'], next_snapshot['model_hash'])
            self.assertIn('Oi', email_model.compose(snapshot, 'Teste', lambda body: body,
                                                      choice=lambda options: options[-1])['effective_body'])
            self.assertIn('Boa tarde', email_model.compose(next_snapshot, 'Teste', lambda body: body,
                                                            choice=lambda options: options[0])['effective_body'])

    def test_missing_config_never_falls_back_to_old_hardcoded_copy(self):
        with tempfile.TemporaryDirectory() as directory:
            with self.assertRaises(RuntimeError):
                email_model.load_snapshot(Path(directory) / 'missing.json')
        with self.assertRaises(ValueError):
            email_model.snapshot_from_document({'email': 'Text {empresa}', 'email_subject': 'No company'})


if __name__ == '__main__':
    unittest.main()
