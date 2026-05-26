{{-- resources/views/representantes/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Representantes')

@section('content')
    <div class="container-fluid px-0">
        <div class="rep-page-header">
            <div class="rep-header-card">
                <div class="rep-header-top">
                    <div class="rep-title-wrapper">
                        <div class="rep-icon-badge"><i class="bi bi-person-badge-fill"></i></div>
                        <div>
                            <h1 class="rep-main-title">Representantes</h1>
                            <p class="rep-main-subtitle">Gestão de representantes e vínculos com fornecedores</p>
                        </div>
                    </div>
                    <a href="{{ route('representantes.create') }}" class="rep-btn-create">
                        <i class="bi bi-plus-circle"></i> <span>Novo Representante</span>
                    </a>
                </div>
                <div class="rep-stats-inline">
                    <div class="stat-inline stat-clickable {{ !request('status_filtro') ? 'active' : '' }}" data-filter="all">
                        <span class="stat-value">{{ $totalRepresentantes }}</span>
                        <span class="stat-label">Total</span>
                    </div>
                    <div class="stat-separator"></div>
                    <div class="stat-inline stat-clickable {{ request('status_filtro') === 'ativos' ? 'active' : '' }}" data-filter="ativos">
                        <span class="stat-value stat-success">{{ $totalAtivos }}</span>
                        <span class="stat-label">Ativos</span>
                    </div>
                    <div class="stat-separator"></div>
                    <div class="stat-inline stat-clickable {{ request('status_filtro') === 'com_pendentes' ? 'active' : '' }}" data-filter="com_pendentes">
                        <span class="stat-value stat-warning">{{ $repComPendentes }}</span>
                        <span class="stat-label">Com Pendentes</span>
                    </div>
                    <div class="stat-separator"></div>
                    <div class="stat-inline stat-clickable {{ request('status_filtro') === 'sem_vinculos' ? 'active' : '' }}" data-filter="sem_vinculos">
                        <span class="stat-value stat-info">{{ $semVinculos }}</span>
                        <span class="stat-label">Sem Vínculos</span>
                    </div>
                    <div class="stat-separator"></div>
                    <div class="stat-inline stat-clickable {{ request('status_filtro') === 'inativos' ? 'active' : '' }}" data-filter="inativos">
                        <span class="stat-value stat-danger">{{ $totalInativos }}</span>
                        <span class="stat-label">Inativos</span>
                    </div>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('representantes.index') }}" id="filterForm">
            <input type="hidden" name="status_filtro" id="statusFilter" value="{{ request('status_filtro') }}">
            <div class="rep-toolbar">
                <div class="toolbar-left">
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" name="search" class="search-input" placeholder="Buscar por nome, e-mail, empresa ou CNPJ..." value="{{ request('search') }}" autocomplete="off">
                        @if (request('search'))
                            <button type="button" class="search-clear"><i class="bi bi-x"></i></button>
                        @endif
                        <button type="submit" class="search-btn" title="Pesquisar"><i class="bi bi-search"></i></button>
                    </div>
                </div>
                <div class="toolbar-right">
                    <span class="results-count">{{ $representantes->total() }} {{ Str::plural('resultado', $representantes->total()) }}</span>
                </div>
            </div>
        </form>

        @if (request('search') || request('status_filtro'))
            <div class="active-filters-strip">
                <span class="filters-label">Filtros ativos:</span>
                <div class="filter-tags">
                    @if (request('search'))
                        <span class="filter-tag">Busca: {{ request('search') }} <button type="button" class="remove-filter" data-filter="search">×</button></span>
                    @endif
                    @if (request('status_filtro'))
                        <span class="filter-tag">
                            @switch(request('status_filtro'))
                                @case('com_pendentes') Com Pendentes @break
                                @case('ativos') Ativos @break
                                @case('inativos') Inativos @break
                                @case('sem_vinculos') Sem Vínculos @break
                            @endswitch
                            <button type="button" class="remove-filter" data-filter="status_filtro">×</button>
                        </span>
                    @endif
                </div>
                <a href="{{ route('representantes.index') }}" class="clear-filters-link">Limpar tudo</a>
            </div>
        @endif

        <div class="rep-table-container">
            @if ($representantes->count() > 0)
                <div class="rep-table-wrapper">
                    <table class="rep-data-table">
                        <thead>
                            <tr>
                                <th class="th-primary"><span class="th-content"><i class="bi bi-person-badge"></i> Representante</span></th>
                                <th class="th-secondary">Empresa</th>
                                <th class="th-secondary th-center">Fornecedores</th>
                                <th class="th-secondary th-center">Pendentes</th>
                                <th class="th-secondary">Status</th>
                                <th class="th-secondary">Cadastro</th>
                                <th class="th-actions">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($representantes as $rep)
                                <tr class="rep-table-row {{ !$rep->ativo ? 'row-inactive' : '' }}" id="row-{{ $rep->id }}">
                                    <td class="td-primary">
                                        <div class="rep-cell">
                                            <span class="rep-name-table">{{ $rep->user->name ?? 'N/A' }}</span>
                                            <span class="rep-email">{{ $rep->user->email ?? '' }}</span>
                                            @if ($rep->telefone)
                                                <span class="rep-extra"><i class="bi bi-telephone"></i> {{ $rep->telefone }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="td-secondary">
                                        <div class="empresa-cell">
                                            <span class="{{ !$rep->nome_empresa ? 'text-muted-it' : '' }}">{{ $rep->nome_empresa ?: 'Não informada' }}</span>
                                            @if ($rep->cnpj)
                                                <span class="cnpj-mono">{{ substr($rep->cnpj, 0, 2) }}.{{ substr($rep->cnpj, 2, 3) }}.{{ substr($rep->cnpj, 5, 3) }}/{{ substr($rep->cnpj, 8, 4) }}-{{ substr($rep->cnpj, 12, 2) }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="td-secondary td-center">
                                        @if ($rep->vinculos_ativos_count > 0)
                                            <span class="count-badge count-info">{{ $rep->vinculos_ativos_count }}</span>
                                        @else
                                            <span class="count-badge count-none" title="Nenhum fornecedor vinculado">0</span>
                                        @endif
                                    </td>
                                    <td class="td-secondary td-center">
                                        @if ($rep->vinculos_pendentes_count > 0)
                                            <span class="count-badge count-warning pulse-badge">{{ $rep->vinculos_pendentes_count }}</span>
                                        @else
                                            <span class="count-badge count-muted">0</span>
                                        @endif
                                    </td>
                                    <td class="td-secondary">
                                        <div class="status-cell">
                                            <span class="status-badge {{ $rep->ativo ? 'status-active' : 'status-inactive' }}">{{ $rep->ativo ? 'Ativo' : 'Inativo' }}</span>
                                            @if ($rep->ativo && $rep->vinculos_ativos_count === 0 && $rep->vinculos_pendentes_count === 0)
                                                <span class="status-badge status-sem-vinculo" title="Nenhum fornecedor vinculado">
                                                    <i class="bi bi-link-45deg"></i> Sem vínculos
                                                </span>
                                            @elseif ($rep->ativo && $rep->precisaCompletarCadastro())
                                                <span class="status-badge status-incompleto" title="Campos faltantes: {{ implode(', ', $rep->getCamposFaltantes()) }}">
                                                    <i class="bi bi-exclamation-circle-fill"></i> Cadastro Incompleto
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="td-secondary">
                                        <span class="date-info">
                                            {{ $rep->created_at->format('d/m/Y') }}
                                            <span class="date-relative">{{ $rep->created_at->diffForHumans() }}</span>
                                        </span>
                                    </td>
                                    <td class="td-actions">
                                        <button type="button" class="action-menu-btn" onclick="toggleActionMenu(event, {{ $rep->id }})">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <div class="action-menu" id="actionMenu-{{ $rep->id }}">
                                            <button type="button" class="action-menu-item btn-view-rep" data-rep-id="{{ $rep->id }}">
                                                <i class="bi bi-eye"></i>
                                                <span>Detalhes e Desempenho</span>
                                            </button>
                                            <a href="{{ route('representantes.edit', $rep->id) }}" class="action-menu-item">
                                                <i class="bi bi-pencil"></i>
                                                <span>Editar Representante</span>
                                            </a>
                                            <div class="action-menu-divider"></div>
                                            <button type="button" class="action-menu-item btn-reenviar-cred" data-rep-id="{{ $rep->id }}" data-email="{{ $rep->user->email ?? '' }}">
                                                <i class="bi bi-envelope-arrow-up text-info"></i>
                                                <span>Reenviar Credenciais</span>
                                            </button>
                                            <button type="button" class="action-menu-item btn-toggle-ativo" data-rep-id="{{ $rep->id }}" data-ativo="{{ $rep->ativo ? '1' : '0' }}">
                                                @if ($rep->ativo)
                                                    <i class="bi bi-pause-circle text-danger"></i>
                                                    <span>Desativar</span>
                                                @else
                                                    <i class="bi bi-play-circle text-success"></i>
                                                    <span>Ativar</span>
                                                @endif
                                            </button>
                                            <div class="action-menu-divider"></div>
                                            <button type="button" class="action-menu-item item-danger btn-destroy-rep" data-rep-id="{{ $rep->id }}" data-nome="{{ $rep->user->name ?? 'Representante' }}">
                                                <i class="bi bi-trash3"></i>
                                                <span>Excluir</span>
                                            </button>
                                            @if ($rep->user)
                                                <div class="action-menu-divider"></div>
                                                <a href="{{ route('users.edit', $rep->user->id) }}" class="action-menu-item">
                                                    <i class="bi bi-person-gear"></i>
                                                    <span>Editar Usuário</span>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @include('layouts.trade-pagination', ['paginator' => $representantes])
            @else
                <div class="rep-empty-state">
                    <div class="rep-empty-icon"><i class="bi bi-person-badge"></i></div>
                    <h3 class="rep-empty-title">Nenhum representante encontrado</h3>
                    <p class="rep-empty-text">
                        @if (request('search') || request('status_filtro'))
                            Tente ajustar os filtros de busca.
                        @else
                            Ainda não há representantes cadastrados no sistema.
                        @endif
                    </p>
                    @if (request('search') || request('status_filtro'))
                        <a href="{{ route('representantes.index') }}" class="rep-btn-empty"><i class="bi bi-arrow-counterclockwise"></i> Limpar Filtros</a>
                    @else
                        <a href="{{ route('representantes.create') }}" class="rep-btn-empty"><i class="bi bi-plus-circle"></i> Criar Representante</a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="repModal" tabindex="-1" aria-labelledby="repModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="repModalLabel"><i class="bi bi-person-badge-fill"></i> <span id="repModalTitleText">Detalhes</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body" id="repModalBody">
                    <div class="text-center py-5">
                        <div class="spinner-border" role="status" style="color:var(--primary-color);"><span class="visually-hidden">Carregando...</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/@floating-ui/core@1.5.2"></script>
    <script src="https://cdn.jsdelivr.net/npm/@floating-ui/dom@1.5.3"></script>
    <script>
        let currentOpenMenu = null;
        let currentOpenButton = null;

        function closeCurrentMenu() {
            if (currentOpenMenu) {
                currentOpenMenu.classList.remove('active');
                currentOpenMenu.style.display = 'none';
                currentOpenMenu = null;
                currentOpenButton = null;
            }
        }

        async function positionMenu(button, menu) {
            const menuWidth = 220;
            if (typeof FloatingUIDOM !== 'undefined') {
                try {
                    const { x, y } = await FloatingUIDOM.computePosition(button, menu, {
                        placement: 'bottom-end',
                        strategy: 'fixed',
                        middleware: [
                            FloatingUIDOM.offset(8),
                            FloatingUIDOM.flip({ fallbackPlacements: ['top-end', 'bottom-start', 'top-start'], padding: 8 }),
                            FloatingUIDOM.shift({ padding: 8 }),
                            FloatingUIDOM.size({ apply({ availableHeight, elements }) { Object.assign(elements.floating.style, { maxHeight: `${Math.max(200, availableHeight - 16)}px`, overflowY: 'auto' }); }, padding: 8 })
                        ]
                    });
                    Object.assign(menu.style, { position: 'fixed', left: `${x}px`, top: `${y}px`, margin: '0', transform: 'none', width: `${menuWidth}px` });
                    return;
                } catch (e) {}
            }
            const rect = button.getBoundingClientRect();
            let left = rect.right - menuWidth;
            let top = rect.bottom + 8;
            if (left < 8) left = rect.left;
            if (left + menuWidth > window.innerWidth - 8) left = window.innerWidth - menuWidth - 8;
            if (top + 300 > window.innerHeight - 8) top = rect.top - 300 - 8;
            if (top < 8) top = 8;
            Object.assign(menu.style, { position: 'fixed', left: `${left}px`, top: `${top}px`, width: `${menuWidth}px`, margin: '0', transform: 'none' });
        }

        async function toggleActionMenu(event, repId) {
            event.stopPropagation();
            const button = event.currentTarget;
            const menu = document.getElementById(`actionMenu-${repId}`);
            if (currentOpenMenu && currentOpenMenu !== menu) closeCurrentMenu();
            if (menu.classList.contains('active')) {
                closeCurrentMenu();
            } else {
                menu.style.display = 'block';
                await positionMenu(button, menu);
                requestAnimationFrame(() => { menu.classList.add('active'); currentOpenMenu = menu; currentOpenButton = button; });
            }
        }

        document.addEventListener('click', function(e) {
            if (currentOpenMenu && !e.target.closest('.action-menu') && !e.target.closest('.action-menu-btn')) closeCurrentMenu();
        });

        document.addEventListener('click', function(e) {
            if (e.target.closest('.action-menu-item')) closeCurrentMenu();
        });

        window.addEventListener('scroll', function() {
            if (currentOpenMenu && currentOpenButton) positionMenu(currentOpenButton, currentOpenMenu);
        });

        var tableWrapper = document.querySelector('.rep-table-wrapper');
        if (tableWrapper) tableWrapper.addEventListener('scroll', function() {
            if (currentOpenMenu && currentOpenButton) positionMenu(currentOpenButton, currentOpenMenu);
        });
    </script>
    <script>
        $(document).ready(function() {
            var currentRepId = null;
            var csrfToken = $('meta[name="csrf-token"]').attr('content');

            function getCurrentFilters() {
                var p = new URLSearchParams(window.location.search);
                return { search: p.get('search') || '', status_filtro: p.get('status_filtro') || '' };
            }
            function buildUrl(f) {
                var p = new URLSearchParams();
                if (f.search) p.append('search', f.search);
                if (f.status_filtro) p.append('status_filtro', f.status_filtro);
                return '{{ route("representantes.index") }}' + (p.toString() ? '?' + p.toString() : '');
            }

            $('.search-input').on('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); $('#filterForm').submit(); } });
            $('.search-clear').on('click', function() { $('.search-input').val(''); $('#filterForm').submit(); });
            $('.remove-filter').on('click', function() { var f = getCurrentFilters(); f[$(this).data('filter')] = ''; window.location.href = buildUrl(f); });
            $('.stat-clickable').on('click', function() { var f = getCurrentFilters(); f.status_filtro = $(this).data('filter') === 'all' ? '' : $(this).data('filter'); window.location.href = buildUrl(f); });

            function esc(s) { if (!s) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
            function fmtCNPJ(c) { if (!c) return ''; c = c.replace(/\D/g, ''); if (c.length !== 14) return c; return c.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5'); }
            function swalTheme() { var dk = document.documentElement.getAttribute('data-theme') === 'dark'; return { background: dk ? '#1e293b' : '#fff', color: dk ? '#f1f5f9' : '#1f2937' }; }

            $(document).on('click', '.btn-view-rep', function() {
                currentRepId = $(this).data('rep-id');
                loadRepDetails(currentRepId);
            });

            function loadRepDetails(id) {
                var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('repModal'));
                $('#repModalBody').html('<div class="text-center py-5"><div class="spinner-border" role="status" style="color:var(--primary-color);"></div></div>');
                modal.show();
                $.get('/representantes/' + id + '/detalhes', function(r) {
                    if (r.success) {
                        currentRepId = id;
                        $('#repModalTitleText').text(r.representante.nome);
                        renderModal(r.representante, r.vinculos, r.metricas);
                    }
                }).fail(function() {
                    $('#repModalBody').html('<div class="text-center py-4"><i class="bi bi-exclamation-triangle" style="font-size:2.5rem;color:var(--danger-color);"></i><p class="mt-2" style="color:var(--text-muted);">Erro ao carregar dados.</p></div>');
                });
            }

            function renderModal(rep, vinculos, m) {
                var h = '';

                h += '<div class="ms">';
                h += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">';
                h += '<h4 class="ms-title" style="margin:0;"><i class="bi bi-person-vcard"></i> Dados Cadastrais</h4>';
                h += '<div style="display:flex;gap:.5rem;">';
                h += '<button class="ms-link btn-modal-reenviar" data-rep-id="' + rep.id + '" data-email="' + esc(rep.email) + '"><i class="bi bi-envelope-arrow-up"></i> Reenviar Credenciais</button>';
                h += '<a href="/representantes/' + rep.id + '/edit" class="ms-link"><i class="bi bi-pencil"></i> Editar</a>';
                h += '</div></div>';
                if (rep.cadastro_incompleto) {
                    h += '<div class="ms-alert-incompleto"><div class="ms-alert-icon"><i class="bi bi-exclamation-triangle-fill"></i></div><div class="ms-alert-body"><strong>Cadastro Incompleto</strong><p>Faltam: ' + esc(rep.campos_faltantes.join(', ')) + '</p><div class="ms-progress"><div class="ms-progress-fill" style="width:' + rep.progresso_cadastro + '%"></div></div><span class="ms-progress-text">' + rep.progresso_cadastro + '% completo</span></div></div>';
                }
                h += '<div class="dg">';
                h += di('person', 'Nome', rep.nome);
                h += di('envelope', 'E-mail', rep.email);
                h += di('building', 'Empresa', rep.empresa || '<em class="text-muted-it">Não informada</em>');
                h += di('card-text', 'CNPJ', rep.cnpj ? fmtCNPJ(rep.cnpj) : '<em class="text-muted-it">Não informado</em>');
                h += di('telephone', 'Telefone', rep.telefone || '<em class="text-muted-it">Não informado</em>');
                h += di('calendar', 'Cadastro', rep.created_at);
                if (rep.observacoes) h += '<div class="di di-full"><div class="dl"><i class="bi bi-chat-left-text"></i> Observações</div><div class="dv">' + esc(rep.observacoes) + '</div></div>';
                h += '</div>';
                h += '</div>';

                h += '<div class="ms">';
                h += '<h4 class="ms-title"><i class="bi bi-bar-chart"></i> Desempenho</h4>';
                h += '<div class="mg">';
                h += mc('collection-play', 'Fornecedores Ativos', m.fornecedores_ativos, 'mc-primary');
                h += mc('file-earmark-plus', 'Total de PIs', m.total_pis, 'mc-info');
                h += mc('hourglass-split', 'PIs em Andamento', m.pis_ativos, 'mc-warning');
                h += mc('check-circle', 'PIs Concluídos', m.pis_concluidos, 'mc-success');
                h += mc('file-arrow-up', 'Docs Enviados', m.docs_enviados, 'mc-info');
                h += mc('file-check', 'Docs Aprovados', m.docs_aprovados, 'mc-success');
                h += mc('file-x', 'Docs Reprovados', m.docs_reprovados, 'mc-danger');
                h += mc('clock-history', 'Aguardando Validação', m.docs_aguardando_validacao, 'mc-warning');
                h += mc('file-earmark', 'Pendentes Envio', m.docs_pendentes_envio, 'mc-muted');
                h += '</div></div>';

                h += '<div class="ms ms-last">';
                h += '<h4 class="ms-title"><i class="bi bi-link-45deg"></i> Vínculos com Fornecedores (' + vinculos.length + ')</h4>';
                if (vinculos.length === 0) {
                    h += '<div class="vinculos-empty"><i class="bi bi-link-45deg"></i><p>Nenhum vínculo registrado.</p></div>';
                } else {
                    var pend = vinculos.filter(function(v) { return v.is_pendente; });
                    var aprov = vinculos.filter(function(v) { return v.is_aprovado; });
                    var outros = vinculos.filter(function(v) { return !v.is_pendente && !v.is_aprovado; });
                    if (pend.length) { h += '<div class="vg"><div class="vg-title vg-warning"><i class="bi bi-hourglass-split"></i> Aguardando Aprovação (' + pend.length + ')</div>'; pend.forEach(function(v) { h += vc(v); }); h += '</div>'; }
                    if (aprov.length) { h += '<div class="vg"><div class="vg-title vg-success"><i class="bi bi-check-circle-fill"></i> Ativos (' + aprov.length + ')</div>'; aprov.forEach(function(v) { h += vc(v); }); h += '</div>'; }
                    if (outros.length) { h += '<div class="vg"><div class="vg-title vg-muted"><i class="bi bi-archive"></i> Histórico (' + outros.length + ')</div>'; outros.forEach(function(v) { h += vc(v); }); h += '</div>'; }
                }
                h += '</div>';

                $('#repModalBody').html(h);
            }

            function di(icon, label, val) { return '<div class="di"><div class="dl"><i class="bi bi-' + icon + '"></i> ' + label + '</div><div class="dv">' + (val || '') + '</div></div>'; }
            function mc(icon, label, val, cls) { return '<div class="mc ' + cls + '"><div class="mc-val">' + val + '</div><div class="mc-lbl"><i class="bi bi-' + icon + '"></i> ' + label + '</div></div>'; }

            function vc(v) {
                var cls = 'vc-ok', lbl = 'Aprovado';
                if (v.is_pendente) { cls = 'vc-pend'; lbl = 'Pendente'; }
                else if (v.is_rejeitado) { cls = 'vc-rej'; lbl = 'Rejeitado'; }
                else if (v.data_desvinculo || !v.ativo) { cls = 'vc-off'; lbl = 'Desvinculado'; }

                var c = '<div class="vc ' + cls + '"><div class="vc-info"><div class="vc-nome">';
                c += '<a href="/fornecedores/' + v.fornecedor_id + '/edit" class="vc-nome-link" title="Abrir cadastro do fornecedor">' + esc(v.fornecedor_razao) + '</a>';
                if (v.fornecedor_fantasia) c += ' <span class="vc-fant">· ' + esc(v.fornecedor_fantasia) + '</span>';
                c += '</div><div class="vc-meta">';
                if (v.fornecedor_cnpj) c += '<span><i class="bi bi-card-text"></i> ' + fmtCNPJ(v.fornecedor_cnpj) + '</span>';
                c += '<span><i class="bi bi-calendar"></i> ' + esc(v.data_vinculo) + '</span>';
                if (v.aprovado_por) c += '<span><i class="bi bi-person-check"></i> ' + esc(v.aprovado_por) + '</span>';
                if (v.data_desvinculo) c += '<span><i class="bi bi-x-circle"></i> ' + esc(v.data_desvinculo) + '</span>';
                c += '</div>';
                if (v.motivo_rejeicao) c += '<div class="vc-motivo"><i class="bi bi-chat-left-text"></i> ' + esc(v.motivo_rejeicao) + '</div>';
                c += '</div><div class="vc-right"><span class="vc-badge vc-badge-' + cls + '">' + lbl + '</span><div class="vc-acts">';
                if (v.is_pendente) {
                    c += '<button class="vb vb-ok btn-aprovar" data-id="' + v.id + '" title="Aprovar"><i class="bi bi-check-lg"></i></button>';
                    c += '<button class="vb vb-no btn-rejeitar" data-id="' + v.id + '" title="Rejeitar"><i class="bi bi-x-lg"></i></button>';
                } else if (v.is_aprovado) {
                    c += '<button class="vb vb-off btn-desvincular" data-id="' + v.id + '" title="Desvincular"><i class="bi bi-link-45deg"></i></button>';
                }
                c += '</div></div></div>';
                return c;
            }

            function doModalAction(url, btn, icon, successMsg) {
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                $.post(url, { _token: csrfToken }, function(r) {
                    if (r.success) {
                        toastr.success(r.message || successMsg);
                        loadRepDetails(currentRepId);
                    } else {
                        toastr.error(r.message);
                        btn.prop('disabled', false).html('<i class="bi bi-' + icon + '"></i>');
                    }
                }).fail(function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Erro');
                    btn.prop('disabled', false).html('<i class="bi bi-' + icon + '"></i>');
                });
            }

            $(document).on('click', '.btn-aprovar', function() {
                var id = $(this).data('id'), btn = $(this), t = swalTheme();
                Swal.fire({ title: 'Aprovar Vínculo', text: 'O representante terá acesso aos dados e PIs deste fornecedor.', icon: 'question', showCancelButton: true, confirmButtonText: 'Aprovar', cancelButtonText: 'Cancelar', confirmButtonColor: '#10b981', ...t
                }).then(function(r) { if (r.isConfirmed) doModalAction('/vinculos-representante/' + id + '/aprovar', btn, 'check-lg', 'Vínculo aprovado!'); });
            });

            $(document).on('click', '.btn-rejeitar', function() {
                var id = $(this).data('id'), btn = $(this), t = swalTheme();
                Swal.fire({ title: 'Rejeitar Vínculo', input: 'textarea', inputLabel: 'Motivo (opcional)', inputPlaceholder: 'Informe o motivo...', showCancelButton: true, confirmButtonText: 'Rejeitar', cancelButtonText: 'Cancelar', confirmButtonColor: '#ef4444', ...t
                }).then(function(r) { if (r.isConfirmed) {
                    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                    $.post('/vinculos-representante/' + id + '/rejeitar', { _token: csrfToken, motivo: r.value }, function(res) {
                        if (res.success) { toastr.success(res.message); loadRepDetails(currentRepId); }
                        else { toastr.error(res.message); btn.prop('disabled', false).html('<i class="bi bi-x-lg"></i>'); }
                    }).fail(function(xhr) { toastr.error(xhr.responseJSON?.message || 'Erro'); btn.prop('disabled', false).html('<i class="bi bi-x-lg"></i>'); });
                }});
            });

            $(document).on('click', '.btn-desvincular', function() {
                var id = $(this).data('id'), btn = $(this), t = swalTheme();
                Swal.fire({ title: 'Desfazer Vínculo', text: 'O representante perderá acesso a este fornecedor.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Desvincular', cancelButtonText: 'Cancelar', confirmButtonColor: '#ef4444', ...t
                }).then(function(r) { if (r.isConfirmed) doModalAction('/representantes/vinculos/' + id + '/desvincular', btn, 'link-45deg', 'Vínculo desfeito!'); });
            });

            $(document).on('click', '.btn-toggle-ativo', function() {
                var id = $(this).data('rep-id');
                var ativo = $(this).data('ativo') == 1;
                var acao = ativo ? 'Desativar' : 'Ativar';
                var msg = ativo ? 'O representante não poderá mais acessar o sistema e todos os vínculos ativos serão desfeitos.' : 'O representante voltará a ter acesso ao sistema.';
                var cor = ativo ? '#ef4444' : '#10b981';
                var t = swalTheme();

                Swal.fire({
                    title: acao + ' Representante',
                    text: msg,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: acao,
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: cor,
                    ...t
                }).then(function(r) {
                    if (r.isConfirmed) {
                        $.post('/representantes/' + id + '/toggle-ativo', { _token: csrfToken }, function(res) {
                            if (res.success) {
                                toastr.success(res.message);
                                setTimeout(function() { window.location.reload(); }, 800);
                            } else {
                                toastr.error(res.message);
                            }
                        }).fail(function(xhr) {
                            toastr.error(xhr.responseJSON?.message || 'Erro ao alterar status.');
                        });
                    }
                });
            });

            function reenviarCredenciais(repId, email) {
                var t = swalTheme();
                Swal.fire({
                    title: 'Reenviar Credenciais',
                    html: 'Uma nova senha temporária será gerada e enviada para:<br><strong>' + esc(email) + '</strong>',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Reenviar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#0ea5e9',
                    ...t
                }).then(function(r) {
                    if (r.isConfirmed) {
                        Swal.fire({ title: 'Enviando...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); }, ...t });
                        $.post('/representantes/' + repId + '/reenviar-credenciais', { _token: csrfToken }, function(res) {
                            Swal.close();
                            if (res.success) { toastr.success(res.message); }
                            else { toastr.error(res.message); }
                        }).fail(function(xhr) {
                            Swal.close();
                            toastr.error(xhr.responseJSON?.message || 'Erro ao reenviar credenciais.');
                        });
                    }
                });
            }

            $(document).on('click', '.btn-reenviar-cred, .btn-modal-reenviar', function() {
                reenviarCredenciais($(this).data('rep-id'), $(this).data('email'));
            });

            $(document).on('click', '.btn-destroy-rep', function() {
                var id = $(this).data('rep-id');
                var nome = $(this).data('nome');
                var t = swalTheme();

                Swal.fire({
                    title: 'Excluir Representante',
                    html: 'Tem certeza que deseja excluir <strong>' + esc(nome) + '</strong>?<br><small class="text-muted">O representante e o usuário associado serão removidos.</small>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Excluir',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#ef4444',
                    ...t
                }).then(function(r) {
                    if (r.isConfirmed) {
                        $.ajax({
                            url: '/representantes/' + id,
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': csrfToken },
                            success: function(res) {
                                if (res.success) {
                                    toastr.success(res.message);
                                    $('#row-' + id).fadeOut(400, function() { $(this).remove(); });
                                } else {
                                    toastr.error(res.message);
                                }
                            },
                            error: function(xhr) {
                                toastr.error(xhr.responseJSON?.message || 'Erro ao excluir representante.');
                            }
                        });
                    }
                });
            });

            @if (session('success'))
                toastr.success('{{ session('success') }}');
            @elseif (session('error'))
                toastr.error('{{ session('error') }}');
            @endif
        });
    </script>
@endpush

@push('styles')
    <style>
        @keyframes repFadeInDown { from { opacity:0; transform:translateY(-20px); } to { opacity:1; transform:translateY(0); } }
        @keyframes repFadeInUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        @keyframes pulseGlow { 0%,100% { box-shadow:0 0 0 0 rgba(245,158,11,0.4); } 50% { box-shadow:0 0 0 6px rgba(245,158,11,0); } }

        .rep-page-header { margin-bottom:2rem; animation:repFadeInDown .6s ease; }
        .rep-header-card { background:var(--bg-card); border:1px solid var(--border-color); border-radius:16px; overflow:hidden; box-shadow:var(--shadow-md); }
        .rep-header-top { display:flex; justify-content:space-between; align-items:center; padding:2rem; border-bottom:1px solid var(--border-color); }
        .rep-title-wrapper { display:flex; align-items:center; gap:1.5rem; }
        .rep-icon-badge { width:60px; height:60px; background:linear-gradient(135deg,var(--primary-color),var(--primary-light)); border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.75rem; color:white; box-shadow:0 6px 20px rgba(0,94,130,.25); }
        .rep-main-title { font-size:1.875rem; font-weight:700; color:var(--text-color); margin:0; }
        .rep-main-subtitle { font-size:.95rem; color:var(--text-muted); margin:.25rem 0 0; }
        .rep-btn-create { display:inline-flex; align-items:center; gap:.625rem; padding:.875rem 1.75rem; background:linear-gradient(135deg,var(--primary-color),var(--primary-light)); color:white; border:none; border-radius:10px; font-weight:600; font-size:.95rem; text-decoration:none; box-shadow:0 4px 14px rgba(0,94,130,.25); transition:all .3s; }
        .rep-btn-create:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,94,130,.35); color:white; }

        .rep-stats-inline { display:flex; align-items:center; padding:1.25rem 2rem; background:rgba(0,94,130,.02); gap:1.5rem; }
        .stat-inline { display:flex; flex-direction:column; align-items:center; cursor:pointer; padding:.5rem 1rem; border-radius:8px; transition:all .2s; min-width:70px; }
        .stat-inline:hover { background:rgba(0,94,130,.08); }
        .stat-inline.active { background:var(--primary-color); }
        .stat-inline.active .stat-value, .stat-inline.active .stat-label { color:white !important; }
        .stat-value { font-size:1.75rem; font-weight:800; line-height:1; color:var(--text-color); }
        .stat-value.stat-success { color:#059669; } .stat-value.stat-danger { color:#dc2626; } .stat-value.stat-warning { color:#d97706; } .stat-value.stat-info { color:#2563eb; }
        .stat-label { font-size:.8rem; color:var(--text-muted); margin-top:.25rem; font-weight:600; text-align:center; }
        .stat-separator { width:1px; height:40px; background:var(--border-color); }

        .rep-toolbar { display:flex; justify-content:space-between; align-items:center; padding:1rem 0; margin:1.5rem 0; gap:1rem; animation:repFadeInUp .6s ease .2s backwards; }
        .toolbar-left { display:flex; gap:1rem; flex:1; }
        .search-box { position:relative; flex:1; max-width:500px; display:flex; align-items:stretch; }
        .search-icon { position:absolute; left:1rem; top:50%; transform:translateY(-50%); color:var(--text-muted); pointer-events:none; }
        .search-input { width:100%; padding:.75rem 3rem .75rem 2.75rem; border:2px solid var(--border-color); border-radius:10px 0 0 10px; font-size:.95rem; transition:all .2s; background:var(--bg-body); color:var(--text-color); }
        .search-input:focus { border-color:var(--primary-color); outline:none; box-shadow:0 0 0 3px rgba(0,94,130,.1); }
        .search-btn { display:flex; align-items:center; justify-content:center; padding:0 1rem; background:var(--primary-color); color:#fff; border:2px solid var(--primary-color); border-left:none; border-radius:0 10px 10px 0; cursor:pointer; font-size:1rem; transition:all .2s; flex-shrink:0; }
        .search-btn:hover { background:var(--primary-dark); border-color:var(--primary-dark); }
        .search-clear { position:absolute; right:3.5rem; top:50%; transform:translateY(-50%); background:none; border:none; font-size:1.25rem; color:var(--text-muted); cursor:pointer; padding:0; line-height:1; }
        .search-clear:hover { color:var(--text-color); }
        .toolbar-right { display:flex; align-items:center; }
        .results-count { font-size:.875rem; color:var(--text-muted); font-weight:600; white-space:nowrap; }

        .active-filters-strip { display:flex; align-items:center; gap:1rem; padding:.75rem 1.25rem; background:rgba(0,94,130,.05); border:1px solid var(--border-color); border-radius:10px; margin-bottom:1.5rem; }
        .filters-label { font-size:.8125rem; font-weight:600; color:var(--text-muted); }
        .filter-tags { display:flex; flex-wrap:wrap; gap:.5rem; flex:1; }
        .filter-tag { display:inline-flex; align-items:center; gap:.5rem; padding:.25rem .75rem; background:var(--bg-card); border:1px solid var(--border-color); border-radius:6px; font-size:.8125rem; color:var(--text-color); font-weight:500; }
        .remove-filter { background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:1rem; padding:0; line-height:1; }
        .remove-filter:hover { color:#ef4444; }
        .clear-filters-link { font-size:.8125rem; color:var(--primary-color); text-decoration:none; font-weight:600; white-space:nowrap; }
        .clear-filters-link:hover { text-decoration:underline; }

        .rep-table-container { animation:repFadeInUp .6s ease .3s backwards; }
        .rep-table-wrapper { background:var(--bg-card); border:1px solid var(--border-color); border-radius:16px; overflow:hidden; box-shadow:var(--shadow-sm); }
        .rep-data-table { width:100%; border-collapse:collapse; }
        .rep-data-table thead { background:rgba(0,94,130,.04); }
        .rep-data-table th { padding:1rem 1.25rem; font-size:.8125rem; font-weight:700; text-transform:uppercase; letter-spacing:.03em; color:var(--text-muted); border-bottom:2px solid var(--border-color); white-space:nowrap; }
        .th-center { text-align:center; }
        .th-actions { text-align:center; width:80px; }
        .th-content { display:flex; align-items:center; gap:.375rem; }
        .rep-data-table td { padding:1rem 1.25rem; border-bottom:1px solid var(--border-color); vertical-align:middle; }
        .rep-table-row { transition:background .15s; }
        .rep-table-row:hover { background:rgba(0,94,130,.02); }
        .rep-table-row:last-child td { border-bottom:none; }
        .row-inactive { opacity:.55; }
        .td-center { text-align:center; }
        .td-actions { text-align:center; }

        .rep-cell { display:flex; flex-direction:column; gap:.125rem; }
        .rep-name-table { font-weight:600; color:var(--text-color); font-size:.9375rem; }
        .rep-email { font-size:.8125rem; color:var(--text-muted); }
        .rep-extra { font-size:.75rem; color:var(--text-muted); display:flex; align-items:center; gap:.375rem; }
        .rep-extra i { font-size:.75rem; }
        .empresa-cell { display:flex; flex-direction:column; gap:.125rem; }
        .text-muted-it { color:var(--text-muted); font-style:italic; font-size:.875rem; }
        .cnpj-mono { font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace; font-size:.8125rem; color:var(--text-muted); }
        .count-badge { display:inline-flex; align-items:center; justify-content:center; min-width:28px; height:28px; border-radius:8px; font-size:.8125rem; font-weight:700; padding:0 .5rem; }
        .count-info { background:rgba(37,99,235,.1); color:#2563eb; }
        .count-warning { background:rgba(245,158,11,.12); color:#d97706; }
        .count-muted { background:var(--bg-body); color:var(--text-muted); }
        .count-none { background:var(--bg-body); color:var(--text-muted); opacity:.6; }
        .pulse-badge { animation:pulseGlow 2s infinite; }
        .status-cell { display:flex; flex-direction:column; gap:.375rem; }
        .status-badge { display:inline-flex; align-items:center; gap:.375rem; padding:.25rem .625rem; border-radius:6px; font-size:.75rem; font-weight:700; width:fit-content; }
        .status-active { background:rgba(16,185,129,.1); color:#059669; }
        .status-inactive { background:rgba(239,68,68,.1); color:#ef4444; }
        .status-sem-vinculo { background:rgba(37,99,235,.08); color:#2563eb; }
        .status-incompleto { background:rgba(245,158,11,.1); color:#d97706; }
        .date-info { display:flex; flex-direction:column; gap:.125rem; font-size:.875rem; color:var(--text-color); }
        .date-relative { font-size:.75rem; color:var(--text-muted); }
        .action-menu-btn { width:36px; height:36px; display:inline-flex; align-items:center; justify-content:center; background:transparent; color:var(--primary-color); border:2px solid var(--primary-color); border-radius:8px; cursor:pointer; transition:all .2s; font-size:1.125rem; }
        .action-menu-btn:hover { background:rgba(0,94,130,.08); border-color:var(--primary-light); color:var(--primary-light); transform:translateY(-1px); }
        .action-menu { position:fixed; min-width:220px; padding:.5rem 0; border:2px solid var(--border-color); border-radius:12px; box-shadow:0 12px 48px rgba(0,0,0,.15); background:var(--bg-card); z-index:9999; opacity:0; transform:scale(.95); transition:opacity .15s,transform .15s; pointer-events:none; display:none; }
        @keyframes menuSlideIn { from { opacity:0; transform:translateY(-8px) scale(.96); } to { opacity:1; transform:translateY(0) scale(1); } }
        .action-menu.active { opacity:1; transform:scale(1); pointer-events:auto; animation:menuSlideIn .15s cubic-bezier(.16,1,.3,1); }
        .action-menu-item { display:flex; align-items:center; gap:.75rem; padding:.875rem 1.25rem; color:var(--text-color); font-size:.9375rem; font-weight:600; transition:all .2s; border:none; background:none; width:100%; text-align:left; cursor:pointer; text-decoration:none; }
        .action-menu-item:hover { background:linear-gradient(135deg,rgba(0,94,130,.08),rgba(0,94,130,.04)); color:var(--primary-color); }
        .action-menu-item i { font-size:1.125rem; width:20px; text-align:center; }
        .action-menu-item.item-danger:hover { background:linear-gradient(135deg,rgba(239,68,68,.1),rgba(239,68,68,.05)); color:#dc2626; }
        .action-menu-divider { height:1px; background:var(--border-color); margin:.5rem 0; }
        [data-theme="dark"] .action-menu { box-shadow:0 12px 48px rgba(0,0,0,.6); }
        [data-theme="dark"] .action-menu-btn { color:#38bdf8; border-color:#38bdf8; }
        [data-theme="dark"] .action-menu-btn:hover { background:rgba(56,189,248,.12); border-color:#7dd3fc; color:#7dd3fc; }
        [data-theme="dark"] .action-menu-item:hover { background:rgba(56,189,248,.08); }

        .rep-empty-state { text-align:center; padding:5rem 2rem; }
        .rep-empty-icon { width:120px; height:120px; margin:0 auto 2rem; background:linear-gradient(135deg,rgba(0,94,130,.1),rgba(0,94,130,.05)); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:4rem; color:var(--primary-color); opacity:.6; }
        .rep-empty-title { font-size:1.5rem; font-weight:700; color:var(--text-color); margin-bottom:.75rem; }
        .rep-empty-text { font-size:1rem; color:var(--text-muted); margin-bottom:2rem; max-width:450px; margin-left:auto; margin-right:auto; }
        .rep-btn-empty { display:inline-flex; align-items:center; gap:.625rem; padding:.875rem 1.75rem; background:var(--primary-color); color:white; border-radius:10px; font-weight:600; text-decoration:none; transition:all .3s; }
        .rep-btn-empty:hover { background:var(--primary-dark); color:white; transform:translateY(-2px); }

        .modal-content { background:var(--bg-card); border:1px solid var(--border-color); border-radius:16px; }
        .modal-header { border-bottom:1px solid var(--border-color); padding:1.25rem 1.5rem; }
        .modal-title { color:var(--text-color); font-weight:600; font-size:1.125rem; display:flex; align-items:center; gap:.5rem; }
        .modal-title i { color:var(--primary-color); }
        .modal-body { padding:1.5rem; }
        [data-theme="dark"] .btn-close { filter:invert(1) grayscale(100%) brightness(200%); }

        .ms { margin-bottom:2rem; padding-bottom:1.5rem; border-bottom:1px solid var(--border-color); }
        .ms.ms-last { margin-bottom:0; padding-bottom:0; border-bottom:none; }
        .ms-title { font-size:1rem; font-weight:700; color:var(--text-color); display:flex; align-items:center; gap:.5rem; margin:0 0 1rem; }
        .ms-title i { color:var(--primary-color); font-size:1.125rem; }
        .ms-link { display:inline-flex; align-items:center; gap:.5rem; padding:.5rem 1rem; border:2px solid var(--border-color); border-radius:8px; color:var(--text-color); text-decoration:none; font-weight:600; font-size:.875rem; transition:all .2s; background:transparent; cursor:pointer; }
        .ms-link:hover { border-color:var(--primary-color); color:var(--primary-color); }

        .ms-alert-incompleto { display:flex; gap:1rem; padding:1rem; background:linear-gradient(135deg,#fef3c7,#fde68a); border:1px solid #fde68a; border-radius:10px; margin-bottom:1rem; }
        .ms-alert-icon { width:40px; height:40px; background:rgba(217,119,6,.15); border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .ms-alert-icon i { color:#d97706; font-size:1.25rem; }
        .ms-alert-body { flex:1; }
        .ms-alert-body strong { color:#92400e; font-size:.9375rem; }
        .ms-alert-body p { color:#a16207; font-size:.8125rem; margin:.25rem 0 .5rem; }
        .ms-progress { height:4px; background:rgba(217,119,6,.2); border-radius:4px; overflow:hidden; }
        .ms-progress-fill { height:100%; background:#d97706; border-radius:4px; transition:width .3s; }
        .ms-progress-text { font-size:.75rem; color:#a16207; font-weight:600; }
        [data-theme="dark"] .ms-alert-incompleto { background:rgba(217,119,6,.1); border-color:rgba(217,119,6,.2); }
        [data-theme="dark"] .ms-alert-body strong { color:#fbbf24; }
        [data-theme="dark"] .ms-alert-body p { color:#fcd34d; }

        .dg { display:grid; grid-template-columns:repeat(2,1fr); gap:1rem; }
        .di { display:flex; flex-direction:column; gap:.25rem; }
        .di-full { grid-column:1/-1; }
        .dl { font-size:.75rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.03em; display:flex; align-items:center; gap:.375rem; }
        .dl i { font-size:.8125rem; }
        .dv { font-size:.9375rem; color:var(--text-color); font-weight:500; }

        .mg { display:grid; grid-template-columns:repeat(3,1fr); gap:.75rem; }
        .mc { background:var(--bg-body); border:1px solid var(--border-color); border-radius:10px; padding:1rem; text-align:center; transition:transform .15s; }
        .mc:hover { transform:translateY(-2px); }
        .mc-val { font-size:1.75rem; font-weight:800; line-height:1.2; }
        .mc-lbl { font-size:.75rem; font-weight:600; color:var(--text-muted); margin-top:.25rem; display:flex; align-items:center; justify-content:center; gap:.375rem; }
        .mc-lbl i { font-size:.8125rem; }
        .mc-primary .mc-val { color:var(--primary-color); } .mc-success .mc-val { color:#059669; } .mc-warning .mc-val { color:#d97706; } .mc-danger .mc-val { color:#dc2626; } .mc-info .mc-val { color:#2563eb; } .mc-muted .mc-val { color:var(--text-muted); }

        .vinculos-empty { text-align:center; padding:2rem; color:var(--text-muted); }
        .vinculos-empty i { font-size:2.5rem; display:block; margin-bottom:.5rem; opacity:.5; }
        .vg { margin-bottom:1.25rem; } .vg:last-child { margin-bottom:0; }
        .vg-title { font-size:.875rem; font-weight:700; display:flex; align-items:center; gap:.5rem; margin-bottom:.625rem; padding-bottom:.375rem; border-bottom:1px dashed var(--border-color); }
        .vg-warning { color:#d97706; } .vg-success { color:#059669; } .vg-muted { color:var(--text-muted); }

        .vc { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.875rem 1rem; background:var(--bg-body); border:1px solid var(--border-color); border-radius:10px; margin-bottom:.5rem; }
        .vc:last-child { margin-bottom:0; }
        .vc.vc-pend { border-left:3px solid #f59e0b; } .vc.vc-ok { border-left:3px solid #10b981; }
        .vc.vc-rej { border-left:3px solid #ef4444; opacity:.65; } .vc.vc-off { border-left:3px solid #64748b; opacity:.55; }
        .vc-info { flex:1; min-width:0; }
        .vc-nome { font-weight:600; color:var(--text-color); font-size:.9375rem; }
        .vc-nome-link { color:var(--primary-color); text-decoration:none; font-weight:600; }
        .vc-nome-link:hover { text-decoration:underline; }
        .vc-fant { font-weight:400; color:var(--text-muted); font-size:.8125rem; }
        .vc-meta { display:flex; flex-wrap:wrap; gap:.75rem; margin-top:.25rem; font-size:.75rem; color:var(--text-muted); }
        .vc-meta span { display:flex; align-items:center; gap:.25rem; }
        .vc-motivo { margin-top:.25rem; font-size:.8125rem; color:#ef4444; font-style:italic; display:flex; align-items:flex-start; gap:.375rem; }
        .vc-right { display:flex; align-items:center; gap:.625rem; flex-shrink:0; }
        .vc-badge { padding:.25rem .625rem; border-radius:6px; font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.025em; }
        .vc-badge-vc-pend { background:rgba(245,158,11,.12); color:#d97706; }
        .vc-badge-vc-ok { background:rgba(16,185,129,.12); color:#059669; }
        .vc-badge-vc-rej { background:rgba(239,68,68,.12); color:#ef4444; }
        .vc-badge-vc-off { background:rgba(100,116,139,.12); color:#64748b; }
        .vc-acts { display:flex; gap:.375rem; }
        .vb { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border:none; border-radius:8px; cursor:pointer; transition:all .2s; font-size:.875rem; }
        .vb:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,.15); }
        .vb:disabled { opacity:.5; cursor:not-allowed; transform:none; }
        .vb-ok { background:linear-gradient(135deg,#d1fae5,#a7f3d0); color:#059669; } .vb-ok:hover { background:#10b981; color:white; }
        .vb-no { background:linear-gradient(135deg,#fee2e2,#fecaca); color:#dc2626; } .vb-no:hover { background:#ef4444; color:white; }
        .vb-off { background:linear-gradient(135deg,#f1f5f9,#e2e8f0); color:#64748b; } .vb-off:hover { background:#64748b; color:white; }

        [data-theme="dark"] .rep-stats-inline { background:rgba(56,189,248,.05); }
        [data-theme="dark"] .stat-inline:hover { background:rgba(56,189,248,.1); }
        [data-theme="dark"] .rep-data-table thead { background:rgba(56,189,248,.08); }
        [data-theme="dark"] .rep-table-row:hover { background:rgba(56,189,248,.04); }
        [data-theme="dark"] .active-filters-strip { background:rgba(56,189,248,.08); }
        [data-theme="dark"] .filter-tag { background:var(--bg-card); }
        [data-theme="dark"] .swal2-popup { background:#1e293b !important; }
        [data-theme="dark"] .swal2-title { color:#f1f5f9 !important; }
        [data-theme="dark"] .swal2-html-container { color:#cbd5e1 !important; }
        [data-theme="dark"] .swal2-input,[data-theme="dark"] .swal2-textarea { background:#334155 !important; color:#f1f5f9 !important; border-color:#475569 !important; }

        @media (max-width:992px) { .rep-header-top { flex-direction:column; gap:1.5rem; } .rep-btn-create { width:100%; justify-content:center; } .toolbar-left { flex-direction:column; width:100%; } .search-box { max-width:100%; } .rep-toolbar { flex-direction:column; } .toolbar-right { width:100%; text-align:center; } .dg { grid-template-columns:1fr; } .mg { grid-template-columns:repeat(2,1fr); } }
        @media (max-width:768px) { .rep-stats-inline { justify-content:space-around; padding:1rem; gap:.75rem; flex-wrap:wrap; } .stat-inline { min-width:auto; padding:.375rem .625rem; } .stat-value { font-size:1.25rem; } .stat-separator { height:30px; } .th-secondary:nth-child(4),.td-secondary:nth-child(4),.th-secondary:nth-child(6),.td-secondary:nth-child(6) { display:none; } .rep-data-table th,.rep-data-table td { padding:.875rem; } .vc { flex-direction:column; align-items:stretch; } .vc-right { justify-content:space-between; margin-top:.5rem; } .mg { grid-template-columns:repeat(2,1fr); } }
        @media (max-width:576px) { .rep-main-title { font-size:1.5rem; } .rep-icon-badge { width:50px; height:50px; font-size:1.5rem; } .rep-header-top { padding:1.5rem; } .th-secondary:nth-child(3),.td-secondary:nth-child(3) { display:none; } .mg { grid-template-columns:1fr 1fr; } }
    </style>
@endpush