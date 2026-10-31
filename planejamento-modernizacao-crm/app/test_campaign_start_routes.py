"""Mock-only campaign route coverage. No outreach engine or bot is started."""
import json
import importlib.util
import io
import sqlite3
import sys
import tempfile
import threading
import unittest
from types import SimpleNamespace
from http.server import BaseHTTPRequestHandler, HTTPServer
from pathlib import Path
from unittest.mock import patch
from urllib.error import URLError


class _StubModuleLoader:
    def __init__(self, name):
        self.name = name

    def create_module(self, spec):
        return None

    def exec_module(self, module):
        if self.name == 'iarandu_cockpit_legacy':
            class LegacyHandler:
                def send_json(self, payload, status=200):
                    body = json.dumps(payload).encode('utf-8')
                    self.send_response(status)
                    self.send_header('Content-Type', 'application/json')
                    self.send_header('Content-Length', str(len(body)))
                    self.end_headers()
                    self.wfile.write(body)
            module.RevenueOSHandler = LegacyHandler
            module.DB_PATH = '/tmp/mock-canonical.db'
            module.CENTRAL_DB = '/tmp/mock-central.db'
        elif self.name == 'revenue_os_email_runner':
            module.preflight = lambda *args, **kwargs: {'eligible': 0, 'daily_remaining': 0, 'active': False}
        elif self.name == 'revenue_os_whatsapp_runner':
            module.LOCK = '/tmp/mock-wa.lock'
            module.sent_today = lambda: 0
            module.engine = type('MockEngine', (), {'get_active_session': lambda self: None})()


_real_spec_from_file_location = importlib.util.spec_from_file_location


def _test_spec_from_file_location(name, location, *args, **kwargs):
    if name in {'iarandu_cockpit_legacy', 'revenue_os_email_runner', 'revenue_os_whatsapp_runner'}:
        return importlib.util.spec_from_loader(name, _StubModuleLoader(name))
    return _real_spec_from_file_location(name, location, *args, **kwargs)


with patch.object(importlib.util, 'spec_from_file_location', side_effect=_test_spec_from_file_location):
    sys.modules.pop('server', None)
    import server


CONFIG = {
    'daily_targets': {'email': 30, 'whatsapp': 24, 'instagram_5050': 50, 'instagram_5051': 15},
    'delays': {'email_min': 120, 'email_max': 180,
               'instagram_5050_min': 90, 'instagram_5050_max': 180,
               'instagram_5051_min': 150, 'instagram_5051_max': 300},
    'schedule': {'enabled': False, 'weekdays': [0, 1, 2, 3, 4, 5], 'times': {}},
}


