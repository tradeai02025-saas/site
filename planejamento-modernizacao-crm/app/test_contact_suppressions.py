import sqlite3
import tempfile
import unittest
from pathlib import Path

from contact_suppressions import load_suppressed_cnpis


class ContactSuppressionTests(unittest.TestCase):
    def test_manual_binder_dnc_and_canonical_trade_are_shared_suppressions(self):
        with tempfile.TemporaryDirectory() as temp_dir:
            canonical = Path(temp_dir) / 'canonical.sqlite'
            central = Path(temp_dir) / 'central.sqlite'
            conn = sqlite3.connect(canonical)
            try:
                conn.executescript('''
                    CREATE TABLE agencias(cnpj TEXT,is_cliente INTEGER,tag_cliente TEXT);
                    CREATE TABLE crm_exclusions(cnpj TEXT,bloquear_disparos INTEGER);
                    INSERT INTO agencias VALUES
                        ('81078289000163',1,'CLIENTE ATIVO IARANDU (NÃO PROSPECTAR)'),
                        ('72190242000104',0,NULL),
                        ('72190242000368',0,NULL);
                ''')
            finally:
                conn.close()
            conn = sqlite3.connect(central)
            try:
                conn.executescript('''
                    CREATE TABLE agencies(cnpj TEXT,is_customer INTEGER,do_not_contact INTEGER);
                    INSERT INTO agencies VALUES('81078289000163',1,1);
                ''')
            finally:
                conn.close()

            suppressed = load_suppressed_cnpis(canonical, central)

        self.assertIn('81078289000163', suppressed)
        self.assertIn('72190242000104', suppressed)
        self.assertIn('72190242000368', suppressed)
        self.assertIn('dnc_audit_preventivo', suppressed['72190242000104'])


if __name__ == '__main__':
    unittest.main()
