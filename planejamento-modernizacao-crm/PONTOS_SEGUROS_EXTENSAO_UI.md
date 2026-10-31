# Mapeamento de Servidor & Pontos Seguros de Extensão da Nova UI
## Documento Técnico para Coordenação do Codex-3 | Projeto: Modernização do CRM 8051

Data: 25/10/2026  
Status: **Inventário e Análise Arquitetural Read-Only**  
Arquivo Auditado: `C:/Users/user/iarandu_crm_cockpit.py` (2.313 linhas)  
Servidor Ativo: `http://localhost:8051`  

---

## 1. Como o Servidor Atual Serve HTML e Trata GET e POST

### 1.1. Como Serve HTML
Atualmente, o servidor **não serve arquivos estáticos** a partir de disco. Ele opera de forma puramente monolítica:
* **Armazenamento:** A interface completa está definida em uma string constante em memória: `HTML_TEMPLATE` (linhas 384 a 1914).
* **Entrega:** Quando a requisição `GET /` é recebida, o método `send_html` é acionado:
  ```python
  def send_html(self, html):
      self.send_response(200)
      self.send_header("Content-Type", "text/html; charset=utf-8")
      self.end_headers()
      self.wfile.write(html.encode("utf-8"))
  ```
* **Limitação Atual:** Não há suporte para arquivos `.css`, `.js`, `.webp`, `.webm`, `.svg` ou `.png`. Qualquer URL fora do roteamento explícito cai no erro 404 (`Rota não encontrada`).

---

### 1.2. Como Trata Requisições GET
No método `RevenueOSHandler.do_GET(self)` (linhas 1939 a 2068):
1. **Parsing da URL e Query Parameters:**
   ```python
   parsed = urlparse(self.path)
   path = parsed.path
   query = parse_qs(parsed.query)
   ```
2. **Roteamento por Igualdade Estrita e Prefixos:**
   * `path == "/"` ➔ Entrega `HTML_TEMPLATE`.
   * `path == "/api/hoje"` ➔ Executa `get_hoje_data()` (tarefas e negociações do dia).
   * `path == "/api/agencias"` ➔ Executa `get_agencias_crm(q, uf, etapa, pendencia)` lendo parâmetros `query.get(...)`.
   * `path.startswith("/api/agencia/")` ➔ Extrai o CNPJ da URL (`path.split("/")[-1]`) e invoca `get_agencia_detalhe(cnpj)`.
   * `path == "/api/funnel"` ➔ Executa `get_funnel_summary()` (agregação por etapas de CRM).
   * `path == "/api/enrichment/status"` ➔ Consulta saldo da API Apify (`get_real_apify_balance()`).
   * `path == "/api/enrichment/review"` ➔ Query SQL em `contatos_enriquecidos WHERE aprovado_para_disparo = 0`.
   * `path == "/api/exclusions"` ➔ Query SQL em `crm_exclusions`.
   * `path == "/api/action/status-all"` ➔ Endpoint central de telemetria consolidando Motor 5050, Motor 5051, WhatsApp, E-mail e Autopilot.
3. **Fallback:** Caso o caminho não coincida com nenhum dos blocos `if/elif`, responde:
   `self.send_json({"error": "Rota não encontrada"}, 404)`.

---

### 1.3. Como Trata Requisições POST
No método `RevenueOSHandler.do_POST(self)` (linhas 2070 a 2301):
1. **Leitura e Desserialização do Payload:**
   ```python
   content_length = int(self.headers.get("Content-Length", 0))
   body = self.rfile.read(content_length) if content_length > 0 else b"{}"
   payload = json.loads(body.decode("utf-8"))
   ```
2. **Despacho por Ações Operacionais e CRM:**
   * **CRM e Dados:**
     * `/api/tasks/toggle` ➔ Alterna status de tarefa entre `PENDENTE` e `CONCLUIDA`.
     * `/api/tasks/create` ➔ Cria tarefa em `crm_tasks`.
     * `/api/deals/update` ➔ Insere/atualiza etapa em `crm_deals` e gera log em `crm_history`.
     * `/api/proposals/save` ➔ Grava proposta comercial em `crm_proposals` e `crm_deals`.
     * `/api/notes/create` ➔ Registra nota manual em `crm_history`.
     * `/api/enrichment/approve` ➔ Aprova contato minerado e atualiza campos cadastrais na tabela `agencias`.
   * **Motores e Automações:**
     * `/api/action/start-instagram` ➔ Executa comando PowerShell isolado para levantar o Motor 5050 (Diego) e envia `POST /api/bot/start`.
     * `/api/action/stop-instagram` ➔ Envia `POST http://127.0.0.1:5050/api/bot/stop`.
     * `/api/action/open-login-firefox` ➔ Inicia processo Playwright Firefox Nightly no perfil isolado `@iarandu.br`.
     * `/api/action/start-instagram-oficial` ➔ Executa PowerShell isolado para levantar o Motor 5051 (Oficial) e envia `POST /api/bot/start`.
     * `/api/action/stop-instagram-oficial` ➔ Envia `POST http://127.0.0.1:5051/api/bot/stop`.
     * `/api/action/start-whatsapp` ➔ Dispara `executar_lote_whatsapp_seguro.py 8`.
     * `/api/action/stop-whatsapp` ➔ Cria arquivo de trava `whatsapp_dispatch.lock`.
     * `/api/action/start-email` ➔ Dispara thread em background com `executar_disparo_email_agencias.py`.
