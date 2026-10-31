# Plano Diretor CRM — Central de Logs de Disparo (`/metas`)
**Documento Canônico de Especificação Técnica, Contrato `dispatch_logs` e Mapa de Reconciliação**
*Versão 2.2 — Homologada para Implementação do Adaptador e Endpoints*

- **Data de Registro:** 27 de Outubro de 2026
- **Frente:** Antigravity-4 (Especificação Técnica de Logs e Critérios de Aceite)
- **Time Envolvido:** Codex-3 (Backend / Runners / Adaptador), Antigravity-2 (Engenharia de Dados e Bancos), Segundo Cérebro (Coordenação Geral)
- **Status:** Documento de Especificação Técnica e Contrato (Somente Leitura: sem edições em backend/UI/DBs de produção; sem chamadas de preflight/start ou disparos)

---

## 1. Diretrizes Inegociáveis de Integridade e Governança

1. **Taxonomia Rigorosa por Protocolo:**
   - **`smtp_accepted`:** Exclusivo para o canal **E-mail**. Indica que o servidor SMTP intermediário aceitou o despacho (`250 OK` do Zoho SMTP). Não equivale a entrega na caixa de entrada do destinatário.
   - **`gateway_accepted`:** Exclusivo para o canal **WhatsApp**. Indica recebimento e aceitação pelo gateway OpenWA com atribuição de `messageId`.
   - **`sent_unconfirmed`:** Exclusivo para o canal **Instagram Direct**. Indica que a rotina do navegador do bot completou o envio da mensagem na interface sem erro imediato, porém sem confirmação de entrega ou leitura do destinatário.
   - **`delivery_confirmed`:** Aplicável a qualquer canal **estritamente mediante prova técnica comprovada** (recibo de leitura/entrega `ack >= 2` no WhatsApp; marcação de "Visto"/"Delivered" no Direct; notificação DSN de entrega positiva no e-mail).
   - **`uncertain`:** Para estados indeterminados ou **timeouts após a tentativa de envio**. **NUNCA classificar como falha segura e NUNCA gerar reenvio automático**, evitando disparos duplicados indesejados ao lead.
2. **Resolução Dinâmica de Banco dos Bots (Sem Contagens Fixas Assumidas):**
   - O caminho do banco SQLite do Bot 5051 apontado no `build-info` do processo ativo é o caminho aninhado:  
     `C:\Users\user\AppData\Local\iArticuleBot_Oficial\iArticuleBot_Comercial\comercial.db`.
   - **Regra de Engenharia Mandatória:** O backend **deve resolver o caminho do banco dinamicamente via processo ativo / `build-info`** antes de consultar dados ou computar totais. É vedado assumir contagens estáticas prévias, bem como assumir a base oficial como 208 ou zero sem essa resolução comprovada.
3. **Remetente WhatsApp e Telefone de Assinatura Oficial:**
   - O remetente do WhatsApp (`sender_account`) só pode ser preenchido se houver **prova documental da sessão conectada** no momento do envio. Na ausência de prova: **`sender_account = null`**, `sender_proof_status = "desconhecido"` e `sender_instance_id` separado (ou `null`). **É vedado inventar nomes fictícios de sessão (como `"whatsapp_sessao_iarandu"`) ou presumir a sessão sem validação documental.**
   - Telefone para assinatura oficial informado por Diego Maldonado: **`+5541998136312`** (9 dígitos).
4. **Convenção Canônica de `lead_id` (`agency_id`):**
   - Leads com CNPJ: 14 dígitos numéricos normalizados (ex.: `"90771544000140"`).
   - Leads sem CNPJ (SINAPRO): Usar estritamente a convenção existente no backend: **`sina:<id>`** (ex.: `"sina:980"` ou `"sina:<hash>"`). Não inventar aliases novos como `SINAPRO_`.
   - Leads não vinculados / desconhecidos: **`lead_id = null`** e `lead_id_type = null`. **NUNCA usar strings compartilhadas como `"DESCONHECIDO"`**, para evitar agregação artificial de entidades distintas.
