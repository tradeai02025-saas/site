(function () {
  'use strict';

  const $ = (selector) => document.querySelector(selector);
  const esc = (value) => String(value == null ? '' : value).replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
  const number = (value) => Number(value || 0).toLocaleString('pt-BR');
  let campaignTimer;
  let campaignTargetsLoaded = false;
  let scheduleChoiceDirty = false;
  const dirtyCampaignTargets = new Set();
  let searchTimer;
  let agencyState = {q:'',uf:'',channel:'',offset:0,total:0,items:[]};
  let selectedAgencyIds = new Set();
  let priorityAgencyRows = [];
  let priorityAgencyMeta = {};
  const selectionStorageKey = 'iarandu.revenue.selectedAgencies.v1';

  function updateSelectionUI() {
    setText('#selectedAgencyCount', number(selectedAgencyIds.size));
    setText('#campaignSelectionSummary', selectedAgencyIds.size
      ? number(selectedAgencyIds.size) + ' agencias selecionadas na base canonica.'
      : 'Nenhuma lista manual selecionada.');
  }

  async function persistAgencySelection() {
    const agencyIds = Array.from(selectedAgencyIds);
    localStorage.setItem(selectionStorageKey, JSON.stringify(agencyIds));
    updateSelectionUI();
    await request('/api/local/agency-selection', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({agency_ids:agencyIds})});
  }

  function room(content, monitor) {
    const stage = monitor ? 'monitor' : 'board';
    return `<section class="scene room-scene"><div class="room-stage ${stage}-stage"><img class="scene-background" src="/scene/${monitor ? 'desk-screen.webp' : 'board-room.webp'}" alt=""><div class="${stage}-box">${content}</div></div></section>`;
  }

  function request(path, options) {
    return fetch(path, Object.assign({cache:'no-store'}, options || {})).then(async (response) => {
      const data = await response.json().catch(() => ({}));
      if (!response.ok) {
        const error = new Error(data.message || data.error || `Erro HTTP ${response.status}`);
        error.status = response.status;
        throw error;
      }
      return data;
    });
  }

  function errorBox(error) {
    return `<div class="connection-error"><strong>Dados indisponíveis</strong><span>${esc(error.message || error)}</span></div>`;
  }

  function table(headings, rows) {
    if (!rows.length) return '<div class="loading-state">Nenhum registro nesta seleção.</div>';
    return `<div class="table-wrap"><table class="revenue-table"><thead><tr>${headings.map((x) => `<th>${esc(x)}</th>`).join('')}</tr></thead><tbody>${rows.map((row) => `<tr>${row.map((cell) => `<td>${cell}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`;
  }

  function campaignsView() {
    const field = (label, id, value, max) => `<label class="campaign-field">${label}<input class="input" id="${id}" type="number" inputmode="numeric" step="1" min="1" max="${max}" value="${value}"></label>`;
    const card = (id, title, detail, port, login, noStop) => `<article class="motor-card" id="motor-${id}"><header><span>${detail}</span><h2>${title}</h2></header><div class="motor-state" id="state-${id}">Verificando estado...</div><div class="motor-kpi" id="kpi-${id}">?</div><div class="motor-actions"><button class="black-button" data-action="start-${id}" data-ready-label="Enviar restante da meta" disabled>Verificando pré-voo…</button>${noStop ? '' : `<button class="outline-button" data-action="stop-${id}">Pausar</button>`}${port ? `<a class="outline-button" target="_blank" rel="noopener" href="http://127.0.0.1:${port}/">Painel ${port}</a>` : ''}${port ? `<button class="outline-button" data-action="login-${id}">Login Firefox ${port}</button>` : ''}</div></article>`;
    const content = `<div class="kicker">Operação local · metas do dia</div><h1 class="route-title sans">CAMPANHAS</h1><p class="route-intro">Defina o total desejado por canal. O sistema desconta os envios já contabilizados e usa somente contatos elegíveis da seleção.</p><section class="campaign-plan"><div><h2>Meta diária por canal</h2><p>O total inclui o que já foi enviado no dia; a automação opera de segunda a sexta, das 08h às 18h.</p></div><div class="campaign-fields">${field('E-mails · até 60 / dia','target-email',60,60)}${field('WhatsApp · até 30 / dia','target-whatsapp',30,30)}${field('@maldonadodiego55 · até 50 / dia','target-ig-5050',50,50)}${field('@iarandu.br · até 50 / dia','target-ig-5051',50,50)}</div><div class="campaign-actions"><button class="outline-button" id="saveCampaignConfig">Salvar metas do dia</button></div><p class="small-note" id="campaignSelectionSummary">Carregando seleção manual...</p><p class="small-note">A rotina automática abrange e-mail, WhatsApp e somente a conta oficial @iarandu.br. O Instagram pessoal permanece manual. Os limites próprios de cada motor também são respeitados.</p><p class="small-note" id="scheduleStatus">Carregando metas e estado dos canais...</p></section><div class="motor-grid">${card('ig-pessoal','Instagram pessoal','@maldonadodiego55',5050,false,false)}${card('ig-oficial','Instagram oficial','@iarandu.br',5051,true,false)}${card('whatsapp','WhatsApp Gateway','Motor operacional',null,false,false)}${card('email','E-mail Zoho SMTP','Modelo HTML aprovado',null,false,false)}</div><p class="operation-message" id="operationMessage" role="status">Consultando motores...</p><p class="small-note">Cada canal respeita o menor valor entre a meta diária restante e os contatos elegíveis selecionados.</p>`;
    return room(content, true);
  }

  function prospectingView() {
    const ufOptions = 'AC AL AP AM BA CE DF ES GO MA MT MS MG PA PB PR PE PI RJ RN RS RO RR SC SP SE TO'.split(' ').map((uf) => '<option value="' + uf + '">' + uf + '</option>').join('');
    return room(
      '<div class="kicker">Base canonica atualizada</div><h1 class="route-title">PROSPECCAO</h1>' +
      '<p class="route-intro">Banco principal de 846 agencias revisadas. Selecione manualmente por UF ou por lista; cadastro nao equivale a autorizacao para disparo.</p>' +
      '<div class="channel-summary" id="agencySummary">' +
      '<div class="channel-stat"><span>Universo canonico</span><strong>—</strong></div>' +
      '<div class="channel-stat"><span>Registros no CRM antigo</span><strong>—</strong></div>' +
      '<div class="channel-stat"><span>E-mail elegiveis</span><strong>—</strong></div>' +
      '<div class="channel-stat"><span>WhatsApp elegiveis</span><strong>—</strong></div>' +
      '<div class="channel-stat"><span>IG 5050 na fila</span><strong>—</strong></div>' +
      '<div class="channel-stat"><span>IG 5051 na fila</span><strong>—</strong></div></div>' +
      '<p class="small-note" id="agencySourceMeta">Carregando fonte e data da atualizacao...</p>' +
      '<div class="filter-row" id="agencyTabs"><button class="filter-chip active" data-channel="">Todos</button>' +
      '<button class="filter-chip" data-channel="instagram">Instagram</button><button class="filter-chip" data-channel="whatsapp">WhatsApp</button>' +
      '<button class="filter-chip" data-channel="email">E-mail</button></div>' +
      '<div class="search-row"><input class="input" id="agencySearch" placeholder="Buscar agencia, cidade, UF, CNPJ ou decisor" autocomplete="off" aria-label="Buscar agencia">' +
      '<select class="select uf-select" id="agencyUf" aria-label="Filtrar UF"><option value="">Todas as UFs</option>' + ufOptions + '</select></div>' +
      '<div class="selection-toolbar"><span><strong id="selectedAgencyCount">0</strong> agencias selecionadas</span>' +
      '<button class="outline-button" id="selectFilteredAgencies">Selecionar todas do filtro atual (0)</button>' +
      '<button class="outline-button" id="undoFilteredSelection">Desfazer selecao do filtro</button>' +
      '<button class="outline-button" id="selectPageAgencies">Selecionar pagina</button><button class="outline-button" id="clearSelectedAgencies">Limpar selecao</button></div>' +
      '<div class="revenue-panel" id="agencyPanel"><div class="loading-state">Carregando base...</div></div>' +
      '<div class="pager"><button class="outline-button" id="agencyPrev">Anterior</button><span id="agencyPage">—</span>' +
      '<button class="outline-button" id="agencyNext">Proxima</button></div>',
      false
    );
  }

  function metasView() {
    const channelOptions = [
      ['all', 'Todos os canais'],
      ['email', 'E-mail (Zoho SMTP)'],
      ['whatsapp', 'WhatsApp (OpenWA)'],
      ['instagram_direct', 'Instagram Direct']
    ].map(([val, label]) => `<option value="${val}">${label}</option>`).join('');

    const statusOptions = [
      ['all', 'Todos os status canônicos'],
      ['smtp_accepted', 'Aceito pelo SMTP (E-mail)'],
      ['gateway_accepted', 'Aceito pelo Gateway WA (WhatsApp)'],
      ['sent_unconfirmed', 'Enviado sem confirmação (Instagram Direct)'],
      ['delivery_confirmed', 'Entrega confirmada (Recibo comprovado)'],
      ['uncertain', 'Incerto / Timeout após tentativa'],
      ['errors', 'Erros de envio'],
      ['exclusions', 'Exclusões / Suprimidos'],
      ['queued', 'Na fila de disparo']
    ].map(([val, label]) => `<option value="${val}">${label}</option>`).join('');

    const content = '<div class="kicker">Retornos e auditoria da operação</div>' +
      '<h1 class="route-title">METAS</h1>' +
      '<p class="route-intro">Respostas, reuniões e histórico auditável de disparos extraído dos registros reais. Nenhuma entrega ou remetente é presumido sem evidência.</p>' +

      '<section class="goals-commercial-section">' +
        '<h2 class="marker-title">Retornos e avanços comerciais</h2>' +
        '<div class="score-grid" id="goalsSummary">' +
          '<div class="score-card"><span>RESPOSTAS/AVANÇOS</span><strong>—</strong></div>' +
          '<div class="score-card"><span>REUNIÕES</span><strong>—</strong></div>' +
          '<div class="score-card"><span>TAREFAS PENDENTES</span><strong>—</strong></div>' +
          '<div class="score-card"><span>AGÊNCIAS</span><strong>—</strong></div>' +
        '</div>' +
        '<h3 class="marker-title" style="margin-top:16px;font-size:16px;">Próximos passos com agências</h3>' +
        '<div class="revenue-panel" id="goalsPanel"><div class="loading-state">Carregando...</div></div>' +
      '</section>' +

      '<section class="dispatch-logs-section" style="margin-top:32px;padding-top:24px;border-top:2px solid #d7d0c7;">' +
        '<div class="kicker">Auditoria de envios multicanal</div>' +
        '<h2 class="marker-title" style="margin-top:2px;">Central de Logs de Disparo</h2>' +
        '<p class="small-note" style="margin-bottom:14px;">Registros reais consolidados de disparos. Status canônicos auditáveis: e-mail aceito pelo SMTP e WhatsApp aceito pelo gateway não comprovam entrega na caixa; envios do Instagram são não-confirmados por ausência de recibo; timeouts e falhas incertas permanecem segregados sem reenvio automático.</p>' +

        '<div id="dispatchLogsWarning" class="operation-message" style="display:none;margin-bottom:14px;border-left:3px solid #e05244;background:#fff5f5;"></div>' +

        '<div id="dispatchLogsUnavailable" class="connection-error" style="display:none;margin-bottom:14px;">' +
          '<strong>Central de logs indisponível / em implantação</strong>' +
          '<span id="dispatchLogsUnavailableMsg">O endpoint /api/local/dispatch-logs ainda não está disponível neste servidor local. Nenhuma contagem ou entrega fictícia foi presumida.</span>' +
        '</div>' +

        '<div class="score-grid" id="dispatchLogsSummary" style="margin-bottom:14px;">' +
          '<div class="score-card"><span>TOTAL REGISTRADO</span><strong id="dlStatTotal">—</strong></div>' +
          '<div class="score-card"><span>ACEITOS SMTP</span><strong id="dlStatSmtp">—</strong></div>' +
          '<div class="score-card"><span>ACEITOS GATEWAY WA</span><strong id="dlStatWa">—</strong></div>' +
          '<div class="score-card"><span>IG SEM CONFIRMAÇÃO</span><strong id="dlStatIg">—</strong></div>' +
        '</div>' +
        '<div class="channel-summary" id="dispatchLogsSecondarySummary" style="margin-bottom:16px;grid-template-columns:repeat(auto-fit, minmax(130px, 1fr));">' +
          '<div class="channel-stat"><span>Entrega confirmada</span><strong id="dlStatDelivery">—</strong></div>' +
          '<div class="channel-stat"><span>Incerto / Timeout</span><strong id="dlStatUncertain">—</strong></div>' +
          '<div class="channel-stat"><span>Erros de envio</span><strong id="dlStatErrors">—</strong></div>' +
          '<div class="channel-stat"><span>Exclusões / Suprimidos</span><strong id="dlStatExclusions">—</strong></div>' +
          '<div class="channel-stat"><span>Na fila</span><strong id="dlStatQueued">—</strong></div>' +
        '</div>' +

        '<div class="campaign-plan" style="margin:14px 0 16px;">' +
          '<h2>Filtros de auditoria</h2>' +
          '<p class="small-note">Filtre por canal, status canônico, instância, conta ou pesquise por agência, CNPJ/ID, destinatário ou texto da mensagem.</p>' +
          '<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:10px;margin:12px 0 8px;">' +
            '<label class="campaign-field">Canal<select class="select" id="dlFilterChannel">' + channelOptions + '</select></label>' +
            '<label class="campaign-field">Status canônico<select class="select" id="dlFilterStatus">' + statusOptions + '</select></label>' +
            '<label class="campaign-field">Instância remetente<input class="input" id="dlFilterInstance" placeholder="Ex: ig:5050, openwa:2785, zoho:iarandu"></label>' +
            '<label class="campaign-field">Conta remetente<input class="input" id="dlFilterAccount" placeholder="Ex: @maldonadodiego55, contato@iarandu.com.br"></label>' +
          '</div>' +
          '<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(150px, 1fr));gap:10px;margin:8px 0 12px;">' +
            '<label class="campaign-field">Data inicial<input class="input" id="dlFilterDateFrom" type="date"></label>' +
            '<label class="campaign-field">Data final<input class="input" id="dlFilterDateTo" type="date"></label>' +
            '<label class="campaign-field">Limite por página<select class="select" id="dlFilterLimit"><option value="25" selected>25 por página</option><option value="50">50 por página</option><option value="100">100 por página</option></select></label>' +
            '<label class="campaign-field">Busca livre<input class="input" id="dlFilterSearch" placeholder="Agência, lead ID, destinatário, texto..."></label>' +
          '</div>' +
          '<div class="campaign-actions">' +
            '<button class="black-button" id="dlApplyFilters" style="height:34px;padding:0 16px;font-size:11px;">Aplicar filtros</button>' +
            '<button class="outline-button" id="dlClearFilters" style="height:34px;padding:0 14px;font-size:11px;">Limpar filtros</button>' +
            '<span class="small-note" id="dlFilterStatusText" style="margin-left:8px;">Aguardando consulta...</span>' +
          '</div>' +
        '</div>' +

        '<div class="revenue-panel" id="dispatchLogsPanel"><div class="loading-state">Consultando registros de disparo...</div></div>' +

        '<div class="pager" id="dispatchLogsPager">' +
          '<button class="outline-button" id="dlPrevPage" disabled>Anterior</button>' +
          '<span id="dlPageInfo">—</span>' +
          '<button class="outline-button" id="dlNextPage" disabled>Próxima</button>' +
        '</div>' +
      '</section>';

    return room(content, false);
  }

  function cycleView() {
    return room('<div class="kicker">Sistema de crescimento</div><h1 class="route-title">CICLO COMERCIAL</h1><p class="route-intro">Pesquisar, qualificar, conversar e entregar com dados verificáveis do CRM.</p><div class="score-grid" id="cycleSummary"><div class="score-card"><span>AGÊNCIAS CANÔNICAS</span><strong>—</strong></div><div class="score-card"><span>SITES REAIS CADASTRADOS</span><strong>—</strong></div><div class="score-card"><span>COM E-MAIL</span><strong>—</strong></div><div class="score-card"><span>COM TELEFONE</span><strong>—</strong></div></div><div class="cycle-track"><article><b>1</b><strong>Pesquisar</strong><small>Empresa e fonte</small></article><i>→</i><article><b>2</b><strong>Qualificar</strong><small>Contato e contexto</small></article><i>→</i><article><b>3</b><strong>Conversar</strong><small>Respostas reais</small></article><i>→</i><article><b>4</b><strong>Entregar</strong><small>Próximo passo</small></article></div><div class="cycle-result"><span>PRINCÍPIO DA OPERAÇÃO</span><strong>Qualidade antes de volume.</strong><p>Uma presença online cadastrada não prova atividade recente nem aprovação para contato.</p></div>', false);
  }

  function legacyPrioritiesView() {
    return room('<div class="kicker">Respostas que exigem ação</div><h1 class="route-title">PRIORIDADES</h1><p class="route-intro">Empresas com avanço explícito no CRM, ordenadas pelo próximo passo. Valor contratual não substitui uma resposta.</p><div class="revenue-panel" id="priorityPanel"><div class="loading-state">Carregando respostas...</div></div>', false);
  }

  function prioritiesView() {
    const ufOptions = 'AC AL AP AM BA CE DF ES GO MA MT MS MG PA PB PR PE PI RJ RN RS RO RR SC SP SE TO'.split(' ').map((uf) => `<option value="${uf}">${uf}</option>`).join('');
    return room('<div class="kicker">Base canonica e respostas comerciais</div><h1 class="route-title">PRIORIDADES</h1>' +
      '<p class="route-intro">A base completa mostra primeiro as agencias com CNPJ. As 40 sem CNPJ aparecem no fim e podem ser selecionadas para os canais que tenham contato cadastrado e elegivel. Respostas e negocios continuam separados da lista de prospeccao.</p>' +
      '<h2 class="marker-title">Respostas confirmadas</h2><div class="revenue-panel" id="priorityPanel"><div class="loading-state">Carregando respostas...</div></div>' +
      '<h2 class="marker-title">Negocios existentes no CRM</h2><p class="small-note">Etapas registradas em crm_deals sao mostradas separadamente. Uma etapa "resposta" sem evento confirmado em crm_history nao e apresentada como resposta recebida.</p><div class="revenue-panel" id="priorityDealsPanel"><div class="loading-state">Carregando negocios...</div></div>' +
      '<h2 class="marker-title">Universo canonico de prospeccao</h2><p class="small-note">A selecao marcada e a lista atual salva. Alterar uma caixa salva a selecao manual. Registros sem CNPJ usam o identificador SINAPRO e podem entrar nos canais com contato elegivel.</p>' +
      '<div class="search-row"><input class="input" id="priorityAgencySearch" placeholder="Buscar agencia, CNPJ, cidade, UF, decisor ou canal" autocomplete="off" aria-label="Buscar na base de prioridades">' +
      '<select class="select uf-select" id="priorityAgencyUf" aria-label="Filtrar agencias por UF"><option value="">Todas as UFs</option>' + ufOptions + '</select></div>' +
      '<p class="small-note" id="priorityAgencyMeta">Carregando base canonica...</p><div class="revenue-panel" id="priorityAgencyPanel"><div class="loading-state">Carregando as agencias...</div></div>', false);
  }

  function messagesView() {
    const editor = (label, id, title) => `<article class="motor-card"><header><span>${label}</span><h2>${title}</h2></header><label class="campaign-field">Modelo oficial<textarea class="textarea" id="${id}" rows="12" spellcheck="false"></textarea></label><small class="small-note" data-message-count="${id}">Carregando modelo...</small></article>`;
    const emailEditor = `<article class="motor-card"><header><span>E-MAIL CORPORATIVO · ZOHO SMTP</span><h2>contato@iarandu.com.br</h2></header><label class="campaign-field">Assunto oficial ({empresa} obrigatório)<input class="input" id="messageEmailSubject" placeholder="Assunto do e-mail" autocomplete="off" spellcheck="false" style="width:100%;font-size:12px;"></label><small class="small-note" data-message-count="messageEmailSubject">Carregando assunto...</small><label class="campaign-field" style="margin-top:10px;">Corpo do e-mail ({empresa} e variações rotativas)<textarea class="textarea" id="messageEmail" rows="12" spellcheck="false"></textarea></label><small class="small-note" data-message-count="messageEmail">Carregando corpo...</small><p class="small-note" style="margin-top:6px;">Cabeçalho institucional e assinatura oficial são fixados pelo backend e não são editáveis aqui.</p></article>`;
    const previewCard = `<section class="motor-card" id="emailPreviewCard"><header style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;"><div><span>PRÉVIA EM TEMPO REAL</span><h2 style="margin:4px 0 0;">E-mail Composto pelo Backend</h2></div><div style="display:flex;gap:8px;align-items:center;"><input class="input" id="emailPreviewCompany" value="Agência Exemplo" placeholder="Agência para teste" style="font-size:12px;padding:6px 10px;width:160px;" aria-label="Nome da agência para teste"><button class="outline-button" id="refreshEmailPreview" type="button" style="height:32px;font-size:11px;">Prévia do rascunho</button><button class="outline-button" id="checkSavedEmailPreview" type="button" style="height:32px;font-size:11px;">Conferir salvo</button></div></header><div style="margin:10px 0;display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;font-size:12px;"><div><strong>Assunto:</strong> <span id="emailPreviewSubject">—</span></div><div class="small-note" id="emailPreviewMeta">Hash: —</div></div><p class="small-note" id="emailPreviewNotice" style="margin-bottom:10px;color:#886e43;background:#faf7f2;padding:8px 12px;border-radius:8px;border:1px solid #e4ded7;">Prévia do HTML real gerado pelo backend. As variações sorteadas são ilustrativas para demonstração e não representam garantia de texto exato no sorteio aleatório.</p><iframe id="emailPreviewFrame" title="Prévia do e-mail HTML real" sandbox="allow-same-origin" style="width:100%;height:460px;border:1px solid #d7d0c7;border-radius:12px;background:#eef1f5;"></iframe></section>`;

    return room('<div class="kicker">Biblioteca de abordagem</div><h1 class="route-title">MENSAGENS</h1>' +
      '<p class="route-intro">Este texto reúne os modelos oficiais usados pelos canais de prospecção (Instagram, WhatsApp e E-mail Zoho). As opções entre chaves separadas por | são variações rotativas; {empresa} recebe o nome da agência. Salvar altera os modelos oficiais no backend, mas não inicia envios.</p>' +
      '<div class="message-editors">' +
      editor('INSTAGRAM PESSOAL · 5050', 'messageInstagram5050', '@maldonadodiego55') +
      editor('INSTAGRAM OFICIAL · 5051', 'messageInstagram5051', '@iarandu.br') +
      editor('WHATSAPP', 'messageWhatsapp', 'OpenWA') +
      emailEditor +
      previewCard +
      '</div>' +
      '<div class="motor-actions"><button class="black-button" id="saveOfficialMessages">Salvar modelos oficiais</button><span class="small-note" id="messageSaveStatus"></span></div>', false);
  }

  function docsView() {
    const sections = [
      ['01','Fluxo de uso: passo a passo','1) Em Prospecção ou Prioridades, selecione as agências manualmente ou filtre por UF. A base completa tem 846 agências: 806 com CNPJ e 40 sem CNPJ; as sem CNPJ também podem ser selecionadas pelos identificadores SINAPRO. 2) Na seção de metas, defina e salve a meta diária como o TOTAL DO DIA, e NÃO como um valor adicional (exemplo: se já enviou 6 e deseja enviar mais 2 hoje, salve a meta como 8; se salvar 2, o sistema entenderá que a meta diária já foi alcançada). 3) Volte para Campanhas e confira os contatos elegíveis pendentes e a meta restante exibidos em cada card. Cada canal considera somente o contato daquele canal: ausência de e-mail ou CNPJ não bloqueia um Instagram elegível; um telefone geral não comprova WhatsApp ativo. 4) Clique em "Enviar restante da meta" no canal desejado (o sistema envia até o menor valor entre contatos elegíveis pendentes e meta restante). 5) Acompanhe o status operacional, atualizado automaticamente a cada 10 segundos.'],
      ['02','Bloqueios operacionais e botões desabilitados','Se o botão "Enviar restante da meta" estiver cinza ou desabilitado, verifique o motivo exibido no card: a) Meta alcançada: o total de envios realizados hoje já atingiu ou superou a meta diária configurada; b) Zero elegíveis: a seleção manual ativa não possui nenhuma agência pendente e apta para o canal selecionado; c) Supressões e status no Instagram: agências presentes na lista de exclusão/supressão, contatos já abordados pelo perfil em questão (@maldonadodiego55 na porta 5050 ou @iarandu.br na 5051), registros sem handle ou com status diferente de pendente no bot são bloqueados; d) Telefone comum não é prova de WhatsApp: a presença de um número de telefone geral ou fixo cadastrado na base não garante a existência de uma conta ativa no WhatsApp; apenas números validados e aptos são liberados.'],
      ['03','Base e evidência','Prospecção reúne agências das tabelas agencias e agencias_sinapro_master. Fonte, CNPJ e contato devem ser confirmados antes de qualquer abordagem.'],
      ['04','Contas Instagram','As contas @maldonadodiego55 (5050) e @iarandu.br (5051) têm filas e históricos independentes. A mesma agência pode receber a mensagem de cada conta. Histórico de uma conta não exclui a agência da outra; cada conta evita repetir os próprios contatos.'],
      ['05','Campanhas e motores','Os botões Iniciar e Pausar acionam os endpoints existentes. O status é consultado separadamente a cada 10 segundos. Ao iniciar um lote, aguarde confirmação do backend e acompanhe o painel do motor.'],
      ['06','E-mail Zoho SMTP','O canal usa o email_runner com seleção manual de agências, deduplicação por endereço, supressões e quota diária. Os registros sem CNPJ são selecionados pelo ID SINAPRO. O início é manual. “Aceito pelo SMTP” não confirma entrega na caixa do destinatário.'],
      ['07','Retornos comerciais','Prioridades e Metas mostram apenas avanços explícitos em crm_deals. Chats importados ou números sem associação verificada não viram resposta comercial.'],
      ['08','Operação legada','Durante a migração, tarefas, propostas, notas, revisão de contatos e exclusões continuam disponíveis no painel operacional original, no mesmo servidor.'],
      ['09','Segurança operacional','Abrir a página ou consultar status não inicia motores. Em preview, salvar metas e a seleção é permitido; ações de início e pausa ficam bloqueadas. Cada canal exige seleção e preflight.'],
      ['10','Tamanho dos contatos cadastrados','Leitura da base de 846 agencias em 26/10/2026: 723 tem campo de e-mail (697 enderecos distintos), 723 tem Instagram (693 handles distintos), 131 tem WhatsApp explicito (125 numeros distintos) e 846 tem telefone (820 numeros distintos). Os 41 contatos de WhatsApp sem CNPJ que aparecem no enriquecimento seguem pendentes de auditoria e sem aprovação para envio; não entram na fila ativa. Telefone cadastrado não significa WhatsApp validado nem elegibilidade para envio.'],
      ['11','Recuperação do sistema','A interface anterior e o código original permanecem preservados. Em falha, interrompa a nova instância e retome o cockpit anterior na porta 8051.']
    ];
    return room(`<div class="kicker">Manual operacional local</div><h1 class="route-title">DOCUMENTAÇÃO</h1><p class="route-intro">Como o Revenue OS visual utiliza os dados e motores locais.</p><div class="doc-callout"><strong>Área operacional antiga:</strong> disponível em <a href="/legado" target="_blank" rel="noopener">/legado</a> enquanto os recursos são integrados ao novo visual.</div>${sections.map(([n,title,copy]) => `<section class="doc-section"><h2><span>${n}</span>${title}</h2><p>${copy}</p></section>`).join('')}`, false);
  }

  function setText(selector, value) { const element = $(selector); if (element) element.textContent = value; }
  function showSummary() {
    return request('/api/local/summary').then((s) => {
      const agencyBoxes = $$('#agencySummary strong');
      [s.agencies,s.sites,s.with_email,s.with_instagram].forEach((value,i) => {if (agencyBoxes[i]) agencyBoxes[i].textContent = number(value);});
      const goalsBoxes = $$('#goalsSummary strong');
      [s.replies,s.meetings,s.pending_tasks,s.agencies].forEach((value,i) => {if (goalsBoxes[i]) goalsBoxes[i].textContent = number(value);});
      const cycleBoxes = $$('#cycleSummary strong');
      [s.agencies,s.sites,s.with_email,s.with_phone].forEach((value,i) => {if (cycleBoxes[i]) cycleBoxes[i].textContent = number(value);});
      return s;
    });
  }
  function showProspectingSummary() {
    return request('/api/local/prospecting-summary').then((state) => {
      const boxes = $$('#agencySummary strong');
      const channels = state.channel_eligibility || {};
      const values = [
        state.canonical && state.canonical.total,
        state.legacy_crm && state.legacy_crm.agency_table_rows,
        channels.email && channels.email.eligible,
        channels.whatsapp && channels.whatsapp.eligible,
        channels.instagram_5050 && channels.instagram_5050.pending_in_motor,
        channels.instagram_5051 && channels.instagram_5051.pending_in_motor,
      ];
      values.forEach((value,index) => {if (boxes[index]) boxes[index].textContent = value == null ? '—' : number(value);});
      const canonical = state.canonical || {};
      const tables = canonical.tables || {};
      setText('#agencySourceMeta', 'Fonte: ' + (canonical.database || 'indisponivel') +
        ' | agencias=' + number(tables.agencias) + ' + Sinapro=' + number(tables.agencias_sinapro_master) +
        ' | ultima atualizacao: ' + (canonical.updated_at || 'indisponivel') +
        ' | elegibilidade e filas sao contagens por canal, nao o total da base.');
      return state;
    });
  }
  function $$(selector) { return Array.from(document.querySelectorAll(selector)); }

  function priorityTable(items) {
    if (!items.length) return '<div class="loading-state">Nenhuma resposta confirmada com evidência no CRM. Etapas antigas chamadas “resposta” não são contabilizadas até a conferência da conversa.</div>';
    return table(['Agência','Etapa','Próximo passo','Prazo','Responsável'], items.map((r) => [
      `<button class="table-link" data-detail="${esc(r.cnpj)}">${esc(r.nome)}</button><small>${esc([r.municipio,r.uf].filter(Boolean).join(' · '))}</small>`,
      `<span class="status-pill">${esc(r.etapa)}</span>`,
      esc(r.proximo_passo || 'Definir próximo passo'),
      esc(r.prazo_proximo_passo || 'Sem prazo'),
      esc(r.responsavel || '—')
    ]));
  }

  function priorityDealsTable(items) {
    if (!items.length) return '<div class="loading-state">Nenhum negocio em crm_deals.</div>';
    return table(['Agencia','Etapa registrada','Proximo passo','Prazo','Responsavel','Prioridade','Historico de resposta'], items.map((r) => [
      r.cnpj && /^\d{14}$/.test(String(r.cnpj))
        ? `<button class="table-link" data-detail="${esc(r.cnpj)}" data-name="${esc(r.nome || '')}">${esc(r.nome || r.cnpj)}</button><small>${esc([r.municipio,r.uf].filter(Boolean).join(' / '))}</small>`
        : esc(r.nome || r.cnpj || 'Agencia sem identificador'),
      esc(r.etapa || 'Etapa nao informada') + (r.etapa === 'resposta' && !r.has_confirmed_reply ? '<small>Sem evento confirmado</small>' : ''),
      esc(r.proximo_passo || 'Nao registrado'), esc(r.prazo_proximo_passo || 'Sem prazo'),
      esc(r.responsavel || 'Nao informado'), esc(r.prioridade || 'Nao classificada'),
      r.has_confirmed_reply ? 'Evento confirmado' : 'Sem resposta confirmada'
    ]));
  }

  function loadPriorityDeals() {
    return request('/api/local/priority-deals').then((rows) => {
      const panel = $('#priorityDealsPanel');
      if (panel) panel.innerHTML = priorityDealsTable(rows);
      return rows;
    }).catch((error) => {
      const saveButton = document.querySelector('#saveCampaignConfig');
      if (saveButton) saveButton.disabled = true;
      const panel = $('#priorityDealsPanel');
      if (panel) panel.innerHTML = errorBox(error);
    });
  }

  function loadPriorities(selector) {
    return request('/api/local/priorities').then((rows) => {
      const panel = $(selector);
      if (panel) panel.innerHTML = priorityTable(rows);
    }).catch((error) => { const panel = $(selector); if (panel) panel.innerHTML = errorBox(error); });
  }

  let dispatchLogsSearchTimer;
  let dispatchLogsState = {
    page: 1,
    limit: 25,
    channel: 'all',
    status: 'all',
    sender_instance_id: '',
    sender_account: '',
    date_from: '',
    date_to: '',
    search: '',
    total_records: 0,
    total_pages: 0,
    has_next: false,
    has_prev: false,
    items: [],
    loading: false
  };

  function renderStatusBadge(status) {
    const s = String(status || '').toLowerCase().trim();
    const config = {
      smtp_accepted: { label: 'Aceito pelo SMTP (não entregue)', style: 'background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;' },
      gateway_accepted: { label: 'Aceito pelo Gateway WA (não entregue)', style: 'background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;' },
      sent_unconfirmed: { label: 'Enviado sem confirmação', style: 'background:#fffbeb;color:#92400e;border:1px solid #fde68a;' },
      delivery_confirmed: { label: 'Entrega confirmada', style: 'background:#dcfce7;color:#14532d;border:1px solid #86efac;font-weight:800;' },
      uncertain: { label: 'Incerto / Timeout (sem reenvio)', style: 'background:#f5f3ff;color:#5b21b6;border:1px solid #ddd6fe;' },
      errors: { label: 'Erro no envio', style: 'background:#fef2f2;color:#991b1b;border:1px solid #fecaca;' },
      exclusions: { label: 'Excluído / Suprimido', style: 'background:#f3f4f6;color:#374151;border:1px solid #d1d5db;' },
      queued: { label: 'Na fila', style: 'background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;' }
    };
    const c = config[s] || { label: esc(status || 'Desconhecido'), style: 'background:#f3f4f6;color:#4b5563;border:1px solid #e5e7eb;' };
    return `<span class="status-pill" style="display:inline-block;padding:3px 8px;border-radius:99px;font-size:10px;line-height:1.3;${c.style}">${c.label}</span>`;
  }

  function renderChannelBadge(channel) {
    const ch = String(channel || '').toLowerCase().trim();
    if (ch === 'email') return '<span style="display:inline-block;padding:2px 7px;border-radius:6px;background:#e0f2fe;color:#0369a1;font-size:10px;font-weight:700;">E-mail</span>';
    if (ch === 'whatsapp') return '<span style="display:inline-block;padding:2px 7px;border-radius:6px;background:#dcfce7;color:#15803d;font-size:10px;font-weight:700;">WhatsApp</span>';
    if (ch === 'instagram_direct') return '<span style="display:inline-block;padding:2px 7px;border-radius:6px;background:#fae8ff;color:#86198f;font-size:10px;font-weight:700;">Instagram Direct</span>';
    return `<span style="display:inline-block;padding:2px 7px;border-radius:6px;background:#f3f4f6;color:#374151;font-size:10px;font-weight:700;">${esc(channel || 'Canal')}</span>`;
  }

  function renderSenderInfo(item) {
    const account = item.sender_account;
    const instance = item.sender_instance_id;
    const proof = item.sender_proof_status;
    const channel = item.channel;
    const isIg = channel === 'instagram_direct' || (instance && String(instance).startsWith('ig:'));

    let accountHtml = '';
    if (account) {
      accountHtml = `<strong>${esc(account)}</strong>`;
    } else if (isIg) {
      accountHtml = '<span style="color:#b45309;font-weight:700;">[Conta não comprovada]</span>';
    } else {
      accountHtml = '<span style="color:#b45309;font-weight:700;">[Remetente Desconhecido]</span>';
    }

    const instanceText = instance ? `Instância: ${esc(instance)}` : 'Instância: não informada';
    let proofTag = '';
    if (!account) {
      proofTag = '<span style="display:inline-block;margin-top:2px;padding:1px 6px;border-radius:4px;background:#fef3c7;color:#92400e;font-size:9px;font-weight:700;">Conta não comprovada</span>';
    } else if (proof && proof !== 'unknown' && proof !== 'desconhecido') {
      proofTag = `<small style="display:block;color:#6b7280;font-size:10px;">Prova: ${esc(proof)}</small>`;
    }

    return `<div>${accountHtml}<small style="display:block;color:#6b7280;font-size:10px;">${instanceText}</small>${proofTag}</div>`;
  }

  function renderTimestamp(item) {
    if (item.timestamp_tz) {
      return `<div><strong>${esc(item.timestamp_tz)}</strong><small style="display:block;color:#059669;font-size:9px;">TZ comprovado (${esc(item.timezone_provenance || 'offset')})</small></div>`;
    }
    if (item.timestamp_raw) {
      return `<div><strong>${esc(item.timestamp_raw)}</strong><small style="display:block;color:#b45309;font-size:9px;">Sem timezone comprovado</small></div>`;
    }
    return '<div><span style="color:#9ca3af;font-size:11px;">Data desconhecida</span><small style="display:block;color:#9ca3af;font-size:9px;">Sem timestamp registrado</small></div>';
  }

  function renderLeadRecipient(item) {
    const leadId = item.lead_id;
    const leadType = item.lead_id_type;
    const agencyName = item.agency_name;
    const recipient = item.recipient_address;

    let leadDisplay = '';
    if (leadId) {
      const isCnpj = leadType === 'cnpj' || /^\d{14}$/.test(leadId);
      leadDisplay = isCnpj
        ? `<button class="table-link" data-detail="${esc(leadId)}" data-name="${esc(agencyName || '')}">${esc(agencyName || leadId)}</button><small style="display:block;color:#6b7280;font-size:10px;">CNPJ: ${esc(leadId)}</small>`
        : `<span>${esc(agencyName || leadId)}</span><small style="display:block;color:#6b7280;font-size:10px;">ID: ${esc(leadId)}</small>`;
    } else if (agencyName) {
      leadDisplay = `<span>${esc(agencyName)}</span><small style="display:block;color:#9ca3af;font-size:10px;">Sem ID conciliado</small>`;
    } else {
      leadDisplay = '<span style="color:#9ca3af;">[Lead não conciliado]</span>';
    }

    const recipDisplay = recipient
      ? `<div style="margin-top:2px;font-size:11px;">Destinatário: <strong>${esc(recipient)}</strong></div>`
      : '<div style="margin-top:2px;font-size:10px;color:#9ca3af;">Destinatário não registrado</div>';

    return `<div>${leadDisplay}${recipDisplay}</div>`;
  }

  function renderDispatchLogsTable() {
    const panel = $('#dispatchLogsPanel');
    if (!panel) return;

    const items = dispatchLogsState.items;
    if (!items.length) {
      panel.innerHTML = '<div class="loading-state">Nenhum evento de disparo encontrado para os filtros selecionados.</div>';
      return;
    }

    const rows = items.map((item, idx) => {
      const colTime = renderTimestamp(item);
      const colChannel = renderChannelBadge(item.channel);
      const colSender = renderSenderInfo(item);
      const colLead = renderLeadRecipient(item);

      let statusBlock = `<div style="display:flex;flex-direction:column;gap:3px;align-items:flex-start;">${renderStatusBadge(item.status)}`;
      if (item.reason_code || item.reason_detail) {
        statusBlock += `<div style="font-size:10px;color:#4b5563;margin-top:2px;overflow-wrap:anywhere;"><b>${esc(item.reason_code || '')}</b>: ${esc(item.reason_detail || '')}</div>`;
      }
      if (item.delivery_receipt) {
        const rcpt = typeof item.delivery_receipt === 'object' ? JSON.stringify(item.delivery_receipt) : String(item.delivery_receipt);
        statusBlock += `<div style="font-size:9px;color:#047857;margin-top:2px;"><b>Recibo:</b> ${esc(rcpt)}</div>`;
      }
      statusBlock += '</div>';

      let msgBlock = '';
      if (item.body_text) {
        msgBlock = `<details class="dl-body-details" style="margin-top:6px;">` +
          `<summary class="table-link" style="cursor:pointer;font-size:10px;">Ver mensagem enviada (${item.body_text.length} carac.)</summary>` +
          `<pre class="dl-body-pre" data-dl-body-idx="${idx}" style="margin:4px 0 0;padding:8px;background:#f5f2ea;border:1px solid #d7d0c7;border-radius:6px;font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:11px;line-height:1.4;white-space:pre-wrap;max-height:160px;overflow-y:auto;user-select:text;"></pre>` +
          `</details>`;
      } else {
        msgBlock = '<div class="small-note" style="color:#9ca3af;font-size:10px;margin-top:4px;">[Corpo não preservado na fonte]</div>';
      }

      let sourceBlock = `<div style="font-size:10px;color:#6b7280;">Fonte: <strong>${esc(item.source_system || '—')}</strong><br>Registro: <code>${esc(item.source_record_id || '—')}</code></div>`;
      if (item.lead_id) {
        sourceBlock += `<button class="outline-button" data-detail="${esc(item.lead_id)}" data-name="${esc(item.agency_name || '')}" style="height:24px;padding:0 8px;font-size:10px;margin-top:4px;">Ver agência</button>`;
      }

      return [
        colTime,
        colChannel,
        colSender,
        colLead,
        statusBlock + msgBlock,
        sourceBlock
      ];
    });

    panel.innerHTML = table(['Data / Hora', 'Canal', 'Remetente', 'Destinatário / Lead', 'Status & Mensagem', 'Origem'], rows);

    // CRITICAL SECURITY RULE: Insert body_text exclusively via textContent! NEVER innerHTML!
    panel.querySelectorAll('.dl-body-pre[data-dl-body-idx]').forEach((pre) => {
      const idx = Number(pre.dataset.dlBodyIdx);
      const entry = items[idx];
      if (entry && entry.body_text) {
        pre.textContent = entry.body_text;
      }
    });
  }

  function renderDispatchLogsResponse(data) {
    dispatchLogsState.loading = false;

    // 1. Hide unavailable banner
    const unavailEl = $('#dispatchLogsUnavailable');
    if (unavailEl) unavailEl.style.display = 'none';

    // 2. Warnings banner (partial or source_warnings)
    const warningEl = $('#dispatchLogsWarning');
    const warnings = Array.isArray(data.source_warnings) ? data.source_warnings : [];
    const isPartial = data.status === 'partial' || warnings.length > 0;
    if (warningEl) {
      if (isPartial) {
        warningEl.style.display = 'block';
        warningEl.innerHTML = `<strong>⚠️ Atenção: Leitura parcial das fontes de disparo.</strong><br><span>Nem todos os bancos ou fontes puderam ser consultados. Avisos da fonte: ${esc(warnings.join(' | ') || 'Algumas fontes de log estão indisponíveis.')}</span>`;
      } else {
        warningEl.style.display = 'none';
        warningEl.textContent = '';
      }
    }

    // 3. Update Summary KPIs
    const summary = data.summary || {};
    setText('#dlStatTotal', summary.total_events != null ? number(summary.total_events) : '—');
    setText('#dlStatSmtp', summary.smtp_accepted != null ? number(summary.smtp_accepted) : '—');
    setText('#dlStatWa', summary.gateway_accepted != null ? number(summary.gateway_accepted) : '—');
    setText('#dlStatIg', summary.sent_unconfirmed != null ? number(summary.sent_unconfirmed) : '—');
    setText('#dlStatDelivery', summary.delivery_confirmed != null ? number(summary.delivery_confirmed) : '—');
    setText('#dlStatUncertain', summary.uncertain != null ? number(summary.uncertain) : '—');
    setText('#dlStatErrors', summary.errors != null ? number(summary.errors) : '—');
    setText('#dlStatExclusions', summary.exclusions != null ? number(summary.exclusions) : '—');
    setText('#dlStatQueued', summary.queued != null ? number(summary.queued) : '—');

    // 4. Update Pagination
    const pagination = data.pagination || {};
    dispatchLogsState.total_records = Number(pagination.total_records || 0);
    dispatchLogsState.total_pages = Number(pagination.total_pages || 1);
    dispatchLogsState.page = Number(pagination.current_page || 1);
    dispatchLogsState.has_next = Boolean(pagination.has_next);
    dispatchLogsState.has_prev = Boolean(pagination.has_prev);
    dispatchLogsState.items = Array.isArray(data.data) ? data.data : [];

    setText('#dlPageInfo', `Página ${number(dispatchLogsState.page)} de ${number(dispatchLogsState.total_pages)} (${number(dispatchLogsState.total_records)} registros)`);
    const prevBtn = $('#dlPrevPage');
    const nextBtn = $('#dlNextPage');
    if (prevBtn) prevBtn.disabled = !dispatchLogsState.has_prev;
    if (nextBtn) nextBtn.disabled = !dispatchLogsState.has_next;

    setText('#dlFilterStatusText', `${number(dispatchLogsState.total_records)} registros encontrados.`);

    // 5. Render Table
    renderDispatchLogsTable();
  }

  function classifyError(error) {
    const status = error && (error.status || (error.response && error.response.status));
    const msg = String((error && error.message) || '').toLowerCase();
    const name = String((error && error.name) || '').toLowerCase();

    // 1. Endpoint ausente / em implantação (HTTP 404)
    if (status === 404 || msg.includes('404') || msg.includes('rota não encontrada') || msg.includes('não encontrada')) {
      return {
        title: 'Central de logs em implantação',
        message: 'O endpoint de logs de disparo ainda não está ativo neste servidor local. Nenhuma contagem ou entrega foi presumida.',
        badge: 'Em implantação'
      };
    }

    // 2. Timeout
    if (status === 504 || status === 408 || name.includes('abort') || msg.includes('timeout') || msg.includes('timed out')) {
      return {
        title: 'Tempo limite esgotado',
        message: 'A consulta aos registros de disparo demorou mais que o esperado. Tente novamente em instantes.',
        badge: 'Tempo limite'
      };
    }

    // 3. Falha no servidor (HTTP 500 / 502 / 503)
    if ((status >= 500 && status < 600) || msg.includes('500') || msg.includes('502') || msg.includes('503') || msg.includes('internal server error')) {
      return {
        title: 'Instabilidade no servidor',
        message: 'O servidor local encontrou uma falha temporária ao consultar as fontes de log. Nenhuma contagem ou entrega foi presumida.',
        badge: 'Falha no servidor'
      };
    }

    // 4. Falha de conexão / rede
    if (name.includes('typeerror') || msg.includes('failed to fetch') || msg.includes('networkerror') || msg.includes('conexão') || msg.includes('econnrefused')) {
      return {
        title: 'Falha de conexão',
        message: 'Não foi possível conectar ao servidor local. Verifique se o sistema está em execução.',
        badge: 'Sem conexão'
      };
    }

    // 5. Fallback genérico
    return {
      title: 'Dados indisponíveis',
      message: 'Não foi possível carregar os registros de disparo no momento. Nenhuma contagem ou entrega foi presumida.',
      badge: 'Indisponível'
    };
  }

  function handleDispatchLogsError(error) {
    dispatchLogsState.loading = false;
    const info = classifyError(error);

    const warningEl = $('#dispatchLogsWarning');
    if (warningEl) warningEl.style.display = 'none';

    const unavailEl = $('#dispatchLogsUnavailable');
    const msgEl = $('#dispatchLogsUnavailableMsg');
    if (unavailEl) {
      unavailEl.style.display = 'block';
      if (msgEl) {
        msgEl.textContent = `${info.title}. ${info.message}`;
      }
    }

    // Do NOT set summary to 0! Set all to "—" (dash)
    ['#dlStatTotal', '#dlStatSmtp', '#dlStatWa', '#dlStatIg', '#dlStatDelivery', '#dlStatUncertain', '#dlStatErrors', '#dlStatExclusions', '#dlStatQueued'].forEach((sel) => {
      setText(sel, '—');
    });

    const panel = $('#dispatchLogsPanel');
    if (panel) {
      panel.innerHTML = `<div class="connection-error"><strong>${esc(info.title)}</strong><span>${esc(info.message)}</span></div>`;
    }

    setText('#dlPageInfo', info.badge);
    const prevBtn = $('#dlPrevPage');
    const nextBtn = $('#dlNextPage');
    if (prevBtn) prevBtn.disabled = true;
    if (nextBtn) nextBtn.disabled = true;
    setText('#dlFilterStatusText', info.badge);
  }

  function loadDispatchLogs() {
    const panel = $('#dispatchLogsPanel');
    if (!panel) return Promise.resolve();

    dispatchLogsState.loading = true;
    setText('#dlFilterStatusText', 'Consultando central de logs...');

    const params = new URLSearchParams();
    params.set('page', String(dispatchLogsState.page));
    params.set('limit', String(dispatchLogsState.limit));

    if (dispatchLogsState.channel && dispatchLogsState.channel !== 'all') {
      params.set('channel', dispatchLogsState.channel);
    }
    if (dispatchLogsState.status && dispatchLogsState.status !== 'all') {
      params.set('status', dispatchLogsState.status);
    }
    if (dispatchLogsState.sender_instance_id) {
      params.set('sender_instance_id', dispatchLogsState.sender_instance_id);
    }
    if (dispatchLogsState.sender_account) {
      params.set('sender_account', dispatchLogsState.sender_account);
    }
    if (dispatchLogsState.date_from) {
      params.set('date_from', dispatchLogsState.date_from);
    }
    if (dispatchLogsState.date_to) {
      params.set('date_to', dispatchLogsState.date_to);
    }
    if (dispatchLogsState.search) {
      params.set('search', dispatchLogsState.search);
    }

    return request('/api/local/dispatch-logs?' + params.toString())
      .then((data) => {
        renderDispatchLogsResponse(data);
        return data;
      })
      .catch((error) => {
        handleDispatchLogsError(error);
      });
  }

  function bindMetas() {
    const applyInputsToState = () => {
      const ch = $('#dlFilterChannel');
      if (ch) dispatchLogsState.channel = ch.value;
      const st = $('#dlFilterStatus');
      if (st) dispatchLogsState.status = st.value;
      const lim = $('#dlFilterLimit');
      if (lim) dispatchLogsState.limit = Number(lim.value) || 25;
      const inst = $('#dlFilterInstance');
      if (inst) dispatchLogsState.sender_instance_id = inst.value.trim();
      const acc = $('#dlFilterAccount');
      if (acc) dispatchLogsState.sender_account = acc.value.trim();
      const df = $('#dlFilterDateFrom');
      if (df) dispatchLogsState.date_from = df.value.trim();
      const dt = $('#dlFilterDateTo');
      if (dt) dispatchLogsState.date_to = dt.value.trim();
      const srch = $('#dlFilterSearch');
      if (srch) dispatchLogsState.search = srch.value.trim();
    };

    const syncStateToInputs = () => {
      const ch = $('#dlFilterChannel');
      if (ch) ch.value = dispatchLogsState.channel;
      const st = $('#dlFilterStatus');
      if (st) st.value = dispatchLogsState.status;
      const lim = $('#dlFilterLimit');
      if (lim) lim.value = String(dispatchLogsState.limit);
      const inst = $('#dlFilterInstance');
      if (inst) inst.value = dispatchLogsState.sender_instance_id;
      const acc = $('#dlFilterAccount');
      if (acc) acc.value = dispatchLogsState.sender_account;
      const df = $('#dlFilterDateFrom');
      if (df) df.value = dispatchLogsState.date_from;
      const dt = $('#dlFilterDateTo');
      if (dt) dt.value = dispatchLogsState.date_to;
      const srch = $('#dlFilterSearch');
      if (srch) srch.value = dispatchLogsState.search;
    };

    syncStateToInputs();

    $('#dlFilterChannel')?.addEventListener('change', () => {
      dispatchLogsState.channel = $('#dlFilterChannel').value;
      dispatchLogsState.page = 1;
      loadDispatchLogs();
    });

    $('#dlFilterStatus')?.addEventListener('change', () => {
      dispatchLogsState.status = $('#dlFilterStatus').value;
      dispatchLogsState.page = 1;
      loadDispatchLogs();
    });

    $('#dlFilterLimit')?.addEventListener('change', () => {
      dispatchLogsState.limit = Number($('#dlFilterLimit').value) || 25;
      dispatchLogsState.page = 1;
      loadDispatchLogs();
    });

    $('#dlFilterSearch')?.addEventListener('input', (event) => {
      clearTimeout(dispatchLogsSearchTimer);
      dispatchLogsSearchTimer = setTimeout(() => {
        dispatchLogsState.search = event.target.value.trim();
        dispatchLogsState.page = 1;
        loadDispatchLogs();
      }, 300);
    });

    $('#dlApplyFilters')?.addEventListener('click', () => {
      applyInputsToState();
      dispatchLogsState.page = 1;
      loadDispatchLogs();
    });

    $('#dlClearFilters')?.addEventListener('click', () => {
      dispatchLogsState.page = 1;
      dispatchLogsState.limit = 25;
      dispatchLogsState.channel = 'all';
      dispatchLogsState.status = 'all';
      dispatchLogsState.sender_instance_id = '';
      dispatchLogsState.sender_account = '';
      dispatchLogsState.date_from = '';
      dispatchLogsState.date_to = '';
      dispatchLogsState.search = '';
      syncStateToInputs();
      loadDispatchLogs();
    });

    $('#dlPrevPage')?.addEventListener('click', () => {
      if (dispatchLogsState.has_prev) {
        dispatchLogsState.page = Math.max(1, dispatchLogsState.page - 1);
        loadDispatchLogs();
      }
    });

    $('#dlNextPage')?.addEventListener('click', () => {
      if (dispatchLogsState.has_next) {
        dispatchLogsState.page += 1;
        loadDispatchLogs();
      }
    });

    loadDispatchLogs();
  }


  function priorityAgencyTable(rows) {
    return table(['Selecao','CNPJ','Agencia','UF / cidade','Contratos / valor cadastrado','Canais disponiveis','Fonte'], rows.map((a) => {
      const hasCnpj = /^\d{14}$/.test(String(a.cnpj || '')) && a.agency_id === a.cnpj;
      const channels = [['site','Site'],['email','E-mail'],['telefone','Telefone'],['whatsapp','WhatsApp'],['instagram','Instagram']]
        .filter(([key]) => a[key]).map(([,label]) => label).join(' / ') || 'Nenhum canal cadastrado';
      const amount = a.contratos == null || a.contratos === '' ? 'Nao informado' : Number.isFinite(Number(a.contratos))
        ? Number(a.contratos).toLocaleString('pt-BR', {style:'currency',currency:'BRL'})
        : String(a.contratos);
      const selectable = Boolean(a.agency_id);
      const selection = selectable
        ? `<label class="agency-select"><input type="checkbox" data-priority-agency-selection="${esc(a.agency_id)}" ${selectedAgencyIds.has(a.agency_id) ? 'checked' : ''}><span>${selectedAgencyIds.has(a.agency_id) ? 'Selecionada' : 'Selecionar'}</span></label>`
        : '<label class="agency-select"><input type="checkbox" disabled aria-label="Agencia sem identificador estavel"><span>Indisponivel</span></label>';
      const agency = selectable
        ? `<button class="table-link" data-detail="${esc(a.agency_id)}" data-name="${esc(a.nome || '')}">${esc(a.nome || 'Agencia sem nome')}</button>`
        : `<span>${esc(a.nome || 'Agencia sem nome')}</span><small>Sem identificador estável; indisponivel para selecao</small>`;
      return [selection, esc(hasCnpj ? a.cnpj : 'Sem CNPJ'), `${agency}<small>${esc(a.dono || 'Decisor nao informado')}</small>`,
        esc([a.uf,a.municipio].filter(Boolean).join(' / ') || 'Localidade nao informada'), esc(amount), esc(channels), esc(a.source || 'Fonte nao informada')];
    }));
  }

  async function fetchPriorityAgencyRows() {
    const first = await request('/api/local/agencies?offset=0&limit=100');
    const rows = (first.items || []).slice();
    for (let offset = rows.length; offset < Number(first.total || 0); offset += 100) {
      const page = await request('/api/local/agencies?offset=' + offset + '&limit=100');
      if (page.total !== first.total) throw new Error('A contagem da base mudou durante a leitura; recarregue a pagina.');
      rows.push(...(page.items || []));
    }
    if (rows.length !== Number(first.total || 0)) throw new Error(`Base incompleta: ${rows.length} de ${first.total} registros carregados.`);
    rows.sort((a,b) => {
      const aHas = /^\d{14}$/.test(String(a.cnpj || '')) && a.agency_id === a.cnpj;
      const bHas = /^\d{14}$/.test(String(b.cnpj || '')) && b.agency_id === b.cnpj;
      return Number(!aHas) - Number(!bHas) || String(a.nome || '').localeCompare(String(b.nome || ''), 'pt-BR');
    });
    const actualCnpjCount = rows.filter((row) => /^\d{14}$/.test(String(row.cnpj || '')) && row.agency_id === row.cnpj).length;
    if (actualCnpjCount !== Number(first.cnpj_count) || rows.length - actualCnpjCount !== Number(first.cnpjless_count)) {
      throw new Error('A classificacao CNPJ/CNPJless da resposta nao confere com a contagem da fonte.');
    }
    priorityAgencyRows = rows;
    priorityAgencyMeta = first;
  }

  function renderPriorityAgencies() {
    const panel = $('#priorityAgencyPanel');
    if (!panel) return;
    const query = ($('#priorityAgencySearch')?.value || '').trim().toLocaleLowerCase('pt-BR');
    const uf = ($('#priorityAgencyUf')?.value || '').trim().toUpperCase();
    const searchable = (row) => [row.nome,row.cnpj,row.municipio,row.uf,row.dono,row.site,row.email,row.telefone,row.whatsapp,row.instagram,row.source]
      .filter(Boolean).join(' ').toLocaleLowerCase('pt-BR');
    const rows = priorityAgencyRows.filter((row) => (!uf || String(row.uf || '').toUpperCase() === uf) && (!query || searchable(row).includes(query)));
    const cnpjCount = rows.filter((row) => /^\d{14}$/.test(String(row.cnpj || '')) && row.agency_id === row.cnpj).length;
    const cnpjlessCount = rows.length - cnpjCount;
    const source = priorityAgencyMeta.source || 'base canonica';
    const updated = priorityAgencyMeta.source_updated_at || 'data nao informada pela fonte';
    setText('#priorityAgencyMeta', `${number(rows.length)} agencias no filtro / ${number(cnpjCount)} com CNPJ / ${number(cnpjlessCount)} sem CNPJ, selecionaveis por canal. Base completa: ${number(priorityAgencyMeta.canonical_total)} (${number(priorityAgencyMeta.canonical_cnpj_count)} com CNPJ + ${number(priorityAgencyMeta.canonical_cnpjless_count)} sem CNPJ). Fonte: ${source} / atualizacao: ${updated}.`);
    panel.innerHTML = priorityAgencyTable(rows);
  }

  function bindPriorityAgencies() {
    const panel = $('#priorityAgencyPanel');
    if (!panel) return;
    const load = async () => {
      panel.innerHTML = '<div class="loading-state">Lendo todas as paginas da base canonica...</div>';
      try {
        const selection = await request('/api/local/agency-selection');
        selectedAgencyIds = new Set(selection.agency_ids || selection.cnpjs || []);
        updateSelectionUI();
        await fetchPriorityAgencyRows();
        renderPriorityAgencies();
      } catch (error) {
        panel.innerHTML = errorBox(error);
        setText('#priorityAgencyMeta', 'Base incompleta ou indisponivel; nenhum total foi presumido.');
      }
    };
    $('#priorityAgencySearch').addEventListener('input', renderPriorityAgencies);
    $('#priorityAgencyUf').addEventListener('change', renderPriorityAgencies);
    panel.addEventListener('change', async (event) => {
      const input = event.target.closest('[data-priority-agency-selection]');
      if (!input) return;
      const id = input.dataset.priorityAgencySelection;
      if (!id) return;
      const wasSelected = selectedAgencyIds.has(id);
      if (input.checked) selectedAgencyIds.add(id); else selectedAgencyIds.delete(id);
      input.disabled = true;
      try { await persistAgencySelection(); renderPriorityAgencies(); }
      catch (error) {
        if (wasSelected) selectedAgencyIds.add(id); else selectedAgencyIds.delete(id);
        localStorage.setItem(selectionStorageKey, JSON.stringify(Array.from(selectedAgencyIds)));
        updateSelectionUI();
        setText('#priorityAgencyMeta', 'Selecao nao salva: ' + error.message);
        renderPriorityAgencies();
      }
    });
    load();
  }

  function loadAgencies() {
    const panel = $('#agencyPanel');
    if (!panel) return;
    if (agencyState.channel === 'replies') {
      $('#agencyPage').textContent = 'Respostas explícitas';
      $('#agencyPrev').disabled = $('#agencyNext').disabled = true;
      loadPriorities('#agencyPanel');
      return;
    }
    panel.innerHTML = '<div class="loading-state">Buscando agências...</div>';
    const params = new URLSearchParams({q:agencyState.q,uf:agencyState.uf,channel:agencyState.channel,offset:String(agencyState.offset),limit:'50'});
    request('/api/local/agencies?' + params).then((data) => {
      if (!$('#agencyPanel')) return;
      agencyState.total = data.total;
      agencyState.selectableIds = data.selectable_agency_ids || 0;
      agencyState.items = data.items || [];
      const selectFilteredButton = $('#selectFilteredAgencies');
      if (selectFilteredButton) selectFilteredButton.textContent = `Selecionar todas do filtro atual (${number(agencyState.selectableIds)} agências)`;
      $('#agencyPage').textContent = `${number(data.total)} agências · ${data.total ? number(data.offset + 1) : 0}–${number(data.offset + data.items.length)}`;
      $('#agencyPrev').disabled = data.offset <= 0;
      $('#agencyNext').disabled = data.offset + data.items.length >= data.total;
      panel.innerHTML = table(['Agência','Localidade','Decisor','Canais cadastrados','Fonte'], data.items.map((a) => [
        `<label class="agency-select"><input type="checkbox" data-select-agency="${esc(a.agency_id || '')}" ${selectedAgencyIds.has(a.agency_id) ? 'checked' : ''} ${a.agency_id ? '' : 'disabled'}><span>${a.agency_id ? 'Selecionar' : 'Sem identificador'}</span></label>${a.agency_id ? `<button class="table-link" data-detail="${esc(a.agency_id)}" data-name="${esc(a.nome)}">${esc(a.nome)}</button>` : `<span>${esc(a.nome)}</span>`}<small>${/^\d{14}$/.test(a.cnpj || '') ? esc(a.cnpj) : 'Sem CNPJ · selecionavel por canal quando houver contato validado'}</small>`,
        esc([a.municipio,a.uf].filter(Boolean).join(' · ') || 'Não informado'),
        esc(a.dono || 'A verificar'),
        ['site','email','instagram','whatsapp','telefone'].filter((key) => a[key]).map((key) => esc(key)).join(' · ') || 'A verificar',
        esc(a.source)
      ]));
    }).catch((error) => { panel.innerHTML = errorBox(error); });
  }

  function bindProspecting() {
    showProspectingSummary().catch((error) => setText('#agencySourceMeta', error.message));
    request('/api/local/agency-selection').then((selection) => {
      selectedAgencyIds = new Set(selection.agency_ids || selection.cnpjs || []);
      localStorage.setItem(selectionStorageKey, JSON.stringify(Array.from(selectedAgencyIds)));
      updateSelectionUI();
      loadAgencies();
    }).catch(() => {
      try { selectedAgencyIds = new Set(JSON.parse(localStorage.getItem(selectionStorageKey) || '[]')); } catch (_) {}
      updateSelectionUI();
    });
    updateSelectionUI();
    $('#agencyPanel').addEventListener('change', async (event) => {
      const input = event.target.closest('[data-select-agency]');
      if (!input || !input.dataset.selectAgency) return;
      if (input.checked) selectedAgencyIds.add(input.dataset.selectAgency);
      else selectedAgencyIds.delete(input.dataset.selectAgency);
      try { await persistAgencySelection(); }
      catch (error) { setText('#agencySourceMeta', 'Selecao nao salva: ' + error.message); }
    });
    $('#selectPageAgencies').addEventListener('click', async () => {
      (agencyState.items || []).forEach((agency) => {if (agency.agency_id) selectedAgencyIds.add(agency.agency_id);});
      try { await persistAgencySelection(); loadAgencies(); }
      catch (error) { setText('#agencySourceMeta', 'Selecao nao salva: ' + error.message); }
    });
    const filteredSelectionQuery = () => new URLSearchParams({q:agencyState.q,uf:agencyState.uf,channel:agencyState.channel});
    $('#selectFilteredAgencies').addEventListener('click', async (event) => {
      const button = event.currentTarget; button.disabled = true;
      try {
        const result = await request('/api/local/agency-selection-candidates?' + filteredSelectionQuery());
        (result.agency_ids || result.cnpjs || []).forEach((agencyId) => selectedAgencyIds.add(agencyId));
        await persistAgencySelection();
        setText('#agencySourceMeta', `${number(result.selectable_agencies || result.selectable_cnpjs)} identificadores selecionaveis entre ${number(result.total)} agencias do filtro atual.`);
        loadAgencies();
      } catch (error) { setText('#agencySourceMeta', 'Selecao do filtro nao salva: ' + error.message); }
      finally { button.disabled = false; }
    });
    $('#undoFilteredSelection').addEventListener('click', async (event) => {
      const button = event.currentTarget; button.disabled = true;
      try {
        const result = await request('/api/local/agency-selection-candidates?' + filteredSelectionQuery());
        (result.agency_ids || result.cnpjs || []).forEach((agencyId) => selectedAgencyIds.delete(agencyId));
        await persistAgencySelection();
        setText('#agencySourceMeta', `Selecao desfeita para ${number(result.selectable_agencies || result.selectable_cnpjs)} identificadores do filtro atual.`);
        loadAgencies();
      } catch (error) { setText('#agencySourceMeta', 'Selecao do filtro nao alterada: ' + error.message); }
      finally { button.disabled = false; }
    });
    $('#clearSelectedAgencies').addEventListener('click', async () => {
      selectedAgencyIds.clear();
      try { await persistAgencySelection(); loadAgencies(); }
      catch (error) { setText('#agencySourceMeta', 'Selecao nao salva: ' + error.message); }
    });
    $('#agencySearch').addEventListener('input', (event) => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {agencyState.q = event.target.value.trim(); agencyState.offset = 0; loadAgencies();}, 220);
    });
    $('#agencyUf').addEventListener('change', (event) => {agencyState.uf = event.target.value; agencyState.offset = 0; loadAgencies();});
    $$('#agencyTabs [data-channel]').forEach((button) => button.addEventListener('click', () => {
      $$('#agencyTabs [data-channel]').forEach((x) => x.classList.remove('active'));
      button.classList.add('active'); agencyState.channel = button.dataset.channel; agencyState.offset = 0; loadAgencies();
    }));
    $('#agencyPrev').addEventListener('click', () => {agencyState.offset = Math.max(0,agencyState.offset - 50); loadAgencies();});
    $('#agencyNext').addEventListener('click', () => {agencyState.offset += 50; loadAgencies();});
    loadAgencies();
  }

  const actionPaths = {
    'start-ig-pessoal':'/api/action/start-instagram', 'stop-ig-pessoal':'/api/action/stop-instagram',
    'start-ig-oficial':'/api/action/start-instagram-oficial', 'stop-ig-oficial':'/api/action/stop-instagram-oficial',
    'login-ig-pessoal':'/api/action/open-login-firefox',
    'login-ig-oficial':'/api/action/open-login-firefox',
    'start-whatsapp':'/api/action/start-whatsapp', 'stop-whatsapp':'/api/action/stop-whatsapp',
    'start-email':'/api/action/start-email', 'stop-email':'/api/action/stop-email'
  };

  function campaignStatus() {
    if (!document.querySelector('#motor-ig-pessoal')) return;
    request('/api/local/campaign-status').then((state) => {
      const check = state.preflight || {};
      const display = (id, status, detail) => {
        setText('#state-' + id, status);
        setText('#kpi-' + id, detail);
      };
      const instagram = check.instagram || {};
      const personal = instagram['5050'] || {};
      const official = instagram['5051'] || {};
      const dailyCount = (item) => item.sent_today == null ? 'Enviados hoje: indisponivel' : number(item.sent_today) + ' enviados hoje';
      const queueCount = (item) => {
        const eligible = item.bot_coverage && item.bot_coverage.eligible_pending;
        return eligible == null ? 'elegiveis na selecao: indisponivel' : number(eligible) + ' elegiveis pendentes na selecao';
      };
      const igReasons = (item) => {
        const mapping = item.coverage || {};
        const bot = item.bot_coverage || {};
        const r = bot.reason_counts || {};
        const attemptLabel = item.attempt_count == null ? 'tentara indisponivel' : 'tentara ' + number(item.attempt_count);
        if (bot.reason_contract !== 'selected-handle-reasons-v1') {
          return [attemptLabel,
            'sem handle ' + number(mapping.selected_agencies_without_handle),
            'handle ambiguo ' + number(mapping.ambiguous_handle_agencies),
            'motivos do motor indisponiveis; build/preflight atualizado necessario'
          ].join(' | ');
        }
        return [
          attemptLabel,
          'sem handle ' + number(mapping.selected_agencies_without_handle),
          'handle ambiguo ' + number(mapping.ambiguous_handle_agencies),
          'nao encontrado ' + number(r.handle_not_found),
          'suprimido ' + number(r.suppressed),
          'pendente ' + number(r.pending_eligible),
          'fora da faixa de votos ' + number(r.excluded_by_vote_range),
          'enviado hoje/historico ' + number(r.already_sent_today) + '/' + number(r.already_sent_historically),
          'DM fechada/perfil ausente ' + number(r.dm_closed) + '/' + number(r.profile_not_found),
          'erro/incerto ' + number(r.error_status) + '/' + number(r.uncertain),
          'etapa/status ' + number(r.excluded_by_stage) + '/' + number(r.other_status_or_stage),
          'tambem elegiveis na outra conta ' + number((item.cross_account || {}).shared_pending_total)
        ].join(' | ');
      };
      const metricSource = (item) => (item.daily_count_source || item.status_source || 'Fonte: indisponivel') + ' | verificado em ' + (item.checked_at || 'horario indisponivel');
      display('ig-pessoal', personal.current_state || 'Indisponivel',
        dailyCount(personal) + ' | ' + queueCount(personal) + ' | ' + igReasons(personal) + ' | meta ' + number((check.daily_targets || {}).instagram_5050) + ' | ' + metricSource(personal) + (personal.blocked_reason ? ' | ' + personal.blocked_reason : ''));
      display('ig-oficial', official.current_state || 'Indisponivel',
        dailyCount(official) + ' | ' + queueCount(official) + ' | ' + igReasons(official) + ' | meta ' + number((check.daily_targets || {}).instagram_5051) + ' | ' + metricSource(official) + (official.blocked_reason ? ' | ' + official.blocked_reason : ''));
      const whatsapp = check.whatsapp || {};
      display('whatsapp', whatsapp.active ? 'Campanha em andamento' : whatsapp.session_ready ? 'Sessao OpenWA pronta' : 'OpenWA indisponivel',
        number(whatsapp.sent_today) + ' enviados hoje | ' + number(whatsapp.eligible) + ' elegiveis | meta ' + number(whatsapp.daily_target) +
        (whatsapp.blocked_reason ? ' | ' + whatsapp.blocked_reason : ''));
      const email = check.email || {};
      const emailRun = state.email_run || {};
      const emailAcceptedToday = email.accepted_smtp_today == null
        ? 'Aceitos SMTP hoje: indisponivel'
        : number(email.accepted_smtp_today) + ' aceitos pelo SMTP hoje; entrega nao confirmada';
      const emailRunAccepted = emailRun.sent == null
        ? 'aceites nesta campanha: indisponivel'
        : number(emailRun.sent) + ' aceitos nesta campanha';
      const uncertainReview = email.review_required
        ? `${number(email.uncertain_count)} resultado(s) incerto(s) exigem revisao manual; destinatarios seguem bloqueados contra reenvio (${number(email.uncertain_today)} contam na meta de hoje; ${number(email.stale_reservation_count)} reserva(s) stale). ` +
          (email.uncertain_records || []).slice(0, 3).map((item) => `${item.cnpj || 'CNPJ indisponivel'} / ${item.email || 'email indisponivel'} [${item.state}]`).join('; ')
        : 'Sem resultados incertos pendentes de revisao';
      display('email', email.active ? 'Campanha em andamento: ' + number(emailRun.sent) + ' de ' + number(emailRun.target) + ' aceitos pelo SMTP' : email.can_start ? 'Pronto para iniciar manualmente' : 'Bloqueado: ' + (email.blocked_reason || 'preflight pendente'),
        emailAcceptedToday + ' | ' + (email.daily_remaining == null ? 'restante da meta indisponivel' : number(email.daily_remaining) + ' restantes da meta') +
        ' | ' + number(email.eligible) + ' elegiveis na selecao | ' + emailRunAccepted + ' | meta ' + number(email.daily_target) +
        ' | ' + uncertainReview + (email.active ? ' | Envio em andamento, com intervalo entre mensagens. Aguarde; nao e necessario iniciar novamente. Erros neste lote: ' + number(emailRun.errors) : ''));
      const selectedCount = Number((check.selection || {}).count || 0);
      setText('#campaignSelectionSummary', selectedCount
        ? number(selectedCount) + ' agencias selecionadas da base canonica. A elegibilidade exibida considera esta lista.'
        : 'Nenhuma agencia selecionada. Escolha a lista em Prospeccao antes de preparar o preflight.');
      setText('#operationMessage', 'Status consultado nas fontes de cada motor. A consulta nao inicia disparos.');
      const fill = (id, value) => {
        const input = document.querySelector('#' + id);
        if (!campaignTargetsLoaded && input && !dirtyCampaignTargets.has(id) && value != null) input.value = value;
      };
      const targets = check.daily_targets || {};
      fill('target-email', targets.email); fill('target-whatsapp', targets.whatsapp);
      fill('target-ig-5050', targets.instagram_5050); fill('target-ig-5051', targets.instagram_5051);
      campaignTargetsLoaded = true;
      const automation = document.querySelector('#automationEnabled');
      if (automation && !scheduleChoiceDirty) automation.checked = !!(check.schedule || {}).enabled;
      const quotaNote = document.querySelector('#campaignQuotaNote');
      if (quotaNote) {
        const quota = (item, label, target) => item && item.sent_today != null
          ? label + ': ' + number(item.sent_today) + '/' + number(target) + ' hoje; ' +
            (item.sent_today >= target ? 'meta esgotada' : 'restam ' + number(target - item.sent_today))
          : label + ': contagem de hoje indisponivel';
        quotaNote.textContent = 'O numero digitado e o TOTAL do dia, nao a quantidade adicional. Digitar 1 significa permitir apenas 1 no dia. ' +
          [quota(email, 'E-mail', targets.email), quota(whatsapp, 'WhatsApp', targets.whatsapp),
            quota(personal, '@maldonadodiego55', targets.instagram_5050), quota(official, '@iarandu.br', targets.instagram_5051)].join(' | ');
      }
      const startChecks = {
        'start-ig-pessoal': instagram['5050'] || {},
        'start-ig-oficial': instagram['5051'] || {},
        'start-whatsapp': whatsapp,
        'start-email': email
      };
      Object.entries(startChecks).forEach(([action, status]) => {
        const button = document.querySelector('[data-action="' + action + '"]');
        if (!button) return;
        const readyLabel = button.dataset.readyLabel || button.textContent;
        button.dataset.readyLabel = readyLabel;
        button.disabled = !!check.preview || !status.can_start;
        if (check.preview) {
          button.textContent = 'Envios suspensos na prévia';
        } else if (status.can_start) {
          button.textContent = readyLabel;
        } else {
          const reason = String(status.blocked_reason || '').toLowerCase();
          const active = !!(status.active || status.is_running);
          const remaining = status.daily_remaining ?? status.available;
          const eligible = status.eligible ?? (status.bot_coverage || {}).eligible_pending;
          button.textContent = active ? 'Lote em andamento'
            : /recipient_unverified_before_submit/.test(reason) ? 'Destinat\u00e1rio n\u00e3o confirmado'
            : /interrupted_before_submit|resultado_incerto|timeout/.test(reason) ? 'Falha segura; revisar'
            : /identidade|sessão instagram não confirmada/.test(reason) ? 'Identidade não verificada'
            : remaining === 0 ? 'Meta diária atingida'
            : eligible === 0 ? 'Sem contato elegível'
            : 'Pré-voo bloqueado';
        }
        button.title = check.preview ? 'Prévia ativa: envios reais suspensos.'
          : (status.can_start ? 'Preflight aprovado: pronto para início manual.'
            : (status.blocked_reason || 'Pré-flight do canal não liberou o início.'));
      });
      const saveButton = document.querySelector('#saveCampaignConfig');
      if (saveButton) saveButton.disabled = false;
      const schedule = check.schedule || {};
      const times = schedule.times || {};
      const weekdayNames = {0:'seg',1:'ter',2:'qua',3:'qui',4:'sex',5:'sáb',6:'dom'};
      const weekdays = (schedule.weekdays || []).map((day) => weekdayNames[day]).filter(Boolean).join(', ');
      setText('#scheduleStatus', schedule.enabled
        ? `Automação ativa (${weekdays || 'dias configurados'}, 08h–18h). Inícios: e-mail ${times.email || '—'}, WhatsApp ${times.whatsapp || '—'}, Instagram @iarandu.br ${times.instagram || '—'}. A conta pessoal continua manual.`
        : 'Programação automática pausada. Início manual por canal.');
    }).catch((error) => {
      for (const motor of ['ig-pessoal', 'ig-oficial', 'whatsapp', 'email']) {
        setText('#state-' + motor, 'Status indisponivel');
        setText('#kpi-' + motor, 'Preflight nao confirmado; inicio bloqueado.');
      }
      for (const action of ['start-ig-pessoal', 'start-ig-oficial', 'start-whatsapp', 'start-email']) {
        const button = document.querySelector('[data-action="' + action + '"]');
        if (button) {
          button.disabled = true;
          button.textContent = 'Pré-voo indisponível';
          button.title = 'Status indisponivel; inicio bloqueado ate confirmar o preflight.';
        }
      }
      setText('#scheduleStatus', 'Pré-voo indisponível. Os botões de disparo ficam bloqueados até confirmar o estado dos canais.');
      setText('#operationMessage', 'Status indisponivel; envios bloqueados: ' + error.message);
    });
  }

  function bindCampaigns() {
    campaignTargetsLoaded = false;
    scheduleChoiceDirty = false;
    dirtyCampaignTargets.clear();
    const actions = document.querySelector('.campaign-actions');
    if (actions && !document.querySelector('#automationEnabled')) {
      actions.insertAdjacentHTML('afterend', '<label class="small-note"><input id="automationEnabled" type="checkbox"> Automacao ativa (segunda a sexta, 08h-18h)</label><p class="small-note" id="campaignQuotaNote">Consultando quantos ja foram enviados hoje...</p>');
    }
    document.querySelector('#automationEnabled')?.addEventListener('change', () => { scheduleChoiceDirty = true; });
    const initialSaveButton = document.querySelector('#saveCampaignConfig');
    if (initialSaveButton) initialSaveButton.disabled = true;
    campaignStatus();
    clearInterval(campaignTimer);
    campaignTimer = setInterval(campaignStatus, 10000);
    const targetIds = ['target-email','target-whatsapp','target-ig-5050','target-ig-5051'];
    targetIds.forEach((id) => $('#'+id)?.addEventListener('input', () => dirtyCampaignTargets.add(id)));
    const targetSpecs = [
      ['email','target-email','E-mail',60], ['whatsapp','target-whatsapp','WhatsApp',30],
      ['instagram_5050','target-ig-5050','@maldonadodiego55',50], ['instagram_5051','target-ig-5051','@iarandu.br',50]
    ];
    const campaignConfigPayload = () => {
      const daily_targets = {};
      targetSpecs.forEach(([key,id,label,max]) => {
        const input = $('#'+id);
        const value = input ? input.valueAsNumber : NaN;
        if (!input || !input.value.trim() || !Number.isInteger(value) || value < 1 || value > max) {
          if (input) input.reportValidity();
          throw new Error(`${label}: informe um número inteiro entre 1 e ${max}.`);
        }
        daily_targets[key] = value;
      });
      return {
        daily_targets,
        schedule:{enabled:!!$('#automationEnabled')?.checked,weekdays:[0,1,2,3,4],times:{email:'08:00',whatsapp:'08:02',instagram:'08:05'}}
      };
    };
    const saveTargets = async () => {
      const result = await request('/api/local/campaign-config',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(campaignConfigPayload())});
      const saved = result.config.daily_targets;
      targetSpecs.forEach(([key,id]) => { const input=$('#'+id); if(input) input.value=saved[key]; dirtyCampaignTargets.delete(id); });
      campaignTargetsLoaded = true;
      scheduleChoiceDirty = false;
      setText('#scheduleStatus', `Metas salvas. Automacao ${result.config.schedule.enabled ? 'ativa' : 'pausada'}. E-mail ${saved.email}, WhatsApp ${saved.whatsapp}, @maldonadodiego55 ${saved.instagram_5050} e @iarandu.br ${saved.instagram_5051} por dia.`);
      campaignStatus();
      return result;
    };
    $$('[data-action]').forEach((button) => button.addEventListener('click', async () => {
      const endpoint = actionPaths[button.dataset.action];
      if (!endpoint || button.disabled) return;
      button.disabled = true;
      const actionPayload = {};
      const loginPorts = {'login-ig-pessoal':5050,'login-ig-oficial':5051};
      if (Object.prototype.hasOwnProperty.call(loginPorts, button.dataset.action)) {
        actionPayload.port = loginPorts[button.dataset.action];
      }
      setText('#operationMessage', 'Enviando comando ao backend...');
      try {
        if (button.dataset.action.startsWith('start-')) {
          await saveTargets();
        }
        const result = await request(endpoint, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(actionPayload)});
        setText('#operationMessage', result.message || result.status || 'Comando aceito.');
        campaignStatus();
      } catch (error) {setText('#operationMessage', error.message);}
      finally {
        if (button.dataset.action.startsWith('start-')) {
          campaignStatus();
        } else {
          setTimeout(() => {button.disabled = false;}, 1500);
        }
      }
    }));
    const saveButton = $('#saveCampaignConfig');
    if (saveButton) saveButton.addEventListener('click', async () => {
      if (saveButton.disabled) return;
      saveButton.disabled = true;
      try {await saveTargets();}
      catch(error){setText('#scheduleStatus',error.message);}
      finally{saveButton.disabled=false;}
    });
  }

  function openDetail(cnpj, name) {
    const drawer = $('#drawer');
    if (!drawer) return;
    $('#drawerRole').textContent = 'Ficha operacional';
    $('#drawerTitle').textContent = name || cnpj || 'Agência';
    $('#drawerSubtitle').textContent = cnpj || 'Sem CNPJ';
    $('#drawerAvatar').style.backgroundImage = 'url(/scene/iarandu-logo.png)';
    $('#drawerBody').innerHTML = '<p>Carregando ficha...</p>';
    document.body.classList.add('drawer-open');
    request('/api/local/agency/' + encodeURIComponent(cnpj)).then((d) => {
      const a = d.agencia || {};
      $('#drawerTitle').textContent = a.nome_fantasia || a.razao_social || name || cnpj;
      const details = `<div class="detail-facts"><p><b>Município:</b> ${esc([a.municipio,a.uf].filter(Boolean).join(' / '))}</p><p><b>Decisor:</b> ${esc(a.dono_nome || 'Não informado')}</p><p><b>Site:</b> ${esc(a.website_oficial || 'Não informado')}</p><p><b>E-mail:</b> ${esc(a.email || 'Não informado')}</p><p><b>Instagram:</b> ${esc(a.instagram_handle || 'Não informado')}</p><p><b>Telefone:</b> ${esc(a.telefone || 'Não informado')}</p></div>`;
      if (d.crm_actions_available === false || !/^\d{14}$/.test(a.cnpj || cnpj)) {
        $('#drawerBody').innerHTML = `${details}<p class="small-note">Identidade ${esc(d.agency_id || cnpj)}. ${esc(d.action_block_reason || 'Sem CNPJ conciliado: ficha somente para revisão; ações CRM exigem conciliação.')}</p>`;
        return;
      }
      const tasks = (d.tasks || []).map((t) => `<div class="detail-entry"><span>${esc(t.status)} · ${esc(t.prazo || '')}</span><strong>${esc(t.titulo)}</strong><button class="table-link" data-toggle-task="${Number(t.id)}">Alternar conclusão</button></div>`).join('') || '<p>Nenhuma tarefa.</p>';
      const history = (d.history || []).slice(0,8).map((h) => `<div class="detail-entry"><span>${esc(h.criado_em || '')} · ${esc(h.tipo)}</span><p>${esc(h.descricao)}</p></div>`).join('') || '<p>Sem histórico CRM.</p>';
      $('#drawerBody').innerHTML = `${details}<h3>Etapa</h3><p>${esc((d.deal || {}).etapa || 'pesquisa')} · ${esc((d.deal || {}).proximo_passo || 'Próximo passo não registrado')}</p><form class="detail-form" data-endpoint="/api/deals/update"><input type="hidden" name="cnpj" value="${esc(cnpj)}"><select name="etapa" class="select">${['pesquisa','contato_iniciado','resposta','diagnostico','demonstracao','proposta','negociacao','ganho','perdido'].map((x) => `<option ${x===(d.deal || {}).etapa?'selected':''}>${x}</option>`).join('')}</select><button class="black-button">Salvar etapa</button></form><h3>Nova nota</h3><form class="detail-form" data-endpoint="/api/notes/create"><input type="hidden" name="cnpj" value="${esc(cnpj)}"><textarea class="textarea" name="nota" required placeholder="Registre a conversa ou decisão"></textarea><button class="black-button">Salvar nota</button></form><h3>Nova tarefa</h3><form class="detail-form" data-endpoint="/api/tasks/create"><input type="hidden" name="cnpj" value="${esc(cnpj)}"><input class="input" name="titulo" required placeholder="Próxima ação"><input class="input" name="prazo" type="date" required><button class="black-button">Criar tarefa</button></form><h3>Proposta</h3><form class="detail-form" data-endpoint="/api/proposals/save"><input type="hidden" name="cnpj" value="${esc(cnpj)}"><input class="input" name="valor" type="number" min="0" step="0.01" placeholder="Valor mensal"><input class="input" name="condicoes" placeholder="Condições"><input class="input" name="objecao" placeholder="Objeção registrada"><button class="black-button">Registrar proposta</button></form><h3>Tarefas</h3>${tasks}<h3>Histórico</h3>${history}<p class="small-note">Contatos enriquecidos pendentes exigem revisão humana; esta ficha não os aprova automaticamente.</p><a href="/legado" target="_blank" rel="noopener">Abrir revisão e exclusões no painel original ↗</a><p id="detailMessage" role="status"></p>`;
      $('#drawerBody').insertAdjacentHTML('beforeend', `<h3>Resposta recebida</h3><p class="small-note">Registre somente uma resposta realmente recebida; etapa “resposta” antiga não basta. Esta ação não envia mensagem.</p><form class="detail-form" data-endpoint="/api/local/replies/confirm"><input type="hidden" name="cnpj" value="${esc(cnpj)}"><select class="select" name="canal" required><option value="">Canal da resposta</option><option value="instagram">Instagram</option><option value="whatsapp">WhatsApp</option><option value="email">E-mail</option><option value="outro">Outro</option></select><input class="input" type="datetime-local" name="recebido_em" required><textarea class="textarea" name="evidencia" required minlength="15" maxlength="1000" placeholder="Evidência: o que a agência respondeu e onde foi conferido"></textarea><input class="input" name="proximo_passo" maxlength="500" placeholder="Próximo passo"><button class="black-button">Confirmar resposta no CRM</button></form>`);
      $$('.detail-form').forEach((form) => form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = form.querySelector('button'); button.disabled = true;
        try {const result = await request(form.dataset.endpoint,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(Object.fromEntries(new FormData(form)))}); if (!result.success) throw new Error(result.message || 'Não confirmado'); openDetail(cnpj, name);}
        catch (error) {setText('#detailMessage',error.message); button.disabled = false;}
      }));
      $$('[data-toggle-task]').forEach((button) => button.addEventListener('click', async () => {
        button.disabled = true;
        try {await request('/api/tasks/toggle',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:Number(button.dataset.toggleTask)})}); openDetail(cnpj,name);}
        catch (error) {setText('#detailMessage',error.message); button.disabled = false;}
      }));
    }).catch((error) => {$('#drawerBody').innerHTML = errorBox(error) + '<p>Se a agência existir apenas na base Sinapro, a ficha operacional ainda precisa ser conciliada.</p>';});
  }

  document.addEventListener('click', (event) => {
    const target = event.target.closest('[data-detail]');
    if (target) openDetail(target.dataset.detail, target.dataset.name || target.textContent.trim());
  });

  function bindMessages() {
    const fields = {
      instagram_5050: $('#messageInstagram5050'),
      instagram_5051: $('#messageInstagram5051'),
      whatsapp: $('#messageWhatsapp'),
      email: $('#messageEmail'),
      email_subject: $('#messageEmailSubject')
    };

    const updateCount = (key) => {
      const field = fields[key];
      if (!field) return;
      const note = $(`[data-message-count="${field.id}"]`);
      if (!note) return;
      const len = field.value ? field.value.length : 0;
      const hasCompany = (field.value || '').includes('{empresa}');
      const companyTag = hasCompany ? '{empresa} presente' : 'Atenção: falta {empresa}';
      if (key === 'email_subject') {
        note.textContent = `${number(len)} caracteres · ${companyTag} · sem quebras de linha`;
      } else if (key === 'email') {
        note.textContent = `${number(len)} caracteres · ${companyTag} · variações {opcao1|opcao2}`;
      } else {
        note.textContent = `${number(len)} caracteres · ${companyTag}`;
      }
    };

    const STATIC_IFRAME_ERROR = '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Aviso de Prévia</title><style>body{margin:0;padding:28px;font-family:Arial,Helvetica,sans-serif;background:#faf5f5;color:#8a2626;text-align:center;line-height:1.6}h3{margin:0 0 10px;font-size:16px}p{margin:0;font-size:13px;color:#5a1e17}</style></head><body><h3>Prévia não disponível para o texto atual</h3><p>Verifique o aviso de validação acima. O assunto e o corpo devem conter a tag {empresa} e variações no padrão {opcao1|opcao2}.</p></body></html>';

    async function loadEmailPreview(companyName, options = {}) {
      const previewFrame = $('#emailPreviewFrame');
      const previewSubject = $('#emailPreviewSubject');
      const previewMeta = $('#emailPreviewMeta');
      const previewNotice = $('#emailPreviewNotice');
      if (!previewFrame) return;

      const companyInput = $('#emailPreviewCompany');
      const company = (companyName !== undefined ? companyName : (companyInput ? companyInput.value : '')) || 'Agência Exemplo';
      const safeCompany = company.trim() || 'Agência Exemplo';
      const isPersisted = Boolean(options && options.persisted);

      try {
        let result;
        if (isPersisted) {
          result = await request(`/api/local/messages/email-preview?empresa=${encodeURIComponent(safeCompany)}`);
        } else {
          result = await request('/api/local/messages/email-preview', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
              empresa: safeCompany,
              messages: {
                email: fields.email ? fields.email.value : '',
                email_subject: fields.email_subject ? fields.email_subject.value : ''
              }
            })
          });
        }

        if (result && result.status === 'ok') {
          if (previewSubject) previewSubject.textContent = result.subject || '—';
          if (previewMeta) {
            const shortHash = result.model_hash ? result.model_hash.slice(0, 10) : '—';
            previewMeta.textContent = isPersisted
              ? `Modelo salvo: ${result.model_version || 'email-v1'} · Hash: ${shortHash}`
              : `Rascunho em edição · Hash: ${shortHash}`;
          }
          if (previewNotice) {
            const noticePrefix = result.notice ? `${result.notice} ` : '';
            previewNotice.textContent = `${noticePrefix}As variações sorteadas são ilustrativas para demonstração e não representam garantia de texto exato no sorteio aleatório.`;
          }
          const safeHtml = (result.html || '').replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
          previewFrame.srcdoc = safeHtml;
        } else {
          const errMsg = (result && result.message) || 'Não foi possível gerar a prévia do e-mail.';
          if (previewSubject) previewSubject.textContent = 'Prévia indisponível';
          if (previewMeta) previewMeta.textContent = errMsg;
          if (previewNotice) previewNotice.textContent = `Aviso: ${errMsg}`;
          previewFrame.srcdoc = STATIC_IFRAME_ERROR;
        }
      } catch (err) {
        const errMsg = err && err.message ? err.message : String(err);
        if (previewSubject) previewSubject.textContent = 'Falha na prévia';
        if (previewMeta) previewMeta.textContent = errMsg;
        if (previewNotice) previewNotice.textContent = `Erro: ${errMsg}`;
        previewFrame.srcdoc = STATIC_IFRAME_ERROR;
      }
    }

    request('/api/local/messages').then((result) => {
      const messages = result.messages || {};
      Object.keys(fields).forEach((key) => {
        if (fields[key]) {
          fields[key].value = messages[key] || '';
          updateCount(key);
          fields[key].addEventListener('input', () => updateCount(key));
        }
      });
      loadEmailPreview(undefined, { persisted: true });
    }).catch((error) => {
      setText('#messageSaveStatus', `Não foi possível carregar os modelos oficiais: ${error.message}`);
    });

    const refreshBtn = $('#refreshEmailPreview');
    if (refreshBtn) {
      refreshBtn.addEventListener('click', () => {
        const companyInput = $('#emailPreviewCompany');
        loadEmailPreview(companyInput ? companyInput.value : '', { persisted: false });
      });
    }

    const checkSavedBtn = $('#checkSavedEmailPreview');
    if (checkSavedBtn) {
      checkSavedBtn.addEventListener('click', () => {
        const companyInput = $('#emailPreviewCompany');
        loadEmailPreview(companyInput ? companyInput.value : '', { persisted: true });
      });
    }

    const companyInput = $('#emailPreviewCompany');
    if (companyInput) {
      companyInput.addEventListener('keydown', (ev) => {
        if (ev.key === 'Enter') {
          ev.preventDefault();
          loadEmailPreview(companyInput.value, { persisted: false });
        }
      });
    }

    const save = $('#saveOfficialMessages');
    if (save) save.addEventListener('click', async () => {
      save.disabled = true;
      setText('#messageSaveStatus', 'Salvando modelos oficiais no backend...');
      try {
        const payloadMessages = {};
        Object.entries(fields).forEach(([key, field]) => {
          if (field) payloadMessages[key] = field.value;
        });
        const result = await request('/api/local/messages', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({messages: payloadMessages})
        });

        const persisted = result.messages || {};
        Object.keys(fields).forEach((key) => {
          if (fields[key] && persisted[key] !== undefined) {
            fields[key].value = persisted[key];
          }
          updateCount(key);
        });

        await loadEmailPreview(undefined, { persisted: true });

        const serverMsg = result.message || 'Modelos oficiais salvos com sucesso.';
        setText('#messageSaveStatus', `${serverMsg} Válido para novos lotes. Nenhum envio foi iniciado.`);
      } catch (error) {
        setText('#messageSaveStatus', `Falha ao salvar: ${error.message}`);
      } finally {
        save.disabled = false;
      }
    });

    return { loadEmailPreview, updateCount, fields };
  }

  function bind(route) {
    if (route !== 'home' && window.JarvisOrb) window.JarvisOrb.pause();
    clearInterval(campaignTimer);
    if (route === 'home') {
      const hero = $('.hero');
      if (hero) hero.innerHTML = '<h1>Seu CRM comercial, agora visual.</h1><p>Agências, respostas e campanhas no mesmo sistema local — com dados reais e controle por clique.</p><div class="hero-actions"><a class="pill-button primary" href="/campanhas" data-nav>Campanhas →</a><a class="pill-button" href="/prospeccao" data-nav>Prospecção →</a></div>';
      const product = $('.product-board'); if (product) product.remove(); if (window.JarvisOrb) window.JarvisOrb.mount('#jarvisTotem');
      $$('.hero [data-nav]').forEach((link) => link.addEventListener('click',(event) => {event.preventDefault(); history.pushState({},'',link.getAttribute('href')); window.dispatchEvent(new PopStateEvent('popstate'));}));
    }
    if (route === 'launch') bindCampaigns();
    if (route === 'messages') bindMessages();
    if (route === 'companies') {
      const intro = $('.board-box .route-intro');
      if (intro) intro.textContent = 'Agências das duas fontes mestres, com identificação de origem. Cadastro não equivale a autorização para disparo.';
      const siteLabel = $('#agencySummary .channel-stat:nth-child(2) span');
      if (siteLabel) siteLabel.textContent = 'Sites cadastrados (não demo)';
      bindProspecting();
    }
    if (route === 'milestones') {showSummary().catch(() => {}); loadPriorities('#goalsPanel'); bindMetas();}
    if (route === 'flywheel') {
      const siteLabel = $('#cycleSummary .score-card:nth-child(2) span');
      if (siteLabel) siteLabel.textContent = 'SITES CADASTRADOS';
      showSummary().catch((error) => {setText('#cycleSummary',error.message);});
    }
    if (route === 'ranking') {
      const intro = $('.board-box .route-intro');
      if (intro) intro.textContent = 'Empresas com resposta confirmada e evidência registrada no CRM. Valor contratual não substitui um retorno.';
      loadPriorities('#priorityPanel');
      loadPriorityDeals();
      bindPriorityAgencies();
    }
    if (route === 'docs') {
      const note = $$('.doc-section')[4];
      if (note) note.querySelector('p').textContent = 'Prioridades e Metas contam apenas respostas confirmadas manualmente com canal, data e evidência no histórico. Etapas antigas ou chats importados não são prova de retorno.';
    }
  }

  window.IaranduCRM = {views:{launch:campaignsView,companies:prospectingView,milestones:metasView,flywheel:cycleView,ranking:prioritiesView,messages:messagesView,docs:docsView},bind,messages:{bindMessages},dispatchLogs:{getState:()=>dispatchLogsState,load:loadDispatchLogs,renderResponse:renderDispatchLogsResponse,handleError:handleDispatchLogsError,classifyError:classifyError}};
})();
