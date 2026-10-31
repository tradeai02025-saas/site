"""Offline email safety tests. Uses only temporary SQLite files and never opens SMTP."""
import importlib.util
import json
import sqlite3
import sys
import tempfile
import types
import unittest
from datetime import datetime, timedelta
from pathlib import Path
from unittest.mock import patch


APP_DIR = Path(__file__).resolve().parent
if str(APP_DIR) not in sys.path:
    sys.path.insert(0, str(APP_DIR))

# fcntl is used only for the production process lock; these tests never enter run().
if importlib.util.find_spec('fcntl') is None:
    fcntl_stub = types.ModuleType('fcntl')
    fcntl_stub.LOCK_EX = 1
    fcntl_stub.LOCK_NB = 2
    fcntl_stub.flock = lambda *args, **kwargs: None
    sys.modules['fcntl'] = fcntl_stub


class _ApprovedEmailLoader:
    def create_module(self, spec):
        return None

    def exec_module(self, module):
        module.SMTP_USER = 'sender@example.test'
        module.SMTP_PASS = 'not-used-by-tests'
        module.SMTP_HOST = 'smtp.invalid'
        module.SMTP_PORT = 465
        module.build_html_content = lambda agency, owner=None: '<p>synthetic</p>'
        module.render_standard_email = lambda body: '<html><body>FIXED_HEADER' + body + 'FIXED_SIGNATURE</body></html>'


_real_spec_from_file_location = importlib.util.spec_from_file_location


def _test_spec_from_file_location(name, location, *args, **kwargs):
    if name == 'iarandu_approved_email':
        return importlib.util.spec_from_loader(name, _ApprovedEmailLoader())
    return _real_spec_from_file_location(name, location, *args, **kwargs)


with patch.object(importlib.util, 'spec_from_file_location', side_effect=_test_spec_from_file_location):
    sys.modules.pop('email_runner', None)
    import email_runner