5. **Independência Estrita de Contas e Remoção de Cooldown Bloqueador:**
   - As contas Instagram `@maldonadodiego55` (5050 - Comercial) e `@iarandu.br` (5051 - Oficial) são totalmente independentes.
   - **Não existe cooldown multicanal compartilhado:** disparos em uma conta jamais bloqueiam ou atrasam o envio de outra conta ou canal.
6. **Namespacing de Origem e Deduplicação Estrita:**
   - O campo `source_system` deve ser **namespaced** no formato `<banco_ou_origem>:<tabela>` (ex.: `"agencias_publicidade.db:disparos_email"`, `"central.db:interactions"`), gerando `log_id = "${source_system}:${source_record_id}"` para eliminar qualquer colisão de IDs idênticos entre tabelas distintas.
   - Deduplicação de réplicas intra-fonte por:
     $$\text{Chave de Unicidade Intra-Fonte} = (\text{source_event_key}, \text{sender_account})$$
   - **Réplicas entre fontes distintas SOMENTE com vínculo comprovado:** A fusão de eventos entre bancos diferentes (ex.: `disparos_email` e `interactions`) só é autorizada quando houver elo causal explícito documentado (ex.: chave de evento cruzada `interactions.source_event_key == 'email:' || disparos_email.id`). Sem vínculo comprovado, os registros NUNCA podem ser mesclados por suposição.
   - **NUNCA deduplicar entre contas independentes:** Disparos de `@maldonadodiego55` e `@iarandu.br` para o mesmo lead permanecem como registros independentes.
7. **Integridade de Texto Histórico e Sanitização:**
   - Texto ou template histórico **só é afirmado se houver registro físico persistido na época do evento**. Templates atuais no repositório **não provam** envios passados. Na falta de evidência: `body_text = null`, `template_id = null`, `body_status = "desconhecido"`.
   - `timestamp_raw` preserva o carimbo literal bruto da fonte. O timezone (`timestamp_tz`) só é calculado se houver evidência de fuso comprovada na origem; caso contrário, `timestamp_tz = null`.
   - **Segurança de Renderização:** Qualquer visualização de HTML histórico na UI deve ser estritamente sanitizada, bloqueando scripts e elementos executáveis.

---

## 2. Taxonomia Canônica de Status por Canal

| Status Canônico | Escopo Permitido | Definição Técnica e Requisitos de Evidência |
|---|---|---|
| **`queued`** | Todos | Mensagem enfileirada no runner/motor local, aguardando início de transmissão. |
| **`smtp_accepted`** | **Exclusivo E-mail** | Despacho SMTP aceito pelo servidor intermediário (resposta `250 OK` do Zoho SMTP). Não comprova entrega na caixa de entrada. |
| **`gateway_accepted`** | **Exclusivo WhatsApp** | Mensagem recebida e aceita pelo gateway OpenWA com atribuição de `messageId`. |
| **`sent_unconfirmed`** | **Exclusivo Instagram** | Ação de envio no Direct executada pelo navegador do robô sem erro de interface, porém sem recibo do destinatário. |
| **`delivery_confirmed`** | Todos (sob prova) | **Apenas com prova técnica comprovada de entrega:** recibo `ack >= 2` no WhatsApp; confirmação de "Visto"/"Delivered" no Instagram; confirmação DSN no e-mail. |
| **`uncertain`** | Todos | **Estado indeterminado ou timeout após tentativa de envio.** Não é falha segura; **NUNCA gera reenvio automático**. |
| **`errors`** | Todos | Rejeição técnica documentada durante o envio (código `550 Unusual sending activity`, DM fechada, número inválido, timeout de conexão inicial). |
| **`exclusions`** | Todos | Bloqueio prévio do lead pelas políticas da conta/canal (lead marcado com `do_not_contact`, opt-out formal, teto diário da conta atingido). |

---

## 3. Fontes de Dados e Resolução Dinâmica de Bancos

> *Aviso de Governança:* As contagens apresentadas abaixo são **snapshots históricos pontuais de auditoria**, e **NÃO constantes de implementação**. No legado, 'enviado' não comprova entrega final nem garante qual conta remetente foi utilizada em incidentes passados.

