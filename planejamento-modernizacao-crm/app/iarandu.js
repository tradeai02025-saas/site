(function () {
  'use strict';

  var app = document.getElementById('app');
  var statusText = document.getElementById('statusText');
  var drawer = document.getElementById('drawer');
  var scrim = document.getElementById('scrim');
  var mainNav = document.getElementById('mainNav');
  var mobileMenuToggle = document.getElementById('mobileMenuToggle');
  var CRM_API = '/crm-api';

  var routeAliases = {
    '/': 'home', '/inicio': 'home', '/home': 'home',
    '/campanhas': 'launch', '/launch': 'launch',
    '/prospeccao': 'companies', '/companies': 'companies', '/coins': 'companies',
    '/equipe': 'team', '/team': 'team', '/teams': 'team',
    '/metas': 'milestones', '/milestones': 'milestones',
    '/ciclo-comercial': 'flywheel', '/flywheel': 'flywheel',
    '/prioridades': 'ranking', '/ranking': 'ranking', '/leaderboard': 'ranking',
    '/mensagens': 'messages',
    '/documentacao': 'docs', '/docs': 'docs'
  };

  var agents = {
    lead: { name: 'Orion', role: 'Direção comercial', subtitle: 'Coordena prioridades e decisões da operação', avatar: '/scene/characters/lead.webp', cadence: 'A cada 15 minutos', engine: 'Orquestração IArandú', bullets: ['Define prioridades e distribui tarefas entre os agentes.', 'Revisa pesquisa, abordagem e próximos passos antes do contato.', 'Transforma sinais do CRM em ações objetivas para a equipe.'], activity: ['Priorizando empresas com maior potencial', 'Revisando uma sequência de abordagem', 'Atualizando metas do pipeline'] },
    research: { name: 'Radar', role: 'Inteligência de mercado', subtitle: 'Encontra e qualifica as oportunidades certas', avatar: '/scene/characters/research.webp', cadence: 'Pesquisa contínua', engine: 'Dados públicos + IA', bullets: ['Pesquisa empresas, segmentos, regiões e decisores.', 'Cruza presença digital, prova social e dados públicos.', 'Entrega contexto verificável para uma abordagem personalizada.'], activity: ['Enriquecendo novos leads', 'Validando contatos', 'Mapeando oportunidades regionais'] },
    strategy: { name: 'Atlas', role: 'Estratégia e ofertas', subtitle: 'Transforma contexto em proposta comercial', avatar: '/scene/characters/strategy.webp', cadence: 'Sob demanda', engine: 'Web + automações', bullets: ['Cria páginas e demonstrações coerentes com cada empresa.', 'Organiza argumentos e chamadas para ação sem inventar fatos.', 'Prepara a experiência digital para reunião e fechamento.'], activity: ['Estruturando uma demonstração', 'Revisando proposta', 'Ajustando uma página de conversão'] },
    content: { name: 'Lumi', role: 'Conteúdo comercial', subtitle: 'Escreve mensagens relevantes e humanas', avatar: '/scene/characters/content.webp', cadence: 'Por oportunidade', engine: 'IA generativa', bullets: ['Cria mensagens de WhatsApp, e-mail e LinkedIn.', 'Adapta tom, oferta e prova social ao estágio da conversa.', 'Mantém clareza e objetividade em todos os canais.'], activity: ['Personalizando uma abertura', 'Preparando follow-up', 'Revisando proposta de valor'] },
    social: { name: 'Nexo', role: 'Prospecção social', subtitle: 'Abre conversas nos canais certos', avatar: '/scene/characters/social.webp', cadence: 'Cadência diária', engine: 'Social selling', bullets: ['Organiza abordagens por Instagram, LinkedIn e WhatsApp.', 'Usa sinais públicos recentes para criar pontos de contato.', 'Registra interação e próximo passo no CRM.'], activity: ['Preparando conexões', 'Validando um WhatsApp comercial', 'Registrando respostas'] },
    marketing: { name: 'Fluxo', role: 'Cadência multicanal', subtitle: 'Mantém o ritmo da prospecção', avatar: '/scene/characters/marketing.webp', cadence: 'Fluxo programado', engine: 'Automação + CRM', bullets: ['Orquestra intervalos entre contatos e follow-ups.', 'Evita mensagens duplicadas e respeita o histórico.', 'Sincroniza status, tarefas e responsáveis.'], activity: ['Executando follow-ups', 'Pausando leads respondidos', 'Sincronizando o funil'] },
    creative: { name: 'Íris', role: 'Direção criativa', subtitle: 'Dá forma visual à proposta', avatar: '/scene/characters/creative.webp', cadence: 'Por campanha', engine: 'Design assistido por IA', bullets: ['Cria apresentações e materiais alinhados ao prospect.', 'Transforma argumentos em peças claras para venda.', 'Revisa consistência visual antes de cada entrega.'], activity: ['Criando uma apresentação', 'Finalizando imagens', 'Revisando consistência visual'] }
  };

  var companies = [
    { name: 'Estrela do Chopp', category: 'Bar e choperia', city: 'Ponta Grossa', rating: 4.6, reviews: 5010, stage: 'Site-demo' },
    { name: 'Churrascaria Zancanaro', category: 'Churrascaria', city: 'Ponta Grossa', rating: 4.6, reviews: 2324, stage: 'Site-demo' },
    { name: 'Restaurante La Gôndola', category: 'Restaurante', city: 'Ponta Grossa', rating: 4.5, reviews: 2197, stage: 'Site-demo' },
    { name: 'Cia. do Espeto', category: 'Bar e grill', city: 'Ponta Grossa', rating: 4.5, reviews: 1665, stage: 'Site-demo' },
    { name: 'Panificadora Colonial', category: 'Padaria', city: 'Ponta Grossa', rating: 4.4, reviews: 1467, stage: 'Site-demo' },
    { name: 'Santana Padaria & Café', category: 'Padaria', city: 'Ponta Grossa', rating: 4.6, reviews: 1447, stage: 'Site-demo' },
    { name: 'Pizzaria Forno de Pedra', category: 'Pizzaria', city: 'Ponta Grossa', rating: 4.4, reviews: 1424, stage: 'Site-demo' },
    { name: 'Big Boss Games', category: 'Games e informática', city: 'Curitiba', rating: 5.0, reviews: 354, stage: 'Site-demo' },
    { name: 'Estética Danielle Bocchi', category: 'Estética', city: 'Curitiba', rating: 5.0, reviews: 518, stage: 'Site-demo' }
  ];

  function sceneBackground(file) {
    return '<img class="scene-background" src="/scene/' + file + '" alt="">';
  }

  function room(content, kind) {
    var file = kind === 'monitor' ? 'desk-screen.webp' : 'board-room.webp';
    var box = kind === 'monitor' ? 'monitor-box' : 'board-box';
    var stage = kind === 'monitor' ? 'monitor-stage' : 'board-stage';
    return '<section class="scene room-scene"><div class="room-stage ' + stage + '">' + sceneBackground(file) + '<div class="' + box + '">' + content + '</div></div></section>';
  }

  function syncBoardGeometry() {
    var board = document.querySelector('.board-box');
    if (!board || window.innerWidth <= 650) return;
    var u = Math.min(
      Math.max(window.innerHeight / 940, window.innerWidth / 1672),
      window.innerHeight / 601.6,
      window.innerWidth / 1640
    );
    var perspective = -0.0001656 / u;
    var translateY = 32 * u;
    board.style.transform = 'matrix3d(.825,-.05374,0,' + perspective + ',0,.9009,0,0,0,0,1,0,0,' + translateY + ',0,1)';
  }

  function video(key, style) {
    return '<video class="agent-video" muted loop playsinline autoplay preload="auto" poster="/scene/characters/' + key + '.webp" style="' + style + '"><source src="/scene/characters/' + key + '.webm" type="video/webm"><source src="/scene/characters/' + key + '.mov" type="video/mp4"></video>';
  }

  function hotspot(key, style, team) {
    var a = agents[key];
    return '<button class="' + (team ? 'team-hotspot' : 'agent-hotspot') + '" data-agent="' + key + '" aria-label="' + a.name + ', ' + a.role + '" style="' + style + '"><span class="agent-label">' + a.name + ' · ' + a.role + '</span></button>';
  }

  function homeView() {
    var videos = '';
    videos += video('strategy', 'left:calc(954 * var(--office-u));top:calc(77 * var(--office-u));height:calc(128 * var(--office-u));width:calc(120 * var(--office-u));transform:scaleX(-1);clip-path:inset(0 0 21.88% 22.38%)');
    videos += video('creative', 'left:calc(1182 * var(--office-u));top:calc(384 * var(--office-u));height:calc(188 * var(--office-u));width:calc(168 * var(--office-u));clip-path:polygon(0 0,100% 0,100% 91.75%,0 83.94%)');
    videos += video('marketing', 'left:calc(708 * var(--office-u));top:calc(438 * var(--office-u));height:calc(156 * var(--office-u));width:calc(123 * var(--office-u))');
    videos += video('content', 'left:calc(410 * var(--office-u));top:calc(448 * var(--office-u));height:calc(193 * var(--office-u));width:calc(146 * var(--office-u))');
    videos += video('lead', 'left:calc(638 * var(--office-u));top:calc(570 * var(--office-u));height:calc(157 * var(--office-u));width:calc(163 * var(--office-u))');
    videos += video('social', 'left:calc(1087 * var(--office-u));top:calc(605 * var(--office-u));height:calc(292 * var(--office-u));width:calc(226 * var(--office-u))');
    videos += video('research', 'left:calc(165 * var(--office-u));top:calc(620 * var(--office-u));height:calc(289 * var(--office-u));width:calc(189 * var(--office-u))');
    var spots = '';
    spots += hotspot('lead', 'left:calc(585 * var(--office-u));top:calc(572 * var(--office-u));width:calc(359 * var(--office-u));height:calc(179 * var(--office-u))');
    spots += hotspot('research', 'left:calc(10 * var(--office-u));top:calc(603 * var(--office-u));width:calc(447 * var(--office-u));height:calc(232 * var(--office-u))');
    spots += hotspot('strategy', 'left:calc(819 * var(--office-u));top:calc(34 * var(--office-u));width:calc(231 * var(--office-u));height:calc(188 * var(--office-u))');
    spots += hotspot('content', 'left:calc(342 * var(--office-u));top:calc(431 * var(--office-u));width:calc(260 * var(--office-u));height:calc(197 * var(--office-u))');
    spots += hotspot('social', 'left:calc(953 * var(--office-u));top:calc(595 * var(--office-u));width:calc(421 * var(--office-u));height:calc(246 * var(--office-u))');
    spots += hotspot('marketing', 'left:calc(590 * var(--office-u));top:calc(398 * var(--office-u));width:calc(248 * var(--office-u));height:calc(185 * var(--office-u))');
    spots += hotspot('creative', 'left:calc(1139 * var(--office-u));top:calc(355 * var(--office-u));width:calc(251 * var(--office-u));height:calc(192 * var(--office-u))');
    var jarvisTotem = '<div class="jarvis-board" id="jarvisTotem" aria-label="Orbe Neural IArandú 80K" title="IArandú Neural Engine · 80.000 Quanta"><canvas id="jarvisCanvas" class="jarvis-canvas"></canvas></div>';
    var ecosystem = '<aside class="product-board" aria-label="Ecossistema IArandú"><img class="product-logo" src="/scene/iarandu-logo.png" alt="IArandú"><span class="product-kicker">ECOSSISTEMA</span><a href="https://www.iarandu.com.br" target="_blank" rel="noopener"><strong>IArandú SaaS</strong><small>Software e automações</small><b>iarandu.com.br ↗</b><span class="product-viz product-viz-saas" aria-hidden="true"><i style="--bar:35%"></i><i style="--bar:52%"></i><i style="--bar:44%"></i><i style="--bar:72%"></i><i style="--bar:60%"></i><i style="--bar:90%"></i></span></a><a href="https://pinnaai.store" target="_blank" rel="noopener"><strong>Pinna AI</strong><small>SMM e crescimento digital</small><b>pinnaai.store ↗</b><span class="product-viz product-viz-pinna" aria-hidden="true"><i style="--bar:58%"></i><i style="--bar:30%"></i><i style="--bar:78%"></i><i style="--bar:48%"></i><i style="--bar:92%"></i><i style="--bar:66%"></i></span></a><a class="product-author" href="https://diegomaldonado.vercel.app/homepage" target="_blank" rel="noopener"><span class="product-author-copy"><small>CRIADO POR</small><strong>Diego Maldonado</strong><b>Conheça o desenvolvedor ↗</b></span></a></aside>';
    return '<section class="scene home-scene"><div class="office-stage">' + sceneBackground('office-iarandu-pt-v3.png') + jarvisTotem + ecosystem + videos + spots + '</div>' +
      '<div class="hero"><h1>Sua equipe comercial de IA</h1><p>que pesquisa, qualifica e agenda reuniões onde seus clientes estão — no Instagram, WhatsApp e E-mail.</p><div class="hero-actions"><a class="pill-button primary" href="/campanhas" data-nav>Ver campanhas →</a><a class="pill-button" href="/equipe" data-nav>Conhecer agentes</a></div></div>' +
      '<div class="legal"><a href="/documentacao" data-nav>Documentação</a><a href="/ciclo-comercial" data-nav>Como funciona</a></div></section>';
  }

  function launchView() {
    var content = '<div class="kicker">Campanhas gerenciadas</div><h1 class="route-title sans">Sua operação comercial, conduzida por nós.</h1><p class="route-intro">Você define o objetivo. A IArandú configura e opera pesquisa, cadência e canais na nossa plataforma — usando o Instagram, o WhatsApp e os e-mails autorizados pela sua empresa.</p>' +
      '<div class="service-journey"><article><span>01</span><h2>Briefing comercial</h2><p>Entendemos região, cliente ideal, oferta, metas e limites da campanha.</p></article><article><span>02</span><h2>Configuração assistida</h2><p>Conectamos os canais autorizados e preparamos mensagens, filtros e cadências.</p></article><article><span>03</span><h2>Operação pela IArandú</h2><p>Nossa plataforma executa, acompanha e organiza a prospecção sem instalar sistemas na sua empresa.</p></article><article><span>04</span><h2>Contatos entregues</h2><p>Sua equipe recebe oportunidades, contexto e próximos passos para conduzir a venda.</p></article></div>' +
      '<section class="campaign-offer"><div><span>O QUE O CLIENTE RECEBE</span><h2>Conversas comerciais prontas para avançar.</h2><p>Leads qualificados, histórico de contato, origem, interesse e orientação para o próximo passo.</p><div class="channel-pills"><b>Instagram</b><b>WhatsApp</b><b>E-mail</b><b>LinkedIn</b></div></div><aside><small>FALE DIRETAMENTE COM</small><strong>Diego Maldonado</strong><p>Apresentamos o formato ideal para sua operação e configuramos tudo para você.</p><a class="black-button inline-action" href="https://wa.me/5541998136312?text=Olá%20Diego!%20Quero%20conhecer%20a%20operação%20comercial%20IArandú." target="_blank" rel="noopener">Conversar no WhatsApp →</a></aside></section>';
    return room(content, 'monitor');
  }

  function companyCards(items) {
    if (!items.length) return '<div class="empty">Nenhuma empresa encontrada com estes filtros.</div>';
    return items.map(function (c) { return '<article class="company-card"><h3>' + c.name + '</h3><p>' + c.category + ' · ' + c.city + '</p><span class="tag">' + c.stage + '</span><div class="company-meta"><span>NOTA<strong>★ ' + c.rating.toFixed(1) + '</strong></span><span>AVALIAÇÕES<strong>' + c.reviews.toLocaleString('pt-BR') + '</strong></span></div></article>'; }).join('');
  }

  function companiesView() {
    var content = '<div class="kicker">Revenue OS · visão consolidada</div><h1 class="route-title">PROSPECÇÃO MULTICANAL</h1><p class="route-intro">Indicadores reais da operação gerenciada pela IArandú. Dados de contato e mensagens permanecem protegidos dentro da plataforma.</p>' +
      '<div class="channel-summary" id="channelSummary"><div class="channel-stat"><span>Instagram</span><strong id="sumInstagram">93</strong><small>fila + histórico</small></div><div class="channel-stat"><span>WhatsApp</span><strong id="sumWhatsapp">19</strong><small>interações auditadas</small></div><div class="channel-stat"><span>E-mails</span><strong id="sumEmail">52</strong><small>entregues</small></div><div class="channel-stat"><span>Inbound</span><strong id="sumInbound">3</strong><small>oportunidades</small></div></div>' +
      '<div class="filter-row" id="revenueTabs"><button class="filter-chip active" data-channel="market">Mercado & licitações</button><button class="filter-chip" data-channel="inbound">Oportunidades</button><button class="filter-chip" data-channel="instagram">Instagram</button><button class="filter-chip" data-channel="whatsapp">WhatsApp</button><button class="filter-chip" data-channel="email">E-mails</button></div>' +
      '<div class="revenue-panel" id="revenuePanel"><div class="public-preview"><strong>Visão da operação comercial</strong><p>Carregando a leitura consolidada mais recente do IArandú Revenue OS.</p><div><span>Pesquisa</span><span>Qualificação</span><span>Cadência</span><span>Oportunidades</span></div></div></div>';
    return room(content, 'board');
  }

  function teamView() {
    var spots = '';
    spots += hotspot('creative', 'left:10.5%;top:23%;width:14%;height:58%', true);
    spots += hotspot('social', 'left:24%;top:29%;width:13%;height:55%', true);
    spots += hotspot('research', 'left:29%;top:7%;width:18%;height:38%', true);
    spots += hotspot('lead', 'left:36%;top:22%;width:23%;height:72%', true);
    spots += hotspot('strategy', 'left:51%;top:6%;width:16%;height:39%', true);
    spots += hotspot('content', 'left:58%;top:25%;width:19%;height:62%', true);
    spots += hotspot('marketing', 'left:75%;top:22%;width:20%;height:66%', true);
    return '<section class="scene team-scene"><div class="team-stage">' + spots + '<div class="team-shade"></div></div><div class="team-caption"><div class="kicker">Equipe autônoma IArandú</div><h1>Conheça seus agentes.</h1><p>Clique em cada integrante para ver sua função, rotina e entregas dentro da operação comercial.</p></div></section>';
  }

  function milestonesView() {
    var rows = [
      ['✓', 'Motor de prospecção estruturado', 'Busca, filtros e qualificação conectados à operação.', 'Concluído'],
      ['✓', 'Primeira base regional', 'Curitiba e Ponta Grossa organizadas por categoria e prova social.', 'Concluído'],
      ['✓', 'Sites-demo comerciais', '11 demonstrações preparadas para abordagem e validação.', 'Concluído'],
      ['→', 'Cadência comercial', 'Mensagens, retornos e próximas ações centralizadas no CRM.', 'Em execução'],
      ['◎', 'Aprendizado por categoria', 'Padrões de abordagem e oferta por segmento.', 'Próxima etapa'],
      ['◎', 'Escala com qualidade', 'Aumentar produção mantendo pesquisa e revisão factual.', 'Próxima etapa']
    ];
    var list = rows.map(function (r) { return '<div class="list-row"><span class="round-icon">' + r[0] + '</span><div><strong>' + r[1] + '</strong><small>' + r[2] + '</small></div><span class="row-time">' + r[3] + '</span></div>'; }).join('');
    return room('<div class="kicker">Evolução da operação</div><h1 class="route-title">METAS</h1><p class="route-intro">Marcos reais que mostram o que já foi construído e o que entra no próximo ciclo.</p><div class="cards-list">' + list + '</div><h2 class="marker-title">Princípio de qualidade</h2><div class="doc-callout">Nenhum volume compensa informação errada. A operação só avança quando empresa, contato, mensagem e proposta pertencem à mesma conta.</div>', 'board');
  }

  function flywheelView() {
    return room('<div class="kicker">Sistema de crescimento</div><h1 class="route-title">CICLO COMERCIAL</h1><p class="route-intro">Cada interação melhora o contexto da próxima. A IArandú executa o ciclo na nossa plataforma e entrega oportunidades organizadas para sua equipe.</p><div class="score-grid"><div class="score-card"><span>BASE MAPEADA</span><strong>885</strong>leads organizados</div><div class="score-card"><span>SITES-DEMO</span><strong>11</strong>propostas visuais</div><div class="score-card"><span>AGENTES</span><strong>7</strong>funções coordenadas</div><div class="score-card"><span>OPERAÇÃO</span><strong>1</strong>gestão central</div></div><div class="cycle-track"><article><b>1</b><strong>Pesquisar</strong><small>Empresas e sinais</small></article><i>→</i><article><b>2</b><strong>Qualificar</strong><small>Contato e contexto</small></article><i>→</i><article><b>3</b><strong>Conversar</strong><small>Cadência relevante</small></article><i>→</i><article><b>4</b><strong>Entregar</strong><small>Oportunidade pronta</small></article></div><div class="cycle-result"><span>RESULTADO DO CICLO</span><strong>Mais contexto. Melhor conversa. Mais oportunidades.</strong><p>Respostas e objeções voltam para a inteligência e tornam a próxima abordagem mais precisa.</p></div>', 'board');
  }

  function rankingRows(items) {
    return items.map(function (c, i) { return '<tr><td>' + (i + 1) + '</td><td>' + c.name + '</td><td>' + c.category + '</td><td class="rating">★ ' + c.rating.toFixed(1) + '</td><td>' + c.reviews.toLocaleString('pt-BR') + '</td></tr>'; }).join('');
  }

  function rankingView() {
    return room('<div class="kicker">Radar VIP · dados reais</div><h1 class="route-title">PRIORIDADES</h1><p class="route-intro">Decisores e contas com maior valor contratual, trazidos diretamente do Revenue OS.</p><div class="priority-summary"><div><span>PIPELINE MAPEADO</span><strong>R$ 17,17 Bi</strong></div><div><span>BASE HOMOLOGADA</span><strong>288 agências</strong></div></div><div class="revenue-panel" id="priorityPanel"><div class="loading-state">Carregando decisores e contratos...</div></div>', 'board');
  }

  function docsView() {
    var sections = [
      ['01', 'ICP e região', 'Defina cidade, perfil de empresa, porte e sinais comerciais antes de iniciar a busca.', ['Comece com uma região controlada.', 'Registre critérios de inclusão e exclusão.', 'Não misture segmentos sem uma hipótese comercial.']],
      ['02', 'Pesquisa e fontes', 'Use Google Maps, perfil público, Instagram e demais fontes oficiais da própria empresa.', ['Prefira informações publicadas pela empresa.', 'Confirme nome, endereço e contato.', 'Não invente serviços, preços ou diferenciais.']],
      ['03', 'Qualificação', 'O lead entra na fila apenas depois que o contato e a oportunidade foram verificados.', ['Confirme ausência de site próprio.', 'Diferencie telefone fixo de WhatsApp.', 'Revise duplicidades e empresas do mesmo grupo.']],
      ['04', 'Equipe de agentes', 'Cada agente tem uma responsabilidade clara dentro do fluxo.', ['Radar pesquisa.', 'Atlas prepara a proposta.', 'Lumi escreve.', 'Fluxo coordena a cadência.', 'Orion supervisiona.']],
      ['05', 'Sites-demo', 'A demonstração deve parecer um site terminado e respeitar a identidade real do negócio.', ['Pesquisar Instagram antes de criar.', 'Usar imagens fortes e coerentes.', 'Separar avisos comerciais do site oficial.']],
      ['06', 'Abordagem', 'A mensagem apresenta contexto real, a iniciativa tomada e um próximo passo simples.', ['Identifique-se como Diego Maldonado.', 'Envie o link correto da empresa correta.', 'Não dispare antes da revisão final.']],
      ['07', 'Pipeline', 'Todo contato precisa de status, histórico e próxima ação no CRM.', ['Novo.', 'Contato preparado.', 'Mensagem enviada.', 'Respondeu.', 'Reunião.', 'Proposta.']],
      ['08', 'Métricas', 'Meça qualidade e conversão, não apenas quantidade.', ['Contatos válidos.', 'Respostas.', 'Reuniões marcadas.', 'Propostas enviadas.', 'Vendas.']],
      ['09', 'Dados e segurança', 'Credenciais e tokens devem ficar fora do código e nunca aparecer na interface.', ['Use variáveis de ambiente.', 'Revogue chaves expostas.', 'Mantenha um histórico auditável.']],
      ['10', 'Operação gerenciada', 'A plataforma é operada pela equipe IArandú. O cliente não precisa instalar nem administrar o sistema.', ['Configuramos o Instagram autorizado pela empresa.', 'Configuramos o WhatsApp e os e-mails autorizados.', 'Entregamos contatos, contexto e próximos passos para a equipe comercial.']]
    ];
    var chips = sections.map(function (s) { return '<a class="doc-chip" href="#doc-' + s[0] + '">' + s[0] + ' · ' + s[1] + '</a>'; }).join('');
    var body = sections.map(function (s) { return '<section class="doc-section" id="doc-' + s[0] + '"><h2><span>' + s[0] + '</span>' + s[1] + '</h2><p>' + s[2] + '</p><ul>' + s[3].map(function (x) { return '<li>' + x + '</li>'; }).join('') + '</ul></section>'; }).join('');
    return room('<div class="kicker">Como entregamos o serviço</div><h1 class="route-title">DOCUMENTAÇÃO</h1><p class="route-intro">Uma visão transparente do método usado pela IArandú para pesquisar, abordar e entregar oportunidades comerciais aos clientes.</p><div class="doc-chips">' + chips + '</div><div class="doc-callout"><strong>Modelo do serviço:</strong> operamos a tecnologia na IArandú, configuramos os canais autorizados do cliente e entregamos os contatos qualificados para sua equipe.</div>' + body, 'board');
  }

  var views = { home: homeView, launch: launchView, companies: companiesView, team: teamView, milestones: milestonesView, flywheel: flywheelView, ranking: rankingView, messages: function () { return ''; }, docs: docsView };
  var titles = { home: 'Central — IArandú CRM Local', launch: 'Campanhas — IArandú CRM Local', companies: 'Prospecção — IArandú CRM Local', team: 'Equipe — IArandú CRM Local', milestones: 'Metas — IArandú CRM Local', flywheel: 'Ciclo comercial — IArandú CRM Local', ranking: 'Prioridades — IArandú CRM Local', messages: 'Mensagens — IArandú CRM Local', docs: 'Documentação — IArandú CRM Local' };
  var statuses = { home: 'CRM local', launch: 'Campanhas locais', companies: 'Base comercial', team: 'Equipe de agentes', milestones: 'Retornos reais', flywheel: 'Ciclo comercial', ranking: 'Respostas e próximos passos', messages: 'Modelos oficiais de abordagem', docs: 'Manual operacional' };

  function currentRoute() {
    if (location.protocol === 'file:') return routeAliases[location.hash.replace('#', '') || '/'] || 'home';
    return routeAliases[location.pathname.replace(/\/$/, '') || '/'] || 'home';
  }

  function pathFor(route) {
    var paths = { home: '/inicio', launch: '/campanhas', companies: '/prospeccao', team: '/equipe', milestones: '/metas', flywheel: '/ciclo-comercial', ranking: '/prioridades', messages: '/mensagens', docs: '/documentacao' };
    return paths[route] || '/inicio';
  }

  function navigate(path) {
    if (location.protocol === 'file:') location.hash = path;
    else history.pushState({}, '', path);
    render();
  }

  function setMobileMenuOpen(open) {
    mainNav.classList.toggle('mobile-open', open);
    mobileMenuToggle.setAttribute('aria-expanded', String(open));
    mobileMenuToggle.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
    mobileMenuToggle.textContent = open ? 'Fechar' : 'Menu';
  }

  function closeMobileMenu() { setMobileMenuOpen(false); }
  mobileMenuToggle.addEventListener('click', function () {
    setMobileMenuOpen(mobileMenuToggle.getAttribute('aria-expanded') !== 'true');
  });

  function render() {
    var route = currentRoute();
    closeDrawer();
    closeMobileMenu();
    document.body.setAttribute('data-route', route);
    document.title = titles[route];
    statusText.textContent = statuses[route];
    app.innerHTML = window.IaranduCRM && window.IaranduCRM.views[route]
      ? window.IaranduCRM.views[route]() : views[route]();
    syncBoardGeometry();
    document.querySelectorAll('[data-route]').forEach(function (link) { link.classList.toggle('active', link.getAttribute('data-route') === route); });
    bindView(route);
    if (window.IaranduCRM) window.IaranduCRM.bind(route);
    if (route === 'home') { if (window.JarvisOrb) window.JarvisOrb.mount('#jarvisTotem'); } else { if (window.JarvisOrb) window.JarvisOrb.pause(); }
    window.scrollTo(0, 0);
  }

  window.addEventListener('resize', syncBoardGeometry);

  function esc(value) {
    return String(value == null ? '' : value).replace(/[&<>\"']/g, function (char) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '\"': '&quot;', "'": '&#39;' }[char];
    });
  }

  function api(path) {
    return fetch(CRM_API + path, { cache: 'no-store' }).then(function (response) {
      if (!response.ok) throw new Error('Revenue OS indisponível');
      return response.json();
    });
  }

  function apiSequence(paths) {
    var values = [];
    return paths.reduce(function (chain, path) {
      return chain.then(function () { return api(path); }).then(function (value) { values.push(value); });
    }, Promise.resolve()).then(function () { return values; });
  }

  function snapshot() {
    var localHost = /^(localhost|127\.0\.0\.1)$/.test(window.location.hostname);
    var live = localHost ? api('/snapshot') : Promise.reject(new Error('Snapshot público'));
    return live.catch(function () {
      return fetch('/public-data/snapshot.json', { cache: 'no-store' }).then(function (response) {
        if (!response.ok) throw new Error('Visão pública indisponível');
        return response.json();
      });
    });
  }

  function unavailable(message) {
    return '<div class="connection-error"><strong>Visão consolidada temporariamente indisponível</strong><span>Os dados protegidos continuam sendo processados na plataforma IArandú. Esta vitrine não oferece acesso à área técnica.</span></div>';
  }

  function loadHomeLive() {
    snapshot().then(function (data) {
      var inbound = data['/api/inbound-opportunities'] || [];
      var instagram = data['/api/instagram-history'] || [];
      var whatsapp = data['/api/whatsapp-live'] || [];
      var email = data['/api/email-data'] || { stats: {} };
      var total = instagram.length + whatsapp.length + Number(email.stats.enviados || 0);
      document.getElementById('homeInteractions').textContent = total || 97;
      document.getElementById('homeHot').textContent = inbound.length || 2;
      document.getElementById('homeLiveState').textContent = 'CRM AO VIVO';
    }).catch(function () { document.getElementById('homeLiveState').textContent = 'CRM LOCAL'; });
  }

  function loadCampaignLive() {
    snapshot().then(function (data) {
      var live = data['/api/bot-live'] || {};
      var kpi = live.kpi || {};
      document.getElementById('botTarget').textContent = live.active_candidate && live.active_candidate.nome || 'Motor comercial preparado';
      document.getElementById('botState').textContent = live.is_running ? 'ATIVO' : 'PRONTO';
      document.getElementById('botPending').textContent = kpi.pendentes == null ? '—' : kpi.pendentes;
      document.getElementById('botSent').textContent = kpi.enviados_hoje == null ? '—' : kpi.enviados_hoje;
      document.getElementById('botGoal').textContent = kpi.meta_hoje == null ? '—' : kpi.meta_hoje;
      document.getElementById('botRate').textContent = kpi.taxa_sucesso || '—';
    }).catch(function () { document.getElementById('botTarget').textContent = 'Abra o Revenue OS na porta 8051'; document.getElementById('botState').textContent = 'OFFLINE'; });
  }

  function revenueTable(headings, rows) {
    return '<div class="table-wrap"><table class="revenue-table"><thead><tr>' + headings.map(function (h) { return '<th>' + esc(h) + '</th>'; }).join('') + '</tr></thead><tbody>' + rows.map(function (row) { return '<tr>' + row.map(function (cell) { return '<td>' + cell + '</td>'; }).join('') + '</tr>'; }).join('') + '</tbody></table></div>';
  }

  function renderRevenueChannel(channel, data) {
    var panel = document.getElementById('revenuePanel');
    if (!panel) return;
    if (channel === 'market') {
      var market = (data.market || []).slice(0, 18);
      panel.innerHTML = '<div class="panel-heading"><div><span>INTELIGÊNCIA DE MERCADO & LICITAÇÕES</span><strong>' + market.length + ' contas na leitura pública</strong></div><input class="input compact" id="revenueSearch" placeholder="Buscar empresa ou cidade"></div>' + revenueTable(['Empresa', 'Qualificação', 'Potencial', 'Canais validados', 'Localidade'], market.map(function (r) { var channels = [r.site ? 'Site' : '', r.instagram ? 'Instagram' : '', r.whatsapp ? 'WhatsApp' : '', r.email ? 'E-mail' : ''].filter(Boolean).join(' · '); return ['<strong>' + esc(r.nome_fantasia) + '</strong><small>Conta mapeada</small>', '<strong>' + esc(r.dono_cargo || 'Decisor em pesquisa') + '</strong><small>Dados protegidos</small>', '<b class="gold">' + esc(r.valor_contratos) + '</b><small>' + esc(r.orgao) + '</small>', esc(channels || 'Em auditoria'), esc(r.uf + ' · ' + r.municipio)]; })) ;
    } else if (channel === 'inbound') {
      panel.innerHTML = '<div class="panel-heading"><div><span>OPORTUNIDADES & INBOUND</span><strong>Retornos que pedem ação comercial</strong></div></div>' + (data.inbound || []).map(function (r) { return '<article class="inbound-item"><div><strong>' + esc(r.agencia || 'Conta qualificada') + '</strong><span class="status-pill">' + esc(r.status_etapa) + '</span><small>' + esc(r.uf) + ' · ' + esc(r.canal_origem) + '</small></div><p>Oportunidade registrada e preparada para encaminhamento à equipe comercial.</p><footer><b>Contato protegido</b><span>' + esc(r.contratos_relevantes) + '</span></footer></article>'; }).join('');
    } else if (channel === 'instagram') {
      var ig = (data.instagram || []).slice(0, 20);
      panel.innerHTML = '<div class="panel-heading"><div><span>MOTOR INSTAGRAM</span><strong>Histórico consolidado de abordagens</strong></div></div>' + revenueTable(['Conta', 'Canal', 'Localidade', 'Status'], ig.map(function (r) { return ['<strong>' + esc(r.nome) + '</strong>', 'Instagram autorizado', esc(r.municipio), '<span class="status-pill">' + esc(r.status) + '</span><small>' + esc(r.enviado_em) + '</small>']; }));
    } else if (channel === 'whatsapp') {
      var wa = (data.whatsapp || []).slice(0, 20);
      panel.innerHTML = '<div class="panel-heading"><div><span>WHATSAPP INTELIGENTE</span><strong>Interações auditadas</strong></div></div>' + revenueTable(['Conta', 'Canal', 'Data', 'Status'], wa.map(function (r) { return ['<strong>' + esc(r.agencia) + '</strong>', 'WhatsApp autorizado', esc(r.enviado_em), '<span class="status-pill">' + esc(r.status) + '</span>']; }));
    } else {
      var emails = ((data.email || {}).leads || []).slice(0, 20);
      panel.innerHTML = '<div class="panel-heading"><div><span>E-MAILS INSTITUCIONAIS</span><strong>Esteira de contatos autorizados</strong></div></div>' + revenueTable(['Conta', 'Potencial', 'Canal', 'Status'], emails.map(function (r) { return ['<strong>' + esc(r.agencia) + '</strong>', '<b class="gold">' + esc(r.valor_contrato) + '</b>', 'E-mail institucional', '<span class="status-pill">' + esc(r.status) + '</span>']; }));
    }
    var search = document.getElementById('revenueSearch');
    if (search) search.addEventListener('input', function () { var term = search.value.toLowerCase(); panel.querySelectorAll('tbody tr').forEach(function (row) { row.hidden = row.textContent.toLowerCase().indexOf(term) === -1; }); });
  }

  function bindRevenue() {
    var cache = { market: [], inbound: [], instagram: [], queue: [], whatsapp: [], email: { stats: {}, leads: [] } };
    snapshot().then(function (data) {
      cache = { market: data['/api/mercado-intelligence'] || [], inbound: data['/api/inbound-opportunities'] || [], instagram: data['/api/instagram-history'] || [], queue: data['/api/instagram-queue'] || [], whatsapp: data['/api/whatsapp-live'] || [], email: data['/api/email-data'] || { stats: {}, leads: [] } };
      document.getElementById('sumInstagram').textContent = (cache.instagram.length + cache.queue.length);
      document.getElementById('sumWhatsapp').textContent = cache.whatsapp.length;
      document.getElementById('sumEmail').textContent = cache.email.stats && cache.email.stats.enviados || 0;
      document.getElementById('sumInbound').textContent = cache.inbound.length;
      renderRevenueChannel('market', cache);
    }).catch(function (error) { document.getElementById('revenuePanel').innerHTML = unavailable(error.message); });
    document.querySelectorAll('[data-channel]').forEach(function (button) { button.addEventListener('click', function () { document.querySelectorAll('[data-channel]').forEach(function (b) { b.classList.remove('active'); }); button.classList.add('active'); renderRevenueChannel(button.getAttribute('data-channel'), cache); }); });
  }

  function loadPriorities() {
    snapshot().then(function (data) {
      var vip = data['/api/contatos-quentes'] || [];
      var market = data['/api/mercado-intelligence'] || [];
      var rows = vip.length ? vip.slice(0, 16).map(function (r) { return ['<strong>' + esc(r.agencia) + '</strong><small>' + esc(r.orgao) + '</small>', '<strong>' + esc(r.cargo || 'Decisor validado') + '</strong><small>Identidade protegida</small>', '<span class="status-pill">Contato validado</span>', '<b class="gold">' + esc(r.contrato) + '</b>', 'Abordagem executiva preparada']; }) : market.slice(0, 16).map(function (r) { return ['<strong>' + esc(r.nome_fantasia) + '</strong><small>' + esc(r.orgao) + '</small>', '<strong>' + esc(r.dono_cargo || 'Em pesquisa') + '</strong><small>Identidade protegida</small>', '<span class="status-pill">Canal auditado</span>', '<b class="gold">' + esc(r.valor_contratos) + '</b>', esc(r.uf + ' · ' + r.municipio)]; });
      document.getElementById('priorityPanel').innerHTML = revenueTable(['Empresa', 'Perfil decisor', 'Qualificação', 'Potencial', 'Contexto'], rows);
    }).catch(function (error) { document.getElementById('priorityPanel').innerHTML = unavailable(error.message); });
  }

  function bindView(route) {
    document.querySelectorAll('[data-agent]').forEach(function (el) { el.addEventListener('click', function () { openAgent(el.getAttribute('data-agent')); }); });
    document.querySelectorAll('[data-nav]').forEach(function (el) { el.addEventListener('click', function (e) { e.preventDefault(); navigate(el.getAttribute('href')); }); });
    if (!window.IaranduCRM || !window.IaranduCRM.views[route]) {
      if (route === 'companies') bindRevenue();
      if (route === 'ranking') loadPriorities();
      if (route === 'docs') bindDocs();
    }
  }

  function bindCompanies() {
    var input = document.getElementById('companySearch');
    var grid = document.getElementById('companyGrid');
    var active = 'all';
    function refresh() {
      var term = input.value.trim().toLowerCase();
      var items = companies.filter(function (c) {
        var hay = (c.name + ' ' + c.category + ' ' + c.city).toLowerCase();
        return (!term || hay.indexOf(term) !== -1) && (active === 'all' || hay.indexOf(active) !== -1);
      });
      grid.innerHTML = companyCards(items);
    }
    input.addEventListener('input', refresh);
    document.getElementById('clearSearch').addEventListener('click', function () { input.value = ''; active = 'all'; document.querySelectorAll('.filter-chip').forEach(function (b) { b.classList.toggle('active', b.getAttribute('data-filter') === 'all'); }); refresh(); input.focus(); });
    document.querySelectorAll('.filter-chip').forEach(function (button) { button.addEventListener('click', function () { active = button.getAttribute('data-filter'); document.querySelectorAll('.filter-chip').forEach(function (b) { b.classList.remove('active'); }); button.classList.add('active'); refresh(); }); });
  }

  function bindLaunch() {
    var form = document.getElementById('campaignForm');
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var data = {};
      new FormData(form).forEach(function (value, key) { if (key === 'channel') { if (!data[key]) data[key] = []; data[key].push(value); } else data[key] = value; });
      try { localStorage.setItem('iarandu-campaign-draft', JSON.stringify(data)); } catch (ignore) {}
      document.getElementById('campaignSuccess').classList.add('show');
    });
  }

  function bindRanking() {
    var direction = -1;
    document.querySelectorAll('[data-sort]').forEach(function (head) { head.addEventListener('click', function () {
      var key = head.getAttribute('data-sort');
      if (key === 'rank') key = 'reviews';
      direction *= -1;
      var sorted = companies.slice().sort(function (a, b) { if (typeof a[key] === 'string') return a[key].localeCompare(b[key], 'pt-BR') * direction; return (a[key] - b[key]) * direction; });
      document.getElementById('rankingBody').innerHTML = rankingRows(sorted);
    }); });
  }

  function bindDocs() {
    document.querySelectorAll('.doc-chip').forEach(function (chip) { chip.addEventListener('click', function (e) { e.preventDefault(); var target = document.querySelector(chip.getAttribute('href')); if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }); });
  }

  function openAgent(key) {
    var a = agents[key];
    if (!a) return;
    document.getElementById('drawerAvatar').style.backgroundImage = 'url(' + a.avatar + ')';
    document.getElementById('drawerRole').textContent = a.role;
    document.getElementById('drawerTitle').textContent = a.name;
    document.getElementById('drawerSubtitle').textContent = a.subtitle;
    document.getElementById('drawerBody').innerHTML = '<h3>Responsabilidades</h3><ul>' + a.bullets.map(function (x) { return '<li>' + x + '</li>'; }).join('') + '</ul><div class="facts"><div class="fact">FUNÇÃO<strong>' + a.role + '</strong></div><div class="fact">SISTEMA<strong>' + a.engine + '</strong></div></div><p class="agent-disclaimer">Papéis da equipe. A atividade em tempo real não é informada por esta tela.</p>';
    document.body.classList.add('drawer-open');
  }

  function closeDrawer() { document.body.classList.remove('drawer-open'); }
  document.getElementById('closeDrawer').addEventListener('click', closeDrawer);
  scrim.addEventListener('click', closeDrawer);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeDrawer(); closeMobileMenu(); } });
  document.querySelectorAll('.route-link').forEach(function (link) { link.addEventListener('click', function (e) { e.preventDefault(); navigate(link.getAttribute('href')); }); });
  window.addEventListener('popstate', render);
  window.addEventListener('hashchange', render);
  render();
})();