class TestEmailRunnerSafety(unittest.TestCase):
    def test_effective_body_audit_is_persisted_before_any_smtp(self):
        with tempfile.TemporaryDirectory(prefix='email-audit-') as directory:
            path = Path(directory) / 'effective.jsonl'
            record = {'model_version': 'email-v1', 'model_hash': 'synthetic-hash',
                      'effective_body': 'Olá, Agência Exemplo.', 'status': 'prepared_before_smtp'}
            email_runner._write_effective_email(path, record)
            self.assertEqual(json.loads(path.read_text(encoding='utf-8').strip()), record)

    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix='email-safety-')
        self.root = Path(self.temp.name)
        self.agency_db = self.root / 'agency.sqlite'
        self.central_db = self.root / 'central.sqlite'
        self._create_databases()
        self.patchers = [
            patch.object(email_runner, 'AGENCY_DB', self.agency_db),
            patch.object(email_runner, 'CENTRAL_DB', self.central_db),
            patch.object(email_runner, 'STATUS_FILE', self.root / 'status.json'),
            patch.object(email_runner, 'STOP_FILE', self.root / 'stop.txt'),
            patch.object(email_runner, 'load_suppressed_cnpis', return_value=set()),
        ]
        for patcher in self.patchers:
            patcher.start()

    def tearDown(self):
        for patcher in reversed(self.patchers):
            patcher.stop()
        self.temp.cleanup()

    def _create_databases(self):
        with sqlite3.connect(self.agency_db) as conn:
            conn.executescript('''
                CREATE TABLE agencias_sinapro_master (
                    id INTEGER PRIMARY KEY, cnpj TEXT, nome TEXT, municipio TEXT, uf TEXT, email TEXT,
                    dono_nome TEXT, website TEXT, fonte TEXT, valor_contratos REAL,
                    status TEXT, instagram TEXT, telefone TEXT
                );
                CREATE TABLE agencias (cnpj TEXT, is_cliente INTEGER DEFAULT 0, email TEXT);
                CREATE TABLE crm_exclusions (
                    id INTEGER PRIMARY KEY, cnpj TEXT, bloquear_disparos INTEGER DEFAULT 0
                );
                CREATE TABLE disparos_email (
                    id INTEGER PRIMARY KEY, cnpj TEXT, agencia_nome TEXT, municipio TEXT,
                    uf TEXT, email TEXT, orgao_referencia TEXT, valor_contratos REAL,
                    status TEXT, assunto TEXT, enviado_em TEXT, ultimo_log TEXT
                );
            ''')
        with sqlite3.connect(self.central_db) as conn:
            conn.execute('''CREATE TABLE interactions (
                id INTEGER PRIMARY KEY, source_system TEXT, source_event_key TEXT,
                cnpj TEXT, channel TEXT, direction TEXT, status TEXT, summary TEXT,
                event_ts TEXT, account_operator TEXT, identity_value TEXT,
                match_confidence TEXT, payload_json TEXT, updated_at TEXT
            )''')
            conn.executescript('''
                CREATE TABLE identities (cnpj TEXT,channel TEXT,value_norm TEXT);
                CREATE TABLE agencies (cnpj TEXT,is_customer INTEGER,do_not_contact INTEGER);
            ''')

    def _insert_candidate(self, cnpj, email, name='Synthetic agency'):
        with sqlite3.connect(self.agency_db) as conn:
            conn.execute('''INSERT INTO agencias_sinapro_master
                (cnpj,nome,municipio,uf,email,dono_nome,website,fonte,valor_contratos,status,instagram,telefone)
                VALUES (?,?,?,?,?,?,?,?,?,'qualificada','','')''',
                (cnpj, name, 'Synthetic City', 'ST', email, '', 'https://example.test',
                 'fixture', 0))

    def _insert_dispatch(self, cnpj, email, status, sent_at, log='fixture-campaign'):
        with sqlite3.connect(self.agency_db) as conn:
            conn.execute('''INSERT INTO disparos_email
                (cnpj,agencia_nome,email,status,enviado_em,ultimo_log)
                VALUES (?,?,?,?,?,?)''', (cnpj, 'Synthetic agency', email, status, sent_at, log))

    def test_stale_enviando_is_exposed_as_uncertain_and_keeps_recipient_blocked(self):
        cnpj, email = '11111111000111', 'stale@agency.example'
        self._insert_candidate(cnpj, email)
        stale_at = (datetime.now() - timedelta(minutes=45)).strftime('%Y-%m-%d %H:%M:%S')
        self._insert_dispatch(cnpj, email, 'enviando', stale_at)

        state = email_runner.preflight(10, selected_cnpjs={cnpj})

        self.assertEqual(state['eligible'], 0)
        self.assertEqual(state['accepted_smtp_today'], 0)
        self.assertEqual(state['reserved_today'], 0)
        self.assertEqual(state['uncertain_today'], 1)
        self.assertEqual(state['uncertain_count'], 1)
        self.assertEqual(state['stale_reservation_count'], 1)
        self.assertEqual(state['sent_today'], 1)
        self.assertEqual(state['daily_remaining'], 9)
        self.assertTrue(state['review_required'])
        self.assertEqual(state['uncertain_records'][0]['original_status'], 'enviando')
        self.assertEqual(state['uncertain_records'][0]['email'], email)
        with sqlite3.connect(self.agency_db) as conn:
            stored = conn.execute('SELECT status FROM disparos_email').fetchone()[0]
        self.assertEqual(stored, 'enviando')  # no automatic release or silent DB rewrite

    def test_shared_email_across_cnpjs_is_deduplicated_and_suppressed_by_prior_uncertainty(self):
        email = 'shared@agency.example'
        self._insert_candidate('11111111000111', email, 'Agency A')
        self._insert_candidate('22222222000122', email, 'Agency B')

        eligible = email_runner.eligible_emails()
        self.assertEqual(len(eligible), 1)
        self.assertEqual(eligible[0]['email'].casefold(), email)

        now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        self._insert_dispatch('11111111000111', email, 'incerto', now)
        self.assertEqual(email_runner.eligible_emails(), [])

    def test_canonical_suppression_still_excludes_selected_email(self):
        cnpj, email = '44444444000144', 'suppressed@agency.example'
        self._insert_candidate(cnpj, email)
        with patch.object(email_runner, 'load_suppressed_cnpis', return_value={cnpj}):
            self.assertEqual(email_runner.eligible_emails(selected_cnpjs={cnpj}), [])

    def test_uncertain_status_consumes_quota_and_remains_visible_for_manual_review(self):
        cnpj, email = '33333333000133', 'uncertain@agency.example'
        self._insert_candidate(cnpj, email)
        now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        self._insert_dispatch(cnpj, email, 'enviando', now, 'campaign-uncertain-fixture')
        with sqlite3.connect(self.agency_db) as conn:
            conn.row_factory = sqlite3.Row
            dispatch_id = conn.execute('SELECT id FROM disparos_email').fetchone()['id']
            email_runner._finish_record(
                conn, dispatch_id, {'cnpj': cnpj, 'email': email}, 'Synthetic subject',
                'campaign-uncertain-fixture', False, 'connection lost after DATA', uncertain=True)

        state = email_runner.preflight(6, selected_cnpjs={cnpj})

        self.assertEqual(state['eligible'], 0)
        self.assertEqual(state['uncertain_today'], 1)
        self.assertEqual(state['sent_today'], 1)
        self.assertEqual(state['daily_remaining'], 5)
        self.assertTrue(state['review_required'])
        self.assertEqual(state['uncertain_records'][0]['state'], 'incerto')
        self.assertEqual(state['uncertain_records'][0]['dispatch_id'], 1)
        self.assertIn('campaign-uncertain-fixture', state['uncertain_records'][0]['record_note'])

    def test_send_transport_failure_is_uncertain_but_explicit_smtp_rejection_is_not(self):
        self.assertTrue(email_runner._is_uncertain_delivery_error(ConnectionError('connection lost'), True))
        self.assertFalse(email_runner._is_uncertain_delivery_error(ConnectionError('connection lost'), False))
        self.assertFalse(email_runner._is_uncertain_delivery_error(
            email_runner.smtplib.SMTPDataError(550, b'rejected'), True))

    def test_manual_pause_during_delay_interrupts_wait_without_smtp(self):
        stop_file = self.root / 'stop.txt'
        sleep_calls = []

        def simulated_sleep(seconds):
            sleep_calls.append(seconds)
            if len(sleep_calls) == 2:
                stop_file.write_text('pause', encoding='utf-8')

        with patch.object(email_runner.smtplib, 'SMTP_SSL') as smtp, \
             patch.object(email_runner.time, 'sleep', side_effect=simulated_sleep):
            completed = email_runner._wait_between_recipients(10, stop_path=stop_file)

        self.assertFalse(completed)
        self.assertEqual(sleep_calls, [1, 1])
        smtp.assert_not_called()

    def test_wait_progress_reports_remaining_time_without_smtp(self):
        remaining = []
        with patch.object(email_runner.smtplib, 'SMTP_SSL') as smtp:
            completed = email_runner._wait_between_recipients(
                12, stop_path=self.root / 'no-stop.txt', sleep_fn=lambda _: None,
                progress_fn=remaining.append)
        self.assertTrue(completed)
        self.assertEqual(remaining, [12, 7, 2])
        smtp.assert_not_called()


if __name__ == '__main__':
    unittest.main(verbosity=2)