| Fonte / Motor | Canal / Conta | Resolução Canônica do Banco / Endpoint | Tratamento do Corpo e Identidade |
|---|---|---|---|
| **E-mail (Zoho SMTP)** | E-mail (`contato@iarandu.com.br`) | `agencias_publicidade.db:disparos_email` | Corpo não foi salvo nessa tabela legada. Marcar `body_status: "desconhecido"`, `body_text: null`. Telefone oficial de assinatura: `+5541998136312`. |
| **WhatsApp Gateway** | WhatsApp | Docker OpenWA (`127.0.0.1:2785`) e `agencias_publicidade.db:disparos_whatsapp` | Mensagem integral persistida. Remetente exige prova da sessão ativa conectada (`sender_proof_status`); se não houver prova: `sender_account = null`. |
| **Instagram Bot 5050 (Comercial)** | Instagram (`@maldonadodiego55`) | `C:\Users\user\AppData\Local\iArticuleBot_Comercial\comercial.db` *(confirmar via build-info)* | Mensagem integral persistida em `candidatos.conteudo_mensagem`. Erros de DM fechada em `ultimo_erro`. |
| **Instagram Bot 5051 (Oficial)** | Instagram (`@iarandu.br`) | `C:\Users\user\AppData\Local\iArticuleBot_Oficial\iArticuleBot_Comercial\comercial.db` *(caminho aninhado confirmado no processo; resolver via build-info)* | **Exigir confirmação dinâmica via processo/build-info antes de computar contagens.** Não assumir contagens estáticas prévias (sem assumir 208 ou zero). |
| **Central Unificada** | Multicanal | `central.db:interactions` | Extrair detalhes do `payload_json` preservando regras de integridade e vínculo comprovado. |

---

## 4. Contrato Canônico `dispatch_logs`

### 4.1. Interface TypeScript (`DispatchLogEntry`)

```typescript
interface DispatchLogEntry {
  log_id: string;                       // Identificador canônico único e namespaced ("${source_system}:${source_record_id}")
  source_system: string;                // Origem namespaced (ex: "agencias_publicidade.db:disparos_email", "central.db:interactions")
  source_record_id: string;             // ID primário original na fonte
  source_event_key: string | null;      // Chave original do evento na fonte (essencial para deduplicação comprovada)
  lead_id: string | null;               // 14 dígitos de CNPJ ou "sina:<id>". NUNCA chave string compartilhada; null se desconhecido
  lead_id_type: 'cnpj' | 'sinapro' | null;
  agency_name: string | null;           // Nome fantasia ou razão social da agência
  channel: 'email' | 'whatsapp' | 'instagram_direct';
  sender_account: string | null;        // Ex: "contato@iarandu.com.br", "@maldonadodiego55", "@iarandu.br"; null se desconhecido
  sender_instance_id: string | null;    // Identificador de instância/porta/sessão (ex: "port:5050", "openwa_docker"); null se desconhecido
  sender_proof_status: 'verified' | 'requer_prova_sessao' | 'desconhecido';
  recipient_address: string;            // E-mail, telefone DDI/DDD ou handle @
  recipient_name: string | null;
  template_id: string | null;           // Somente se houver prova documental no log histórico
  body_status: 'presente' | 'desconhecido';
  body_text: string | null;            // Texto integral real; nunca inferido
  body_html: string | null;            // HTML sanitizado para UI; null se indisponível
  timestamp_raw: string | null;        // Carimbo literal original preservado (ex: "2026-10-21 10:52:09")
  timestamp_tz: string | null;          // ISO 8601 com timezone (-03:00); null se não houver evidência de fuso
  timezone_provenance: string | null;   // Proveniência do fuso (ex: "persisted_offset", "inferred_local_collection", null)
  status: 'queued' | 'smtp_accepted' | 'gateway_accepted' | 'sent_unconfirmed' | 'delivery_confirmed' | 'uncertain' | 'errors' | 'exclusions';
  status_raw: string;                  // Status original da fonte de dados
  reason_code: string;                  // Código técnico estruturado
  reason_detail: string;                // Log literal capturado (timeout pós-envio = status 'uncertain', sem reenvio automático)
  commercial_reply_detected: boolean;  // Indicador de resposta comercial comprovada
  commercial_reply_excerpt: string | null; // Trecho da resposta do lead se houver
}
```