3. **Fallback:** Caso a rota POST não exista, responde:
   `self.send_json({"error": "Ação inválida"}, 400)`.

---

## 2. Inventário de Funções de Apoio e Banco de Dados

| Função | Finalidade | Fontes de Dados Consultadas |
| :--- | :--- | :--- |
| `get_agencias_crm(...)` | Busca textual e filtros de agências com ordenação e limite | SQLite `agencias_publicidade.db` (`agencias`, `crm_deals`) |
| `get_agencia_detalhe(cnpj)` | Dossiê cadastral, sócios, notas e propostas de uma agência | SQLite `agencias_publicidade.db` (`agencias`, `socios_administradores`, etc.) |
| `get_hoje_data()` | Agenda do operador: tarefas vencidas, do dia e deals em curso | SQLite `agencias_publicidade.db` (`crm_tasks`, `crm_deals`) |
| `get_funnel_summary()` | Contagem consolidada de contas por etapa do CRM | SQLite `agencias_publicidade.db` (`crm_deals`, `agencias`) |
| `get_bot_live_status()` | Telemetria do Motor 1 (5050) com fallback para consulta SQLite direta | `http://127.0.0.1:5050/api/bot/status` e SQLite `comercial.db` |
| `get_bot_oficial_live_status()` | Telemetria do Motor 2 (5051) com fallback para consulta SQLite direta | `http://127.0.0.1:5051/api/bot/status` e SQLite oficial |
| `get_db_kpi(db_path, meta)` | Consulta instantânea da contagem de pendentes, enviados e DMs fechadas | SQLite `comercial.db` (`candidatos`) |
| `get_autopilot_daemon_info()` | Status do agendador automático e janelas de disparo | Janelas horárias em memória |
| `get_real_apify_balance()` | Saldo em créditos USD da conta Apify | API externa Apify (`api.apify.com`) |

---

## 3. Pontos Seguros de Extensão para a Nova Interface (`www.iarandu.xyz`)

Para que o Codex-3 integre a nova identidade visual sem quebrar o servidor ativo, foram mapeados **5 pontos seguros de extensão**:

### Ponto 1: Servidor de Assets Estáticos com MIME-Types Dedicados
* **Problema:** A UI nova precisa carregar `iarandu.css`, `iarandu.js`, imagens de cenário (`/scene/*.webp`, `office-iarandu-pt-v3.png`), vídeos em loop dos agentes (`.webm`, `.mov`) e SVGs.
* **Ponto Seguro:** Inserir no início de `do_GET` uma verificação de arquivo físico:
  ```python
  # Ponto Seguro de Extensão: Serviço de Assets
  STATIC_DIR = "/mnt/d/site-iarandu/planejamento-modernizacao-crm/preview_ui" # ou pasta nova
  if path != "/" and not path.startswith("/api/"):
      local_path = os.path.join(STATIC_DIR, path.lstrip("/"))
      if os.path.isfile(local_path):
          mime, _ = mimetypes.guess_type(local_path)
          self.send_response(200)
          self.send_header("Content-Type", mime or "application/octet-stream")
          self.end_headers()
          with open(local_path, "rb") as f:
              self.wfile.write(f.read())
          return
  ```

### Ponto 2: Fallback de Rotas SPA para `index.html`
* **Problema:** No frontend moderno, a navegação entre salas ocorre via rotas HTML5 (`/inicio`, `/campanhas`, `/prospeccao`, `/equipe`, `/metas`, `/ciclo-comercial`, `/prioridades`, `/documentacao`). Se o usuário recarregar a página (F5), o servidor retornará 404.
* **Ponto Seguro:** Mapear essas 8 rotas explícitas para entregar o `index.html` da nova interface:
  ```python
  SPA_ROUTES = {"/inicio", "/campanhas", "/prospeccao", "/equipe", "/metas", "/ciclo-comercial", "/prioridades", "/documentacao"}
  if path in SPA_ROUTES or path == "/":
      # Entrega o index.html da nova UI
  ```

