# CRM Revenue OS local — estado da modernização

Data: 25/10/2026. Código novo em `app/`. A prévia é `http://localhost:8090/`; o cockpit antigo permanece em `http://localhost:8051/` até o corte controlado.

## O que a prévia já entrega

- Oito telas com a identidade visual copiada do `iarandu-revenue`, sem alterar o checkout original ou o site público.
- Prospecção das duas tabelas mestres, deduplicada pela mesma regra do exportador. Em 25/10, a API retornou 846 agências — igual ao CSV canônico do Desktop. Busca por nome, cidade, UF, CNPJ e decisor, filtros por canal e paginação.
- Ficha de agência com tarefas, notas, etapa e proposta pelas APIs legadas; fichas só Sinapro também são legíveis pelo CNPJ.
- Prioridades e Metas contam apenas respostas confirmadas manualmente com canal, data e evidência em `crm_history`. Há cinco negócios antigos marcados como `resposta`, mas os textos indicam acompanhamento de retorno, não prova de resposta recebida; por isso a prévia mostra zero confirmadas até revisão. A ficha oferece um formulário de confirmação explícita, sem envio de mensagem.
- Snapshot historico da fase de preview; confira o estado operacional reconciliado ao final deste documento.
- E-mail permanece bloqueado no backend ate auditoria do motor real; consulte o estado operacional reconciliado ao final.
- O wrapper WhatsApp resolve a sessao OpenWA ativa dinamicamente e oferece preflight. A ultima tentativa foi parada com 0 envios aceitos.

## Verificações já feitas

- `python3 -m unittest -v .../app/test_crm_local.py`: 5 testes passaram, incluindo SQLite temporário e parada WhatsApp simulada.
- Sintaxe Python e JavaScript validada; oito rotas e assets retornaram HTTP 200; WebP e WebM com MIME correto.
- Busca Curitiba retornou 28 registros na consulta ao banco; formulário na prévia recebeu HTTP 403 e confirmou zero notas de teste gravadas.
- Console do portal sem erros após recarga. O comando de captura do Maestri expirou, mas as telas Campanhas, Prospecção e Prioridades foram renderizadas em Chrome headless e examinadas em `evidencias/`. A árvore do portal confirmou geometria móvel 390px sem rolagem horizontal do documento. Ainda falta revisão visual das cinco outras telas e teste de escrita apenas em banco isolado.

## Critérios antes de substituir a porta 8051

1. Resolver o motor de e-mail e gerar/revisar uma fila do dia sem enviar; preservar o layout HTML aprovado.
2. Testar em ambiente isolado o fluxo de início/pausa dos motores com mocks, além da parada do WhatsApp já simulada. Não usar produção como teste de envio.
3. Revisar visualmente as oito telas no desktop e no celular; captura do portal falhou por timeout de renderização nesta sessão.
4. Validar o rollback e o processo que ocupa 8051; nenhuma troca deve depender de encerrar agentes do Maestri.

## Estado operacional reconciliado - 25/10/2026

- `http://localhost:8090/campanhas` esta em execucao local com `preview=true`, confirmado em `GET /api/local/health` em 25/10/2026. A porta 8090 e a fonte da interface nova; nao houve corte para 8051.
- `http://localhost:8051/api/action/status-all` respondeu. Uma chamada GET a raiz `/` expirou nesta checagem; nao planejar corte enquanto a porta e seus fluxos nao forem revalidados.
- As fontes de status divergem: `status-all` da 8051 retornou IG=47; `/api/local/campaign-status` consultou os motores 5050 e 5051 separadamente e retornou 6 em cada. Os valores nao foram reconciliados; nao usar o HUD como fonte unica.
- Os motores IG 5050 e 5051 responderam a GET de status; na checagem anterior, ambos estavam parados. O Revenue OS tem campos de meta diaria independentes e selecao manual persistida por CNPJ. O preflight agora zera elegibilidade quando a selecao esta vazia. IG permanece bloqueado porque o contrato conhecido de `/api/bot/start` nao aceita a lista manual por CNPJ/handle.
- OpenWA respondeu com uma sessao `ready`; o motor WhatsApp resolve a sessao ativa via API antes de cada envio. O preflight le estado, meta e fila sem disparar mensagem.
- E-mail permanece bloqueado no Revenue OS ate auditoria do motor real. O runner da sessao anterior terminou parado: 2/5 mensagens aceitas pelo SMTP, 0 erros, e as outras 3 nao iniciadas. Aceite SMTP nao comprova entrega ao destinatario. O template canonico usa saudacao fixa `Prezada liderança da <strong>{agencia_nome}</strong>,` e ignora personalizacao por nome de socio.
- WhatsApp teve 0 envios aceitos nesta sessao; uma tentativa retornou HTTP 400 antes da parada. Nenhum DM novo foi iniciado nos bots 5050/5051. Envios seguem suspensos e a programacao automatica esta desativada.
- O banco canonico retornou 846 agencias; a tabela `agencias` do CRM antigo tem 453 linhas (distintos da contagem Sinapro bruta). O preflight de WhatsApp filtra contatos validados e ineditos pelos CNPJs selecionados, e o runner recebe a mesma lista persistida. A elegibilidade de e-mail tambem e filtrada pela lista, mas o envio continua bloqueado.
- Estado divergente preservado: `8051/api/action/status-all` reportou IG agregado=47, enquanto os motores 5050 e 5051 reportaram 6 enviados hoje cada. Nao reconciliado; nao usar HUD antigo como total da base nem como fonte unica.
- Nesta atualizacao nao houve envio nem teste de disparo. Validacao feita: sintaxe Python/JS e GET de health, campaign-status, prospecting-summary e `/campanhas`. O health confirmou `preview=true`; a selecao esta vazia e os quatro botoes de inicio devem permanecer bloqueados.
- Pendencias: auditoria read-only do contrato IG para filtro exato por lista/handle; validar mapeamento das selecoes e elegibilidade com Antigravity-4 e revisao independente de origem/contagem e wiring com Antigravity-2; reconciliar status entre fontes; revisar motor de email sem enviar; obter resposta confiavel da 8051 antes de qualquer corte.