### 4.2. Exemplos Sintéticos do Schema

#### Exemplo A: Instagram Direct — Bot 5051 Oficial (Lead Sinapro)
```json
{
  "log_id": "bot_5051:candidatos:76",
  "source_system": "bot_5051:candidatos",
  "source_record_id": "76",
  "source_event_key": "ig:candidato_76",
  "lead_id": "sina:980",
  "lead_id_type": "sinapro",
  "agency_name": "Agência Exemplo Sinapro [Sintético]",
  "channel": "instagram_direct",
  "sender_account": "@iarandu.br",
  "sender_instance_id": "port:5051",
  "sender_proof_status": "verified",
  "recipient_address": "@agencia_exemplo",
  "recipient_name": "Contato Exemplo",
  "template_id": null,
  "body_status": "presente",
  "body_text": "Olá, time da Agência Exemplo! Como estão as coisas por aí?... [Exemplo Sintético]",
  "body_html": null,
  "timestamp_raw": "2026-10-26 11:30:00",
  "timestamp_tz": "2026-10-26T11:30:00-03:00",
  "timezone_provenance": "persisted_local_brt",
  "status": "sent_unconfirmed",
  "status_raw": "enviado",
  "reason_code": "IG_DIRECT_SENT",
  "reason_detail": "Mensagem enviada no Direct sem confirmação de leitura",
  "commercial_reply_detected": false,
  "commercial_reply_excerpt": null
}
```

#### Exemplo B: E-mail Legado (Corpo Desconhecido)
```json
{
  "log_id": "agencias_publicidade.db:disparos_email:1",
  "source_system": "agencias_publicidade.db:disparos_email",
  "source_record_id": "1",
  "source_event_key": "email:1",
  "lead_id": "04432749000169",
  "lead_id_type": "cnpj",
  "agency_name": "Agência Teste E-mail [Sintético]",
  "channel": "email",
  "sender_account": "contato@iarandu.com.br",
  "sender_instance_id": "zoho_smtp",
  "sender_proof_status": "verified",
  "recipient_address": "contato@agenciateste.com.br",
  "recipient_name": null,
  "template_id": null,
  "body_status": "desconhecido",
  "body_text": null,
  "body_html": null,
  "timestamp_raw": "2026-10-21 10:52:09",
  "timestamp_tz": "2026-10-21T10:52:09-03:00",
  "timezone_provenance": "persisted_local_brt",
  "status": "smtp_accepted",
  "status_raw": "enviado",
  "reason_code": "SMTP_250_OK",
  "reason_detail": "Sucesso via Zoho SMTP",
  "commercial_reply_detected": false,
  "commercial_reply_excerpt": null
}
```

#### Exemplo C: WhatsApp Gateway — Timeout Pós-Envio (Estado Incerto e Remetente Desconhecido)
```json
{
  "log_id": "central.db:interactions:424",
  "source_system": "central.db:interactions",
  "source_record_id": "424",
  "source_event_key": "openwa:chat:81f45f0c429bad568fe02e9d0c9cd1f4817ff1bd4c19cf8cd01024d29ceaf744",
  "lead_id": null,
  "lead_id_type": null,
  "agency_name": null,
  "channel": "whatsapp",
  "sender_account": null,
  "sender_instance_id": "openwa_docker",
  "sender_proof_status": "desconhecido",
  "recipient_address": "5541999999999",
  "recipient_name": null,
  "template_id": null,
  "body_status": "desconhecido",
  "body_text": null,
  "body_html": null,
  "timestamp_raw": "1790759467",
  "timestamp_tz": null,
  "timezone_provenance": null,
  "status": "uncertain",
  "status_raw": "timeout_post_send",
  "reason_code": "GATEWAY_TIMEOUT_POST_SEND",
  "reason_detail": "Timeout após tentativa de despacho; estado de entrega indeterminado; reenvio automático bloqueado",
  "commercial_reply_detected": false,
  "commercial_reply_excerpt": null
}
```

---

## 5. Regras de Deduplicação e Integridade Técnica

