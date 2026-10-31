# RELATÓRIO DE AUDITORIA, SANEAMENTO E PARECER TÉCNICO DE DISPATCH LOGS
**IArandú Revenue OS — Plano Diretor CRM (Versão 2 — Homologada)**  
**Data:** 27 de Outubro de 2026 | **Auditor Responsável:** Antigravity-2  
**Destinatários:** Diego Maldonado / Segundo Cérebro / Codex-3  
**Modo:** 100% Read-Only (Zero Escrita em Bancos, Zero Disparos, Zero Créditos Externos)  
**Caminho Local do Documento:**  
* `D:\site-iarandu\RELATORIO_AUDITORIA_ELEGIBILIDADE_SANEAMENTO_846.md`  
* `D:\site-iarandu\planejamento-modernizacao-crm\docs\RELATORIO_AUDITORIA_ELEGIBILIDADE_SANEAMENTO_846.md`

---

## 1. PARECER TÉCNICO READ-ONLY: `app/dispatch_logs.py` e `server.py` (GATE CODEX-3)

A inspeção estática e os testes de regressão automatizados (`50 tests OK`, incluindo `test_dispatch_logs.py` e `test_campaign_start_routes.py`) confirmam que a implementação entregue pelo Codex-3 cumpre rigorosamente os 9 requisitos centrais do contrato.

### Matriz de Conformidade do Adaptador