## Execução e segurança

Modo mantido: `python3 /mnt/d/site-iarandu/planejamento-modernizacao-crm/app/server.py --port 8090` sem `--live` (`preview=true`). Envios reais e agendamento permanecem suspensos. A 8090 nao substitui a 8051 ate os criterios de corte e autorizacao de Diego.


## Adendo de wiring e estado de runtime - 25/10/2026

- Os trechos de auditoria anteriores neste README sao snapshots historicos. O checkout `planejamento-modernizacao-crm/app` agora calcula orcamento por meta restante e elegibilidade, mantem a selecao manual por CNPJ e bloqueia o inicio do IG se o bot nao comprovar `exact-handles-v1`, cobertura pendente e isolamento dos bancos.
- O start-email do codigo-fonte usa `email_runner.py`, revalida selecao, elegibilidade, reservas/aceites e meta antes do subprocesso, e passa o arquivo persistido `selected_agencies.json` por `--cnpjs-file`. A metrica distingue aceites SMTP de reservas e nunca chama aceite de entrega. O inicio depende de clique manual; `schedule.enabled` permanece falso.
- O canal WhatsApp limita o processo pela cota restante e candidatos do runner filtrados pela selecao e supressoes comuns. Telefone cadastral nao e indicador de WhatsApp elegivel. A base canonica tem 846 agencias; telefones gerais, contatos validados do runner e leads legados sao contagens distintas.
- O endpoint legado `/api/action/start-test-batch` responde 409 imediatamente; `_preflight_test_batch` e `_launch_test_batch` permanecem codigo legado inativo e nao sao caminho de campanha.
- O valor 5050 persistido em 50 e limitado a 25 quando carregado pelo codigo; os demais alvos sao preservados. O bot tambem limita a meta recebida ao `meta_hoje` efetivo do proprio motor (cap observado 25). A meta do 5051 permanece 15.
- **Runtime nao atualizado:** a checagem read-only mais recente informou `8090 preview=true`, config servida ainda com 5050=50 e preflight de email antigo; `build-info` e `/api/bot/preflight` retornam 404 em 5050/5051. A 8051 expirou em `/api/local/summary` e `/api/local/campaign-status`. Portanto, os arquivos descritos acima ainda nao estao carregados nas portas de runtime; nenhum servico foi reiniciado, nenhum build/cutover foi feito e nenhum envio foi iniciado.
- As contagens seguem nao reconciliadas entre HUD 8051 e os motores/banco datado. Nao usar 8051 como prova de entrega ou de ausencia de scheduler externo. Sem autorizacao de Diego, nao iniciar lotes.
- Validacao mock-only: `app/test_campaign_start_routes.py` cobre 12 casos via `unittest` (selecao por UF/Trade, quotas por canal, preflight IG/Email/WhatsApp e round-trip PowerShell JSON no echo HTTP mock, sem envio real); `euROBOexe/tests/test_web_campaign_start.py` cobre filtro exato, lista vazia e cap do bot com scheduler mockado. Os exit codes diretos estao no handoff desta sessao.