1. **Namespacing de IDs e Prevenção de Colisão:**
   $$\text{ID Canônico} = \text{source_system} + \text{":"} + \text{source_record_id}$$
   - Impede que registros de tabelas distintas com IDs inteiros coincidentes (ex.: ID 1 de `disparos_email` e ID 1 de `disparos_whatsapp`) colidam no backend ou na UI.
2. **Deduplicação Intra-Fonte:**
   $$\text{Unicidade Intra-Fonte} = (\text{source_event_key}, \text{sender_account})$$
   - Colapsa repetições da mesma ocorrência originadas na mesma fonte mantendo o registro mais rico.
3. **Réplicas Entre Fontes Distintas Apenas com Vínculo Comprovado:**
   - A fusão de eventos entre bases diferentes (ex.: `agencias_publicidade.db:disparos_email` vs `central.db:interactions`) **exige vínculo causal documental explícito** (ex.: `interactions.source_event_key == 'email:' || disparos_email.id`).
   - Sem vínculo explícito, os registros **não podem ser mesclados** por semelhança de dados.
   - Disparos de contas independentes (`@maldonadodiego55` e `@iarandu.br`) **nunca** são deduplicados entre si.
4. **Resolução de `lead_id`:**
   - CNPJ (14 dígitos): `lead_id = "<cnpj>"`, `lead_id_type = "cnpj"`.
   - Sinapro sem CNPJ: `lead_id = "sina:<id>"`, `lead_id_type = "sinapro"`.
   - Sem agência correspondente: `lead_id = null`, `lead_id_type = null`.
5. **Tratamento de Estado Incerto (`uncertain`):**
   - Falhas de resposta ou perda de socket ocorridas **após** a emissão do comando de envio devem ser registradas como `status: "uncertain"`.
   - **Regra de Segurança Operacional:** O sistema **não deve agendar reenvio automático** para itens com status `uncertain`. A confirmação deve ser manual ou aguardar evento inbound do lead.

---

## 6. Paginação, Filtros e Resposta da API (`/metas`)

Rota a ser exposta pelo **Codex-3**: `GET /api/local/dispatch-logs`.

### 6.1. Parâmetros de Consulta (Query String)
- `page`: Número da página (inteiro $\ge 1$, default `1`).
- `limit`: Itens por página (default `25`, opções `50`, `100`).
- `channel`: `"email" | "whatsapp" | "instagram_direct" | "all"`.
- `sender_account`: Filtrar por remetente exato (`"@maldonadodiego55"`, `"@iarandu.br"`, `"contato@iarandu.com.br"`, `"desconhecido"`, `"all"`).
- `status`: `"queued" | "smtp_accepted" | "gateway_accepted" | "sent_unconfirmed" | "delivery_confirmed" | "uncertain" | "errors" | "exclusions" | "all"`.
- `search`: Busca textual livre em nome da agência, CNPJ, destinatário ou trecho da mensagem.
- `date_from` / `date_to`: Filtro por período (`YYYY-MM-DD`).
- `only_replies`: Booleano para exibir apenas disparos com retorno comercial comprovado.

### 6.2. Estrutura de Retorno Canônica
```json
{
  "status": "ok",
  "pagination": {
    "current_page": 1,
    "limit": 25,
    "total_records": 0,
    "total_pages": 0,
    "has_next": false,
    "has_prev": false
  },
  "summary": {
    "total_dispatches": 0,
    "by_status": {
      "queued": 0,
      "smtp_accepted": 0,
      "gateway_accepted": 0,
      "sent_unconfirmed": 0,
      "delivery_confirmed": 0,
      "uncertain": 0,
      "errors": 0,
      "exclusions": 0
    },
    "by_account": {
      "@maldonadodiego55": 0,
      "@iarandu.br": 0,
      "contato@iarandu.com.br": 0,
      "desconhecido": 0
    }
  },
  "data": [
    /* Lista de objetos DispatchLogEntry */
  ]
}
```

---

## 7. Critérios de Aceitação para Homologação de Tela