class CampaignStartRouteTests(unittest.TestCase):
    def setUp(self):
        self.previous_preview = server.CRMHandler.preview
        server.CRMHandler.preview = False

    def tearDown(self):
        server.CRMHandler.preview = self.previous_preview

    def invoke_handler(self, method, path, payload=None):
        body = json.dumps(payload or {}).encode('utf-8')
        handler = object.__new__(server.CRMHandler)
        handler.preview = False
        handler.path = path
        handler.headers = {'Content-Length': str(len(body))}
        handler.rfile = io.BytesIO(body)
        handler.wfile = io.BytesIO()
        response = {}
        handler.send_json = lambda data, status=200: response.update(data=data, status=status)
        getattr(server.CRMHandler, method)(handler)
        return {**response.get('data', {}), '_http_status': response.get('status', 200)}

    def post(self, path, payload=None):
        return self.invoke_handler('do_POST', path, payload)

    def get(self, path):
        return self.invoke_handler('do_GET', path)

    def test_email_draft_preview_does_not_save_or_start(self):
        draft = {'email': '{Olá|Oi} {empresa}.', 'email_subject': 'Convite | {empresa}'}
        with patch.object(server, 'load_campaign_config', return_value=CONFIG), \
             patch.object(server.email_runner, 'render_model_email', create=True,
                          return_value={'html': '<html>fixed frame</html>', 'subject': 'Convite | Teste',
                                        'model_hash': 'synthetic'}) as render, \
             patch.object(server, 'save_channel_messages') as save, \
             patch.object(server.subprocess, 'Popen') as popen:
            response = self.post('/api/local/messages/email-preview', {'messages': draft, 'empresa': 'Teste'})
        self.assertEqual(response['_http_status'], 200)
        self.assertEqual(response['variant_groups'], [['Olá', 'Oi']])
        self.assertEqual(response['subject'], 'Convite | Teste')
        render.assert_called_once()
        save.assert_not_called()
        popen.assert_not_called()

    def test_dispatch_logs_route_is_read_only_and_reports_partial_sources(self):
        payload = {'status': 'partial', 'source_warnings': ['synthetic source unavailable'],
                   'pagination': {'total_records': 0}, 'data': []}
        with patch.object(server, '_dispatch_logs_response', return_value=payload) as adapter, \
             patch.object(server.subprocess, 'Popen') as popen:
            response = self.get('/api/local/dispatch-logs?page=1&limit=25')
        self.assertEqual(response['status'], 'partial')
        self.assertEqual(response['_http_status'], 200)
        adapter.assert_called_once_with({'page': ['1'], 'limit': ['25']})
        popen.assert_not_called()

    def test_dispatch_log_bot_paths_require_live_build_info_per_account(self):
        with tempfile.TemporaryDirectory() as temp_dir:
            paths = {port: Path(temp_dir) / f'{port}.sqlite' for port in (5050, 5051)}
            for path in paths.values():
                path.touch()
            def build_info(port, route, timeout=5):
                self.assertEqual(route, '/api/bot/build-info')
                return {'status': 'ok', 'database': {'path': str(paths[port]),
                        'device': 1, 'file_id': port}}
            with patch.object(server, 'BOT_DATABASE_PATHS', {p: str(v) for p, v in paths.items()}), \
                 patch.object(server, '_local_json', side_effect=build_info):
                sources, warnings = server._dispatch_log_ig_sources()
            self.assertEqual(sources, paths)
            self.assertEqual(warnings, [])
            with patch.object(server, 'BOT_DATABASE_PATHS', {p: str(v) for p, v in paths.items()}), \
                 patch.object(server, '_local_json', return_value={
                     'status': 'ok', 'database': {'path': str(paths[5050]),
                                               'device': 1, 'file_id': 5050}}):
                sources, warnings = server._dispatch_log_ig_sources()
            self.assertEqual(set(sources), {5050})
            self.assertTrue(any('Instagram 5051' in warning for warning in warnings))

    def test_dispatch_log_windows_db_paths_translate_to_distinct_wsl_paths(self):
        paths = {5050: r'C:\Temp\ig5050\comercial.db',
                 5051: r'C:\Temp\ig5051\comercial.db'}
        def build_info(port, route, timeout=5):
            return {'status': 'ok', 'database': {'path': paths[port],
                    'device': 1, 'file_id': port}}
        with patch.object(server, 'BOT_DATABASE_PATHS', paths), \
             patch.object(server, '_local_json', side_effect=build_info), \
             patch.object(server.Path, 'is_file', return_value=True):
            sources, warnings = server._dispatch_log_ig_sources()
        self.assertEqual(warnings, [])
        self.assertEqual(str(sources[5050]), '/mnt/c/Temp/ig5050/comercial.db')
        self.assertEqual(str(sources[5051]), '/mnt/c/Temp/ig5051/comercial.db')

    def coverage(self, port):
        target, sent, eligible = ({5050: (25, 6, 40), 5051: (15, 6, 20)})[port]
        return {'port': port, 'mapping': {'handles': [f'handle{i}' for i in range(eligible)],
                                          'coverage': {'selected_agencies': eligible}},
                'can_start': True,
                'coverage': {'protocol': 'exact-handles-v1', 'can_start': True,
                             'sent_today': sent, 'daily_target': target,
                             'daily_remaining': target - sent, 'eligible_pending': eligible,
                             'allowed_session_limit': min(target - sent, eligible)}}

    def test_shared_instagram_agencies_are_independently_assigned_to_both_accounts(self):
        shared = {f'shared-{index}' for index in range(6)}
        pending = {5050: shared | {'personal-only'}, 5051: shared | {'official-only'}}
        assigned = server._independent_instagram_assignments(
            pending, {5050: 50, 5051: 15}
        )
        self.assertEqual(assigned[5050], pending[5050])
        self.assertEqual(assigned[5051], pending[5051])
        self.assertEqual(assigned[5050] & assigned[5051], shared)

    def test_instagram_daily_capacity_is_applied_per_account_without_cross_suppression(self):
        shared = {f'shared-{index}' for index in range(6)}
        pending = {5050: shared | {'personal-only'}, 5051: shared | {'official-only'}}
        assigned = server._independent_instagram_assignments(
            pending, {5050: 3, 5051: 2}
        )
        self.assertEqual(len(assigned[5050]), 3)
        self.assertEqual(len(assigned[5051]), 2)
        self.assertIn('shared-0', assigned[5050] & assigned[5051])

    def test_priority_deals_route_keeps_existing_deals_without_fabricating_reply_history(self):
        cnpi_list = [f'{index:014d}' for index in range(1, 6)]
        agencies = [{'cnpj': cnpj, 'agency_id': cnpj, 'nome': f'Agencia {index}',
                     'municipio': 'Curitiba', 'uf': 'PR', 'contratos': index * 1000}
                    for index, cnpj in enumerate(cnpi_list, 1)]
        with tempfile.TemporaryDirectory() as temp_dir:
            db_path = Path(temp_dir) / 'crm.sqlite'
            connection = sqlite3.connect(db_path)
            connection.executescript('''
                CREATE TABLE crm_deals (
                    id INTEGER PRIMARY KEY, cnpj TEXT, etapa TEXT, valor_proposta REAL,
                    responsavel TEXT, proximo_passo TEXT, prazo_proximo_passo TEXT,
                    prioridade TEXT, criado_em TEXT, atualizado_em TEXT
                );
                CREATE TABLE crm_history (
                    id INTEGER PRIMARY KEY, cnpj TEXT, tipo TEXT, descricao TEXT,
                    metadata_json TEXT, criado_em TEXT
                );
            ''')
            connection.executemany(
                'INSERT INTO crm_deals(cnpj,etapa,responsavel,proximo_passo,prazo_proximo_passo,prioridade,criado_em,atualizado_em) VALUES(?,?,?,?,?,?,?,?)',
                [(cnpj, 'resposta', 'Equipe', f'Acompanhar agencia {index}', '2026-10-26', 'ALTA',
                  '2026-10-25 09:00:00', '2026-10-25 10:00:00') for index, cnpj in enumerate(cnpi_list, 1)],
            )
            connection.execute(
                "INSERT INTO crm_history(cnpj,tipo,descricao,metadata_json,criado_em) VALUES(?,?,?,?,?)",
                (cnpi_list[0], 'nota', 'Nota existente, nao e resposta', '{}', '2026-10-25 09:30:00'),
            )
            connection.commit()
            connection.close()
            with patch.object(server, 'DB_PATH', str(db_path)), patch.object(server, 'agency_rows', return_value=agencies):
                handler = object.__new__(server.CRMHandler)
                handler.path = '/api/local/priority-deals'
                handler.headers = {'Content-Length': '0'}
                handler.rfile = io.BytesIO(b'')
                handler.wfile = io.BytesIO()
                response = {}
                handler.send_json = lambda data, status=200: response.update(data=data, status=status)
                server.CRMHandler.do_GET(handler)

        self.assertEqual(response['status'], 200)
        deals = response['data']
        self.assertEqual(len(deals), 5)
        self.assertEqual({deal['cnpj'] for deal in deals}, set(cnpi_list))
        self.assertTrue(all(deal['etapa'] == 'resposta' for deal in deals))
        self.assertTrue(all(deal['has_confirmed_reply'] is False for deal in deals))
        self.assertTrue(all('recebido_em' not in deal and 'history' not in deal for deal in deals))

    def test_instagram_single_account_routes_use_preflight_limit(self):
        for port, path, expected in (
            (5050, '/api/action/start-instagram', 19),
            (5051, '/api/action/start-instagram-oficial', 9),
        ):
            start_result = {'port': port, 'allowed_session_limit': expected,
                            'coverage': {'allowed_session_limit': expected}}
            preflight_state = {'instagram': {str(port): {
                'can_start': True, '_startable_agency_ids': ['12345678901234'],
                'attempt_count': expected, 'blocked_reason': None,
            }}}
            with self.subTest(port=port), \
                 patch.object(server, 'load_campaign_config', return_value=CONFIG), \
                 patch.object(server, '_campaign_preflight', return_value=preflight_state) as preflight, \
                 patch.object(server, '_start_instagram', return_value=start_result) as start:
                result = self.post(path)
                self.assertEqual(result['allowed_session_limit'], expected)
                preflight.assert_called_once_with(preview=False, include_internal=True)
                start.assert_called_once_with(port, expected, CONFIG['daily_targets'][f'instagram_{port}'],
                                              CONFIG, selected_ids=['12345678901234'])

    def test_whatsapp_route_uses_remaining_daily_quota_not_test_default(self):
        with patch.object(server, 'load_campaign_config', return_value=CONFIG), \
             patch.object(server, 'selected_agency_ids', return_value={'12345678901234'}), \
             patch.object(server, 'selected_whatsapp_candidates', return_value=[{'cnpj': str(i)} for i in range(20)]), \
             patch.object(server.wa_runner, 'sent_today', return_value=7), \
             patch.object(server, '_start_whatsapp', return_value={'pid': 123, 'limit': 17}) as start:
            result = self.post('/api/action/start-whatsapp')
        self.assertEqual(result['limit'], 17)
        start.assert_called_once_with(17, 24, CONFIG)

    def test_uf_candidates_include_cnpjless_ids_and_suppress_trade_for_instagram(self):
        cnpjless = {'cnpj': '', 'nome': 'Agencia Nova', 'uf': 'PR', 'municipio': 'Curitiba',
                    'instagram': '@new.agency', 'email': 'nova@example.test', 'whatsapp': ''}
        cnpjless['agency_id'] = server.agency_id(cnpjless)
        agencies = [
            {'cnpj': '11111111000111', 'agency_id': '11111111000111', 'nome': 'Agencia PR', 'uf': 'PR', 'municipio': 'Curitiba',
             'instagram': '@agency.pr', 'email': 'pr@example.test', 'whatsapp': '5541999990000'},
            {'cnpj': '81078289000163', 'agency_id': '81078289000163', 'nome': 'Trade Comunicacao', 'uf': 'PR', 'municipio': 'Curitiba',
             'instagram': '@agenciatrade', 'email': 'trade@example.test', 'whatsapp': '5541999990001'},
            {'cnpj': '22222222000122', 'agency_id': '22222222000122', 'nome': 'Agencia SP', 'uf': 'SP', 'municipio': 'Sao Paulo',
             'instagram': '@agency.sp', 'email': 'sp@example.test', 'whatsapp': '5511999990000'},
            cnpjless,
        ]
        with tempfile.TemporaryDirectory() as temp_dir, \
             patch.object(server, 'agency_rows', return_value=agencies), \
             patch.object(server, 'canonical_base_metadata', return_value={
                 'source': 'synthetic-test', 'updated_at': 'test-only',
                 'agencies': len(agencies), 'with_site': 0, 'with_email': 0,
                 'with_phone': 0, 'with_instagram': 0,
             }), \
             patch.object(server, 'load_suppressed_cnpis', return_value={'81078289000163'}), \
             patch.object(server, 'load_suppressed_contacts', return_value={'instagram': set()}), \
             patch.object(server, 'SELECTION_PATH', Path(temp_dir) / 'selected.json'):
            page = self.get('/api/local/agencies?uf=PR&channel=instagram')
            self.assertEqual([row['nome'] for row in page['items']], ['Agencia PR', 'Trade Comunicacao', 'Agencia Nova'])
            self.assertEqual(page['items'][-1]['agency_id'], cnpjless['agency_id'])
            candidates = self.get('/api/local/agency-selection-candidates?uf=PR&channel=instagram')
            self.assertEqual(candidates['selectable_agencies'], 3)
            self.assertEqual(candidates['selectable_cnpjs'], 2)
            self.assertEqual(set(candidates['agency_ids']), {'11111111000111', '81078289000163', cnpjless['agency_id']})
            saved = self.post('/api/local/agency-selection', {'agency_ids': candidates['agency_ids']})
            self.assertEqual(set(saved['selection']['agency_ids']), set(candidates['agency_ids']))
            self.assertEqual(set(saved['selection']['cnpjs']), {'11111111000111', '81078289000163'})
            mapped = server.selected_instagram_handles(5050, set(saved['selection']['agency_ids']))
        self.assertEqual(mapped['handles'], ['agency.pr', 'new.agency'])
        self.assertEqual(mapped['coverage']['suppressed_agencies'], 1)
        self.assertEqual(mapped['coverage']['selected_agencies_without_verified_cnpj'], 1)
        self.assertEqual(mapped['coverage']['handles_sent_to_bot'], 2)

    def test_whatsapp_candidate_preflight_receives_only_selected_unsuppressed_cnpjs(self):
        selected = {'11111111000111', '81078289000163', 'SINA_PR_agencianova'}
        with patch.object(server, 'load_suppressed_cnpis', return_value={'81078289000163'}), \
             patch.object(server.wa_runner.engine, 'get_next_batch', create=True,
                          return_value=[{'cnpj': '11111111000111'}]) as batch:
            result = server.selected_whatsapp_candidates(2000, selected_ids=selected)

        self.assertEqual(result, [{'cnpj': '11111111000111'}])
        batch.assert_called_once_with(2000, selected_cnpjs={'11111111000111'})

    def test_whatsapp_engine_queue_in_temp_db_respects_exact_cnpjs_and_shared_dnc(self):
        with tempfile.TemporaryDirectory() as temp_dir:
            canonical = Path(temp_dir) / 'canonical.sqlite'
            central = Path(temp_dir) / 'central.sqlite'
            master = Path(temp_dir) / 'whatsapp.json'
            conn = sqlite3.connect(canonical)
            try:
                conn.executescript('''
                    CREATE TABLE agencias(cnpj TEXT,is_cliente INTEGER,tag_cliente TEXT);
                    CREATE TABLE crm_exclusions(cnpj TEXT,bloquear_disparos INTEGER);
                    INSERT INTO agencias VALUES('11111111000111',0,NULL);
                    INSERT INTO agencias VALUES('81078289000163',1,'CLIENTE ATIVO IARANDU (NAO PROSPECTAR)');
                ''')
            finally:
                conn.close()
            conn = sqlite3.connect(central)
            try:
                conn.executescript('''
                    CREATE TABLE agencies(cnpj TEXT,is_customer INTEGER,do_not_contact INTEGER);
                    CREATE TABLE interactions(identity_value TEXT,payload_json TEXT,channel TEXT,
                        direction TEXT,status TEXT,event_ts TEXT);
                    INSERT INTO agencies VALUES('81078289000163',1,1);
                ''')
            finally:
                conn.close()
            master.write_text(json.dumps([
                {'cnpj':'11111111000111','nome':'Synthetic Selected','whatsapp_ativo':True,
                 'whatsapp_id':'selected@c.us','classificacao_lead':'OURO','telefone_normalizado':'5541999990001'},
                {'cnpj':'22222222000122','nome':'Synthetic Outside','whatsapp_ativo':True,
                 'whatsapp_id':'outside@c.us','classificacao_lead':'OURO','telefone_normalizado':'5541999990002'},
                {'cnpj':'81078289000163','nome':'Synthetic Suppressed','whatsapp_ativo':True,
                 'whatsapp_id':'suppressed@c.us','classificacao_lead':'OURO','telefone_normalizado':'5541999990003'},
            ]), encoding='utf-8')
            engine_path = Path('/mnt/d/site-iarandu/executar_lote_whatsapp_seguro.py')
            if not engine_path.is_file():
                engine_path = Path(r'D:\site-iarandu\executar_lote_whatsapp_seguro.py')
            engine_spec = _real_spec_from_file_location('synthetic_whatsapp_engine', engine_path)
            engine = importlib.util.module_from_spec(engine_spec)
            engine_spec.loader.exec_module(engine)
            with patch.object(server, 'DB_PATH', str(canonical)), \
                 patch.object(server, 'CENTRAL_DB', str(central)), \
                 patch.object(server.wa_runner, 'engine', engine), \
                 patch.object(engine, 'CENTRAL_DB', str(central)), \
                 patch.object(engine, 'MASTER_JSON', str(master)):
                result = server.selected_whatsapp_candidates(
                    20, selected_ids={'11111111000111','81078289000163'})

        self.assertEqual([row['cnpj'] for row in result], ['11111111000111'])
        self.assertNotIn('22222222000122', {row['cnpj'] for row in result})
        self.assertNotIn('81078289000163', {row['cnpj'] for row in result})

    def test_cnpjless_duplicates_deduplicate_with_stable_selectable_identity(self):
        with tempfile.TemporaryDirectory() as temp_dir:
            db_path = Path(temp_dir) / 'agencies.sqlite'
            conn = sqlite3.connect(db_path)
            try:
                conn.executescript('''
                    CREATE TABLE agencias (cnpj TEXT,nome_fantasia TEXT,razao_social TEXT,municipio TEXT,uf TEXT,
                        website_oficial TEXT,email TEXT,telefone TEXT,whatsapp_agencia TEXT,instagram_handle TEXT,
                        dono_nome TEXT,total_valor_contratos REAL,status_enriquecimento TEXT);
                    CREATE TABLE agencias_sinapro_master (id INTEGER PRIMARY KEY,cnpj TEXT,nome TEXT,municipio TEXT,uf TEXT,website TEXT,
                        email TEXT,telefone TEXT,instagram TEXT,dono_nome TEXT,valor_contratos REAL,status TEXT);
                    INSERT INTO agencias_sinapro_master
                        (cnpj,nome,municipio,uf,website,email,telefone,instagram,dono_nome,valor_contratos,status) VALUES
                        ('','Estudio Aurora','Curitiba','PR','https://aurora-a.example','a@aurora.example','41999990001','@aurora.a','',0,'qualificada'),
                        ('','Estudio Aurora','Londrina','PR','https://aurora-b.example','b@aurora.example','43999990001','@aurora.b','',0,'qualificada'),
                        ('','Estudio Aurora','Curitiba','PR','https://aurora-a.example','a@aurora.example','41999990001','@aurora.a','',0,'qualificada');
                ''')
            finally:
                conn.close()
            with patch.object(server, 'DB_PATH', str(db_path)):
                first = server.agency_rows()
                second = server.agency_rows()
        self.assertEqual(len(first), 2)
        self.assertTrue(all(row['agency_id'].startswith('sina:') for row in first))
        self.assertEqual([row['agency_id'] for row in first], [row['agency_id'] for row in second])
        self.assertTrue(all(row['agency_identity_confidence'] == 'sinapro_row_id' for row in first))
        self.assertTrue(all(not server.is_valid_agency_id(row['agency_id']) for row in first))

    def test_cnpjless_drawer_route_is_addressable_by_stable_id(self):
        row = {'cnpj': '', 'agency_id': 'sina:synthetic', 'nome': 'Agencia Nova', 'uf': 'PR',
               'municipio': 'Curitiba', 'instagram': '@new.agency', 'email': 'nova@example.test', 'source': 'sinapro'}
        with patch.object(server, 'agency_rows', return_value=[row]):
            detail = self.get('/api/local/agency/sina:synthetic')
        self.assertEqual(detail['_http_status'], 200)

    def test_selection_accepts_cnpjless_stable_ids_and_keeps_cnpjs_separate(self):
        with tempfile.TemporaryDirectory() as temp_dir, \
             patch.object(server, 'agency_rows', return_value=[
                 {'cnpj': '11111111000111', 'agency_id': '11111111000111'},
                 {'cnpj': '', 'agency_id': 'sina:2', 'sinapro_id': 2, 'sinapro_ids': [2, 3]},
             ]), \
             patch.object(server, 'SELECTION_PATH', Path(temp_dir) / 'selection.json'):
            server.SELECTION_PATH.write_text(json.dumps({'agency_ids': ['11111111000111', 'sina:2']}), encoding='utf-8')
            self.assertEqual(server.selected_agency_ids(), {'11111111000111', 'sina:2'})
            selection_get = self.get('/api/local/agency-selection')
            self.assertEqual(selection_get['agency_ids'], ['11111111000111', 'sina:2'])
            self.assertEqual(selection_get['cnpjs'], ['11111111000111'])
            self.assertEqual(selection_get['sinapro_row_ids'], [2, 3])
            saved = self.post('/api/local/agency-selection', {'agency_ids': ['sina:2']})
        self.assertEqual(saved['_http_status'], 200)
        self.assertEqual(saved['selection']['cnpjs'], [])
        self.assertEqual(saved['selection']['sinapro_row_ids'], [2, 3])

    def test_email_runner_consumes_only_selected_cnpjs_from_canonical_ids(self):
        with tempfile.TemporaryDirectory() as temp_dir:
            agency_db = Path(temp_dir) / 'agencies.sqlite'
            central_db = Path(temp_dir) / 'central.sqlite'
            conn = sqlite3.connect(agency_db)
            try:
                conn.executescript('''
                    CREATE TABLE agencias_sinapro_master (id INTEGER PRIMARY KEY,cnpj TEXT,nome TEXT,municipio TEXT,uf TEXT,email TEXT,
                        dono_nome TEXT,website TEXT,fonte TEXT,valor_contratos REAL,status TEXT,instagram TEXT,telefone TEXT);
                    CREATE TABLE agencias (cnpj TEXT,is_cliente INTEGER,email TEXT);
                    CREATE TABLE crm_exclusions (id INTEGER PRIMARY KEY,cnpj TEXT,bloquear_disparos INTEGER);
                    CREATE TABLE disparos_email (cnpj TEXT,email TEXT);
                    INSERT INTO agencias_sinapro_master
                        (cnpj,nome,municipio,uf,email,dono_nome,website,fonte,valor_contratos,status,instagram,telefone) VALUES
                        ('11111111000111','Agencia CNPJ','Curitiba','PR','comercial@cnpj.example','','https://cnpj.example','sinapro',1,'qualificada','',''),
                        ('','Agencia Sem CNPJ','Curitiba','PR','comercial@semcnpj.example','','https://semcnpj.example','sinapro',1,'qualificada','','');
                ''')
            finally:
                conn.close()
            conn = sqlite3.connect(central_db)
            try:
                conn.executescript('''
                    CREATE TABLE interactions
                        (cnpj TEXT,channel TEXT,direction TEXT,identity_value TEXT,status TEXT,event_ts TEXT);
                    CREATE TABLE identities (cnpj TEXT,channel TEXT,value_norm TEXT);
                    CREATE TABLE agencies (cnpj TEXT,is_customer INTEGER,do_not_contact INTEGER);
                ''')
            finally:
                conn.close()

            class ApprovedEmailLoader:
                def create_module(self, spec):
                    return None

                def exec_module(self, module):
                    module.build_html_content = lambda *args, **kwargs: ''

            real_spec = _real_spec_from_file_location
            def spec_factory(name, location, *args, **kwargs):
                if name == 'iarandu_approved_email':
                    return importlib.util.spec_from_loader(name, ApprovedEmailLoader())
                return real_spec(name, location, *args, **kwargs)

            suppression_stub = SimpleNamespace(load_suppressed_cnpis=lambda *args, **kwargs: set())
            lock_stub = SimpleNamespace(LOCK_EX=1, LOCK_NB=2, LOCK_UN=8, flock=lambda *args, **kwargs: None)
            with patch.object(importlib.util, 'spec_from_file_location', side_effect=spec_factory), \
                 patch.dict(sys.modules, {'contact_suppressions': suppression_stub, 'fcntl': lock_stub}):
                spec = real_spec('isolated_email_runner_selection_test', Path(__file__).with_name('email_runner.py'))
                runner = importlib.util.module_from_spec(spec)
                spec.loader.exec_module(runner)
            cnpj = '11111111000111'
            with patch.object(runner, 'AGENCY_DB', agency_db), \
                 patch.object(runner, 'CENTRAL_DB', central_db), \
                 patch.object(runner, 'load_suppressed_cnpis', return_value=set()):
                no_cnpj_id = runner._sinapro_agency_id({'sinapro_id':2,'nome':'Agencia Sem CNPJ','uf':'PR','municipio':'Curitiba',
                    'website':'https://semcnpj.example','email':'comercial@semcnpj.example','instagram':'','telefone':''})
                self.assertEqual(no_cnpj_id, server.agency_id({'nome':'Agencia Sem CNPJ','uf':'PR','municipio':'Curitiba',
                    'site':'https://semcnpj.example','email':'comercial@semcnpj.example','instagram':'','telefone':'',
                    'sinapro_id':2}))
                selected = runner.eligible_emails(selected_agency_ids={cnpj, no_cnpj_id})
                unselected = runner.eligible_emails(selected_agency_ids={'22222222000122'})
        self.assertEqual({item['agency_id'] for item in selected}, {cnpj, no_cnpj_id})
        self.assertEqual(unselected, [])

    def test_synthetic_uf_channel_preflight_uses_same_agency_ids_and_daily_quotas(self):
        agencies = [
            {'cnpj': '11111111000111', 'agency_id': '11111111000111', 'nome': 'Agencia PR', 'uf': 'PR',
             'municipio': 'Curitiba', 'instagram': '@agency.pr', 'email': 'pr@example.test', 'whatsapp': '5541999990000'},
            {'cnpj': '81078289000163', 'agency_id': '81078289000163', 'nome': 'Trade Comunicacao', 'uf': 'PR',
             'municipio': 'Curitiba', 'instagram': '@agenciatrade', 'email': 'trade@example.test', 'whatsapp': '5541999990001'},
            {'cnpj': '', 'agency_id': None, 'nome': 'Agencia Nova', 'uf': 'PR',
             'municipio': 'Curitiba', 'instagram': '@new.agency', 'email': 'nova@example.test', 'whatsapp': ''},
            {'cnpj': '22222222000122', 'agency_id': '22222222000122', 'nome': 'Agencia SP', 'uf': 'SP',
             'municipio': 'Sao Paulo', 'instagram': '@agency.sp', 'email': 'sp@example.test', 'whatsapp': '5511999990000'},
        ]
        config = {**CONFIG, 'daily_targets': {'email': 10, 'whatsapp': 8, 'instagram_5050': 25, 'instagram_5051': 15}}
        chosen = {'11111111000111', '81078289000163'}
        email_state = {'script_found': True, 'active': False, 'eligible': 1, 'sent_today': 3,
                       'accepted_smtp_today': 3, 'reserved_today': 0, 'uncertain_today': 0,
                       'daily_target': 10, 'daily_remaining': 7}

        def ig_preflight(port, selected_ids, target, requested, passed_config):
            self.assertEqual(selected_ids, chosen)
            sent = {5050: 20, 5051: 2}[port]
            eligible = {5050: 1, 5051: 1}[port]
            allowed = min(target - sent, eligible, requested)
            return {'mapping': server.selected_instagram_handles(port, selected_ids),
                    'coverage': {'protocol': 'exact-handles-v1', 'can_start': allowed > 0,
                                 'eligible_pending': eligible, 'allowed_session_limit': allowed,
                                 'sent_today': sent, 'daily_remaining': target - sent},
                    'can_start': allowed > 0}

        with tempfile.TemporaryDirectory() as temp_dir, \
             patch.object(server, 'agency_rows', return_value=agencies), \
             patch.object(server, 'SELECTION_PATH', Path(temp_dir) / 'selection.json'), \
             patch.object(server, 'load_campaign_config', return_value=config), \
             patch.object(server, 'load_suppressed_cnpis', return_value={'81078289000163'}), \
             patch.object(server, 'load_suppressed_contacts', return_value={'instagram': set()}), \
             patch.object(server.email_runner, 'preflight', return_value=email_state) as email_preflight, \
             patch.object(server.wa_runner.engine, 'get_active_session', return_value='synthetic-ready'), \
             patch.object(server.wa_runner, 'sent_today', return_value=2), \
             patch.object(server, 'selected_whatsapp_candidates', return_value=[{'cnpj': '11111111000111'}]) as wa_candidates, \
             patch.object(server, '_instagram_campaign_preflight', side_effect=ig_preflight), \
             patch.object(server, '_local_json', return_value={'is_running': False, 'current_state': 'parado',
                   'kpi': {'pendentes': 10}}):
            email_candidates = self.get('/api/local/agency-selection-candidates?uf=PR&channel=email')
            wa_candidates_by_filter = self.get('/api/local/agency-selection-candidates?uf=PR&channel=whatsapp')
            self.assertEqual(set(email_candidates['agency_ids']), chosen)
            self.assertEqual(set(wa_candidates_by_filter['agency_ids']), chosen)
            saved = self.post('/api/local/agency-selection', {'agency_ids': sorted(chosen)})
            self.assertEqual(set(saved['selection']['agency_ids']), chosen)
            check = server._campaign_preflight(preview=True)

        email_preflight.assert_called_with(10, selected_agency_ids=chosen)
        wa_candidates.assert_called_with(2000, selected_ids=chosen)
        self.assertEqual(check['selection']['count'], 2)
        self.assertEqual(check['email']['limit'], 1)  # Trade is suppressed; remaining quota exceeds one eligible
        self.assertEqual(check['whatsapp']['daily_remaining'], 6)
        self.assertEqual(check['whatsapp']['eligible'], 1)
        self.assertEqual(check['instagram']['5050']['bot_coverage']['allowed_session_limit'], 1)
        self.assertEqual(check['instagram']['5051']['bot_coverage']['allowed_session_limit'], 1)
        self.assertEqual(check['instagram']['5050']['coverage']['selected_agencies_without_verified_cnpj'], 0)
        self.assertTrue(all(not item['can_start'] for item in check['instagram'].values()))

    def test_email_manual_route_uses_selected_cnpjs_and_preflight_limit_without_smtp(self):
        email_config = {**CONFIG, 'daily_targets': {**CONFIG['daily_targets'], 'email': 10}}
        runner_state = {'script_found': True, 'active': False, 'eligible': 7, 'sent_today': 3,
                        'accepted_smtp_today': 2, 'reserved_today': 1, 'daily_target': 10,
                        'daily_remaining': 7}
        chosen = ['12345678901234', '22345678901234']
        with tempfile.TemporaryDirectory() as temp_dir, \
             patch.object(server, 'load_campaign_config', return_value=email_config), \
             patch.object(server, 'agency_rows', return_value=[{'cnpj': cnpj, 'agency_id': cnpj} for cnpj in chosen]), \
             patch.object(server, 'SELECTION_PATH', Path(temp_dir) / 'selected.json'), \
             patch.object(server.email_runner, 'preflight', return_value=runner_state) as preflight, \
             patch.object(server, 'DATA_DIR', Path(temp_dir)), \
             patch.object(server.subprocess, 'Popen', return_value=type('MockProcess', (), {'pid': 456})()) as popen:
            saved = self.post('/api/local/agency-selection', {'cnpjs': chosen})
            self.assertEqual(set(saved['selection']['cnpjs']), set(chosen))
            result = self.post('/api/action/start-email')
            command = popen.call_args.args[0]
            selection_file = Path(command[command.index('--cnpjs-file') + 1])
            runner_selection = json.loads(selection_file.read_text(encoding='utf-8'))['agency_ids']
        self.assertEqual(result['status'], 'iniciado')
        self.assertEqual(result['limit'], 7)
        self.assertEqual(preflight.call_count, 2)  # route gate plus immediate pre-launch check
        preflight.assert_called_with(10, selected_agency_ids=set(chosen))
        self.assertIn('--limit', command)
        self.assertEqual(command[command.index('--limit') + 1], '7')
        self.assertEqual(runner_selection, chosen)

    def test_email_route_blocks_empty_selection_before_popen(self):
        runner_state = {'script_found': True, 'active': False, 'eligible': 0, 'sent_today': 0,
                        'accepted_smtp_today': 0, 'reserved_today': 0, 'daily_target': 10,
                        'daily_remaining': 10}
        email_config = {**CONFIG, 'daily_targets': {**CONFIG['daily_targets'], 'email': 10}}
        with patch.object(server, 'load_campaign_config', return_value=email_config), \
             patch.object(server, 'selected_agency_ids', return_value=set()), \
             patch.object(server.email_runner, 'preflight', return_value=runner_state), \
             patch.object(server.subprocess, 'Popen') as popen:
            response = self.post('/api/action/start-email')
        self.assertEqual(response['status'], 'blocked')
        self.assertEqual(response['_http_status'], 409)
        self.assertEqual(popen.call_count, 0)

    def test_campaign_preflight_email_respects_preview_and_reports_smtp_acceptance(self):
        email_config = {**CONFIG, 'daily_targets': {**CONFIG['daily_targets'], 'email': 10}}
        runner_state = {'script_found': True, 'active': False, 'eligible': 7, 'sent_today': 3,
                        'accepted_smtp_today': 2, 'reserved_today': 1, 'daily_target': 10,
                        'daily_remaining': 7}
        with patch.object(server, 'load_campaign_config', return_value=email_config), \
             patch.object(server, 'selected_agency_ids', return_value={'12345678901234'}), \
             patch.object(server.email_runner, 'preflight', return_value=runner_state), \
             patch.object(server.wa_runner.engine, 'get_active_session', return_value='mock-session'), \
             patch.object(server.wa_runner, 'sent_today', return_value=0), \
             patch.object(server, 'selected_whatsapp_candidates', return_value=[{'cnpj': '12345678901234'}]), \
             patch.object(server, 'selected_instagram_handles', return_value={'handles': [], 'coverage': {}}), \
             patch.object(server, '_local_json', return_value={'is_running': False, 'kpi': {}}), \
             patch.object(server, '_instagram_campaign_preflight', side_effect=RuntimeError('old build')):
            check = server._campaign_preflight(preview=True)
        email = check['email']
        self.assertFalse(email['can_start'])
        self.assertIn('Preview', email['blocked_reason'])
        self.assertEqual(email['accepted_smtp_today'], 2)
        self.assertEqual(email['daily_remaining'], 7)
        self.assertEqual(email['eligible'], 7)

    def test_email_preflight_blocks_active_campaign_and_zero_budget(self):
        base = {'script_found': True, 'active': False, 'eligible': 4, 'sent_today': 10,
                'accepted_smtp_today': 10, 'reserved_today': 0, 'daily_target': 10,
                'daily_remaining': 0}
        with patch.object(server, 'selected_agency_ids', return_value={'12345678901234'}), \
             patch.object(server.email_runner, 'preflight', side_effect=[{**base, 'active': True}, base]):
            active = server._email_campaign_preflight(10)
            exhausted = server._email_campaign_preflight(10)
        self.assertFalse(active['can_start'])
        self.assertIn('execucao', active['blocked_reason'])
        self.assertFalse(exhausted['can_start'])
        self.assertEqual(exhausted['limit'], 0)

    def test_legacy_test_batch_endpoint_is_disabled(self):
        with patch.object(server, 'load_campaign_config', return_value=CONFIG):
            result = self.post('/api/action/start-test-batch')
        self.assertEqual(result['_http_status'], 409)
        self.assertEqual(result['status'], 'blocked')

    def test_powershell_bridge_round_trips_quoted_json_to_mock_http(self):
        received = {}

        class EchoHandler(BaseHTTPRequestHandler):
            def do_POST(self):
                raw = self.rfile.read(int(self.headers.get('Content-Length', '0')))
                received['payload'] = json.loads(raw.decode('utf-8'))
                body = json.dumps({'echo': received['payload'], 'accepted': True}, ensure_ascii=False).encode('utf-8')
                self.send_response(200)
                self.send_header('Content-Type', 'application/json; charset=utf-8')
                self.send_header('Content-Length', str(len(body)))
                self.end_headers()
                self.wfile.write(body)

            def log_message(self, format, *args):
                pass

        mock_http = HTTPServer(('127.0.0.1', 0), EchoHandler)
        mock_http_thread = threading.Thread(target=mock_http.serve_forever, daemon=True)
        mock_http_thread.start()
        original_run = server.subprocess.run
        payload = {'message': 'Aspas "duplas", apóstrofo \' simples, barra \\ e acentuação çãõ',
                   'nested': {'quote': '"; $env:SHOULD_NOT_RUN', 'items': [1, True, None]}}

        def run_real_powershell(command, **kwargs):
            ps_script = command[-1]
            self.assertIn('Invoke-RestMethod', ps_script)
            self.assertNotIn('curl.exe', ps_script.lower())
            command = list(command)
            command[0] = 'powershell.exe'
            return original_run(command, **kwargs)

        try:
            with patch.object(server.urllib.request, 'urlopen', side_effect=URLError('force bridge path')), \
                 patch.object(server.Path, 'is_file', return_value=True), \
                 patch.object(server.subprocess, 'run', side_effect=run_real_powershell):
                result = server._local_json(mock_http.server_port, '/echo', 'POST', payload, timeout=5)
        finally:
            mock_http.shutdown()
            mock_http.server_close()
            mock_http_thread.join(timeout=2)
        self.assertEqual(received['payload'], payload)
        self.assertEqual(result, {'echo': payload, 'accepted': True})

    def test_saved_instagram_target_is_clamped_without_losing_other_channels(self):
        stored = {'daily_targets': {'email': 27, 'whatsapp': 11, 'instagram_5050': 50, 'instagram_5051': 15}}
        with tempfile.TemporaryDirectory() as temp_dir, \
             patch.object(server, 'CONFIG_PATH', Path(temp_dir) / 'campaign_config.json'):
            server.CONFIG_PATH.write_text(json.dumps(stored), encoding='utf-8')
            config = server.load_campaign_config()
            self.assertEqual(config['daily_targets'], {
                'email': 27, 'whatsapp': 11, 'instagram_5050': 50, 'instagram_5051': 15,
            })

    def test_campaign_config_saves_each_daily_limit_at_its_supported_maximum(self):
        desired = {'email': 30, 'whatsapp': 24, 'instagram_5050': 50, 'instagram_5051': 15}
        with tempfile.TemporaryDirectory() as temp_dir, \
             patch.object(server, 'CONFIG_PATH', Path(temp_dir) / 'campaign_config.json'):
            saved = server.save_campaign_config({'daily_targets': desired,
                                                 'schedule': {'enabled': False, 'weekdays': [0,1,2,3,4]}})
            self.assertEqual(saved['daily_targets'], desired)
            self.assertFalse(saved['schedule']['enabled'])
            self.assertEqual(server.load_campaign_config()['daily_targets'], desired)

    def test_instagram_start_rechecks_preflight_and_sends_same_handles_and_allowed_limit(self):
        handles = ['alpha.agency', 'beta.agency']
        check = {'mapping': {'handles': handles},
                 'coverage': {'protocol': 'exact-handles-v1', 'can_start': True,
                              'eligible_pending': 12, 'allowed_session_limit': 12},
                 'bot_daily_cap': 50, 'effective_daily_target': 50,
                 'can_start': True}
        with patch.object(server, 'selected_agency_ids', return_value={'12345678901234'}), \
             patch.object(server, '_local_json', side_effect=[{'is_running': False},
                                                               {'is_running': False},
                                                               {'status': 'running'}]) as local_json, \
             patch.object(server, '_instagram_campaign_preflight', return_value=check) as preflight:
            result = server._start_instagram(5050, 17, 50, CONFIG)
        preflight.assert_called_once_with(5050, {'12345678901234'}, 50, 17, CONFIG)
        start_call = local_json.call_args_list[2]
        sent_payload = start_call.args[3]
        self.assertEqual(sent_payload['handles'], handles)
        self.assertEqual(sent_payload['limite_diario'], 12)
        self.assertEqual(sent_payload['requested_limit'], 12)
        self.assertEqual(result['allowed_session_limit'], 12)

    def test_instagram_preflight_sends_exact_protocol_and_legacy_template_id(self):
        selected = {'12345678901234'}
        template = {'template_id': server.BOT_TEMPLATE_ID,
                    'content_sha256': server.BOT_TEMPLATE_SHA256,
                    'path': server.BOT_TEMPLATE_PATHS[5050]}
        response = {'status': 'ok',
                    'coverage': {'protocol': 'exact-handles-v1', 'can_start': True,
                                 'eligible_pending': 2, 'allowed_session_limit': 1,
                                 'reason_contract': 'selected-handle-reasons-v1',
                                 'reason_counts': {'pending_eligible': 1, 'already_sent_today': 0},
                                 'handle_statuses': [{'handle': 'agency.pr', 'reason': 'pending_eligible'}]},
                    'template': template}
        with patch.object(server, '_instagram_runtime_contract', return_value={'daily_cap': server.BOT_DAILY_CAP}), \
             patch.object(server, 'selected_instagram_handles',
                          return_value={'handles': ['agency.pr'], 'coverage': {}}), \
             patch.object(server, '_local_json', return_value=response) as local_json:
            check = server._instagram_campaign_preflight(5050, selected, 50, 1, CONFIG)
        payload = local_json.call_args.args[3]
        self.assertEqual(payload['filter_protocol'], 'exact-handles-v1')
        self.assertEqual(payload['template_id'], server.BOT_TEMPLATE_ID)
        self.assertEqual(payload['handles'], ['agency.pr'])
        self.assertEqual(check['coverage']['allowed_session_limit'], 1)

    def test_instagram_runtime_contract_requires_correct_per_account_db_and_template_files(self):
        def build_info(port, template_path=None, can_start=True):
            return {
                'status': 'ok', 'filter_protocol': 'exact-handles-v1',
                'daily_cap': server.BOT_DAILY_CAP,
                'composer_guard_protocol': 'cancel-on-interaction-v1',
                'recipient_guard_protocol': 'exact-recipient-before-submit-v1',
                'database': {'path': server.BOT_DATABASE_PATHS[port], 'sha256': 'a' * 64,
                             'device': 1, 'file_id': port},
                'template': {'available': True, 'template_id': server.BOT_TEMPLATE_ID,
                             'content_sha256': server.BOT_TEMPLATE_SHA256,
                             'path': template_path or server.BOT_TEMPLATE_PATHS[port]},
                'identity_guard': {
                    'protocol': 'account-identity-v1',
                    'expected_handle': '@maldonadodiego55' if port == 5050 else '@iarandu.br',
                    'can_start': can_start,
                    'source_profile': server.LOGIN_FIREFOX_PROFILES[port],
                    'runtime_profile': server.BOT_RUNTIME_PROFILES[port],
                },
            }

        with patch.object(server, '_local_json', return_value=build_info(5050)) as local_json:
            result = server._instagram_runtime_contract(5050)
        self.assertEqual(local_json.call_count, 1)
        self.assertEqual(result['template']['path'], server.BOT_TEMPLATE_PATHS[5050])

        with patch.object(server, '_local_json', return_value=build_info(5051, can_start=False)):
            with self.assertRaisesRegex(RuntimeError, 'identidade'):
                server._instagram_runtime_contract(5051)

        unguarded = build_info(5050)
        unguarded.pop('recipient_guard_protocol')
        with patch.object(server, '_local_json', return_value=unguarded):
            with self.assertRaisesRegex(RuntimeError, 'destinatário'):
                server._instagram_runtime_contract(5050)

        with patch.object(server, '_local_json', return_value=build_info(5050, server.BOT_TEMPLATE_PATHS[5051])):
            with self.assertRaisesRegex(RuntimeError, 'template path/content'):
                server._instagram_runtime_contract(5050)

        wrong_cap = build_info(5050)
        wrong_cap['daily_cap'] = 25
        with patch.object(server, '_local_json', return_value=wrong_cap):
            with self.assertRaisesRegex(RuntimeError, 'cap efetivo do bot'):
                server._instagram_runtime_contract(5050)

    def test_bot_service_launcher_disables_automatic_browser_open(self):
        with patch.object(server, '_local_json', side_effect=[URLError('not running'), {'status': 'ok'}]), \
             patch.object(server.subprocess, 'run') as run_process:
            result = server._ensure_bot_service(5050)

        self.assertEqual(result, {'status': 'ok'})
        command = run_process.call_args.args[0]
        self.assertEqual(command[0], 'powershell.exe')
        self.assertIn("'--edition','comercial','--no-open-browser'", command[-1])
        self.assertIn(r"$env:IARTICULE_DB_PATH='C:\Users\user\AppData\Local\iArticuleBot_Comercial\comercial.db'", command[-1])

    def test_bot_service_launcher_uses_per_port_db_and_daily_caps(self):
        expected = {
            5050: (r"C:\Users\user\AppData\Local\iArticuleBot_Comercial\comercial.db", '50', 'comercial', r"C:\Users\user\AppData\Local\iArticuleBot_Comercial\custom_templates.json"),
            5051: (r"C:\Users\user\AppData\Local\iArticuleBot_Oficial\iArticuleBot_Comercial\comercial.db", '50', 'oficial', r"C:\Users\user\AppData\Local\iArticuleBot_Oficial\iArticuleBot_Comercial\custom_templates.json"),
        }
        for port, (db_path, cap, edition, templates_path) in expected.items():
            with self.subTest(port=port), \
                 patch.dict('os.environ', {}, clear=True), \
                 patch.object(server, '_local_json', side_effect=[URLError('not running'), {'status': 'ok'}]), \
                 patch.object(server.subprocess, 'run') as run_process:
                server._ensure_bot_service(port)
            script = run_process.call_args.args[0][-1]
            self.assertIn(f"$env:IARTICULE_DB_PATH='{db_path}';", script)
            self.assertIn(f"$env:IARTICULE_TEMPLATES_PATH='{templates_path}';", script)
            self.assertIn(f"$env:IARTICULE_RUNTIME_PROFILE_DIR='{server.BOT_RUNTIME_PROFILES[port]}';", script)
            self.assertIn("$env:IARTICULE_TEMPLATE_ID='custom_1792432503883';", script)
            self.assertIn(f"$env:IARANDU_CANONICAL_DB='{server.BOT_CANONICAL_DB_PATH}';", script)
            self.assertIn(f"$env:IARANDU_CENTRAL_DB='{server.BOT_CENTRAL_DB_PATH}';", script)
            self.assertIn(f"$env:IARTICULE_DAILY_CAP='{cap}';", script)
            self.assertIn(f"$env:BOT_EDITION='{edition}';", script)
            self.assertIn(f"'--edition','{edition}','--no-open-browser'", script)

    def test_bot_service_launcher_rejects_shared_generic_db_override(self):
        explicit = r"D:\Synthetic\shared.sqlite"
        with patch.dict('os.environ', {'IARTICULE_DB_PATH': explicit}, clear=True), \
             patch.object(server, '_local_json', side_effect=URLError('not running')), \
             patch.object(server.subprocess, 'run') as run_process:
            with self.assertRaisesRegex(RuntimeError, 'caminho inesperado bloqueado'):
                server._ensure_bot_service(5051)
        run_process.assert_not_called()

    def test_login_firefox_command_uses_onedir_browser_and_account_profile(self):
        install_dir = r'C:\Staging ArticulaPR'
        profiles = {
            5050: r'C:\Users\user\AppData\Roaming\Mozilla\Firefox\Profiles\1nz82aud.default-release',
            5051: r'C:\Users\user\AppData\Roaming\Mozilla\Firefox\Profiles\k7852du9.default-release-1',
        }
        completed = type('Completed', (), {'returncode': 0})()
        for port, profile in profiles.items():
            with self.subTest(port=port), \
                 patch.dict('os.environ', {'IARTICULE_BOT_INSTALL_DIR': install_dir}, clear=True), \
                 patch.object(server.subprocess, 'run', return_value=completed) as run_process:
                result = server._open_login_firefox({'port': port})

            command = run_process.call_args.args[0]
            script = command[-1]
            expected_browser = install_dir + r'\_internal\playwright_browsers\firefox-1543\firefox\firefox.exe'
            self.assertEqual(result['status'], 'requested')
            self.assertEqual(result['port'], port)
            self.assertEqual(result['browser'], expected_browser)
            self.assertEqual(result['profile'], profile)
            self.assertIn(expected_browser, script)
            self.assertIn(profile, script)
            self.assertIn('Start-Process -FilePath $browser', script)
            self.assertIn("'-no-remote','-profile'", script)
            self.assertIn("('\"{0}\"' -f $firefoxProfile)", script)
            run_process.assert_called_once_with(
                ['powershell.exe', '-NoProfile', '-NonInteractive', '-Command', script],
                check=True, stdin=server.subprocess.DEVNULL, capture_output=True, timeout=15,
            )

    def test_login_firefox_uses_documented_install_fallback(self):
        completed = type('Completed', (), {'returncode': 0})()
        with patch.dict('os.environ', {}, clear=True), \
             patch.object(server.subprocess, 'run', return_value=completed) as run_process:
            result = server._open_login_firefox({'port': 5050})

        self.assertTrue(result['browser'].startswith(server.BOT_INSTALL_DIR_FALLBACK + '\\'))
        self.assertIn(server.LOGIN_FIREFOX_PROFILES[5050], run_process.call_args.args[0][-1])

    def test_login_firefox_rejects_invalid_port_without_subprocess(self):
        with patch.object(server.subprocess, 'run') as run_process:
            with self.assertRaises(ValueError):
                server._open_login_firefox({'port': 5052})
        run_process.assert_not_called()


