# Plano para aprovação — CRM IArandú com visual do Revenue OS

Data: 25/10/2026. Estado: **planejamento; implementação ainda não iniciada**.

## Decisão solicitada

Transformar o CRM operacional em `http://localhost:8051/` na interface visual do `www.iarandu.xyz`, mantendo a porta 8051 e as funções reais do CRM. A implementação será feita nesta pasta nova e validada em prévia local antes da troca da interface ativa. O site público e o CRM atual continuarão como referências intactas durante o desenvolvimento.

## Fontes conferidas

- Vídeo original: `C:\Users\user\Downloads\Gravando 2026-10-25 004053.mp4` (3min34s), assistido por amostragem visual de todo o percurso e transcrito localmente. Evidências em `evidencias/`.
- Plano inicial: `D:\site-iarandu\PLANO_MODERNIZACAO_CRM_IARANDU_XYZ.md`.
- Site de referência: `https://www.iarandu.xyz/`, respondendo HTTP 200; fonte local `D:\iarandu-agency-web`, ligada a `ethosinteligencia-boop/iarandu-revenue`. O `main` do GitHub e o checkout local apontam para `6ff3903` na checagem de 25/10; há alterações locais não commitadas em `index.html`, `iarandu.css` e `iarandu.js` que serão preservadas.
- CRM atual: `C:\Users\user\iarandu_crm_cockpit.py`, em `localhost:8051`.

O plano inicial é uma boa referência visual, mas contém duas interpretações a corrigir: em **Prioridades**, Diego quer empresas que responderam; no **Ciclo comercial**, quer sites reais, sem sites demo. Contagens fixas como 401, 646, 885 ou 7 agentes “ativos” não serão tratadas como estado atual sem fonte operacional.

## O que Diego mostrou no vídeo

| Tempo aproximado | Tela | Comportamento pedido |
|---|---|---|
| 00:22–00:35 | Central | A página inicial do site passa a ser a entrada visual do CRM. |
| 00:35–01:22 | Campanhas | Os quatro cartões do monitor viram controles reais: Instagram pessoal 5050, Instagram oficial 5051, WhatsApp Gateway e Zoho SMTP. Cada Instagram mantém Iniciar, Painel, Pausar e Login Firefox. |
| 01:24–01:36 | Monitor e navegação | Manter a composição visual do computador e o retorno à página inicial. |
| 01:38–02:04 | Prospecção | Quadro branco com a base real de agências e busca funcional por empresa, cidade e UF; Diego demonstrou São Paulo e Curitiba. |
| 02:07–02:32 | Módulos atuais | Remover da **interface** o funil tradicional e “Hoje para conversar”; colocar retornos dos leads em Metas e a base completa em Prospecção. |
| 02:38–03:00 | Equipe e Ciclo comercial | Manter a experiência dos agentes; mostrar mapa e número de sites reais, sem métricas de sites demo. |
| 03:00–03:28 | Prioridades e Documentação | Priorizar empresas conforme respondam; documentar a operação do Revenue OS local. |

## Desenho funcional por tela

1. **Central (`/inicio`)**: reutilizar cenário, animações, personagens e menu lateral. Mostrar estado real da operação; se não houver telemetria dos agentes, exibir função/atribuição sem afirmar que estão ativos.
2. **Campanhas (`/campanhas`)**: manter a moldura do monitor. Quatro cartões ligados aos controles já existentes do 8051. O status deve vir das APIs dos motores, com estados distintos para indisponível, parado, iniciando, executando, pausando e erro. O clique deve mostrar a resposta real do servidor. Os perfis e históricos das contas 5050 e 5051 permanecem independentes; a mesma agência pode estar nas duas contas, mas a mesma conta não pode repetir contato.
3. **Prospecção (`/prospeccao`)**: tabela pesquisável da base canônica local, com nome, município/UF, decisor, contratos e canais com proveniência/status. Abas Mercado & Licitações, Oportunidades, Instagram, WhatsApp e E-mails. Busca por texto com acentos e paginação/virtualização se necessário. O CSV será exportação, não a fonte única de execução.
4. **Metas (`/metas`)**: respostas, reuniões e próximos passos baseados em interações reais; os marcos operacionais podem permanecer. Sem números ilustrativos apresentados como atuais.
5. **Equipe (`/equipe`)**: manter os sete personagens e drawers visuais. Funções e rotinas só aparecem como atividade ao vivo quando há evidência de execução.
6. **Ciclo comercial (`/ciclo-comercial`)**: manter Pesquisar → Qualificar → Conversar → Entregar; trocar os números fixos por contagens verificáveis de agências, regiões, sites reais e operação. “Site demo” não entra na métrica pedida.
7. **Prioridades (`/prioridades`)**: lista principal de empresas que responderam e exigem ação, ordenada pelo próximo passo/data. Valor de contratos pode ser critério secundário, não substituto de resposta. Preservar registro de oportunidade e histórico.
8. **Documentação (`/documentacao`)**: playbook do CRM local, canais, validação de contatos, histórico por conta, uso dos botões, fontes e recuperação de falhas. Retirar texto público genérico que não descreva a operação real.

## Arquitetura proposta