### Cenário 1: Isolamento de Contas e Resolução Dinâmica (Instagram)
- **Dado** que as contas `@maldonadodiego55` (porta 5050) e `@iarandu.br` (porta 5051) possuem bases e caminhos resolvidos dinamicamente via processo ativo;
- **Quando** o operador filtrar a listagem por `sender_account = "@iarandu.br"`;
- **Então** a tela deve exibir estritamente os eventos despachados pela conta oficial;
- **E** em nenhuma hipótese eventos da conta pessoal `@maldonadodiego55` podem aparecer nessa listagem;
- **E** as contagens do summary devem ser computadas dinamicamente a partir dos bancos resolvidos pelo processo ativo, sem valores estáticos assumidos.

### Cenário 2: Exibição Honesta de Dados Não Gravados
- **Dado** um registro de envio de e-mail histórico em que o corpo não foi gravado na base de dados;
- **Quando** o operador abrir a visualização detalhada do evento;
- **Então** o campo do corpo deve exibir a identificação neutra `[Corpo Histórico Desconhecido]`;
- **E** nenhum texto de template atual deve ser renderizado como se fosse o conteúdo enviado no passado.

### Cenário 3: Rigor Semântico de Status por Protocolo
- **Dado** um conjunto de disparos nos diferentes canais;
- **Quando** o operador inspecionar os selos de status na tabela de logs;
- **Então**:
  * Disparos de E-mail aceitos pelo SMTP exibem o selo `SMTP Aceito` (`smtp_accepted`);
  * Disparos de WhatsApp aceitos no gateway exibem o selo `Gateway Aceito` (`gateway_accepted`);
  * Disparos de Instagram concluídos pelo robô exibem o selo `Enviado (Sem Confirmação)` (`sent_unconfirmed`);
  * O selo `Entregue Confirmado` (`delivery_confirmed`) é reservado **exclusivamente** para registros com prova física comprovada (recibo `ack >= 2` ou leitura direta comprovada);
  * Disparos com falha de retorno ou timeout pós-envio exibem o selo `Incerto / Timeout` (`uncertain`).

### Cenário 4: Colisão de Origem Prevenida por Namespacing
- **Dado** que `agencias_publicidade.db:disparos_email` possui um registro com `id=1` e `agencias_publicidade.db:disparos_whatsapp` também possui um registro com `id=1`;
- **Quando** os logs forem processados e renderizados na tabela da tela `/metas`;
- **Então** cada registro deve receber um `log_id` namespaced exclusivo (ex.: `agencias_publicidade.db:disparos_email:1` e `agencias_publicidade.db:disparos_whatsapp:1`);
- **E** ambos os registros devem coexistir na visualização sem sobrescrita de chaves.

### Cenário 5: Tratamento de Estado `uncertain` e Ausência de Reenvio Automático
- **Dado** que um disparo de mensageria sofreu timeout de rede pós-transmissão;
- **Quando** o log for listado na tela `/metas`;
- **Então** seu status deve figurar como `uncertain`;
- **E** o sistema **não** deve classificar o registro como erro de reenvio nem agendar retry automático em background.

### Cenário 6: Remetente Desconhecido e Instância Separada
- **Dado** um registro histórico de WhatsApp em que a sessão conectada não foi persistida no log original;
- **Quando** o evento for carregado na interface;
- **Então** o campo `sender_account` deve exibir `[Remetente Desconhecido]` (`null`), com `sender_instance_id` indicando a instância (`"openwa_docker"`) ou `null`;
- **E** o sistema **não** deve inventar nomes fictícios de sessão (como `"whatsapp_sessao_iarandu"`).

---

## 8. Alinhamento de Papéis e Handoff

* **Antigravity-4:** Documento canônico v2.2 finalizado e homologado, cobrindo integralmente taxonomia estrita, namespacing, integridade documental e cenários de teste.
* **Codex-3 (Backend / Runners):** Implementação isolada do adaptador e do endpoint `GET /api/local/dispatch-logs` no `server.py`, garantindo a resolução dinâmica de paths e a sanitização de HTML na entrega.
* **Antigravity-2 (Engenharia de Dados):** Consolidação dos bancos de dados e views de reconciliação segundo as regras de namespacing `source_system`, unicidade `(source_event_key, sender_account)` e identificadores `sina:<id>`.
* **Segundo Cérebro:** Coordenação e homologação do plano diretor CRM.