| Requisito do Contrato | Status | Evidência no Código / Linhas | Severidade |
| :--- | :---: | :--- | :---: |
| **1. IDs e Aliases sem CNPJ** | **APROVADO** | [`dispatch_logs.py:35-42`](file:///mnt/d/site-iarandu/planejamento-modernizacao-crm/app/dispatch_logs.py#L35-L42): Extrai CNPJ (14 dígitos) ou `sina:\d+`. ID desconhecido retorna `(None, None)`, evitando chave compartilhada `DESCONHECIDO`. *(Nota: para suportar fingerprints sintéticos `sina:fp_<hash>`, recomenda-se ampliar regex para `sina:[a-zA-Z0-9_]+`).* | Baixa (Melhoria Futura) |
| **2. Caminhos Dinâmicos via `build-info`** | **APROVADO** | [`server.py:478-508`](file:///mnt/d/site-iarandu/planejamento-modernizacao-crm/app/server.py#L478-L508): `_dispatch_log_ig_sources()` consulta `/api/bot/build-info` em tempo de execução, valida `device` e `file_id`, garante que 5050 e 5051 não compartilham o mesmo arquivo físico e converte paths Windows para WSL (`/mnt/c/...`). Não assume contagens zero se offline. | Sem impeditivo |
| **3. Conexões Estritamente Read-Only** | **APROVADO** | [`dispatch_logs.py:25`](file:///mnt/d/site-iarandu/planejamento-modernizacao-crm/app/dispatch_logs.py#L25): `sqlite3.connect(path.as_uri() + '?mode=ro', uri=True, timeout=5)`. Verifica existência prévia da tabela sem criar arquivos novos. | Sem impeditivo |
| **4. Zero Efeito Colateral em GET** | **APROVADO** | [`server.py:1500-1507`](file:///mnt/d/site-iarandu/planejamento-modernizacao-crm/app/server.py#L1500-L1507): Endpoint `/api/local/dispatch-logs` apenas consome dados e responde JSON. Sem criação de locks, sem escrita em banco, sem chamadas externas ativas. | Sem impeditivo |
| **5. Status `uncertain` pós-tentativa** | **APROVADO** | [`dispatch_logs.py:69-70, 107-109`](file:///mnt/d/site-iarandu/planejamento-modernizacao-crm/app/dispatch_logs.py#L69-L70): Se houver `timeout` no detalhe ou status `incerto/uncertain`, canonicaliza como `uncertain` e define `reason_code = 'TIMEOUT_AFTER_ATTEMPT'`. Não classifica como falha segura nem autoriza reenvio cego. | Sem impeditivo |
| **6. Preservação de Timezone Real** | **APROVADO** | [`dispatch_logs.py:45-56, 104-105`](file:///mnt/d/site-iarandu/planejamento-modernizacao-crm/app/dispatch_logs.py#L45-L56): Preserva `timestamp_raw`. Se não houver offset explícito no ISO, `timestamp_tz` e `timezone_provenance` retornam `None`. Nunca assume fuso local ou UTC sem prova. | Sem impeditivo |
| **7. Sem Texto Histórico Inventado** | **APROVADO** | [`dispatch_logs.py:102-103, 129, 175, 194`](file:///mnt/d/site-iarandu/planejamento-modernizacao-crm/app/dispatch_logs.py#L102-L103): Fontes sem mensagem armazenada retornam `body_text = None` e `body_status = 'desconhecido'`. No Instagram, se houve avanço de etapas (`msg2+`), não assume o template atual. | Sem impeditivo |
| **8. Deduplicação por Origem + Conta** | **APROVADO** | [`dispatch_logs.py:206-215`](file:///mnt/d/site-iarandu/planejamento-modernizacao-crm/app/dispatch_logs.py#L206-L215): Chave de dedup composta por `(source_system, source_event_key, sender_instance_id, source_record_id)`. Replicas entre contas independentes nunca são descartadas. | Sem impeditivo |
| **9. Resposta Parcial quando Indisponível** | **APROVADO** | [`dispatch_logs.py:263`](file:///mnt/d/site-iarandu/planejamento-modernizacao-crm/app/dispatch_logs.py#L263): Se qualquer fonte falhar ou estiver offline, status da resposta torna-se `'partial'` com lista detalhada em `source_warnings`. Nenhuma fonte ausente é computada como zero silencioso. | Sem impeditivo |

> [!TIP]
> **PARECER FINAL DO AUDITOR**: **GATE LIBERADO PARA O CODEX-3**. Não há impeditivos bloqueantes de segurança, concorrência ou integridade nos arquivos `app/dispatch_logs.py` e `app/server.py`.

---

## 2. DECOMPOSIÇÃO RIGOROSA DA TELEFONIA (EXPLICAÇÃO DOS 846 REGISTROS E DOS 13 ATÍPICOS)

Para sanar qualquer ambiguidade entre **tipo de linha física** (formato numérico) e **capacidade de canal** (WhatsApp comprovado), realizamos a auditoria isolada registro por registro:

### 2.1 Formato Físico das Linhas Telefônicas (846 Registros = 846 Agências)
* **Fixo Comercial Padrão (10 dígitos - DDD + 8 dígitos iniciando em 2 a 5)**: **547 agências** (64,66%)
* **Celular Padrão Anatel (11 dígitos - DDD + 9 dígitos iniciando em 9)**: **198 agências** (23,40%)
* **Celular / Móvel Legado (10 dígitos - DDD + 8 dígitos iniciando em 7 a 9)**: **88 agências** (10,40%)
* **Linhas Fixas com Máscara Atípica ou Gratuitas (10 dígitos)**: **10 agências** (1,18%)
* **Registros Malformados / Múltiplos (Fora do Padrão E.164)**: **3 agências** (0,35%)
* **TOTAL CONSOLIDADO**: $547 + 198 + 88 + 10 + 3 = \mathbf{846}$

### 2.2 Inventário dos 13 Registros Atípicos Identificados:
1. `36444975000156` (Thruster Design): `0000000000` — Registro zerado/placeholder.
2. `05082615000128` (Ivo Amaral Publicidade): `+55 800 092 1920` (`8000921920`) — Número 0800 institucional.
3. `11359761000150` (Hoogli Marketing): `558000001909` (`8000001909`) — Número 0800 institucional.
4. `10703592000161` (Viver Bem Publicidade): `(51) 8179-0073 / (51) 3049-1641` — Dois números concatenados com barra.
5. `04196528000139` (M2 Comunicação): `962233175` — 9 dígitos, ausência de DDD.
6. `60503869000180` (Pefran): `17862023039` — Código internacional (EUA) ou digitação fora do padrão nacional.
7. `35085142000183` (A3CS Comunicação): `1164832216` — Fixo com prefixo inicial atípico.
8. `68418490000184` (Stap Comunicação): `3416394306` — Fixo com prefixo inicial atípico.
9. `10353999000106` (DP Brasil): `5614034238` — Fixo com prefixo inicial atípico.
10. `43059668000117` (Day 1 Advertising): `2164758770` — Fixo com prefixo inicial atípico.
11. `09055999000140` (Eyxo): `2913000717` — Fixo com prefixo inicial atípico.
12. `09350594000135` (Agblue): `1862844567` — Fixo com prefixo inicial atípico.
13. `sina:1147` (ANNX Ltda): `1118364400` — Fixo com prefixo inicial atípico.

### 2.3 Sobreposição e Prova de WhatsApp:
* A prova de WhatsApp não é uma nova linha telefônica, mas sim um **status de handshake homologado** (presença de `@lid`, `@c.us` com sessão ativa ou histórico de envio concluído com sucesso):
  - **43 agências** possuem WhatsApp COMPROVADO. Deste grupo:
    - **41 agências** utilizam números celulares.
    - **2 agências** utilizam linhas fixas homologadas no WhatsApp Business (ex.: Master Comunicação e Blancolima).
  - **245 agências** possuem telefone celular registrado, mas **NÃO possuem teste prévio no WhatsApp** (permanecem em quarentena de validação passiva).
  - **555 agências** possuem linhas fixas e não possuem conta WhatsApp vinculada.

---

## 3. ESTOQUE DE CONTATOS vs. ELEGIBILIDADE REAL DE DISPARO

Diferencia-se expressamente o **potencial cadastral (estoque bruto de dados)** da **elegibilidade imediata por runner**:

```
[Estoque Cadastral Bruto] ---> [Filtro de Higienização] ---> [Elegibilidade por Canal] ---> [Cota do Runner / Dia]
  846 agências na base          Bounces, DNC, Erros            Canais Prontos                 Limites de Segurança
```

### 3.1 E-mail (Zoho SMTP)
* **Estoque Cadastral com formato de e-mail**: 723 agências.
* **Supressões / Falhas Históricas Conhecidas**: 22 agências (19 com bounce/erro em `central.db` + 3 sob DNC).
* **Estoque Higienizado Elegível**: **701 agências**.
* **Capacidade Operacional Real**: Limitada pela cota diária do Zoho SMTP (ex.: 30 e-mails/dia = cadência programada de 24 dias úteis).

### 3.2 Instagram Direct (Robôs 5050 e 5051)
* **Estoque Cadastral com handle**: 723 agências.
* **Perfis Inexistentes / DMs Fechadas / DNC**: 6 agências.
* **Estoque Higienizado Elegível**: **717 agências**.
* **Capacidade Operacional Real**: Dividida entre as duas contas operacionais (limite de segurança por conta de 15 a 25 DMs/dia).

### 3.3 WhatsApp (Gateway OpenWA)
* **Estoque Total de Telefones**: 846 agências.
* **Estoque com Prova Ativa de WhatsApp**: **43 agências**.
* **Estoque em Quarentena para Checagem Passiva (Celulares sem prova)**: **245 agências**.
* **Capacidade Operacional Imediata**: 43 agências aptas para disparo seguro respeitando a cota do gateway.

---

## 4. AUDITORIA DE CLIENTES CONHECIDOS E SUPRESSÕES (DNC)

Auditamos especificamente o status das marcas mencionadas nas diretrizes:

1. **Trade Comunicação e Marketing SS Ltda** (`CNPJ: 81078289000163`):
   - **CONFIRMADO COMO CLIENTE ATIVO IARANDÚ**.
   - Registrado em `agencias` com `is_cliente = 1` e `tag_cliente = 'CLIENTE ATIVO IARANDU (NAO PROSPECTAR)'`.
   - Registrado em `central.db` com `is_customer = 1` e `do_not_contact = 1`.
   - Protegido por `contact_suppressions.py`.
2. **Binder** (`CNPJs: 72190242000104` e `72190242000368`):
   - **CONFIRMADO COMO BLOQUEIO DNC PREVENTIVO**.
   - Ambas as inscrições (Matriz e Filial) permanecem bloqueadas para evitar duplicidade de abordagem institucional.
3. **Explay Web Agency** (`CNPJ: 11607585000129`):
   - Encontra-se cadastrada na base histórica como lead (`is_cliente = 0`, `is_customer = 0`).
   - *Recomendação*: Se houver relação comercial ativa com a Explay, deve ser adicionada à lista manual de DNC antes da execução de novas campanhas.

---

## 5. REALISMO SOBRE ENRIQUECIMENTO VIA WEBSITES E RDAP

* **Websites Cadastrados**: 791 agências possuem URL preenchida. Ter uma URL preenchida não é prova de que o site esteja online ou publique redes sociais ativas (alguns exibem páginas em manutenção, certificados expirados ou links genéricos). O crawler deve ser resiliente a erros de conexão e timeouts.
* **NIC.br RDAP**: É uma excelente fonte gratuita e oficial para domínios `.br` (33 das 40 agências sem CNPJ possuem domínio nacional). Contudo, o RDAP não garante CNPJ em 100% dos casos, pois domínios antigos podem estar registrados sob CPF do titular ou de agências de hospedagem terceirizadas. Quando o RDAP retornar CPF, o dado deve ser descartado para fins de PJ e registrado como pendência para busca via razão social.
