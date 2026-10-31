# 📘 DOSSIÊ MESTRE DE OPERAÇÃO & HANDOFF — AGENTE 2 (STANDBY EXECUTIVO)
**Documento de Governança, Continuidade de Negócio & Operação IArandú Revenue OS**  
*Data de Emissão: 22 de Outubro de 2026 | Autor: Agente 4 (Líder Técnico & Arquitetura) | Destinatário: Agente 2*

---

> [!IMPORTANT]
> **DIRETRIZ ESTRATÉGICA SUPREMA DEFINIDA POR DIEGO:**
> O foco da empresa é **ÚNICO E EXCLUSIVO**: **Vender o SaaS IArandú para Agências de Publicidade que possuem Contratos Públicos ativos acima de R$ 500.000,00**.
> Todos os outros projetos paralelos (sites de empresas sem site em Curitiba, bots de meme-coins na Solana/ElizaOS, tripulações CrewAI genéricas) estão **CONGELADOS**. Se o Agente 4 esgotar tokens ou cair, o **Agente 2 deve assumir esta exata operação** sem desviar 1 milímetro do foco.

---

## 1. RESUMO EXECUTIVO DO NEGÓCIO HOJE

* **Base Homologada:** **401 Agências de Publicidade 100% Puras** (passaram por filtro anti-gráfica, anti-consultoria e anti-locadora).
* **Licitações Auditadas:** **529 Contratos Públicos Oficiais (PNCP)** somando **R$ 3.475.093.850,68** em verbas ativas.
* **Sócios e Decisores Mapeados:** **395 Sócios-Administradores e Diretores físicos** identificados com nome completo, cargo, CPF/LinkedIn e contato direto.
* **Fila WhatsApp OURO:** **57 celulares de decisores** validados na API OpenWA (`@lid`) com 9 dígitos e DDDs locais.
* **Cockpit Visual Ativo:** [http://localhost:8051](http://localhost:8051) (Painel 1-Click executivo para Diego operar sem terminal).

---

## 2. STATUS DAS OPORTUNIDADES QUENTES (INBOUND VIP)

O Agente 2 deve acompanhar prioritariamente estas 4 contas que já deram retorno:

### 1. 🥇 Lápis Raro Comunicação (MG / Belo Horizonte)
* **Origem:** Retorno via Instagram Direct (`@lapisraro`).
* **Mensagem:** *"Oi! Você pode mandar um email para alice.lascasas@lapisraro.com.br e claudia.brandao@lapisraro.com.br"*.
* **Decisores:** Alice Las Casas (Sócia e Diretora de Mídia) e Cláudia Brandão (Sócia e VP de Operações).
* **Ação Executada:** Apresentação executiva formal enviada em 22/10 às 19:08 via Zoho SMTP com anexo de inteligência.
* **Próximo Passo:** Se não houver retorno em até 48 horas (24/10), acionar WhatsApp de contato ou touchpoint de follow-up.
* **Contratos Públicos Relevantes:** R$ 147.000.000,00 (Assembleia Legislativa de Minas Gerais, Prefeitura de Belo Horizonte e Copasa).

### 2. 🥈 Art&C Comunicação Integrada (RN / PB / Nordeste)
* **Origem:** Retorno via Instagram Direct (`@artccom`).
* **Mensagem:** *"Olá! Entre em contato pra apresentar a plataforma com a Head de Mídia neste e-mail: alzira@artc.com.br"*.
* **Decisor:** Alzira Paiva (Head de Mídia e Inteligência).
* **Ação Executada:** Apresentação enviada via Zoho SMTP.
* **Próximo Passo:** Follow-up executivo via WhatsApp agendando demonstração de 15 minutos do SaaS.

### 3. 🥉 Binder Comunicação Ltda. (RJ / DF / Nacional)
* **Origem:** Retorno via Instagram Direct (`@binder`).
* **Mensagem:** Indicou canal oficial `comunicacao@binder.com.br`.
* **Decisores:** Gláucio Binder (CEO & Sócio-Fundador) e Equipe de Mídia.
* **Ação Executada:** E-mail enviado para o canal de mídia e mensagem executiva formal direcionada ao CEO Gláucio Binder (`glaucio@binder.com.br`, tel gabinete: 21 2536-2250).
* **Contratos Públicos Relevantes:** R$ 564.550.000,00 (Caixa Econômica Federal e Prefeitura do Rio).

### 4. 🏅 Orgassi Contabilidade / Blancolima (PR)
* **Origem:** Retorno via WhatsApp.
* **Decisor:** Blanco Lima (Diretor Presidente).
* **Ação Executada:** Telefone direto de Blanco Lima auditado e corrigido para `+55 (45) 99900-3601` na base central.

---

## 3. MAPA DA INFRAESTRUTURA & ARQUIVOS

### 🗄️ Bancos de Dados SQLite
1. **Banco Setorial (401 Agências & Contratos):**  
   `/mnt/d/Eternity-Signals/COMERCIAL/AGENCIAS-PUB/data/agencias_publicidade.db`
   * `agencias`: 401 agências puras, CNPJ, razão, nome fantasia, donos, contratos, telefones.
   * `contratos_publicos`: 529 licitações ativas do PNCP > R$ 500k.
   * `disparos_email`: Histórico de todos os envios de e-mail.
   * `disparos_whatsapp`: Histórico de todos os envios de WhatsApp.
2. **Banco Central do CRM (Interações & Identidades):**  
   `/mnt/c/Users/user/iarandu_crm_central/central.db`
   * `interactions`: Todas as mensagens enviadas em todos os canais (`WHERE channel='whatsapp'`, `WHERE channel='email'`, etc.).
3. **Banco do Bot de Instagram Desktop:**  
   `/mnt/c/Users/user/AppData/Local/iArticuleBot_Comercial/comercial.db`
   * `candidatos`: Fila de perfis do Instagram para envio de DM.

---

## 4. ENGENHARIA DOS 3 CANAIS DE DISPARO

### 📸 Canal 1: Instagram Direct (DMs)
* **Motor:** Robô Desktop `iArticuleBot_Comercial.exe` (API local na porta 5050).
* **Meta Diária:** **50 DMs / dia** (teto seguro da Meta).
* **Delay:** 90s a 180s humanizado entre agências.
* **Endpoints:**
  * Status: `curl -s http://127.0.0.1:5050/api/bot/status`
  * Iniciar: `curl -s -X POST http://127.0.0.1:5050/api/bot/start`
  * Parar: `curl -s -X POST http://127.0.0.1:5050/api/bot/stop`

### 💬 Canal 2: WhatsApp Gateway (OpenWA)
* **Sessão Ativa:** `http://localhost:2785/api/sessions/2f096fca-217d-47f5-9b98-311d2774d95e`
* **API Key:** `owa_k1_0915bba3a57a72c1fcd613a536aefe7e0aa275950aa6710eb6cee3ee15537dfa`
* **Script de Execução:** `/mnt/d/site-iarandu/executar_lote_whatsapp_seguro.py`
* **Tamanho do Lote:** **8 mensagens por clique** (máximo diário recomendado: **24 mensagens**, divididas em 3 lotes de 8).
* **Delay Anti-Ban:** 120s a 240s entre mensagens.
* **Como disparar via terminal se o painel não for usado:**
  ```bash
  python3 /mnt/d/site-iarandu/executar_lote_whatsapp_seguro.py 8
  ```
* **Arquivos de Controle:**
  * Lock: `/mnt/d/site-iarandu/whatsapp_dispatch.lock`
  * Status ao vivo: `/mnt/d/site-iarandu/whatsapp_status.json`

### ✉️ Canal 3: E-mails Institucionais (Zoho SMTP Enterprise)
* **Servidor:** `smtp.zoho.com:465` (SSL Autenticado)
* **Remetente:** `contato@iarandu.com.br`
* **Script de Execução:** `/mnt/d/site-iarandu/executar_disparo_email_agencias.py`
* **Tamanho do Lote:** **30 e-mails por lote matinal**.
* **Delay:** 120s a 180s com Message-ID e DKIM/SPF únicos.
* **Como disparar via terminal:**
  ```bash
  python3 /mnt/d/site-iarandu/executar_disparo_email_agencias.py
  ```
* **Arquivo de Trava Diária:** `/mnt/d/site-iarandu/last_email_dispatch_date.txt` (guarda a data `YYYY-MM-DD` do último envio. Reseta sozinho à meia-noite).

---

## 5. DASHBOARD REVENUE OS (PORTA 8051)

* **Script:** `/mnt/c/Users/user/iarandu_crm_cockpit.py`
* **Acesso:** `http://localhost:8051` ou `http://127.0.0.1:8051`
* **Como reiniciar se o processo cair:**
  ```bash
  fuser -k 8051/tcp 2>/dev/null
  python3 /mnt/c/Users/user/iarandu_crm_cockpit.py &
  ```
* **Recursos do Cockpit:**
  * **Cabeçalho HUD:** Atualiza dinamicamente a cada 4 segundos com os totais de R$ 3,48 Bi, 401 Agências, Envios de Hoje e Inbound VIP.
  * **Botões 1-Click:** Disparam os scripts de WhatsApp, E-mail e Instagram sem necessidade de terminal para o usuário.
  * **Exportações:** Download imediato de CSVs atualizados para o usuário.

---

## 6. REGRAS DE OURO & ANTI-DUPLICIDADE

1. **NUNCA enviar a mesma mensagem duas vezes no mesmo canal para a mesma agência:**
   * Toda consulta de seleção de destinatários filtra `WHERE cnpj NOT IN (SELECT cnpj FROM disparos_...)` e verifica a tabela `interactions` do `central.db`.
2. **Abordagem Multicanal Saudável:**
   * Podemos abordar a mesma agência por Instagram, E-mail e WhatsApp simultaneamente (cada canal complementa o outro), mas **nunca repetir mensagem no mesmo canal sem antes ter havido resposta**.
3. **Virada de Meia-Noite (00:00):**
   * As métricas de "Hoje" zeram automaticamente às 00:00 porque usam `WHERE event_ts LIKE 'YYYY-MM-DD%'`.
   * O banco de dados preserva todo o histórico para que no dia seguinte os robôs continuem **avançando para as próximas agências da fila**.

---

## 7. CHECKLIST OPERACIONAL DIÁRIO

Se o Agente 2 estiver no comando diário:

1. **09:00:** Verificar se o Cockpit está rodando na porta 8051 (`curl -s http://127.0.0.1:8051/api/action/status-all`).
2. **09:15:** Disparar lote matinal de 30 e-mails via painel ou via script.
3. **09:45:** Disparar primeiro lote de 8 WhatsApps.
4. **10:00:** Acionar o robô de Instagram (50 DMs diárias).
5. **14:30:** Disparar segundo lote de 8 WhatsApps.
6. **17:30:** Disparar terceiro lote de 8 WhatsApps (fechando 24 no dia).
7. **Monitorar Respostas:** Checar a aba "Oportunidades & Inbound" do Cockpit e notificar o Diego imediatamente quando qualquer sócio responder.

---
*Este dossiê garante 100% de redundância e prontidão técnica. O Agente 2 tem em mãos o conhecimento total da operação.*
