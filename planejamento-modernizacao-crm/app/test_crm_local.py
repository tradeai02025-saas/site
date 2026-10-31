"""Testes sem envio e sem escrita nos bancos comerciais."""

import importlib.util
import sqlite3
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch


HERE = Path(__file__).resolve().parent


def module(name, filename):
    spec = importlib.util.spec_from_file_location(name, HERE / filename)
    value = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(value)
    return value


class CRMReadTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.crm = module('crm_visual_test', 'server.py')

    def test_summary_matches_canonical_rows(self):
        rows = self.crm.agency_rows()
        data = self.crm.summary()
        self.assertEqual(data['agencies'], len(rows))
        self.assertEqual(data['with_phone'], sum(bool(r.get('telefone') or r.get('whatsapp')) for r in rows))

    def test_sinapro_only_detail_is_readable(self):
        detail = self.crm.agency_detail('00748924000170')
        self.assertTrue(detail['sinapro_only'])
        self.assertEqual(detail['agencia']['nome_fantasia'], '2 Mil Publicidade')

    def test_email_preflight_fails_closed_when_route_script_missing(self):
        check = self.crm.preflight()['email']
        if not check['script_found']:
            self.assertFalse(check['can_start'])

    def test_old_deal_stage_is_not_a_confirmed_reply(self):
        with tempfile.TemporaryDirectory() as folder:
            database = str(Path(folder) / 'isolated.db')
            conn = sqlite3.connect(database)
            conn.execute('CREATE TABLE crm_history (cnpj TEXT, descricao TEXT, metadata_json TEXT, criado_em TEXT, tipo TEXT)')
            conn.execute('CREATE TABLE crm_deals (cnpj TEXT, etapa TEXT, proximo_passo TEXT, prazo_proximo_passo TEXT, responsavel TEXT, prioridade TEXT)')
            conn.execute("INSERT INTO crm_deals VALUES ('12345678000190','resposta','aguardar retorno',NULL,'Franciele','ALTA')")
            conn.commit()
            conn.close()
            agency = [{'cnpj':'12345678000190','nome':'Agência de teste','municipio':'Curitiba','uf':'PR'}]
            with patch.object(self.crm, 'DB_PATH', database), patch.object(self.crm, 'agency_rows', return_value=agency):
                self.assertEqual(self.crm.get_replies(), [])
                conn = sqlite3.connect(database)
                conn.execute("INSERT INTO crm_history VALUES ('12345678000190','Resposta verificada','{\"canal\":\"email\"}','2026-10-25 10:00','resposta_confirmada')")
                conn.commit()
                conn.close()
                self.assertEqual(len(self.crm.get_replies()), 1)


class WhatsAppRunnerTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.runner = module('wa_safe_test', 'whatsapp_runner.py')

    def test_stop_after_first_message(self):
        wa = self.runner
        with tempfile.TemporaryDirectory() as folder:
            lock = Path(folder) / 'dispatch.lock'
            leads = [{'nome': 'A', 'telefone': '11111111111'}, {'nome': 'B', 'telefone': '22222222222'}]
            sent = []

            def send(chat_id, message):
                sent.append(chat_id)
                lock.write_text('STOP', encoding='utf-8')
                return {'ok': True}

            with patch.object(wa, 'LOCK', lock), \
                 patch.object(wa, 'load_suppressed_cnpis', return_value=set()), \
                 patch.object(wa.engine, 'get_next_batch', return_value=leads) as get_next_batch, \
                 patch.object(wa.engine, 'gerar_mensagem', return_value='mensagem de teste'), \
                 patch.object(wa.engine, 'send_message', side_effect=send), \
                 patch.object(wa.engine, 'record_dispatch'), \
                 patch.object(wa.engine, 'write_live_status'), \
                 patch.object(wa.engine, 'log'):
                self.assertEqual(wa.run(2, selected_cnpjs=['00000000000000']), 0)
            get_next_batch.assert_called_once_with(
                2000, selected_cnpjs={'00000000000000'}
            )
            self.assertEqual(len(sent), 1)
            self.assertFalse(lock.exists())


if __name__ == '__main__':
    unittest.main()