class OfficialMessageConfigurationTests(unittest.TestCase):
    def test_save_syncs_three_channels_and_keeps_each_account_file_separate(self):
        with tempfile.TemporaryDirectory() as temp:
            root = Path(temp)
            personal = root / 'personal' / 'custom_templates.json'
            official = root / 'official' / 'custom_templates.json'
            for path in (personal, official):
                path.parent.mkdir(parents=True)
                path.write_text(json.dumps({
                    server.BOT_TEMPLATE_ID: {'id': server.BOT_TEMPLATE_ID, 'conteudo': 'old', 'nome': 'official'},
                    'other': {'conteudo': 'keep'}
                }), encoding='utf-8')
            messages_path = root / 'channel_messages.json'
            messages = {
                'instagram_5050': 'IG pessoal {empresa} {var1|var2}',
                'instagram_5051': 'IG oficial {empresa} {var1|var2}',
                'whatsapp': 'WhatsApp {empresa} {var1|var2}',
                'email': '{Olá|Oi} {empresa}.\n\nCorpo de teste.',
                'email_subject': 'Apresentação | {empresa}',
            }
            with patch.object(server, 'MESSAGES_PATH', messages_path), \
                 patch.dict(server.BOT_TEMPLATE_PATHS, {5050: str(personal), 5051: str(official)}):
                saved = server.save_channel_messages({'messages': messages})

            self.assertEqual(saved['instagram_5050'], messages['instagram_5050'])
            self.assertEqual(saved['instagram_5051'], messages['instagram_5051'])
            self.assertEqual(saved['whatsapp'], messages['whatsapp'])
            self.assertEqual(saved['email'], messages['email'])
            self.assertEqual(saved['email_subject'], messages['email_subject'])
            self.assertEqual(json.loads(personal.read_text(encoding='utf-8'))[server.BOT_TEMPLATE_ID]['conteudo'], messages['instagram_5050'])
            self.assertEqual(json.loads(official.read_text(encoding='utf-8'))[server.BOT_TEMPLATE_ID]['conteudo'], messages['instagram_5051'])
            self.assertEqual(json.loads(personal.read_text(encoding='utf-8'))['other']['conteudo'], 'keep')
            self.assertEqual(json.loads(official.read_text(encoding='utf-8'))['other']['conteudo'], 'keep')
            self.assertNotEqual(personal, official)

    def test_message_save_requires_company_placeholder_and_changes_nothing(self):
        with tempfile.TemporaryDirectory() as temp:
            root = Path(temp)
            paths = {port: root / str(port) / 'custom_templates.json' for port in (5050, 5051)}
            for path in paths.values():
                path.parent.mkdir(parents=True)
                path.write_text(json.dumps({server.BOT_TEMPLATE_ID: {'conteudo': 'before'}}), encoding='utf-8')
            with patch.object(server, 'MESSAGES_PATH', root / 'channel_messages.json'), \
                 patch.dict(server.BOT_TEMPLATE_PATHS, {port: str(path) for port, path in paths.items()}):
                with self.assertRaises(ValueError):
                    server.save_channel_messages({'messages': {
                        'instagram_5050': 'sem empresa', 'instagram_5051': 'ok {empresa}',
                        'whatsapp': 'ok {empresa}', 'email': 'ok {empresa}',
                        'email_subject': 'ok {empresa}'
                    }})
            self.assertFalse((root / 'channel_messages.json').exists())
            self.assertTrue(all(json.loads(path.read_text(encoding='utf-8'))[server.BOT_TEMPLATE_ID]['conteudo'] == 'before'
                                for path in paths.values()))


if __name__ == '__main__':
    unittest.main()
