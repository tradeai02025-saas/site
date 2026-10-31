<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0a0c0e">
    <meta name="description" content="Tecnologia sob medida para o mercado de mídia: agências de publicidade e veículos de comunicação. Adaptamos à sua operação um sistema já validado: PIs, fornecedores, documentação e veiculação em um só lugar.">
    <title>IArandú — tecnologia sob medida para agências de publicidade e veículos de comunicação</title>

    <link rel="icon" href="{{ asset('assets/') }}/img/favicons/favicon.svg" type="image/svg+xml" media="(prefers-color-scheme: light)">
    <link rel="icon" href="{{ asset('assets/') }}/img/favicons/favicon-dark.svg" type="image/svg+xml" media="(prefers-color-scheme: dark)">
    <link rel="alternate icon" href="{{ asset('assets/') }}/favicon.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Geist+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <link href="{{ asset('assets/') }}/css/pages/layouts-site.css" rel="stylesheet">

    <link href="{{ asset('assets/') }}/css/pages/landing_page.css" rel="stylesheet">
</head>
<body>
    <div class="site-shell">
        <header class="site-nav" id="siteNav">
            <div class="site-nav-inner">
                <a href="/" class="site-nav-logo" aria-label="IArandú — início">
                    <img src="{{ asset('assets/img/logo-header.png') }}" alt="IArandú">
                </a>

                <button class="site-nav-toggle" type="button" id="siteNavToggle" aria-label="Abrir menu" aria-expanded="false" aria-controls="siteNavLinks">
                    <span></span><span></span><span></span>
                </button>

                <nav class="site-nav-links" id="siteNavLinks" aria-label="Navegação principal">
                    <a href="#oq-entregamos" class="arandu-link">O que entregamos</a>
                    <a href="#como-trabalhamos" class="arandu-link">Como trabalhamos</a>
                    <a href="#planos" class="arandu-link" style="color: var(--accent); font-weight: 600;">Planos & Preços</a>
                    <a href="#equipe" class="arandu-link">Equipe</a>
                    <a href="#case" class="arandu-link">Demonstração</a>
                    <a href="#para-veiculos" class="arandu-link">Para veículos</a>
                    <span class="site-nav-divider" aria-hidden="true"></span>
                    <a href="{{ route('login') }}" class="site-nav-button">Acessar sistema →</a>
                </nav>
            </div>
        </header>

        <main>
                <section class="arandu-hero">
        <div class="arandu-hero-grid">
            <div class="arandu-hero-text reveal">
                <div class="arandu-chip" style="margin-bottom: 22px;">
                    <span class="arandu-dot arandu-pulse" style="background: var(--accent);"></span>
                    IArandú · tecnologia para agências e veículos de comunicação
                </div>
                <h1 class="arandu-h1">
                    O sistema que organiza a <span class="accent-italic">operação de mídia</span> da sua agência — do PI ao pagamento.
                </h1>
                <p>
                    Atuamos como um time de produto dedicado a agências de publicidade: partimos de um sistema já validado em operação real e o adaptamos à sua agência — Pedidos de Inserção, fornecedores, documentação e veiculação reunidos em um só lugar.
                </p>
                <div class="arandu-hero-actions">
                    <a href="https://wa.me/5541998136312" target="_blank" rel="noopener" class="arandu-cta">
                        Conversar com a equipe
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path d="M5 12h14M13 5l7 7-7 7"/>
                        </svg>
                    </a>
                    <a href="#como-trabalhamos" class="arandu-cta-ghost">Como trabalhamos</a>
                </div>
                <p style="margin-top: 16px; font-size: 14px; color: var(--text-mute);">
                    É um veículo de comunicação? <a href="#para-veiculos" class="arandu-link">Veja o que fazemos para você →</a>
                </p>
            </div>

            <div class="arandu-card arandu-blueprint-card reveal" id="aranduBlueprint" style="--reveal-delay: 120ms;">
                <div class="arandu-blueprint-grid-bg"></div>
                <div class="arandu-blueprint-content">
                    <div class="arandu-blueprint-label">
                        <span>Arquitetura · case Trade Marketing</span>
                        <span class="bp-hint">clique para explorar</span>
                    </div>
                    <div class="bp-modules" role="tablist" aria-label="Módulos do sistema">
                                                    <button type="button" class="bp-node accent" role="tab"
                                    data-bp="0" data-label="PIs" data-desc="Cada Pedido de Inserção nasce, é validado e concluído aqui — com envio em massa e histórico de cada ação." aria-selected="false">
                                <span class="arandu-dot"></span>
                                PIs
                            </button>
                                                    <button type="button" class="bp-node " role="tab"
                                    data-bp="1" data-label="Fornecedores" data-desc="Cadastro, dados bancários e documentos dos veículos, reaproveitados automaticamente entre PIs." aria-selected="false">
                                <span class="arandu-dot"></span>
                                Fornecedores
                            </button>
                                                    <button type="button" class="bp-node " role="tab"
                                    data-bp="2" data-label="Representantes" data-desc="Quem responde por vários fornecedores, com vínculo aprovado pela agência antes de operar." aria-selected="false">
                                <span class="arandu-dot"></span>
                                Representantes
                            </button>
                                                    <button type="button" class="bp-node " role="tab"
                                    data-bp="3" data-label="Documentos" data-desc="Recebidos, validados pelo operacional e com alerta automático antes de cada vencimento." aria-selected="false">
                                <span class="arandu-dot"></span>
                                Documentos
                            </button>
                                                    <button type="button" class="bp-node " role="tab"
                                    data-bp="4" data-label="Chat por PI" data-desc="Conversa vinculada a cada PI, com escalação entre operacional, gerencial e administração." aria-selected="false">
                                <span class="arandu-dot"></span>
                                Chat por PI
                            </button>
                                                    <button type="button" class="bp-node " role="tab"
                                    data-bp="5" data-label="Monitoramento" data-desc="A equipe técnica acompanha saúde do sistema, erros, disparo de e-mails e capacidade do servidor." aria-selected="false">
                                <span class="arandu-dot"></span>
                                Monitoramento
                            </button>
                                            </div>
                    <div class="bp-stem"></div>
                    <div class="bp-hub" id="aranduBlueprintHub">
                        <span class="bp-hub-title"><span class="arandu-dot" style="background: var(--accent);"></span> <span id="bpTitle">Núcleo da operação</span></span>
                        <p class="bp-hub-desc" id="bpDesc">Passe o mouse ou toque em um módulo para ver o que ele faz.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="arandu-pains">
        <div class="arandu-section-header reveal">
            <div class="arandu-eyebrow" style="margin-bottom: 16px;">Quando faz sentido conversar</div>
            <h2 class="arandu-h2" style="max-width: 820px;">Três sinais de que a sua agência já passou do ponto da planilha.</h2>
        </div>
        <div class="arandu-pains-grid">
                            <div class="arandu-card arandu-pain-card reveal" style="--reveal-delay: 0ms;">
                    <div class="arandu-pain-label">✕ Planilha</div>
                    <h3 class="arandu-h3">A operação de mídia não cabe mais na planilha</h3>
                    <p>Cada PI vira uma aba, cada veículo um arquivo, cada documento um anexo perdido no e-mail. Quando o volume de inserções cresce, a planilha deixa de organizar e passa a ser risco.</p>
                </div>
                            <div class="arandu-card arandu-pain-card reveal" style="--reveal-delay: 80ms;">
                    <div class="arandu-pain-label">✕ Sistema genérico</div>
                    <h3 class="arandu-h3">ERP de prateleira não entende uma agência</h3>
                    <p>Sistemas genéricos não conhecem Pedido de Inserção, veiculação, checagem de documento de fornecedor nem comissão. Você molda a agência ao software, quando deveria ser o contrário.</p>
                </div>
                            <div class="arandu-card arandu-pain-card reveal" style="--reveal-delay: 160ms;">
                    <div class="arandu-pain-label">✕ Dado disperso</div>
                    <h3 class="arandu-h3">O status de cada PI mora em cinco lugares</h3>
                    <p>E-mail com o veículo, WhatsApp com o fornecedor, planilha de controle, drive de documentos. Quando o cliente cobra uma posição, ninguém tem a resposta na hora.</p>
                </div>
                    </div>
    </section>

    <section class="arandu-case" id="oq-entregamos">
        <div class="arandu-case-header reveal">
            <div>
                <div class="arandu-eyebrow" style="margin-bottom: 16px;">Case · agência de mídia</div>
                <h2 class="arandu-h2" style="max-width: 920px;">O sistema que construímos para a Trade Marketing.</h2>
            </div>
            <span class="arandu-mono arandu-case-tag">2025 · Curitiba/PR</span>
        </div>

        <p class="arandu-case-intro reveal">
            Centralizamos toda a operação de mídia da Trade Marketing — Pedidos de Inserção, fornecedores, representantes, documentos com controle de vencimento e atendimento por PI — em um sistema único. É essa mesma base, validada no dia a dia, que adaptamos para a sua agência. Explore as telas abaixo:
        </p>

        <div class="arandu-card arandu-window reveal" id="aranduWindow" style="scroll-margin-top: 96px;">
            <div class="arandu-window-bar">
                <div class="arandu-window-dots">
                    <span class="arandu-dot"></span>
                    <span class="arandu-dot"></span>
                    <span class="arandu-dot"></span>
                </div>
                <span class="arandu-window-url" id="awUrl">app.trade · pedidos de inserção</span>
            </div>

            <div class="aw-tabbar" role="tablist" aria-label="Navegação da demonstração">
                                    <button type="button" class="aw-tab " data-view-target="inicio">Início</button>
                                    <button type="button" class="aw-tab " data-view-target="mensagens">Mensagens</button>
                                    <button type="button" class="aw-tab is-active" data-view-target="pis">Gerenciar PIs</button>
                                    <button type="button" class="aw-tab " data-view-target="reatribuir">Reatribuir PIs</button>
                                    <button type="button" class="aw-tab " data-view-target="clientes">Clientes</button>
                                    <button type="button" class="aw-tab " data-view-target="fornecedores">Fornecedores</button>
                                    <button type="button" class="aw-tab " data-view-target="representantes">Representantes</button>
                                    <button type="button" class="aw-tab " data-view-target="tipos">Tipos de Documentos</button>
                                    <button type="button" class="aw-tab " data-view-target="usuarios">Usuários</button>
                                    <button type="button" class="aw-tab " data-view-target="status">Status</button>
                            </div>

            <div class="arandu-window-grid">
                <aside class="arandu-sidebar">
                                            <button type="button" class="arandu-sidebar-item " data-view-target="inicio">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M3 12l9-9 9 9M5 10v10h14V10"/>
                            </svg>
                            <span class="arandu-sidebar-item-label">Início</span>
                                                                                </button>
                                            <button type="button" class="arandu-sidebar-item " data-view-target="mensagens">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                            </svg>
                            <span class="arandu-sidebar-item-label">Mensagens</span>
                                                                                        <span class="arandu-sidebar-badge">3</span>
                                                    </button>
                                            <button type="button" class="arandu-sidebar-item active" data-view-target="pis">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <span class="arandu-sidebar-item-label">Gerenciar PIs</span>
                                                            <span class="arandu-sidebar-count">127</span>
                                                                                </button>
                                            <button type="button" class="arandu-sidebar-item " data-view-target="reatribuir">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M8 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                            <span class="arandu-sidebar-item-label">Reatribuir PIs</span>
                                                                                </button>
                                            <button type="button" class="arandu-sidebar-item " data-view-target="clientes">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-3M9 9h.01M9 13h.01M9 17h.01"/>
                            </svg>
                            <span class="arandu-sidebar-item-label">Clientes</span>
                                                            <span class="arandu-sidebar-count">14</span>
                                                                                </button>
                                            <button type="button" class="arandu-sidebar-item " data-view-target="fornecedores">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M20 7h-3V4a1 1 0 00-1-1H8a1 1 0 00-1 1v3H4a1 1 0 00-1 1v12a1 1 0 001 1h16a1 1 0 001-1V8a1 1 0 00-1-1z"/>
                            </svg>
                            <span class="arandu-sidebar-item-label">Fornecedores</span>
                                                            <span class="arandu-sidebar-count">38</span>
                                                                                </button>
                                            <button type="button" class="arandu-sidebar-item " data-view-target="representantes">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75M9 11a4 4 0 100-8 4 4 0 000 8z"/>
                            </svg>
                            <span class="arandu-sidebar-item-label">Representantes</span>
                                                                                        <span class="arandu-sidebar-badge">2</span>
                                                    </button>
                                            <button type="button" class="arandu-sidebar-item " data-view-target="tipos">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M9 12h6M9 16h6M9 8h6M5 4h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5a1 1 0 011-1z"/>
                            </svg>
                            <span class="arandu-sidebar-item-label">Tipos de Documentos</span>
                                                                                </button>
                                            <button type="button" class="arandu-sidebar-item " data-view-target="usuarios">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M17 21v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2M13 7a4 4 0 11-8 0 4 4 0 018 0zM21 21v-2a4 4 0 00-3-3.87"/>
                            </svg>
                            <span class="arandu-sidebar-item-label">Usuários</span>
                                                                                </button>
                                            <button type="button" class="arandu-sidebar-item " data-view-target="status">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7"/>
                            </svg>
                            <span class="arandu-sidebar-item-label">Status</span>
                                                                                </button>
                                    </aside>

                <div class="arandu-main">
                    
                    <div class="aw-view" data-view="pis">
                        <div class="arandu-main-header">
                            <div>
                                <h3 class="arandu-h3">Gerenciar PIs</h3>
                                <p class="arandu-main-subtitle">Gestão completa de Pedidos de Inserção</p>
                            </div>
                            <div class="arandu-main-actions">
                                <button type="button" class="arandu-button-ghost">Reatribuir PIs</button>
                                <button type="button" class="arandu-button-primary">+ Registrar PI</button>
                            </div>
                        </div>

                        <div class="arandu-filters">
                                                            <button type="button" class="arandu-filter active" data-filter="todos">
                                    Todos <span class="arandu-filter-count">· 127</span>
                                </button>
                                                            <button type="button" class="arandu-filter " data-filter="andamento">
                                    Em andamento <span class="arandu-filter-count">· 46</span>
                                </button>
                                                            <button type="button" class="arandu-filter " data-filter="concluidos">
                                    Concluídos <span class="arandu-filter-count">· 81</span>
                                </button>
                                                    </div>

                        <div class="arandu-table">
                            <div class="arandu-table-header">
                                <span>NÚMERO PI</span><span>CLIENTE</span><span>FORNECEDOR</span><span>OPERACIONAL</span><span>STATUS</span><span>DATA</span>
                            </div>
                                                            <div class="arandu-table-row" data-pi="#2841" data-group="concluidos">
                                    <span class="col-number">#2841</span>
                                    <span class="col-client">Boticário</span>
                                    <span class="col-forn">Folha de Londrina</span>
                                    <span class="col-op">Mariana B.</span>
                                    <span class="col-status">
                                        <span class="arandu-dot" style="background: var(--accent);"></span>
                                        <span style="color: var(--accent);">Pago</span>
                                    </span>
                                    <span class="col-data">02/05/26</span>
                                </div>
                                                            <div class="arandu-table-row" data-pi="#2840" data-group="concluidos">
                                    <span class="col-number">#2840</span>
                                    <span class="col-client">Copel</span>
                                    <span class="col-forn">Band Curitiba</span>
                                    <span class="col-op">Carlos R.</span>
                                    <span class="col-status">
                                        <span class="arandu-dot" style="background: var(--ok);"></span>
                                        <span style="color: var(--ok);">Concluído</span>
                                    </span>
                                    <span class="col-data">28/04/26</span>
                                </div>
                                                            <div class="arandu-table-row" data-pi="#2839" data-group="andamento">
                                    <span class="col-number">#2839</span>
                                    <span class="col-client">Sanepar</span>
                                    <span class="col-forn">Gazeta do Povo</span>
                                    <span class="col-op">Mariana B.</span>
                                    <span class="col-status">
                                        <span class="arandu-dot" style="background: var(--warn);"></span>
                                        <span style="color: var(--warn);">Em Processo</span>
                                    </span>
                                    <span class="col-data">12/05/26</span>
                                </div>
                                                            <div class="arandu-table-row" data-pi="#2838" data-group="andamento">
                                    <span class="col-number">#2838</span>
                                    <span class="col-client">Coca-Cola FEMSA</span>
                                    <span class="col-forn">CBN Curitiba</span>
                                    <span class="col-op">Pedro L.</span>
                                    <span class="col-status">
                                        <span class="arandu-dot" style="background: var(--warn);"></span>
                                        <span style="color: var(--warn);">Aguardando Documentos</span>
                                    </span>
                                    <span class="col-data">15/05/26</span>
                                </div>
                                                            <div class="arandu-table-row" data-pi="#2837" data-group="andamento">
                                    <span class="col-number">#2837</span>
                                    <span class="col-client">Renault</span>
                                    <span class="col-forn">RPC Paraná</span>
                                    <span class="col-op">Carlos R.</span>
                                    <span class="col-status">
                                        <span class="arandu-dot" style="background: var(--danger);"></span>
                                        <span style="color: var(--danger);">Reenvio de Documentos</span>
                                    </span>
                                    <span class="col-data">18/05/26</span>
                                </div>
                                                            <div class="arandu-table-row" data-pi="#2836" data-group="andamento">
                                    <span class="col-number">#2836</span>
                                    <span class="col-client">Hospital Vita</span>
                                    <span class="col-forn">Massa FM</span>
                                    <span class="col-op">Mariana B.</span>
                                    <span class="col-status">
                                        <span class="arandu-dot" style="background: var(--text-mute);"></span>
                                        <span style="color: var(--text-mute);">Gerado</span>
                                    </span>
                                    <span class="col-data">22/05/26</span>
                                </div>
                                                            <div class="arandu-table-row" data-pi="#2835" data-group="concluidos">
                                    <span class="col-number">#2835</span>
                                    <span class="col-client">Boticário</span>
                                    <span class="col-forn">Spotify</span>
                                    <span class="col-op">Pedro L.</span>
                                    <span class="col-status">
                                        <span class="arandu-dot" style="background: var(--ok);"></span>
                                        <span style="color: var(--ok);">Concluído</span>
                                    </span>
                                    <span class="col-data">20/04/26</span>
                                </div>
                                                    </div>

                        <div class="arandu-cards">
                                                            <div class="arandu-pi-card" data-pi="#2841" data-group="concluidos">
                                    <div class="arandu-pi-card-top">
                                        <div>
                                            <div class="arandu-pi-card-num">#2841</div>
                                            <div class="arandu-pi-card-client">Boticário</div>
                                        </div>
                                        <span class="arandu-pi-card-status" style="color: var(--accent);">
                                            <span class="arandu-dot" style="background: var(--accent);"></span>
                                            Pago
                                        </span>
                                    </div>
                                    <div class="arandu-pi-card-meta">
                                        <div><div class="k">Fornecedor</div><div class="v">Folha de Londrina</div></div>
                                        <div><div class="k">Operacional</div><div class="v">Mariana B.</div></div>
                                        <div><div class="k">Data</div><div class="v">02/05/26</div></div>
                                    </div>
                                </div>
                                                            <div class="arandu-pi-card" data-pi="#2840" data-group="concluidos">
                                    <div class="arandu-pi-card-top">
                                        <div>
                                            <div class="arandu-pi-card-num">#2840</div>
                                            <div class="arandu-pi-card-client">Copel</div>
                                        </div>
                                        <span class="arandu-pi-card-status" style="color: var(--ok);">
                                            <span class="arandu-dot" style="background: var(--ok);"></span>
                                            Concluído
                                        </span>
                                    </div>
                                    <div class="arandu-pi-card-meta">
                                        <div><div class="k">Fornecedor</div><div class="v">Band Curitiba</div></div>
                                        <div><div class="k">Operacional</div><div class="v">Carlos R.</div></div>
                                        <div><div class="k">Data</div><div class="v">28/04/26</div></div>
                                    </div>
                                </div>
                                                            <div class="arandu-pi-card" data-pi="#2839" data-group="andamento">
                                    <div class="arandu-pi-card-top">
                                        <div>
                                            <div class="arandu-pi-card-num">#2839</div>
                                            <div class="arandu-pi-card-client">Sanepar</div>
                                        </div>
                                        <span class="arandu-pi-card-status" style="color: var(--warn);">
                                            <span class="arandu-dot" style="background: var(--warn);"></span>
                                            Em Processo
                                        </span>
                                    </div>
                                    <div class="arandu-pi-card-meta">
                                        <div><div class="k">Fornecedor</div><div class="v">Gazeta do Povo</div></div>
                                        <div><div class="k">Operacional</div><div class="v">Mariana B.</div></div>
                                        <div><div class="k">Data</div><div class="v">12/05/26</div></div>
                                    </div>
                                </div>
                                                            <div class="arandu-pi-card" data-pi="#2838" data-group="andamento">
                                    <div class="arandu-pi-card-top">
                                        <div>
                                            <div class="arandu-pi-card-num">#2838</div>
                                            <div class="arandu-pi-card-client">Coca-Cola FEMSA</div>
                                        </div>
                                        <span class="arandu-pi-card-status" style="color: var(--warn);">
                                            <span class="arandu-dot" style="background: var(--warn);"></span>
                                            Aguardando Documentos
                                        </span>
                                    </div>
                                    <div class="arandu-pi-card-meta">
                                        <div><div class="k">Fornecedor</div><div class="v">CBN Curitiba</div></div>
                                        <div><div class="k">Operacional</div><div class="v">Pedro L.</div></div>
                                        <div><div class="k">Data</div><div class="v">15/05/26</div></div>
                                    </div>
                                </div>
                                                            <div class="arandu-pi-card" data-pi="#2837" data-group="andamento">
                                    <div class="arandu-pi-card-top">
                                        <div>
                                            <div class="arandu-pi-card-num">#2837</div>
                                            <div class="arandu-pi-card-client">Renault</div>
                                        </div>
                                        <span class="arandu-pi-card-status" style="color: var(--danger);">
                                            <span class="arandu-dot" style="background: var(--danger);"></span>
                                            Reenvio de Documentos
                                        </span>
                                    </div>
                                    <div class="arandu-pi-card-meta">
                                        <div><div class="k">Fornecedor</div><div class="v">RPC Paraná</div></div>
                                        <div><div class="k">Operacional</div><div class="v">Carlos R.</div></div>
                                        <div><div class="k">Data</div><div class="v">18/05/26</div></div>
                                    </div>
                                </div>
                                                            <div class="arandu-pi-card" data-pi="#2836" data-group="andamento">
                                    <div class="arandu-pi-card-top">
                                        <div>
                                            <div class="arandu-pi-card-num">#2836</div>
                                            <div class="arandu-pi-card-client">Hospital Vita</div>
                                        </div>
                                        <span class="arandu-pi-card-status" style="color: var(--text-mute);">
                                            <span class="arandu-dot" style="background: var(--text-mute);"></span>
                                            Gerado
                                        </span>
                                    </div>
                                    <div class="arandu-pi-card-meta">
                                        <div><div class="k">Fornecedor</div><div class="v">Massa FM</div></div>
                                        <div><div class="k">Operacional</div><div class="v">Mariana B.</div></div>
                                        <div><div class="k">Data</div><div class="v">22/05/26</div></div>
                                    </div>
                                </div>
                                                            <div class="arandu-pi-card" data-pi="#2835" data-group="concluidos">
                                    <div class="arandu-pi-card-top">
                                        <div>
                                            <div class="arandu-pi-card-num">#2835</div>
                                            <div class="arandu-pi-card-client">Boticário</div>
                                        </div>
                                        <span class="arandu-pi-card-status" style="color: var(--ok);">
                                            <span class="arandu-dot" style="background: var(--ok);"></span>
                                            Concluído
                                        </span>
                                    </div>
                                    <div class="arandu-pi-card-meta">
                                        <div><div class="k">Fornecedor</div><div class="v">Spotify</div></div>
                                        <div><div class="k">Operacional</div><div class="v">Pedro L.</div></div>
                                        <div><div class="k">Data</div><div class="v">20/04/26</div></div>
                                    </div>
                                </div>
                                                    </div>

                        
                        <div class="aw-detail" id="awDetail">
                            <div class="aw-detail-empty" data-detail="">Clique em uma PI acima para ver e validar a checagem de documentos.</div>
                                                                                            <div class="aw-detail-pi" data-detail="#2841" hidden>
                                    <div class="aw-detail-head">
                                        <span class="num">PI #2841</span>
                                        <span>· Boticário — Folha de Londrina</span>
                                        <button type="button" class="aw-detail-close" aria-label="Fechar detalhe">×</button>
                                    </div>
                                    <div class="aw-doc-list">
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Contrato social
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Cartão CNPJ
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Certidão negativa
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Dados bancários
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Tabela de preços
                                                                                            </span>
                                                                            </div>
                                                                    </div>
                                                                                            <div class="aw-detail-pi" data-detail="#2840" hidden>
                                    <div class="aw-detail-head">
                                        <span class="num">PI #2840</span>
                                        <span>· Copel — Band Curitiba</span>
                                        <button type="button" class="aw-detail-close" aria-label="Fechar detalhe">×</button>
                                    </div>
                                    <div class="aw-doc-list">
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Contrato social
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Cartão CNPJ
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Procuração
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Certidão negativa
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Dados bancários
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Tabela de preços
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Alvará
                                                                                            </span>
                                                                            </div>
                                                                    </div>
                                                                                            <div class="aw-detail-pi" data-detail="#2839" hidden>
                                    <div class="aw-detail-head">
                                        <span class="num">PI #2839</span>
                                        <span>· Sanepar — Gazeta do Povo</span>
                                        <button type="button" class="aw-detail-close" aria-label="Fechar detalhe">×</button>
                                    </div>
                                    <div class="aw-doc-list">
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Contrato social
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Cartão CNPJ
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--wait">
                                                Certidão negativa
                                                                                                    <span class="aw-doc-actions">
                                                        <button type="button" class="aw-doc-act aw-doc-approve" title="Aprovar" aria-label="Aprovar documento">✓</button>
                                                        <button type="button" class="aw-doc-act aw-doc-reject" title="Reprovar" aria-label="Reprovar documento">✕</button>
                                                    </span>
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Dados bancários
                                                                                            </span>
                                                                            </div>
                                                                            <div class="aw-detail-hint">1 aguardando validação — clique em ✓ / ✕ para validar (demonstração).</div>
                                                                    </div>
                                                                                            <div class="aw-detail-pi" data-detail="#2838" hidden>
                                    <div class="aw-detail-head">
                                        <span class="num">PI #2838</span>
                                        <span>· Coca-Cola FEMSA — CBN Curitiba</span>
                                        <button type="button" class="aw-detail-close" aria-label="Fechar detalhe">×</button>
                                    </div>
                                    <div class="aw-doc-list">
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Contrato social
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Cartão CNPJ
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Certidão negativa
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--wait">
                                                Dados bancários
                                                                                                    <span class="aw-doc-actions">
                                                        <button type="button" class="aw-doc-act aw-doc-approve" title="Aprovar" aria-label="Aprovar documento">✓</button>
                                                        <button type="button" class="aw-doc-act aw-doc-reject" title="Reprovar" aria-label="Reprovar documento">✕</button>
                                                    </span>
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--none">
                                                Tabela de preços
                                                                                            </span>
                                                                            </div>
                                                                            <div class="aw-detail-hint">1 aguardando validação — clique em ✓ / ✕ para validar (demonstração).</div>
                                                                    </div>
                                                                                            <div class="aw-detail-pi" data-detail="#2837" hidden>
                                    <div class="aw-detail-head">
                                        <span class="num">PI #2837</span>
                                        <span>· Renault — RPC Paraná</span>
                                        <button type="button" class="aw-detail-close" aria-label="Fechar detalhe">×</button>
                                    </div>
                                    <div class="aw-doc-list">
                                                                                    <span class="aw-doc aw-doc--bad">
                                                Contrato social
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Cartão CNPJ
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--none">
                                                Certidão negativa
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--none">
                                                Dados bancários
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--none">
                                                Tabela de preços
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--none">
                                                Alvará
                                                                                            </span>
                                                                            </div>
                                                                    </div>
                                                                                            <div class="aw-detail-pi" data-detail="#2836" hidden>
                                    <div class="aw-detail-head">
                                        <span class="num">PI #2836</span>
                                        <span>· Hospital Vita — Massa FM</span>
                                        <button type="button" class="aw-detail-close" aria-label="Fechar detalhe">×</button>
                                    </div>
                                    <div class="aw-doc-list">
                                                                                    <span class="aw-doc aw-doc--none">
                                                Contrato social
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--none">
                                                Cartão CNPJ
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--none">
                                                Certidão negativa
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--none">
                                                Dados bancários
                                                                                            </span>
                                                                            </div>
                                                                    </div>
                                                                                            <div class="aw-detail-pi" data-detail="#2835" hidden>
                                    <div class="aw-detail-head">
                                        <span class="num">PI #2835</span>
                                        <span>· Boticário — Spotify</span>
                                        <button type="button" class="aw-detail-close" aria-label="Fechar detalhe">×</button>
                                    </div>
                                    <div class="aw-doc-list">
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Contrato social
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Cartão CNPJ
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Certidão negativa
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Dados bancários
                                                                                            </span>
                                                                                    <span class="aw-doc aw-doc--ok">
                                                Tabela de preços
                                                                                            </span>
                                                                            </div>
                                                                    </div>
                                                    </div>
                    </div>

                    
                    <div class="aw-view" data-view="inicio" hidden>
                        <div class="aw-view-head"><h3 class="arandu-h3">Início</h3><p>Visão geral da operação</p></div>
                        <div class="aw-stats">
                                                            <div class="aw-stat">
                                    <div class="arandu-mono aw-stat-v">24</div>
                                    <div class="aw-stat-l">Usuários</div>
                                    <div class="aw-stat-sub">2 inativos</div>
                                </div>
                                                            <div class="aw-stat">
                                    <div class="arandu-mono aw-stat-v">14</div>
                                    <div class="aw-stat-l">Clientes</div>
                                    <div class="aw-stat-sub">13 ativos</div>
                                </div>
                                                            <div class="aw-stat">
                                    <div class="arandu-mono aw-stat-v">38</div>
                                    <div class="aw-stat-l">Fornecedores</div>
                                    <div class="aw-stat-sub">36 ativos</div>
                                </div>
                                                            <div class="aw-stat">
                                    <div class="arandu-mono aw-stat-v">127</div>
                                    <div class="aw-stat-l">PIs totais</div>
                                    <div class="aw-stat-sub">46 em andamento</div>
                                </div>
                                                    </div>
                        <div class="aw-home-cols">
                            <div class="aw-home-section">
                                <h4>PIs recentes</h4>
                                <div class="aw-list">
                                                                            <div class="aw-list-row">
                                            <div class="aw-list-main">
                                                <span class="aw-list-title">#2841 · Boticário</span>
                                                <span class="aw-list-sub">Folha de Londrina · há 2h</span>
                                            </div>
                                            <span class="aw-pill" style="color: var(--accent); border-color: currentColor;">Pago</span>
                                        </div>
                                                                            <div class="aw-list-row">
                                            <div class="aw-list-main">
                                                <span class="aw-list-title">#2840 · Copel</span>
                                                <span class="aw-list-sub">Band Curitiba · há 5h</span>
                                            </div>
                                            <span class="aw-pill" style="color: var(--ok); border-color: currentColor;">Concluído</span>
                                        </div>
                                                                            <div class="aw-list-row">
                                            <div class="aw-list-main">
                                                <span class="aw-list-title">#2839 · Sanepar</span>
                                                <span class="aw-list-sub">Gazeta do Povo · ontem</span>
                                            </div>
                                            <span class="aw-pill" style="color: var(--warn); border-color: currentColor;">Em Processo</span>
                                        </div>
                                                                    </div>
                            </div>
                            <div class="aw-home-section">
                                <h4>Documentos vencendo</h4>
                                <div class="aw-list">
                                                                            <div class="aw-list-row">
                                            <div class="aw-list-main">
                                                <span class="aw-list-title">Certidão negativa (FGTS)</span>
                                                <span class="aw-list-sub">RPC Paraná</span>
                                            </div>
                                            <span class="aw-pill aw-pill--incompleto">vence em 3 dias</span>
                                        </div>
                                                                            <div class="aw-list-row">
                                            <div class="aw-list-main">
                                                <span class="aw-list-title">Certidão negativa (INSS)</span>
                                                <span class="aw-list-sub">CBN Curitiba</span>
                                            </div>
                                            <span class="aw-pill aw-pill--incompleto">vence em 6 dias</span>
                                        </div>
                                                                            <div class="aw-list-row">
                                            <div class="aw-list-main">
                                                <span class="aw-list-title">Contrato social</span>
                                                <span class="aw-list-sub">Massa FM</span>
                                            </div>
                                            <span class="aw-pill aw-pill--incompleto">vence em 9 dias</span>
                                        </div>
                                                                    </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="aw-view" data-view="mensagens" hidden>
                        <div class="aw-view-head"><h3 class="arandu-h3">Mensagens</h3><p>Conversas por PI com os fornecedores</p></div>
                        <div class="aw-list">
                                                            <div class="aw-list-row">
                                    <div class="aw-msg-avatar">F</div>
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Folha de Londrina<span class="aw-msg-pi">#2839</span></span>
                                        <span class="aw-list-sub">Reenviamos a certidão negativa atualizada, podem validar?</span>
                                    </div>
                                    <span class="aw-pill aw-pill--ativo">Aberta</span>
                                                                            <span class="aw-msg-badge">2</span>
                                                                    </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-msg-avatar">R</div>
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">RPC Paraná<span class="aw-msg-pi">#2837</span></span>
                                        <span class="aw-list-sub">O contrato social foi reprovado — qual ajuste é necessário?</span>
                                    </div>
                                    <span class="aw-pill aw-pill--incompleto">Escalada</span>
                                                                            <span class="aw-msg-badge">1</span>
                                                                    </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-msg-avatar">C</div>
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">CBN Curitiba<span class="aw-msg-pi">#2838</span></span>
                                        <span class="aw-list-sub">Dúvida sobre os dados bancários informados no PI.</span>
                                    </div>
                                    <span class="aw-pill aw-pill--geral">Resolvida</span>
                                                                    </div>
                                                    </div>
                    </div>

                    
                    <div class="aw-view" data-view="clientes" hidden>
                        <div class="aw-view-head"><h3 class="arandu-h3">Clientes</h3><p>Cadastro e gestão de clientes</p></div>
                        <div class="aw-list">
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Boticário</span>
                                        <span class="aw-list-sub">12.345.678/0001-90 · Curitiba/PR · Mariana B.</span>
                                    </div>
                                    <span class="aw-pill aw-pill--ativo">Ativo</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Copel</span>
                                        <span class="aw-list-sub">04.368.898/0001-06 · Curitiba/PR · Carlos R.</span>
                                    </div>
                                    <span class="aw-pill aw-pill--ativo">Ativo</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Sanepar</span>
                                        <span class="aw-list-sub">76.484.013/0001-45 · Curitiba/PR · Mariana B.</span>
                                    </div>
                                    <span class="aw-pill aw-pill--ativo">Ativo</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Coca-Cola FEMSA</span>
                                        <span class="aw-list-sub">45.997.418/0001-53 · São Paulo/SP · Pedro L.</span>
                                    </div>
                                    <span class="aw-pill aw-pill--ativo">Ativo</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Renault</span>
                                        <span class="aw-list-sub">00.913.443/0001-73 · S. J. dos Pinhais/PR · Carlos R.</span>
                                    </div>
                                    <span class="aw-pill aw-pill--inativo">Inativo</span>
                                </div>
                                                    </div>
                    </div>

                    
                    <div class="aw-view" data-view="fornecedores" hidden>
                        <div class="aw-view-head"><h3 class="arandu-h3">Fornecedores</h3><p>Veículos e parceiros comerciais</p></div>
                        <div class="aw-list">
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Folha de Londrina</span>
                                        <span class="aw-list-sub">Jornal</span>
                                    </div>
                                    <span class="aw-list-tag ok">5/5 docs</span>
                                    <span class="aw-pill aw-pill--ativo">Ativo</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Band Curitiba</span>
                                        <span class="aw-list-sub">TV</span>
                                    </div>
                                    <span class="aw-list-tag ok">7/7 docs</span>
                                    <span class="aw-pill aw-pill--ativo">Ativo</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Gazeta do Povo</span>
                                        <span class="aw-list-sub">Jornal / Web</span>
                                    </div>
                                    <span class="aw-list-tag wait">3/4 docs</span>
                                    <span class="aw-pill aw-pill--ativo">Ativo</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">CBN Curitiba</span>
                                        <span class="aw-list-sub">Rádio</span>
                                    </div>
                                    <span class="aw-list-tag wait">4/5 docs</span>
                                    <span class="aw-pill aw-pill--incompleto">Incompleto</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">RPC Paraná</span>
                                        <span class="aw-list-sub">TV</span>
                                    </div>
                                    <span class="aw-list-tag wait">2/6 docs</span>
                                    <span class="aw-pill aw-pill--ativo">Ativo</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Spotify</span>
                                        <span class="aw-list-sub">Áudio digital</span>
                                    </div>
                                    <span class="aw-list-tag ok">5/5 docs</span>
                                    <span class="aw-pill aw-pill--ativo">Ativo</span>
                                </div>
                                                    </div>
                    </div>

                    
                    <div class="aw-view" data-view="representantes" hidden>
                        <div class="aw-view-head"><h3 class="arandu-h3">Representantes</h3><p>Representantes e vínculos com fornecedores</p></div>
                        <div class="aw-list">
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Ana Paula Reis</span>
                                        <span class="aw-list-sub">AP Mídia · 3 fornecedores</span>
                                    </div>
                                                                        <span class="aw-pill aw-pill--ativo">Ativo</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Marcos Lima</span>
                                        <span class="aw-list-sub">ML Representações · 5 fornecedores</span>
                                    </div>
                                                                            <span class="aw-pill aw-pill--incompleto">1 pendente(s)</span>
                                                                        <span class="aw-pill aw-pill--ativo">Ativo</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Júlia Souza</span>
                                        <span class="aw-list-sub">JS Comunicação · 0 fornecedores</span>
                                    </div>
                                                                        <span class="aw-pill aw-pill--incompleto">Sem vínculos</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Rafael Dias</span>
                                        <span class="aw-list-sub">RD Veículos · 2 fornecedores</span>
                                    </div>
                                                                            <span class="aw-pill aw-pill--incompleto">1 pendente(s)</span>
                                                                        <span class="aw-pill aw-pill--ativo">Ativo</span>
                                </div>
                                                    </div>
                    </div>

                    
                    <div class="aw-view" data-view="reatribuir" hidden>
                        <div class="aw-view-head"><h3 class="arandu-h3">Reatribuir PIs</h3><p>Transfira PIs entre operacionais em massa</p></div>
                        <div class="aw-reassign-row">
                            <div class="aw-field">
                                <label>De (operacional)</label>
                                <select class="aw-select"><option>Mariana B.</option><option>Carlos R.</option><option>Pedro L.</option></select>
                            </div>
                            <div class="aw-field">
                                <label>Para (operacional)</label>
                                <select class="aw-select"><option>Carlos R.</option><option>Mariana B.</option><option>Pedro L.</option></select>
                            </div>
                        </div>
                        <div class="aw-list">
                            <label class="aw-check-row"><input type="checkbox" checked> #2839 · Sanepar — Gazeta do Povo</label>
                            <label class="aw-check-row"><input type="checkbox" checked> #2836 · Hospital Vita — Massa FM</label>
                            <label class="aw-check-row"><input type="checkbox"> #2841 · Boticário — Folha de Londrina</label>
                        </div>
                        <div class="aw-reassign-foot"><button type="button" class="arandu-button-primary">Reatribuir selecionados</button></div>
                    </div>

                    
                    <div class="aw-view" data-view="tipos" hidden>
                        <div class="aw-view-head"><h3 class="arandu-h3">Tipos de Documentos</h3><p>Documentos aceitos no sistema</p></div>
                        <div class="aw-list">
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Contrato social</span>
                                        <span class="aw-list-sub">Validade: Indeterminada · Geral</span>
                                    </div>
                                    <span class="aw-pill aw-pill--ativo">Com controle</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Cartão CNPJ</span>
                                        <span class="aw-list-sub">Validade: 90 dias · Geral</span>
                                    </div>
                                    <span class="aw-pill aw-pill--ativo">Com controle</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Certidão negativa (FGTS)</span>
                                        <span class="aw-list-sub">Validade: 30 dias · Geral</span>
                                    </div>
                                    <span class="aw-pill aw-pill--ativo">Com controle</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Tabela de preços</span>
                                        <span class="aw-list-sub">Validade: Indeterminada · Geral</span>
                                    </div>
                                    <span class="aw-pill aw-pill--geral">Uso único</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Mapa de cobertura</span>
                                        <span class="aw-list-sub">Validade: Indeterminada · Rádio/TV</span>
                                    </div>
                                    <span class="aw-pill aw-pill--geral">Uso único</span>
                                </div>
                                                    </div>
                    </div>

                    
                    <div class="aw-view" data-view="usuarios" hidden>
                        <div class="aw-view-head"><h3 class="arandu-h3">Usuários</h3><p>Usuários e permissões do sistema</p></div>
                        <div class="aw-list">
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Mariana Borges</span>
                                        <span class="aw-list-sub">mariana@trademkt.com.br</span>
                                    </div>
                                    <span class="aw-pill aw-pill--operacional">Operacional</span>
                                    <span class="aw-pill aw-pill--ativo">Ativo</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Carlos Ribeiro</span>
                                        <span class="aw-list-sub">carlos@trademkt.com.br</span>
                                    </div>
                                    <span class="aw-pill aw-pill--operacional">Operacional</span>
                                    <span class="aw-pill aw-pill--ativo">Ativo</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Patrícia Gomes</span>
                                        <span class="aw-list-sub">patricia@trademkt.com.br</span>
                                    </div>
                                    <span class="aw-pill aw-pill--gerencial">Gerencial</span>
                                    <span class="aw-pill aw-pill--ativo">Ativo</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Administração</span>
                                        <span class="aw-list-sub">admin@trademkt.com.br</span>
                                    </div>
                                    <span class="aw-pill aw-pill--admin">Admin</span>
                                    <span class="aw-pill aw-pill--ativo">Ativo</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Folha de Londrina</span>
                                        <span class="aw-list-sub">contato@folhadelondrina.com.br</span>
                                    </div>
                                    <span class="aw-pill aw-pill--fornecedor">Fornecedor</span>
                                    <span class="aw-pill aw-pill--ativo">Ativo</span>
                                </div>
                                                    </div>
                    </div>

                    
                    <div class="aw-view" data-view="status" hidden>
                        <div class="aw-view-head"><h3 class="arandu-h3">Status</h3><p>Status do sistema para PIs e documentos</p></div>
                        <div class="aw-list">
                                                            <div class="aw-list-row">
                                    <span class="aw-swatch" style="background: #9aa0aa;"></span>
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Gerado</span>
                                        <span class="aw-list-sub">12 usos</span>
                                    </div>
                                    <span class="aw-pill aw-pill--pi">PI</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <span class="aw-swatch" style="background: #f5b94a;"></span>
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Aguardando Documentos</span>
                                        <span class="aw-list-sub">23 usos</span>
                                    </div>
                                    <span class="aw-pill aw-pill--pi">PI</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <span class="aw-swatch" style="background: #5b8cff;"></span>
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Em Processo</span>
                                        <span class="aw-list-sub">34 usos</span>
                                    </div>
                                    <span class="aw-pill aw-pill--pi">PI</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <span class="aw-swatch" style="background: #ff6363;"></span>
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Reenvio de Documentos</span>
                                        <span class="aw-list-sub">9 usos</span>
                                    </div>
                                    <span class="aw-pill aw-pill--pi">PI</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <span class="aw-swatch" style="background: #5fcf80;"></span>
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Concluído</span>
                                        <span class="aw-list-sub">81 usos</span>
                                    </div>
                                    <span class="aw-pill aw-pill--pi">PI</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <span class="aw-swatch" style="background: #C28D44;"></span>
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Pago</span>
                                        <span class="aw-list-sub">67 usos</span>
                                    </div>
                                    <span class="aw-pill aw-pill--pi">PI</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <span class="aw-swatch" style="background: #5fcf80;"></span>
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Aprovado</span>
                                        <span class="aw-list-sub">540 usos</span>
                                    </div>
                                    <span class="aw-pill aw-pill--documento">Documento</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <span class="aw-swatch" style="background: #f5b94a;"></span>
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Aguardando validação</span>
                                        <span class="aw-list-sub">48 usos</span>
                                    </div>
                                    <span class="aw-pill aw-pill--documento">Documento</span>
                                </div>
                                                            <div class="aw-list-row">
                                    <span class="aw-swatch" style="background: #ff6363;"></span>
                                    <div class="aw-list-main">
                                        <span class="aw-list-title">Reprovado</span>
                                        <span class="aw-list-sub">15 usos</span>
                                    </div>
                                    <span class="aw-pill aw-pill--documento">Documento</span>
                                </div>
                                                    </div>
                    </div>

                    
                    <div class="aw-view" data-view="generic" hidden>
                        <div class="aw-generic">
                            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>
                            </svg>
                            <p>Este módulo faz parte do sistema completo.</p>
                            <p class="aw-generic-sub">Use o menu para explorar as demais telas da demonstração.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="arandu-case-features">
                            <div class="arandu-feature reveal" style="--reveal-delay: 0ms;">
                    <div class="arandu-feature-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path d="M5 12l5 5L20 7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="arandu-feature-title">Workflow completo de PIs</div>
                    <div class="arandu-feature-desc">Criação, atribuição, validação de documentos, conclusão e pagamento — com envio em massa e histórico auditável de cada ação.</div>
                </div>
                            <div class="arandu-feature reveal" style="--reveal-delay: 70ms;">
                    <div class="arandu-feature-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path d="M5 12l5 5L20 7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="arandu-feature-title">Leitura automática de validade</div>
                    <div class="arandu-feature-desc">O sistema extrai a data de validade direto do PDF e antecipa o alerta antes de qualquer documento vencer.</div>
                </div>
                            <div class="arandu-feature reveal" style="--reveal-delay: 140ms;">
                    <div class="arandu-feature-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path d="M5 12l5 5L20 7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="arandu-feature-title">5 perfis de acesso</div>
                    <div class="arandu-feature-desc">Admin, gerencial, operacional, fornecedor e representante — cada um enxerga exatamente o que precisa, e nada além.</div>
                </div>
                            <div class="arandu-feature reveal" style="--reveal-delay: 210ms;">
                    <div class="arandu-feature-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path d="M5 12l5 5L20 7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="arandu-feature-title">Notificações por e-mail</div>
                    <div class="arandu-feature-desc">Aprovações, pendências e cobranças chegam por e-mail, para que nada dependa de alguém lembrar de abrir o sistema.</div>
                </div>
                    </div>

        <div class="arandu-card arandu-monitor reveal">
            <div>
                <div class="arandu-eyebrow" style="margin-bottom: 14px;">Operação monitorada</div>
                <h3>Não basta entregar o sistema. A gente mantém ele de pé.</h3>
                <p>
                    Nossa equipe técnica acompanha, em tempo real, a saúde da aplicação, os erros antes que afetem o usuário, o disparo dos e-mails e a capacidade do servidor — com alertas internos automáticos. Quando algo sai do esperado, nós sabemos primeiro. Você não descobre um problema pelo seu cliente.
                </p>
            </div>
            <div class="arandu-monitor-panel" aria-hidden="true">
                                    <div class="arandu-monitor-stat">
                        <div class="s-top">
                            <span class="arandu-dot" style="background: var(--ok);"></span>
                            Saúde do sistema
                        </div>
                        <div class="s-val">Operacional</div>
                    </div>
                                    <div class="arandu-monitor-stat">
                        <div class="s-top">
                            <span class="arandu-dot" style="background: var(--accent);"></span>
                            Erros
                        </div>
                        <div class="s-val">Alerta proativo</div>
                    </div>
                                    <div class="arandu-monitor-stat">
                        <div class="s-top">
                            <span class="arandu-dot" style="background: var(--accent);"></span>
                            Disparo de e-mails
                        </div>
                        <div class="s-val">Monitorado</div>
                    </div>
                                    <div class="arandu-monitor-stat">
                        <div class="s-top">
                            <span class="arandu-dot" style="background: var(--ok);"></span>
                            Armazenamento
                        </div>
                        <div class="s-val">Sob controle</div>
                    </div>
                            </div>
        </div>

        <div class="arandu-spacer-80"></div>
    </section>

    <section class="arandu-process" id="como-trabalhamos">
        <div class="arandu-process-header reveal">
            <div>
                <div class="arandu-eyebrow" style="margin-bottom: 16px;">Como trabalhamos</div>
                <h2 class="arandu-h2">Da base validada à sua agência, em quatro etapas.</h2>
            </div>
            <p>Você não começa do zero. Partimos do sistema que já roda na Trade e o ajustamos à operação da sua agência — mais rápido e com menos risco que um projeto do zero. E você aprova cada etapa.</p>
        </div>
        <div class="arandu-process-wrapper">
            <div class="arandu-process-line"></div>
            <div class="arandu-process-grid">
                                    <div class="arandu-process-step reveal" style="--reveal-delay: 0ms;">
                        <div class="arandu-process-step-num"><span>01</span></div>
                        <h3 class="arandu-h3">Entender</h3>
                        <p>Mapeamos a operação da sua agência e onde ela difere do que já construímos. O foco fica no que é específico do seu processo.</p>
                        <span class="arandu-mono arandu-process-meta">1 a 2 semanas</span>
                    </div>
                                    <div class="arandu-process-step reveal" style="--reveal-delay: 80ms;">
                        <div class="arandu-process-step-num"><span>02</span></div>
                        <h3 class="arandu-h3">Adaptar</h3>
                        <p>Partimos da base já validada na Trade — PIs, fornecedores, documentos, chat — e ajustamos fluxos, regras e telas às suas necessidades. Você aprova cada mudança.</p>
                        <span class="arandu-mono arandu-process-meta">Iterativo · semanal</span>
                    </div>
                                    <div class="arandu-process-step reveal" style="--reveal-delay: 160ms;">
                        <div class="arandu-process-step-num"><span>03</span></div>
                        <h3 class="arandu-h3">Implantar</h3>
                        <p>Migração assistida dos seus dados, treinamento da equipe e ambiente de homologação para validar tudo antes de ir ao ar.</p>
                        <span class="arandu-mono arandu-process-meta">Semanas, não meses</span>
                    </div>
                                    <div class="arandu-process-step reveal" style="--reveal-delay: 240ms;">
                        <div class="arandu-process-step-num"><span>04</span></div>
                        <h3 class="arandu-h3">Evoluir</h3>
                        <p>Acompanhamento dedicado nos primeiros 90 dias e evolução contínua conforme a operação da agência cresce.</p>
                        <span class="arandu-mono arandu-process-meta">Contínuo</span>
                    </div>
                            </div>
        </div>

        <div class="reveal" style="margin-top: clamp(28px, 4vw, 40px); text-align: center;">
            <p style="color: var(--text-mute); margin: 0 0 14px;">Quer ver essas etapas aplicadas à operação da sua agência?</p>
            <a href="https://wa.me/5541998136312" target="_blank" rel="noopener" class="arandu-cta-ghost">Falar com o Diego →</a>
        </div>
    </section>

    <section class="arandu-clients" id="depoimentos">
        <div class="arandu-section-header reveal">
            <div class="arandu-eyebrow" style="margin-bottom: 16px;">Depoimentos</div>
            <h2 class="arandu-h2" style="max-width: 760px;">Quem opera a mídia na Trade, todo dia.</h2>
        </div>

        <div class="arandu-carousel reveal" id="aranduCarousel">
            <div class="arandu-carousel-track">
                                    <figure class="arandu-card arandu-testimonial">
                        <blockquote class="arandu-testimonial-quote">A plataforma TradeSaaS não alterou as etapas do processo, mas, ao centralizar o recebimento no lugar do e-mail, padronizou os documentos que chegam e facilitou a comunicação com os fornecedores.</blockquote>
                        <figcaption class="arandu-testimonial-author">
                            <div class="arandu-testimonial-avatar">L</div>
                            <div>
                                <div class="arandu-testimonial-name">Leonardo</div>
                                <div class="arandu-testimonial-role">Operacional · Trade Marketing</div>
                            </div>
                        </figcaption>
                    </figure>
                                    <figure class="arandu-card arandu-testimonial">
                        <blockquote class="arandu-testimonial-quote">O desempenho é rápido e os recursos realmente facilitam o dia a dia do checking.</blockquote>
                        <figcaption class="arandu-testimonial-author">
                            <div class="arandu-testimonial-avatar">M</div>
                            <div>
                                <div class="arandu-testimonial-name">Marisa</div>
                                <div class="arandu-testimonial-role">Operacional · Trade Marketing</div>
                            </div>
                        </figcaption>
                    </figure>
                                    <figure class="arandu-card arandu-testimonial">
                        <blockquote class="arandu-testimonial-quote">O sistema tem sido um grande aliado no nosso processo, tornando a conferência das documentações muito mais rápida e prática. Disponibilizar todos os documentos necessários de cada cliente otimiza a rotina e traz mais organização para todos. Além disso, facilita o trabalho dos veículos, tornando o fluxo mais ágil e assertivo.</blockquote>
                        <figcaption class="arandu-testimonial-author">
                            <div class="arandu-testimonial-avatar">G</div>
                            <div>
                                <div class="arandu-testimonial-name">Giovana</div>
                                <div class="arandu-testimonial-role">Operacional · Trade Marketing</div>
                            </div>
                        </figcaption>
                    </figure>
                                    <figure class="arandu-card arandu-testimonial">
                        <blockquote class="arandu-testimonial-quote">Eles entenderam a Trade antes de propor solução. O sistema que recebemos não parece pacote — parece extensão da nossa equipe.</blockquote>
                        <figcaption class="arandu-testimonial-author">
                            <div class="arandu-testimonial-avatar">TM</div>
                            <div>
                                <div class="arandu-testimonial-name">Trade Marketing</div>
                                <div class="arandu-testimonial-role">Agência de mídia · Trade Marketing</div>
                            </div>
                        </figcaption>
                    </figure>
                            </div>
            <div class="arandu-carousel-controls">
                <div class="arandu-carousel-dots" aria-hidden="true"></div>
                <div class="arandu-carousel-nav">
                    <button type="button" class="arandu-carousel-btn" data-car-prev aria-label="Depoimento anterior">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                    </button>
                    <button type="button" class="arandu-carousel-btn" data-car-next aria-label="Próximo depoimento">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="arandu-card arandu-clients-card reveal">
            <div class="arandu-stats-row">
                                    <div class="arandu-stat-block">
                        <div class="arandu-mono arandu-stat-value">100%</div>
                        <div class="arandu-stat-label">Da operação de mídia em um só lugar</div>
                    </div>
                                    <div class="arandu-stat-block">
                        <div class="arandu-mono arandu-stat-value">Tempo real</div>
                        <div class="arandu-stat-label">Status de cada PI, do início ao pagamento</div>
                    </div>
                                    <div class="arandu-stat-block">
                        <div class="arandu-mono arandu-stat-value">24/7</div>
                        <div class="arandu-stat-label">Operação monitorada pela equipe técnica</div>
                    </div>
                            </div>
        </div>
    </section>


    <!-- SEÇÃO PLANOS E PREÇOS IARANDÚ -->
    <section class="arandu-pricing" id="planos" style="padding: var(--section-y) var(--pad-x); background: var(--bg-raised); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 800px; height: 350px; background: radial-gradient(circle, var(--accent-soft) 0%, transparent 70%); pointer-events: none; opacity: 0.5;"></div>
        
        <div style="max-width: var(--maxw); margin: 0 auto; position: relative; z-index: 2;">
            <div style="text-align: center; max-width: 760px; margin: 0 auto 56px;">
                <div class="arandu-chip" style="margin-bottom: 16px; display: inline-flex;">
                    <span class="arandu-dot" style="background: var(--accent);"></span>
                    Planos & Investimento Oficial
                </div>
                <h2 class="arandu-h2" style="margin-bottom: 18px;">
                    Tecnologia sob medida para o porte da sua <span class="accent-italic">agência de publicidade</span>.
                </h2>
                <p style="font-size: 16px; color: var(--text-mute); line-height: 1.6;">
                    Valores transparentes desenhados para viabilizar operações que gerenciam contratos públicos e privados de mídia no Paraná. Sem custos ocultos.
                </p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 32px; align-items: stretch;">
                
                <!-- PLANO BASIC: 35K/ANO -->
                <div class="arandu-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 36px 32px; display: flex; flex-direction: column; justify-content: space-between; position: relative;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <span class="arandu-mono" style="font-size: 13px; color: var(--text-mute); text-transform: uppercase; letter-spacing: 1px;">Operação Essencial</span>
                            <span style="font-size: 12px; padding: 4px 10px; border-radius: 20px; background: var(--border-soft); color: var(--text-mute); border: 1px solid var(--border);">Retenção & Entrada</span>
                        </div>
                        <h3 style="font-size: 26px; font-weight: 700; color: var(--text); margin-bottom: 8px;">Plano Basic</h3>
                        <p style="font-size: 14px; color: var(--text-dim); margin-bottom: 24px; line-height: 1.5;">
                            Organização completa dos fluxos de mídia, PIs e certidões com implantação imediata para sua agência.
                        </p>

                        <div style="margin-bottom: 28px; padding-bottom: 24px; border-bottom: 1px solid var(--border);">
                            <div style="display: flex; align-items: baseline; gap: 8px;">
                                <span class="arandu-mono" style="font-size: 18px; color: var(--text-mute);">R$</span>
                                <span class="arandu-mono" style="font-size: 42px; font-weight: 700; color: var(--text); letter-spacing: -1px;">35.000</span>
                                <span class="arandu-mono" style="font-size: 14px; color: var(--text-mute);">/ ano</span>
                            </div>
                            <div class="arandu-mono" style="font-size: 13px; color: var(--accent); margin-top: 6px;">
                                ou 12x de ~R$ 2.916,66 / mês
                            </div>
                        </div>

                        <div style="margin-bottom: 32px;">
                            <div style="font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.5px;">O que está incluso:</div>
                            <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px;">
                                <li style="display: flex; align-items: flex-start; gap: 12px; font-size: 14px; color: var(--text-mute); line-height: 1.5;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2.5" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"/></svg>
                                    <span><strong>Gestão completa de PIs:</strong> emissão, conferência em massa e baixa com histórico auditável.</span>
                                </li>
                                <li style="display: flex; align-items: flex-start; gap: 12px; font-size: 14px; color: var(--text-mute); line-height: 1.5;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2.5" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"/></svg>
                                    <span><strong>Base de Fornecedores & Veículos:</strong> reaproveitamento automático de dados bancários e cadastros.</span>
                                </li>
                                <li style="display: flex; align-items: flex-start; gap: 12px; font-size: 14px; color: var(--text-mute); line-height: 1.5;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2.5" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"/></svg>
                                    <span><strong>Módulo de Representantes:</strong> controle de quem assina e responde por cada veículo com aprovação.</span>
                                </li>
                                <li style="display: flex; align-items: flex-start; gap: 12px; font-size: 14px; color: var(--text-mute); line-height: 1.5;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2.5" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"/></svg>
                                    <span><strong>Gestão de Documentos Fiscais:</strong> alertas automáticos de vencimento e auditoria para não travar pagamentos.</span>
                                </li>
                                <li style="display: flex; align-items: flex-start; gap: 12px; font-size: 14px; color: var(--text-mute); line-height: 1.5;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2.5" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"/></svg>
                                    <span><strong>Suporte direto com os fundadores:</strong> canal prioritário via WhatsApp com Diego Maldonado.</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <a href="https://wa.me/5541998136312?text=Ol%C3%A1%20Diego,%20gostaria%20de%20conversar%20sobre%20o%20Plano%20Basic%20da%20IArand%C3%BA%20(R$%2035k/ano)" target="_blank" rel="noopener" class="arandu-cta-ghost" style="width: 100%; text-align: center; justify-content: center; padding: 14px; font-size: 14px; font-weight: 600;">
                        Conversar sobre Plano Basic →
                    </a>
                </div>

                <!-- PLANO FULL: 70K/ANO COM DEV FREE -->
                <div class="arandu-card" style="background: linear-gradient(180deg, rgba(194, 141, 68, 0.09) 0%, var(--surface) 100%); border: 2px solid var(--accent); border-radius: 16px; padding: 36px 32px; display: flex; flex-direction: column; justify-content: space-between; position: relative; box-shadow: 0 16px 48px rgba(194, 141, 68, 0.16);">
                    <div style="position: absolute; top: -14px; left: 50%; transform: translateX(-50%); background: var(--accent); color: var(--accent-ink); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; padding: 5px 18px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.4); white-space: nowrap;">
                        ★ MAIS ESCOLHIDO · TIME DE PRODUTO DEDICADO
                    </div>

                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <span class="arandu-mono" style="font-size: 13px; color: var(--accent); text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Solução Definitiva</span>
                            <span style="font-size: 12px; padding: 4px 10px; border-radius: 20px; background: var(--accent-soft); color: var(--accent); border: 1px solid var(--accent); font-weight: 600;">Dev Free Incluso</span>
                        </div>
                        <h3 style="font-size: 26px; font-weight: 700; color: var(--text); margin-bottom: 8px;">Plano Full</h3>
                        <p style="font-size: 14px; color: var(--text-dim); margin-bottom: 24px; line-height: 1.5;">
                            Sua agência com um time de engenharia próprio construindo features customizadas sob medida.
                        </p>

                        <div style="margin-bottom: 28px; padding-bottom: 24px; border-bottom: 1px solid var(--border);">
                            <div style="display: flex; align-items: baseline; gap: 8px;">
                                <span class="arandu-mono" style="font-size: 18px; color: var(--text-mute);">R$</span>
                                <span class="arandu-mono" style="font-size: 42px; font-weight: 700; color: var(--accent); letter-spacing: -1px;">70.000</span>
                                <span class="arandu-mono" style="font-size: 14px; color: var(--text-mute);">/ ano</span>
                            </div>
                            <div class="arandu-mono" style="font-size: 13px; color: var(--accent-2); margin-top: 6px;">
                                ou 12x de ~R$ 5.833,33 / mês
                            </div>
                        </div>

                        <!-- DESTAQUE DEV FREE -->
                        <div style="background: rgba(194, 141, 68, 0.12); border: 1px dashed var(--accent); border-radius: 12px; padding: 16px; margin-bottom: 24px;">
                            <div style="display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 13px; color: var(--accent); margin-bottom: 6px;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                DESENVOLVEDOR DEDICADO FREE
                            </div>
                            <div style="font-size: 13px; color: var(--text); line-height: 1.5;">
                                Construção contínua de qualquer feature, integração personalizada ou relatório específico da sua agência sem custo extra de horas de desenvolvimento!
                            </div>
                        </div>

                        <div style="margin-bottom: 32px;">
                            <div style="font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.5px;">Tudo do Plano Basic, e mais:</div>
                            <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px;">
                                <li style="display: flex; align-items: flex-start; gap: 12px; font-size: 14px; color: var(--text); line-height: 1.5;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2.5" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"/></svg>
                                    <span><strong>Desenvolvedor Free dedicado:</strong> criação sob demanda de telas, fluxos e automações exclusivas.</span>
                                </li>
                                <li style="display: flex; align-items: flex-start; gap: 12px; font-size: 14px; color: var(--text); line-height: 1.5;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2.5" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"/></svg>
                                    <span><strong>Integrações customizadas:</strong> conexão direta com o ERP contábil ou sistema de BI da agência.</span>
                                </li>
                                <li style="display: flex; align-items: flex-start; gap: 12px; font-size: 14px; color: var(--text); line-height: 1.5;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2.5" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"/></svg>
                                    <span><strong>Auditoria para Contratos Públicos:</strong> trilhas customizadas para prestação de contas rígida.</span>
                                </li>
                                <li style="display: flex; align-items: flex-start; gap: 12px; font-size: 14px; color: var(--text); line-height: 1.5;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2.5" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"/></svg>
                                    <span><strong>SLA Crítico de 2h:</strong> suporte prioritário máximo para demandas operacionais de mídia.</span>
                                </li>
                                <li style="display: flex; align-items: flex-start; gap: 12px; font-size: 14px; color: var(--text); line-height: 1.5;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2.5" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"/></svg>
                                    <span><strong>Treinamento ilimitado:</strong> capacitação presencial/remota para novos membros do time e veículos.</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <a href="https://wa.me/5541998136312?text=Ol%C3%A1%20Diego,%20quero%20contratar%20o%20Plano%20Full%20da%20IArand%C3%BA%20com%20Desenvolvedor%20Dedicado%20Free%20(R$%2070k/ano)" target="_blank" rel="noopener" class="arandu-cta" style="width: 100%; text-align: center; justify-content: center; padding: 16px; font-size: 15px; font-weight: 700;">
                        Contratar Plano Full
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                    </a>
                </div>

            </div>
        </div>
    </section>

    <section class="arandu-team" id="equipe">
        <div class="arandu-team-header reveal">
            <div>
                <div class="arandu-eyebrow" style="margin-bottom: 16px;">Quem está construindo</div>
                <h2 class="arandu-h2">Quatro sócios. Sem terceirização, sem subordinado escondido.</h2>
            </div>
            <p>Todo mundo que aparece aqui está no contrato e na obra. Quando você fecha com a IArandú, são as quatro pessoas desta página que constroem e mantêm o sistema da sua agência.</p>
        </div>
        <div class="arandu-team-grid">
                            <div class="reveal" style="--reveal-delay: 0ms;">
                    <div class="arandu-team-avatar tone-w">
                        <div class="arandu-team-avatar-glow"></div>
                        <div class="arandu-team-avatar-label">
                            <div class="arandu-team-avatar-initial">W</div>
                        </div>
                    </div>
                    <div class="arandu-team-info">
                        <div class="arandu-team-name">Wesley</div>
                        <div class="arandu-team-role">Backend &amp; arquitetura</div>
                        <p class="arandu-team-bio">Define como os dados se estruturam e como o sistema escala. É por aqui que passa a base de tudo.</p>
                    </div>
                </div>
                            <div class="reveal" style="--reveal-delay: 70ms;">
                    <div class="arandu-team-avatar tone-j">
                        <div class="arandu-team-avatar-glow"></div>
                        <div class="arandu-team-avatar-label">
                            <div class="arandu-team-avatar-initial">J</div>
                        </div>
                    </div>
                    <div class="arandu-team-info">
                        <div class="arandu-team-name">Julia</div>
                        <div class="arandu-team-role">Fullstack &amp; design</div>
                        <p class="arandu-team-bio">Cuida da experiência e de boa parte do que faz o produto funcionar: front-end, design de produto e fluxos.</p>
                    </div>
                </div>
                            <div class="reveal" style="--reveal-delay: 140ms;">
                    <div class="arandu-team-avatar tone-d">
                        <div class="arandu-team-avatar-glow"></div>
                        <div class="arandu-team-avatar-label">
                            <div class="arandu-team-avatar-initial">D</div>
                        </div>
                    </div>
                    <div class="arandu-team-info">
                        <div class="arandu-team-name">Diego</div>
                        <div class="arandu-team-role">Comercial &amp; contratos</div>
                        <p class="arandu-team-bio">Primeiro contato. Entende a operação da sua agência antes de qualquer proposta — e conduz a relação comercial.</p>
                    </div>
                </div>
                            <div class="reveal" style="--reveal-delay: 210ms;">
                    <div class="arandu-team-avatar tone-f">
                        <div class="arandu-team-avatar-glow"></div>
                        <div class="arandu-team-avatar-label">
                            <div class="arandu-team-avatar-initial">F</div>
                        </div>
                    </div>
                    <div class="arandu-team-info">
                        <div class="arandu-team-name">Franciele</div>
                        <div class="arandu-team-role">Pós-integração</div>
                        <p class="arandu-team-bio">Depois que o sistema entra no ar, é com ela: ajustes, treinamento e evolução contínua, sempre por perto.</p>
                    </div>
                </div>
                    </div>
    </section>

    
    
    
    <section class="arandu-pains" id="para-veiculos">
        <div class="arandu-section-header reveal">
            <div class="arandu-eyebrow" style="margin-bottom: 16px;">Para veículos de comunicação</div>
            <h2 class="arandu-h2" style="max-width: 880px;">
                Você conhece software house. Nós conhecemos o <span class="accent-italic">mercado de mídia</span>.
            </h2>
            <p class="arandu-case-intro" style="margin-top: 18px; margin-bottom: 0;">
                A IArandú nasceu dentro da operação de agências e veículos — rádios, TVs, jornais, portais e mídia OOH.
                Entendemos os processos, os prazos e a realidade comercial do setor. Por isso não vendemos "sistema para
                qualquer empresa": desenvolvemos tecnologia sob medida para quem vive de comunicação.
            </p>
        </div>

        <div class="arandu-pains-grid" style="margin-top: clamp(28px, 4vw, 40px);">
                            <div class="arandu-card arandu-pain-card reveal" style="--reveal-delay: 0ms;">
                    <div class="arandu-pain-label">✕ Documentação repetida</div>
                    <h3 class="arandu-h3">A mesma certidão enviada dez vezes, para dez agências</h3>
                    <p>Cada agência pede os mesmos documentos, a cada PI, por e-mail. Sem um lugar único, o comercial do veículo vira despachante — e o faturamento atrasa junto.</p>
                </div>
                            <div class="arandu-card arandu-pain-card reveal" style="--reveal-delay: 80ms;">
                    <div class="arandu-pain-label">✕ Comercial na planilha</div>
                    <h3 class="arandu-h3">Anunciantes, contratos e inserções espalhados</h3>
                    <p>A operação comercial roda em planilha e mensagem. Renovação passa batida, inserção fica sem comprovação e o histórico mora na cabeça de uma pessoa só.</p>
                </div>
                            <div class="arandu-card arandu-pain-card reveal" style="--reveal-delay: 160ms;">
                    <div class="arandu-pain-label">✕ Tecnologia genérica</div>
                    <h3 class="arandu-h3">Sistema de prateleira não fala a língua da mídia</h3>
                    <p>PI, veiculação, checking, praça, grade: a ferramenta genérica não conhece o vocabulário do setor — e o veículo acaba se moldando ao software, quando deveria ser o contrário.</p>
                </div>
                    </div>

        <div class="arandu-case-features">
                            <div class="arandu-feature reveal" style="--reveal-delay: 0ms;">
                    <div class="arandu-feature-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path d="M5 12l5 5L20 7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="arandu-feature-title">Portais e presença digital</div>
                    <div class="arandu-feature-desc">Portais de notícias, sites institucionais, landing pages e áreas restritas para anunciantes — pensados para a audiência e para a operação comercial.</div>
                </div>
                            <div class="arandu-feature reveal" style="--reveal-delay: 70ms;">
                    <div class="arandu-feature-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path d="M5 12l5 5L20 7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="arandu-feature-title">Sistemas de gestão</div>
                    <div class="arandu-feature-desc">Gestão de anunciantes e contratos, controle de inserções, gestão documental e workflow operacional — sob medida para o fluxo do seu veículo.</div>
                </div>
                            <div class="arandu-feature reveal" style="--reveal-delay: 140ms;">
                    <div class="arandu-feature-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path d="M5 12l5 5L20 7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="arandu-feature-title">Automação e IA aplicada</div>
                    <div class="arandu-feature-desc">Leitura automática de documentos, relatórios inteligentes e atendimento automatizado — IA onde reduz tarefa operacional de verdade.</div>
                </div>
                            <div class="arandu-feature reveal" style="--reveal-delay: 210ms;">
                    <div class="arandu-feature-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path d="M5 12l5 5L20 7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="arandu-feature-title">Integrações</div>
                    <div class="arandu-feature-desc">ERP, financeiro, WhatsApp, Google Workspace, Microsoft 365 e APIs — a tecnologia nova conversa com o que o veículo já usa.</div>
                </div>
                    </div>

        <div class="arandu-card arandu-clients-card reveal" style="margin-top: clamp(28px, 4vw, 40px);">
            <div class="arandu-stats-row">
                                    <div class="arandu-stat-block">
                        <div class="arandu-mono arandu-stat-value">700+</div>
                        <div class="arandu-stat-label">Veículos de comunicação conectados ao ecossistema IArandú</div>
                    </div>
                                    <div class="arandu-stat-block">
                        <div class="arandu-mono arandu-stat-value">Mídia</div>
                        <div class="arandu-stat-label">Nascemos dentro da operação de agências e veículos — falamos a sua língua</div>
                    </div>
                                    <div class="arandu-stat-block">
                        <div class="arandu-mono arandu-stat-value">Sob medida</div>
                        <div class="arandu-stat-label">Nada de pacote de prateleira: a tecnologia se molda à sua operação</div>
                    </div>
                            </div>
        </div>

        <div class="reveal" style="margin-top: clamp(28px, 4vw, 40px); text-align: center;">
            <h3 class="arandu-h3" style="margin-bottom: 8px;">Vamos desenvolver a próxima tecnologia do seu veículo?</h3>
            <p style="color: var(--text-mute); margin: 0 0 20px;">Agende uma conversa com a equipe — sem compromisso.</p>
            <a href="https://wa.me/5541998136312" target="_blank" rel="noopener" class="arandu-cta">
                Conversar com a equipe
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path d="M5 12h14M13 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </section>

    <section class="arandu-final-cta">
        <div class="arandu-final-cta-glow"></div>
        <div class="arandu-final-cta-content reveal">
            <h2 class="arandu-h2">
                A próxima agência com a <span class="accent-italic">operação de mídia sob controle</span> pode ser a sua.
            </h2>
            <p class="intro">
                Agende 30 minutos com o Diego, sem compromisso. Você sai sabendo se a IArandú é a parceira certa para a operação da sua agência — e nós, se podemos entregar o que ela precisa.
            </p>
            <a href="https://wa.me/5541998136312" target="_blank" rel="noopener" class="arandu-cta" style="padding: 18px 28px; font-size: 16px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M17.6 6.32A7.85 7.85 0 0012.05 4 7.94 7.94 0 004.1 11.94a7.87 7.87 0 001.06 3.97L4 20l4.2-1.1a7.93 7.93 0 003.84.98h.01a7.94 7.94 0 007.94-7.94 7.9 7.9 0 00-2.4-5.62zm-5.55 12.22h-.01a6.6 6.6 0 01-3.36-.92l-.24-.14-2.5.65.67-2.43-.16-.25a6.59 6.59 0 0110.21-8.16 6.55 6.55 0 011.93 4.67 6.61 6.61 0 01-6.54 6.58z"/>
                </svg>
                Falar no WhatsApp
            </a>
            <p class="arandu-mono arandu-phone">
                (41) 99813-6312 · Diego responde em até 2h em horário comercial
            </p>
        </div>
    </section>
        </main>

        <footer class="site-footer">
            <div class="site-footer-inner">
                <div class="site-footer-logo">
                    <img src="{{ asset('assets/img/logo-footer.png') }}" alt="IArandú">
                </div>
                <div class="site-footer-links">
                    
                    <a href="https://wa.me/5541998136312" target="_blank" rel="noopener" class="arandu-link">Contato</a>
                    <span class="arandu-mono site-footer-location">Curitiba · Paraná · Brasil · © 2026</span>
                </div>
            </div>
        </footer>
    </div>

<script src="{{ asset('assets/') }}/js/pages/layouts-site.js"></script>

    <script src="{{ asset('assets/') }}/js/pages/landing_page.js"></script>
</body>
</html>
