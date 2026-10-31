"""Synthetic, read-only dispatch log contract checks. No services or sends."""

import json
import sqlite3
import tempfile
import unittest
from pathlib import Path

import dispatch_logs


class DispatchLogAdapterTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        root = Path(self.temp.name)
        self.agency = root / 'agency.sqlite'
        self.central = root / 'central.sqlite'
        self.ig5050 = root / '5050.sqlite'
        self.ig5051 = root / '5051.sqlite'
        with sqlite3.connect(self.agency) as db:
            db.execute('CREATE TABLE disparos_email (id INTEGER, cnpj TEXT, agencia_nome TEXT, email TEXT, status TEXT, enviado_em TEXT, ultimo_log TEXT)')
            db.execute('CREATE TABLE disparos_whatsapp (id INTEGER, cnpj TEXT, nome TEXT, telefone TEXT, whatsapp_id TEXT, mensagem TEXT, status TEXT, enviado_em TEXT, resposta_api TEXT)')
            db.execute('INSERT INTO disparos_email VALUES (1,?,?,?,?,?,?)',
                       ('sina:7', 'Synthetic Agency', 'lead@example.test', 'enviado',
                        '2026-10-27 09:00:00', 'SMTP aceitou'))
            db.execute('INSERT INTO disparos_whatsapp VALUES (1,?,?,?,?,?,?,?,?)',
                       ('', 'Synthetic Agency', '', '5511999999999@c.us', 'Test body',
                        'enviado', '2026-10-27 09:01:00', json.dumps({'messageId': 'synthetic-1'})))
            db.execute('INSERT INTO disparos_whatsapp VALUES (2,?,?,?,?,?,?,?,?)',
                       ('', 'Other Agency', '', '5511888888888@c.us', None,
                        'erro', '2026-10-27 09:02:00', 'timeout after send attempt'))
        with sqlite3.connect(self.central) as db:
            db.execute('CREATE TABLE interactions (id INTEGER, source_system TEXT, source_event_key TEXT, cnpj TEXT, channel TEXT, direction TEXT, status TEXT, summary TEXT, event_ts TEXT, account_operator TEXT, payload_json TEXT, identity_value TEXT)')
            db.execute('INSERT INTO interactions VALUES (1,?,?,?,?,?,?,?,?,?,?,?)',
                       ('synthetic', 'event-1', '', 'instagram', 'outbound', 'enviado',
                        'Synthetic event', '2026-10-27T09:03:00-03:00', None, '{}', '@shared'))
            db.execute('INSERT INTO interactions VALUES (2,?,?,?,?,?,?,?,?,?,?,?)',
                       ('synthetic', 'event-1', '', 'instagram', 'outbound', 'enviado',
                        'Synthetic event on other account', '2026-10-27T09:04:00-03:00',
                        '@iarandu.br', '{}', '@shared'))
            db.execute('INSERT INTO interactions VALUES (3,?,?,?,?,?,?,?,?,?,?,?)',
                       ('synthetic', 'event-1', '', 'instagram', 'outbound', 'enviado',
                        'Different event with unknown sender', '2026-10-27T09:06:00-03:00',
                        None, '{}', '@shared'))
        for path in (self.ig5050, self.ig5051):
            with sqlite3.connect(path) as db:
                db.execute('CREATE TABLE candidatos (id INTEGER, handle TEXT, nome TEXT, status_geral TEXT, msg1_enviada_em TEXT, conteudo_mensagem TEXT, ultimo_erro TEXT)')
                db.execute('INSERT INTO candidatos VALUES (1,?,?,?,?,?,?)',
                           ('shared', 'Synthetic Agency', 'enviado', '2026-10-27 09:05:00',
                            'Persisted body', None))

    def test_account_isolation_unknown_sender_and_uncertain_status(self):
        logs, warnings = dispatch_logs.collect(self.agency, self.central,
            {5050: self.ig5050, 5051: self.ig5051}, {'shared': 'sina:7'})
        self.assertEqual(warnings, [])
        ig = [item for item in logs if item['source_system'].startswith('ig:')]
        self.assertEqual(len(ig), 2)
        self.assertEqual({item['sender_instance_id'] for item in ig}, {'ig:5050', 'ig:5051'})
        self.assertTrue(all(item['sender_account'] is None for item in ig))
        self.assertEqual({item['lead_id'] for item in ig}, {'sina:7'})
        self.assertTrue(all(item['status'] == 'sent_unconfirmed' for item in ig))
        unknown = next(item for item in logs if item['source_system'] == 'central.db:interactions' and item['source_record_id'] == '1')
        self.assertIsNone(unknown['sender_account'])
        self.assertIsNone(unknown['lead_id'])
        self.assertEqual(unknown['sender_instance_id'], 'ig:unknown')
        self.assertEqual(len([item for item in logs if item['source_system'] == 'central.db:interactions']), 3)
        timeout = next(item for item in logs if item['source_record_id'] == '2' and item['channel'] == 'whatsapp')
        self.assertEqual(timeout['status'], 'uncertain')
        self.assertIsNone(timeout['sender_account'])
        self.assertIsNone(timeout['timestamp_tz'])
        self.assertEqual(timeout['timestamp_raw'], '2026-10-27 09:02:00')
        self.assertIsNone(timeout['timezone_provenance'])
        self.assertEqual(timeout['reason_code'], 'TIMEOUT_AFTER_ATTEMPT')

    def test_channel_statuses_provenance_and_partial_sources(self):
        logs, warnings = dispatch_logs.collect(self.agency, self.central,
            {5050: self.ig5050, 5051: self.ig5051}, {'shared': 'sina:7'})
        email = next(item for item in logs if item['channel'] == 'email')
        self.assertEqual(email['status'], 'smtp_accepted')
        self.assertEqual(email['lead_id'], 'sina:7')
        self.assertIsNone(email['sender_account'])
        self.assertIsNone(email['body_text'])
        self.assertIsNone(email['timestamp_tz'])
        wa = next(item for item in logs if item['channel'] == 'whatsapp' and item['source_record_id'] == '1')
        self.assertEqual(wa['status'], 'gateway_accepted')
        self.assertEqual(wa['body_text'], 'Test body')
        self.assertIsNone(wa['sender_account'])
        self.assertNotEqual(email['log_id'], wa['log_id'])  # Same row ID, different source namespace.
        response = dispatch_logs.page(logs, warnings, {'channel': 'instagram_direct'})
        self.assertEqual(response['status'], 'ok')
        self.assertEqual(response['summary']['smtp_accepted'], 0)
        self.assertEqual(response['summary']['sent_unconfirmed'], 5)
        official = dispatch_logs.page(logs, warnings, {'sender_instance_id': 'ig:5051'})
        self.assertEqual(official['pagination']['total_records'], 2)
        self.assertEqual({item['source_system'] for item in official['data']},
                         {'ig:5051:candidatos', 'central.db:interactions'})
        missing, warnings = dispatch_logs.collect(agency_db=Path(self.temp.name) / 'missing.sqlite')
        self.assertEqual(missing, [])
        self.assertEqual(dispatch_logs.page(missing, warnings)['status'], 'partial')


if __name__ == '__main__':
    unittest.main()