### Ponto 3: Preservação Intransigente da Camada `/api/*`
* **Ponto Seguro:** A verificação `if path.startswith("/api/"):` deve ter **precedência absoluta** no código antes de qualquer roteador de templates ou fallback de páginas. Nenhuma rota existente deve ser renomeada ou ter sua estrutura JSON modificada.

### Ponto 4: Transição para `ThreadingHTTPServer`
* **Problema:** O `HTTPServer` padrão do Python atende uma requisição por vez (single-threaded). Quando um botão como *Iniciar Robô* ou *Login Firefox* invoca subprocessos de 2 segundos, o navegador do operador fica congelado sem atualizar o HUD.
* **Ponto Seguro:** Trocar na inicialização (`run()`):
  ```python
  from http.server import ThreadingHTTPServer
  httpd = ThreadingHTTPServer(server_address, RevenueOSHandler)
  ```
  Isso desacopla completamente as ações de automação do polling de interface.

### Ponto 5: Acoplamento dos Controles em `/campanhas`
* **Ponto Seguro:** No arquivo JavaScript da nova interface (`iarandu.js`), os botões dos 4 cards de campanhas devem chamar exatamente os endpoints existentes:
  * Botão Iniciar Diego ➔ `fetch('/api/action/start-instagram', {method: 'POST'})`
  * Botão Pausar Diego ➔ `fetch('/api/action/stop-instagram', {method: 'POST'})`
  * Botão Iniciar Oficial ➔ `fetch('/api/action/start-instagram-oficial', {method: 'POST'})`
  * Botão Pausar Oficial ➔ `fetch('/api/action/stop-instagram-oficial', {method: 'POST'})`
  * Botão Login Firefox ➔ `fetch('/api/action/open-login-firefox', {method: 'POST'})`
  * Botão WhatsApp ➔ `fetch('/api/action/start-whatsapp', {method: 'POST'})`
  * Botão E-mail ➔ `fetch('/api/action/start-email', {method: 'POST'})`
  * Polling de Estado ➔ `fetch('/api/action/status-all')`

---

## 4. Mapeamento de Riscos e Estratégias de Mitigação

### Risco 1: Disparo Involuntário por Renderização de Componentes
* **Impacto:** Se o novo frontend executar `fetch('/api/action/start-...', {method: 'POST'})` durante o ciclo de montagem de tela ou carregamento inicial, robôs iniciarão disparos sem o consentimento do operador.
* **Mitigação:** Proibir categoricamente chamadas POST automáticas no código JavaScript. Requisições POST só podem existir vinculadas ao evento `click` de botões específicos, com flag de bloqueio de duplo clique (`disabled = true`).

### Risco 2: Interceptação Indevida de Rotas de API pelo Roteamento SPA
* **Impacto:** Se uma regra genérica de rewrite ou fallback para `index.html` for colocada antes dos testes de `/api/`, todas as requisições assíncronas do CRM retornarão o HTML da página inicial em vez do JSON de dados.
* **Mitigação:** Isolar estritamente: toda requisição cujo caminho inicie com `/api/` **nunca** pode cair em fallback de arquivo estático ou SPA.

### Risco 3: Bloqueio do Banco de Dados SQLite (`database is locked`)
* **Impacto:** O Agente 4 opera a base de agências e os robôs consultam o banco a cada ciclo de envio. Se a nova tabela de prospecção abrir conexões persistentes ou demoradas sem fechar o cursor, o SQLite travará.
* **Mitigação:** Todas as consultas do novo backend devem utilizar conexões locais de curta duração (`with sqlite3.connect(DB_PATH, timeout=5) as conn:`), garantindo o fechamento imediato da transação.

### Risco 4: Exibição de Dados "Mocados" / Fictícios em Produção
* **Impacto:** O template original do `www.iarandu.xyz` possui dados fixos de demonstração (*"885 leads"*, *"Site-demo"*, *"Churrascaria Zancanaro"*).
* **Mitigação:** Substituir 100% dos dados hardcoded no JavaScript pelas chamadas dinâmicas da API local (`/api/agencias`, `/api/action/status-all`), exibindo indicador de carregamento caso a API esteja temporariamente inacessível.

---

*Documento gerado em conformidade com as diretrizes de somente leitura. Nenhuma alteração foi realizada em arquivos em execução, bancos de dados ou processos ativos.*