- Criar a aplicação nova **nesta pasta**, copiando apenas os assets e componentes visuais necessários do `iarandu-revenue` a partir da revisão `6ff3903`, comparada com a aparência publicada. `D:\iarandu-agency-web` tem alterações locais; não será sobrescrito para servir como base de cópia.
- Durante a construção, executar uma prévia em porta temporária separada. A passagem final mantém **um único endereço para Diego: `http://localhost:8051/`**.
- Manter as rotas existentes do backend de CRM para detalhes, tarefas, propostas, notas, exclusões, enriquecimento e motores. Criar uma camada de API de leitura própria para a interface nova; não duplicar regras de negócio em JavaScript.
- Servir HTML, CSS, JS, WebP e WebM locais pela mesma origem do CRM, com fallback para as oito rotas da interface. Não depender do servidor público nem de outro serviço de frontend para abrir o CRM.
- Ler `central.db`, `agencias_publicidade.db` e os bancos dos dois bots por interfaces controladas. Preservar esquemas, dados e histórico. A camada de leitura deve registrar fonte e horário de atualização; contagem de empresas será calculada, não fixada em código.
- Reutilizar os comandos de envio existentes só depois de verificar as respostas e as travas. A prévia usa respostas simuladas para ações de envio. A interface não pode iniciar campanhas ao carregar página, navegar ou atualizar status.
- Preservar a interface antiga como caminho temporário de retorno durante a migração; não apagar o código nem as rotas antigas antes dos testes de equivalência.

## Ordem de execução e critérios de aceite

| Fase | Entrega verificável | Aceite |
|---|---|---|
| 0. Congelar referência | Revisão do visual, inventário de APIs, comparação do site atual com a cópia local e cópia recuperável do servidor 8051. | Documento de diferenças e rota clara de retorno ao estado anterior. |
| 1. Casca visual | Oito rotas locais com a identidade do site e assets carregando na prévia. | Navegação, retorno à Central e telas desktop/móvel sem quebra visual. |
| 2. Dados de leitura | APIs e componentes para Prospecção, Metas, Ciclo e Prioridades. | Busca São Paulo/Curitiba; contagens batem com consultas ao banco; nenhum dado de demonstração aparece como real. |
| 3. Campanhas | Quatro cartões operacionais e estado real dos motores. | Testes sem envio real comprovam retorno de falhas, perfis 5050/5051 separados, pausa e ausência de disparo involuntário. |
| 4. Equivalência e troca | Conectar ações já existentes, documentação e substituição da tela ativa na porta 8051. | Checklist de tarefas, notas, propostas, revisão, exclusões e motores; comparação antes/depois; retorno funcional testado. |

O teste de acionamento real de campanhas requer fila auditada e decisão operacional específica de Diego; o teste da interface e das APIs pode terminar antes disso. A troca visual não será usada para presumir que bugs anteriores dos bots foram resolvidos.

## Equipe e coordenação

- **Codex-3:** arquitetura, integração da interface, revisão dos dados e testes finais; decide o corte para 8051 após aprovação de Diego.
- **Agente 2:** inventário do CRM atual, endpoints e fluxos a preservar; verificações pontuais de regressão.
- **Agente 4:** inventário das fontes de dados, filas e travas dos canais; verificações pontuais de proveniência e status.
- Antes da aprovação deste plano, as tarefas da equipe são somente leitura. Nenhum agente deve substituir a interface 8051 durante a revisão.

## Riscos já observados que o trabalho deve corrigir

- O site visual tem números, empresas de exemplo e textos públicos fixos; são úteis como referência gráfica, mas inadequados ao CRM real.
- O código atual do 8051 ainda tem valores fixos no HUD (`401 Agências`, valores de pipeline e oportunidades) e marca `email.is_running` como `False`. O novo painel deverá distinguir dado medido de dado indisponível.
- O site local possui um proxy `/crm-api` apenas de leitura; operações do CRM exigem integração explícita com as rotas do backend 8051. O proxy atual não é solução suficiente para os botões.
- Contatos, histórico e aprovação para disparo têm origens diferentes. A busca não deve promover um contato pendente a destinatário aprovado.
- A interface pública contém links comerciais externos. O CRM local precisa de navegação e controles internos apropriados, sem levar o operador para uma chamada comercial pública por engano.

## Inventários complementares dos agentes 2 e 4

Os dois agentes concluíram inspeções somente leitura. Ambos localizaram no cockpit as rotas `GET /api/action/status-all`, `GET /api/agencias` e ações `POST /api/action/start-instagram`, `stop-instagram`, `start-instagram-oficial`, `stop-instagram-oficial`, `start-whatsapp`, `stop-whatsapp` e `start-email`. Também apontaram as rotas de tarefas, negócios, propostas, notas e aprovação de enriquecimento que precisam continuar acessíveis. O login Firefox do perfil oficial tem rota própria (`/api/action/open-login-firefox`).

O agente 4 apontou riscos operacionais a testar na fase 0: trava diária do e-mail, parada do WhatsApp por lockfile, limites por conta do Instagram e possível cooldown multicanal. Esses controles não devem ser reproduzidos por suposição no frontend: sua existência, abrangência e persistência serão verificadas diretamente no código e com testes sem envio. Em especial, o botão de lote precisa bloquear duplo clique, mostrar o resultado real do backend e jamais iniciar processo pelo simples carregamento da página. Os números de pendentes/enviados relatados pelos agentes não entram no plano como métricas confirmadas; a interface lerá o estado atual de cada motor.

## Aprovação

Diego aprova este escopo e a criação da prévia local. Após a aprovação, começaremos pelas fases 0 e 1, mantendo o CRM existente disponível até o teste de equivalência. Ajustes de escopo indicados por Diego passam a integrar este documento antes da implementação.
