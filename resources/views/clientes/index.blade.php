{{-- resources/views/clientes/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Gerenciar Clientes')

@section('content')
    <div class="container-fluid px-0">
        <div class="page-header">
            <div class="header-card">
                <div class="header-top">
                    <div class="title-wrapper">
                        <div class="icon-badge">
                            <i class="bi bi-building-fill"></i>
                        </div>
                        <div>
                            <h1 class="main-title">Gerenciar Clientes</h1>
                            <p class="main-subtitle">Cadastro e gestão de clientes do sistema</p>
                        </div>
                    </div>
                    @hasanyrole('admin|gerencial|operacional')
                        <a href="{{ route('clientes.create') }}" class="btn-create">
                            <i class="bi bi-plus-circle"></i>
                            <span>Novo Cliente</span>
                        </a>
                    @endhasanyrole
                </div>

                <div class="stats-inline">
                    <div class="stat-inline stat-clickable {{ !request('status') ? 'active' : '' }}" data-filter="all">
                        <span class="stat-value">{{ $totalClientes }}</span>
                        <span class="stat-label">Total</span>
                    </div>
                    <div class="stat-separator"></div>
                    <div class="stat-inline stat-clickable {{ request('status') === 'ativos' ? 'active' : '' }}"
                        data-filter="ativos">
                        <span class="stat-value stat-success">{{ $clientesAtivos }}</span>
                        <span class="stat-label">Ativos</span>
                    </div>
                    @hasrole('admin')
                        <div class="stat-separator"></div>
                        <div class="stat-inline stat-clickable {{ request('status') === 'inativos' ? 'active' : '' }}"
                            data-filter="inativos">
                            <span class="stat-value stat-danger">{{ $clientesInativos }}</span>
                            <span class="stat-label">Inativos</span>
                        </div>
                    @endhasrole
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('clientes.index') }}" id="filterForm">
            <input type="hidden" name="status" id="statusFilter" value="{{ request('status') }}">

            <div class="toolbar">
                <div class="toolbar-left">
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" name="search" class="search-input"
                            placeholder="Buscar por razão social, CNPJ ou nome fantasia..." value="{{ request('search') }}"
                            autocomplete="off">
                        @if (request('search'))
                            <button type="button" class="search-clear">
                                <i class="bi bi-x"></i>
                            </button>
                        @endif
                        <button type="submit" class="search-btn" title="Pesquisar">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>

                <div class="toolbar-right">
                    <span class="results-count">
                        {{ $clientes->total() }} {{ Str::plural('resultado', $clientes->total()) }}
                    </span>
                </div>
            </div>
        </form>

        @if (request('search') || request('status'))
            <div class="active-filters-strip">
                <span class="filters-label">Filtros ativos:</span>
                <div class="filter-tags">
                    @if (request('search'))
                        <span class="filter-tag">
                            Busca: {{ request('search') }}
                            <button type="button" class="remove-filter" data-filter="search">×</button>
                        </span>
                    @endif
                    @if (request('status'))
                        <span class="filter-tag">
                            Status: {{ ucfirst(request('status')) }}
                            <button type="button" class="remove-filter" data-filter="status">×</button>
                        </span>
                    @endif
                </div>
                <a href="{{ route('clientes.index') }}" class="clear-filters-link">
                    Limpar tudo
                </a>
            </div>
        @endif

        <div class="table-container">
            @if ($clientes->count() > 0)
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="th-primary">
                                    <span class="th-content">
                                        <i class="bi bi-building"></i>
                                        Cliente
                                    </span>
                                </th>
                                <th class="th-secondary">CNPJ</th>
                                <th class="th-secondary">Contato</th>
                                <th class="th-secondary">
                                    <span class="th-content">
                                        <i class="bi bi-person-badge"></i>
                                        Operacional Principal
                                    </span>
                                </th>
                                <th class="th-secondary">Status</th>
                                <th class="th-secondary">Cadastro</th>
                                <th class="th-actions">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($clientes as $cliente)
                                <tr class="table-row {{ $cliente->trashed() ? 'row-inactive' : '' }}">
                                    <td class="td-primary">
                                        <div class="cliente-cell">
                                            <div class="cliente-main-info">
                                                <span class="cliente-name-table">{{ $cliente->razao_social }}</span>
                                                @if ($cliente->nome_fantasia)
                                                    <span class="cliente-fantasia">{{ $cliente->nome_fantasia }}</span>
                                                @endif
                                            </div>
                                            @if ($cliente->nome_cliente)
                                                <div class="cliente-extra-info">
                                                    <i class="bi bi-person"></i>
                                                    <span>{{ $cliente->nome_cliente }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="td-secondary">
                                        <span class="cnpj-info">
                                            {{ substr($cliente->cnpj, 0, 2) }}.{{ substr($cliente->cnpj, 2, 3) }}.{{ substr($cliente->cnpj, 5, 3) }}/{{ substr($cliente->cnpj, 8, 4) }}-{{ substr($cliente->cnpj, 12, 2) }}
                                        </span>
                                    </td>

                                    <td class="td-secondary">
                                        <div class="contact-info">
                                            @if ($cliente->telefone)
                                                <span class="contact-item">
                                                    <i class="bi bi-telephone"></i>
                                                    {{ $cliente->telefone }}
                                                </span>
                                            @endif
                                            @if ($cliente->email_contato)
                                                <span class="contact-item">
                                                    <i class="bi bi-envelope"></i>
                                                    {{ $cliente->email_contato }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="td-secondary">
                                        @php
                                            $operacionalPrincipal = $cliente->operacionalPrincipal->first();
                                        @endphp

                                        @if ($operacionalPrincipal)
                                            <div class="operacional-principal-cell">
                                                <div class="operacional-avatar-small">
                                                    {{ substr($operacionalPrincipal->nome_completo, 0, 1) }}{{ substr(explode(' ', $operacionalPrincipal->nome_completo)[1] ?? '', 0, 1) }}
                                                </div>
                                                <div class="operacional-info-small">
                                                    <span
                                                        class="operacional-name-small">{{ $operacionalPrincipal->nome_completo }}</span>
                                                    @if ($operacionalPrincipal->user && $operacionalPrincipal->user->email)
                                                        <span
                                                            class="operacional-email-small">{{ $operacionalPrincipal->user->email }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <span class="no-operacional">
                                                <i class="bi bi-dash-circle"></i>
                                                Não definido
                                            </span>
                                        @endif
                                    </td>

                                    <td class="td-secondary">
                                        <span
                                            class="status-badge {{ $cliente->trashed() ? 'status-inactive' : 'status-active' }}">
                                            {{ $cliente->trashed() ? 'Inativo' : 'Ativo' }}
                                        </span>
                                    </td>

                                    <td class="td-secondary">
                                        <span class="date-info">
                                            {{ $cliente->created_at->format('d/m/Y') }}
                                            <span class="date-relative">{{ $cliente->created_at->diffForHumans() }}</span>
                                        </span>
                                    </td>

                                    <td class="td-actions">
                                        <div class="action-buttons">
                                            <button type="button" class="action-btn action-view btn-view-cliente"
                                                data-cliente-id="{{ $cliente->id }}" title="Visualizar">
                                                <i class="bi bi-eye"></i>
                                            </button>

                                            @if ($cliente->trashed())
                                                @hasrole('admin')
                                                    <form action="{{ route('clientes.restore', $cliente->id) }}"
                                                        method="POST" class="d-inline cliente-restore-form">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="action-btn action-restore"
                                                            title="Restaurar">
                                                            <i class="bi bi-arrow-counterclockwise"></i>
                                                        </button>
                                                    </form>
                                                @endhasrole
                                            @else
                                                @hasanyrole('admin|gerencial|operacional')
                                                    <a href="{{ route('clientes.edit', $cliente) }}"
                                                        class="action-btn action-edit" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form action="{{ route('clientes.destroy', $cliente) }}" method="POST"
                                                        class="d-inline cliente-delete-form"
                                                        data-cliente-name="{{ $cliente->razao_social }}"
                                                        data-cliente-cnpj="{{ substr($cliente->cnpj, 0, 2) }}.{{ substr($cliente->cnpj, 2, 3) }}.{{ substr($cliente->cnpj, 5, 3) }}/{{ substr($cliente->cnpj, 8, 4) }}-{{ substr($cliente->cnpj, 12, 2) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="action-btn action-delete"
                                                            title="Excluir">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                @endhasanyrole
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @include('layouts.trade-pagination', ['paginator' => $clientes])
            @else
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="bi bi-building"></i>
                    </div>
                    <h3 class="empty-title">Nenhum cliente encontrado</h3>
                    <p class="empty-text">
                        Não há clientes cadastrados com os filtros selecionados.
                    </p>
                    <a href="{{ route('clientes.index') }}" class="btn-empty">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Limpar Filtros</span>
                    </a>
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="clienteModal" tabindex="-1" aria-labelledby="clienteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clienteModalLabel">
                        <i class="bi bi-building"></i>
                        Detalhes do Cliente
                    </h5>
                    <button type="button" class="btn-close-modal" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body" id="clienteModalBody">
                    <div class="loading-container">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="loading-text">Carregando dados do cliente...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {

            function getCurrentFilters() {
                const params = new URLSearchParams(window.location.search);
                return {
                    search: params.get('search') || '',
                    status: params.get('status') || ''
                };
            }

            function buildUrl(newFilters) {
                const filters = {
                    ...getCurrentFilters(),
                    ...newFilters
                };
                const params = new URLSearchParams();

                if (filters.search) params.append('search', filters.search);
                if (filters.status) params.append('status', filters.status);

                return `{{ route('clientes.index') }}${params.toString() ? '?' + params.toString() : ''}`;
            }
            $('.search-input').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    $('#filterForm').submit();
                }
            });

            $('.search-clear').on('click', function() {
                $('.search-input').val('');
                $('#filterForm').submit();
            });

            $('.remove-filter').on('click', function() {
                const filter = $(this).data('filter');
                const newFilters = getCurrentFilters();
                newFilters[filter] = '';
                window.location.href = buildUrl(newFilters);
            });

            $('.stat-clickable').on('click', function() {
                const filter = $(this).data('filter');
                const newFilters = getCurrentFilters();

                $('.stat-clickable').removeClass('active');
                $(this).addClass('active');

                $(this).css('transform', 'scale(0.95)');

                setTimeout(() => {
                    $(this).css('transform', '');

                    if (filter === 'all') {
                        newFilters.status = '';
                    } else {
                        newFilters.status = filter;
                    }

                    window.location.href = buildUrl(newFilters);
                }, 200);
            });

            $('.btn-view-cliente').on('click', function() {
                const clienteId = $(this).data('cliente-id');
                const modal = new bootstrap.Modal(document.getElementById('clienteModal'));

                modal.show();

                $('#clienteModal').on('shown.bs.modal', function() {
                    $(this).removeAttr('aria-hidden');
                });

                $('#clienteModal').on('hide.bs.modal', function() {
                    $(this).attr('aria-hidden', 'true');
                });

                $.ajax({
                    url: `/clientes/${clienteId}/detalhes`,
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            renderClienteDetails(response);
                        }
                    },
                    error: function(xhr) {
                        console.error('Erro AJAX:', xhr);
                        $('#clienteModalBody').html(`
                            <div class="empty-message">
                                <i class="bi bi-exclamation-triangle"></i>
                                <p>Erro ao carregar os dados do cliente.</p>
                            </div>
                        `);
                        toastr.error('Erro ao carregar dados do cliente');
                    }
                });
            });

            function renderClienteDetails(data) {
                const cliente = data.cliente;
                let html = '';

                if (data.totalPis !== undefined) {
                    html += `
                        <div class="cliente-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-graph-up"></i>
                                Estatísticas de PIs
                            </h3>
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-card-value">${data.totalPis}</div>
                                    <div class="stat-card-label">Total de PIs</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-card-value" style="color: var(--success-color);">${data.pisConcluidos}</div>
                                    <div class="stat-card-label">PIs Concluídos</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-card-value" style="color: var(--warning-color);">${data.pisPendentes}</div>
                                    <div class="stat-card-label">PIs Pendentes</div>
                                </div>
                            </div>
                        </div>
                    `;
                }

                html += `
                    <div class="cliente-details-section">
                        <h3 class="section-title">
                            <i class="bi bi-info-circle-fill"></i>
                            Dados Gerais
                        </h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-building"></i> Razão Social</div>
                                <div class="detail-value">${cliente.razao_social}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-shop"></i> Nome Fantasia</div>
                                <div class="detail-value ${!cliente.nome_fantasia ? 'empty' : ''}">${cliente.nome_fantasia || 'Não informado'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-person"></i> Nome do Cliente</div>
                                <div class="detail-value ${!cliente.nome_cliente ? 'empty' : ''}">${cliente.nome_cliente || 'Não informado'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-card-text"></i> CNPJ</div>
                                <div class="detail-value">${formatCNPJ(cliente.cnpj)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-file-text"></i> Inscrição Estadual</div>
                                <div class="detail-value ${!cliente.inscricao_estadual ? 'empty' : ''}">${cliente.inscricao_estadual || 'Não informado'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-file-text"></i> Inscrição Municipal</div>
                                <div class="detail-value ${!cliente.inscricao_municipal ? 'empty' : ''}">${cliente.inscricao_municipal || 'Não informado'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-geo-alt"></i> Praça</div>
                                <div class="detail-value ${!cliente.praca ? 'empty' : ''}">${cliente.praca || 'Não informado'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-telephone"></i> Telefone</div>
                                <div class="detail-value ${!cliente.telefone ? 'empty' : ''}">${cliente.telefone || 'Não informado'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-envelope"></i> E-mail</div>
                                <div class="detail-value ${!cliente.email_contato ? 'empty' : ''}">${cliente.email_contato || 'Não informado'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-globe"></i> Site</div>
                                <div class="detail-value ${!cliente.site ? 'empty' : ''}">${cliente.site || 'Não informado'}</div>
                            </div>
                        </div>
                    </div>
                `;

                if (cliente.enderecos && cliente.enderecos.length > 0) {
                    const endereco = cliente.enderecos[0];
                    html += `
                        <div class="cliente-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-house-fill"></i>
                                Endereço
                            </h3>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-mailbox"></i> CEP</div>
                                    <div class="detail-value">${formatCEP(endereco.cep)}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-signpost"></i> Logradouro</div>
                                    <div class="detail-value">${endereco.logradouro || 'Não informado'}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-hash"></i> Número</div>
                                    <div class="detail-value">${endereco.numero || 'Não informado'}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-building"></i> Complemento</div>
                                    <div class="detail-value ${!endereco.complemento ? 'empty' : ''}">${endereco.complemento || 'Não informado'}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-pin-map"></i> Bairro</div>
                                    <div class="detail-value">${endereco.bairro || 'Não informado'}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-geo-alt"></i> Cidade</div>
                                    <div class="detail-value">${endereco.cidade || 'Não informado'}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-map"></i> Estado</div>
                                    <div class="detail-value">${endereco.estado || 'Não informado'}</div>
                                </div>
                            </div>
                        </div>
                    `;
                }

                if (cliente.operacionais && cliente.operacionais.length > 0) {
                    html += `
                        <div class="cliente-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-people-fill"></i>
                                Operacionais/Gerentes Responsáveis (${cliente.operacionais.length})
                            </h3>
                    `;

                    cliente.operacionais.forEach(op => {
                        if (!op || !op.nome_completo) {
                            console.warn('Operacional com dados incompletos ignorado', op);
                            return;
                        }

                        const iniciais = op.nome_completo.split(' ')
                            .filter(n => n.length > 0)
                            .map(n => n[0])
                            .slice(0, 2)
                            .join('')
                            .toUpperCase() || 'OP';

                        const isPrincipal = op.pivot && op.pivot.principal;
                        const email = (op.user && op.user.email) ? op.user.email : 'Email não disponível';

                        html += `
                            <div class="operacional-card ${isPrincipal ? 'principal' : ''}">
                                <div class="operacional-info">
                                    <div class="operacional-avatar">${iniciais}</div>
                                    <div class="operacional-details">
                                        <div class="operacional-nome">${op.nome_completo}</div>
                                        <div class="operacional-email">
                                            <i class="bi bi-envelope"></i>
                                            ${email}
                                        </div>
                                    </div>
                                </div>
                                ${isPrincipal ? '<div class="badge-principal"><i class="bi bi-star-fill"></i> Principal</div>' : ''}
                            </div>
                        `;
                    });

                    html += `</div>`;
                } else {
                    html += `
                        <div class="cliente-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-people-fill"></i>
                                Operacionais/Gerentes Responsáveis
                            </h3>
                            <div class="empty-message">
                                <i class="bi bi-person-x"></i>
                                <p>Nenhum operacional vinculado a este cliente</p>
                            </div>
                        </div>
                    `;
                }

                if (cliente.cliente_documentos_requeridos && cliente.cliente_documentos_requeridos.length > 0) {
                    html += `
                        <div class="cliente-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-file-earmark-text-fill"></i>
                                Documentos Requeridos (${cliente.cliente_documentos_requeridos.length})
                            </h3>
                    `;

                    cliente.cliente_documentos_requeridos.forEach(docReq => {
                        const tipoDoc = docReq.tipo_documento;
                        const validar = tipoDoc.validar;
                        const validadeIndeterminada = tipoDoc.validade_dias === 0;

                        let validadeTexto = '';
                        let validadeIcone = '';
                        let comportamentoTexto = '';
                        let comportamentoCor = '';
                        let comportamentoIcone = '';

                        if (!validar) {
                            validadeTexto = 'Sem Controle de Validade';
                            validadeIcone = 'bi-x-circle';
                            comportamentoTexto = 'Uso Único';
                            comportamentoCor = '#6b7280';
                            comportamentoIcone = 'bi-file-earmark';
                        } else if (validadeIndeterminada) {
                            validadeTexto = 'Validade Indeterminada';
                            validadeIcone = 'bi-infinity';
                            comportamentoTexto = 'Reutilizável Sempre';
                            comportamentoCor = '#0284c7';
                            comportamentoIcone = 'bi-arrow-repeat';
                        } else {
                            const meses = Math.floor(tipoDoc.validade_dias / 30);
                            const anos = (tipoDoc.validade_dias / 365).toFixed(1);
                            if (tipoDoc.validade_dias >= 365) {
                                validadeTexto = `${anos} ${anos == 1 ? 'ano' : 'anos'}`;
                            } else if (tipoDoc.validade_dias >= 30) {
                                validadeTexto = `${meses} ${meses == 1 ? 'mês' : 'meses'}`;
                            } else {
                                validadeTexto =
                                    `${tipoDoc.validade_dias} ${tipoDoc.validade_dias == 1 ? 'dia' : 'dias'}`;
                            }
                            validadeIcone = 'bi-calendar-check';
                            comportamentoTexto = 'Reutilizável até Vencer';
                            comportamentoCor = '#f59e0b';
                            comportamentoIcone = 'bi-clock-history';
                        }

                        html += `
                            <div class="documento-card-enhanced">
                                <div class="documento-main-content">
                                    <div class="documento-icon-wrapper">
                                        <i class="bi bi-file-earmark-text-fill"></i>
                                    </div>
                                    <div class="documento-text-content">
                                        <div class="documento-nome-enhanced">${tipoDoc.nome}</div>
                                        ${tipoDoc.descricao ? `<div class="documento-descricao">${tipoDoc.descricao}</div>` : ''}
                                        <div class="documento-meta-info">
                                            <div class="meta-item">
                                                <i class="bi ${validadeIcone}"></i>
                                                <span>${validadeTexto}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="documento-badges-wrapper">
                                    <div class="badge-comportamento" style="background: linear-gradient(135deg, ${comportamentoCor}15, ${comportamentoCor}08); color: ${comportamentoCor}; border: 1px solid ${comportamentoCor}30;">
                                        <i class="bi ${comportamentoIcone}"></i>
                                        <span>${comportamentoTexto}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    html += `
                            <div class="info-box-docs">
                                <div style="display: flex; gap: 0.75rem;">
                                    <i class="bi bi-info-circle-fill" style="color: var(--info-color); font-size: 1.25rem; margin-top: 0.125rem;"></i>
                                    <div style="flex: 1; color: var(--text-color); font-size: 0.875rem; line-height: 1.6;">
                                        <strong style="display: block; margin-bottom: 0.5rem; color: #1e40af;">Tipos de Documentos:</strong>
                                        <ul style="margin: 0; padding-left: 1.25rem;">
                                            <li><strong>Uso Único:</strong> Cada PI requer novo upload. Não há controle de prazo.</li>
                                            <li><strong>Reutilizável até Vencer:</strong> Pode ser usado em várias PIs até expirar.</li>
                                            <li><strong>Reutilizável Sempre:</strong> Documento permanente, usado em todas PIs.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    html += `
                        <div class="cliente-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-file-earmark-text-fill"></i>
                                Documentos Requeridos
                            </h3>
                            <div class="empty-message">
                                <i class="bi bi-files"></i>
                                <p>Nenhum documento requerido cadastrado</p>
                            </div>
                        </div>
                    `;
                }

                if (data.fornecedores && data.fornecedores.length > 0) {
                    html += `
                        <div class="cliente-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-collection-play"></i>
                                Fornecedores Vinculados (${data.fornecedores.length})
                            </h3>
                            <div class="badge-list">
                    `;

                    data.fornecedores.forEach(fornecedor => {
                        html += `
                            <span class="badge-item">
                                <i class="bi bi-building"></i>
                                ${fornecedor.razao_social}
                            </span>
                        `;
                    });

                    html += `
                            </div>
                        </div>
                    `;
                }

                if (cliente.observacoes) {
                    html += `
                        <div class="cliente-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-sticky-fill"></i>
                                Observações
                            </h3>
                            <div class="detail-item">
                                <div class="detail-value">${escapeHtml(cliente.observacoes)}</div>
                            </div>
                        </div>
                    `;
                }

                $('#clienteModalBody').html(html);
            }

            function formatCNPJ(cnpj) {
                if (!cnpj) return 'Não informado';
                return cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
            }

            function formatCEP(cep) {
                if (!cep) return 'Não informado';
                return cep.replace(/^(\d{5})(\d{3})/, '$1-$2');
            }

            $('.cliente-delete-form').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const clienteName = $(form).data('cliente-name');
                const clienteCnpj = $(form).data('cliente-cnpj');

                Swal.fire({
                    title: 'Confirmar Inativação',
                    html: `
                        <div style="text-align: left;">
                            <p style="margin-bottom: 1.5rem; font-size: 1.05rem; color: var(--text-color); line-height: 1.6;">
                                <strong>Deseja realmente inativar este cliente?</strong>
                            </p>
                            <div class="cliente-info-box">
                                <p><strong>Razão Social:</strong> ${clienteName}</p>
                                <p><strong>CNPJ:</strong> ${clienteCnpj}</p>
                            </div>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-trash"></i> Sim, inativar',
                    cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                    customClass: {
                        confirmButton: 'swal-confirm-btn',
                        cancelButton: 'swal-cancel-btn'
                    },
                    buttonsStyling: false,
                    width: '600px',
                    padding: '2rem'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            $('.cliente-restore-form').on('submit', function(e) {
                e.preventDefault();
                const form = this;

                Swal.fire({
                    title: 'Confirmar Restauração',
                    html: `
                        <div style="text-align: left;">
                            <p style="font-size: 1.05rem; color: var(--text-color); line-height: 1.6;">
                                Deseja realmente restaurar este cliente? Todos os dados relacionados serão reativados.
                            </p>
                            <div class="info-box">
                                <p>
                                    <i class="bi bi-info-circle-fill"></i>
                                    <span>O cliente poderá ser utilizado novamente após a restauração.</span>
                                </p>
                            </div>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Sim, restaurar',
                    cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                    customClass: {
                        confirmButton: 'swal-confirm-btn swal-confirm-restore',
                        cancelButton: 'swal-cancel-btn'
                    },
                    buttonsStyling: false,
                    width: '600px',
                    padding: '2rem'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            @if (session('success'))
                toastr.success('{{ session('success') }}');
            @elseif (session('error'))
                toastr.error('{{ session('error') }}');
            @elseif (session('warning'))
                toastr.warning('{{ session('warning') }}');
            @endif
        });
    </script>
@endpush

@push('styles')
    <link href="{{ asset('css/pages/cliente.css') }}" rel="stylesheet">
    <style>
        .modal.fade {
            transition: opacity 0.3s ease;
        }

        .modal.fade .modal-dialog {
            transition: transform 0.3s ease;
        }

        .modal.show .modal-dialog {
            transform: none;
        }

        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .modal-backdrop.show {
            opacity: 1;
        }

        .modal-dialog {
            max-width: 1200px;
        }

        .modal-content {
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            background: var(--bg-card);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            border-radius: 16px 16px 0 0;
            padding: 1.75rem 2rem;
            border-bottom: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0;
            color: white;
        }

        .modal-title i {
            font-size: 1.75rem;
        }

        .btn-close-modal {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            padding: 0;
        }

        .btn-close-modal:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .btn-close-modal:active {
            transform: scale(0.95);
        }

        .btn-close-modal i {
            font-size: 1.25rem;
        }

        .modal-body {
            padding: 2rem;
            max-height: calc(100vh - 200px);
            background: var(--bg-card);
        }

        .loading-container {
            text-align: center;
            padding: 4rem 2rem;
        }

        .loading-container .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: 0.3rem;
            color: var(--primary-color);
        }

        .loading-text {
            margin-top: 1.5rem;
            color: var(--text-muted);
            font-size: 1.05rem;
            font-weight: 500;
        }

        .cliente-details-section {
            margin-bottom: 2.5rem;
        }

        .cliente-details-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            font-size: 1.375rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.875rem;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: var(--primary-color);
            font-size: 1.5rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.25rem;
        }

        .detail-item {
            background: var(--bg-body);
            padding: 1.25rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
        }

        .detail-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(0, 94, 130, 0.08);
            transform: translateY(-2px);
        }

        .detail-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-label i {
            font-size: 0.875rem;
            opacity: 0.8;
        }

        .detail-value {
            font-size: 1.0625rem;
            color: var(--text-color);
            font-weight: 600;
            word-break: break-word;
        }

        .detail-value.empty {
            color: var(--text-muted);
            font-style: italic;
            font-weight: 400;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: var(--bg-body);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            text-align: center;
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 94, 130, 0.08);
        }

        .stat-card-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stat-card-label {
            font-size: 0.9375rem;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .operacional-card {
            background: var(--bg-body);
            padding: 1.25rem 1.5rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s ease;
        }

        .operacional-card:last-child {
            margin-bottom: 0;
        }

        .operacional-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(0, 94, 130, 0.08);
            transform: translateX(4px);
        }

        .operacional-card.principal {
            border-color: var(--success-color);
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(16, 185, 129, 0.02) 100%);
        }

        .operacional-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .operacional-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.125rem;
            box-shadow: 0 4px 12px rgba(0, 94, 130, 0.2);
        }

        .operacional-card.principal .operacional-avatar {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .operacional-details {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .operacional-nome {
            font-weight: 700;
            color: var(--text-color);
            font-size: 1rem;
        }

        .operacional-email {
            font-size: 0.875rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .operacional-email i {
            font-size: 0.75rem;
        }

        .badge-principal {
            padding: 0.375rem 0.875rem;
            border-radius: 20px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            font-size: 0.8125rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.375rem;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .badge-principal i {
            font-size: 0.875rem;
        }

        .documento-card-enhanced {
            background: var(--bg-body);
            border: 2px solid var(--border-color);
            border-radius: 14px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .documento-card-enhanced:last-child {
            margin-bottom: 0;
        }

        .documento-card-enhanced::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--primary-light) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .documento-card-enhanced:hover {
            border-color: var(--primary-color);
            box-shadow: 0 6px 20px rgba(0, 94, 130, 0.12);
            transform: translateY(-2px);
        }

        .documento-card-enhanced:hover::before {
            opacity: 1;
        }

        .documento-main-content {
            display: flex;
            gap: 1.25rem;
            margin-bottom: 1rem;
        }

        .documento-icon-wrapper {
            width: 56px;
            height: 56px;
            min-width: 56px;
            border-radius: 14px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 16px rgba(245, 158, 11, 0.35);
        }

        .documento-text-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .documento-nome-enhanced {
            font-weight: 700;
            font-size: 1.125rem;
            color: var(--text-color);
            line-height: 1.3;
        }

        .documento-descricao {
            font-size: 0.875rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .documento-meta-info {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.25rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-color);
            font-weight: 600;
        }

        .meta-item i {
            font-size: 1rem;
            color: var(--primary-color);
        }

        .documento-badges-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .badge-comportamento {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 700;
            transition: all 0.2s ease;
        }

        .badge-comportamento:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .badge-comportamento i {
            font-size: 1rem;
        }

        .info-box-docs {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            padding: 1.25rem;
            border-radius: 12px;
            margin-top: 1.5rem;
            border-left: 4px solid var(--info-color);
        }

        .badge-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .badge-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1rem;
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.1) 0%, rgba(0, 94, 130, 0.05) 100%);
            color: var(--primary-color);
            border-radius: 10px;
            font-size: 0.9375rem;
            font-weight: 600;
            border: 1px solid rgba(0, 94, 130, 0.2);
            transition: all 0.2s ease;
        }

        .badge-item:hover {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.15) 0%, rgba(0, 94, 130, 0.1) 100%);
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .badge-item i {
            font-size: 1rem;
        }

        .empty-message {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-muted);
            background: var(--bg-body);
            border-radius: 12px;
            border: 2px dashed var(--border-color);
        }

        .empty-message i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.4;
            color: var(--text-muted);
        }

        .empty-message p {
            margin: 0;
            font-size: 1.0625rem;
            font-weight: 500;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .cliente-details-section {
            animation: modalFadeIn 0.4s ease;
        }

        .cliente-details-section:nth-child(1) {
            animation-delay: 0.05s;
        }

        .cliente-details-section:nth-child(2) {
            animation-delay: 0.1s;
        }

        .cliente-details-section:nth-child(3) {
            animation-delay: 0.15s;
        }

        .cliente-details-section:nth-child(4) {
            animation-delay: 0.2s;
        }

        .cliente-details-section:nth-child(5) {
            animation-delay: 0.25s;
        }

        .operacional-principal-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .operacional-avatar-small {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.875rem;
            box-shadow: 0 2px 8px rgba(0, 94, 130, 0.2);
            text-transform: uppercase;
        }

        .operacional-info-small {
            display: flex;
            flex-direction: column;
            gap: 0.125rem;
            min-width: 0;
        }

        .operacional-name-small {
            font-weight: 600;
            font-size: 0.9375rem;
            color: var(--text-color);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .operacional-email-small {
            font-size: 0.8125rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .no-operacional {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            color: var(--text-muted);
            font-size: 0.875rem;
            font-style: italic;
        }

        .no-operacional i {
            font-size: 0.875rem;
            opacity: 0.7;
        }

        /* Responsivo para a nova coluna */
        @media (max-width: 1400px) {
            .operacional-email-small {
                display: none;
            }
        }

        @media (max-width: 1200px) {
            .operacional-principal-cell {
                gap: 0.5rem;
            }

            .operacional-avatar-small {
                width: 32px;
                height: 32px;
                min-width: 32px;
                font-size: 0.8125rem;
            }

            .operacional-name-small {
                font-size: 0.875rem;
            }
        }

        @media (max-width: 992px) {

            /* Ocultar coluna de operacional principal em telas menores */
            .data-table th:nth-child(4),
            .data-table td:nth-child(4) {
                display: none;
            }
        }

        [data-theme="dark"] .operacional-avatar-small {
            box-shadow: 0 2px 8px rgba(56, 189, 248, 0.3);
        }

        .swal2-popup {
            font-family: inherit !important;
            border-radius: 16px !important;
            padding: 2rem !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15) !important;
        }

        .swal2-title {
            font-size: 1.75rem !important;
            font-weight: 700 !important;
            color: #1f2937 !important;
            margin-bottom: 1.5rem !important;
            padding: 0 1rem !important;
        }

        .swal2-html-container {
            margin: 0 !important;
            padding: 0 !important;
            font-size: 1rem !important;
            line-height: 1.6 !important;
            color: #4b5563 !important;
        }

        .swal2-icon {
            margin: 1.5rem auto !important;
            border-width: 3px !important;
        }

        .swal2-icon.swal2-warning {
            border-color: #fbbf24 !important;
            color: #f59e0b !important;
        }

        .swal2-icon.swal2-error {
            border-color: #f87171 !important;
            color: #ef4444 !important;
        }

        .swal2-icon.swal2-question {
            border-color: #60a5fa !important;
            color: #3b82f6 !important;
        }

        .swal2-icon.swal2-success {
            border-color: #34d399 !important;
            color: #10b981 !important;
        }

        .swal2-actions {
            margin-top: 2rem !important;
            gap: 1rem !important;
            padding: 0 1rem !important;
        }

        .swal-confirm-btn,
        .swal-cancel-btn {
            margin: 0 !important;
            padding: 1rem 2rem !important;
            border-radius: 10px !important;
            font-weight: 600 !important;
            font-size: 1rem !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.625rem !important;
            transition: all 0.3s ease !important;
            border: none !important;
            min-width: 140px !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
        }

        .swal-confirm-btn i,
        .swal-cancel-btn i {
            font-size: 1.125rem !important;
        }

        .swal-confirm-btn {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            color: white !important;
        }

        .swal-confirm-btn:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.35) !important;
        }

        .swal-confirm-btn:active {
            transform: translateY(0) !important;
        }

        .swal-cancel-btn {
            background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%) !important;
            color: white !important;
        }

        .swal-cancel-btn:hover {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(107, 114, 128, 0.3) !important;
        }

        .swal-cancel-btn:active {
            transform: translateY(0) !important;
        }

        .swal2-styled:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3) !important;
        }

        .swal-confirm-restore {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        }

        .swal-confirm-restore:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.35) !important;
        }

        .cliente-info-box,
        .fornecedor-info-box {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            padding: 1.25rem;
            border-radius: 10px;
            margin: 1.25rem 0;
            border-left: 4px solid #f59e0b;
        }

        .cliente-info-box p,
        .fornecedor-info-box p {
            margin: 0.5rem 0;
            color: var(--text-color);
            font-size: 0.9375rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cliente-info-box p:first-child,
        .fornecedor-info-box p:first-child {
            margin-top: 0;
        }

        .cliente-info-box p:last-child,
        .fornecedor-info-box p:last-child {
            margin-bottom: 0;
        }

        .cliente-info-box strong,
        .fornecedor-info-box strong {
            color: #1f2937;
            min-width: 110px;
            font-weight: 700;
        }

        .info-box {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            padding: 1.25rem;
            border-radius: 10px;
            margin-top: 1.5rem;
            border-left: 4px solid #3b82f6;
        }

        .info-box p {
            margin: 0;
            color: #4b5563;
            font-size: 0.9375rem;
            display: flex;
            align-items: flex-start;
            gap: 0.625rem;
            line-height: 1.6;
        }

        .info-box i {
            color: #3b82f6;
            font-size: 1.125rem;
            margin-top: 0.125rem;
        }

        .swal2-container.swal2-backdrop-show {
            background: rgba(0, 0, 0, 0.5) !important;
            backdrop-filter: blur(4px);
        }

        @keyframes swalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(20px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .swal2-show {
            animation: swalFadeIn 0.3s ease !important;
        }

        [data-theme="dark"] .modal-content {
            background: #1f2937 !important;
            border-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .modal-body {
            background: #1f2937 !important;
        }

        [data-theme="dark"] .detail-item,
        [data-theme="dark"] .stat-card,
        [data-theme="dark"] .operacional-card,
        [data-theme="dark"] .documento-card-enhanced,
        [data-theme="dark"] .empty-message {
            background: #111827 !important;
            border-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .section-title {
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .swal2-popup {
            background: #1f2937 !important;
        }

        [data-theme="dark"] .swal2-title {
            color: #f9fafb !important;
        }

        [data-theme="dark"] .swal2-html-container {
            color: #d1d5db !important;
        }

        [data-theme="dark"] .cliente-info-box,
        [data-theme="dark"] .fornecedor-info-box {
            background: rgba(245, 158, 11, 0.1) !important;
            border-left-color: #fbbf24 !important;
        }

        [data-theme="dark"] .cliente-info-box p,
        [data-theme="dark"] .fornecedor-info-box p {
            color: #d1d5db !important;
        }

        [data-theme="dark"] .cliente-info-box strong,
        [data-theme="dark"] .fornecedor-info-box strong {
            color: #f9fafb !important;
        }

        [data-theme="dark"] .info-box {
            background: rgba(59, 130, 246, 0.1) !important;
            border-left-color: #60a5fa !important;
        }

        [data-theme="dark"] .info-box p {
            color: #d1d5db !important;
        }

        [data-theme="dark"] .badge-item {
            background: rgba(56, 189, 248, 0.15) !important;
            color: #38bdf8 !important;
            border-color: rgba(56, 189, 248, 0.3);
        }

        [data-theme="dark"] .badge-item:hover {
            background: rgba(56, 189, 248, 0.2) !important;
            border-color: #38bdf8;
        }

        [data-theme="dark"] .info-box-docs {
            background: rgba(8, 145, 178, 0.15) !important;
            border-left-color: #0891b2 !important;
        }

        [data-theme="dark"] .info-box-docs div {
            color: #d1d5db !important;
        }

        [data-theme="dark"] .info-box-docs strong {
            color: #38bdf8 !important;
        }

        [data-theme="dark"] .documento-descricao {
            color: #9ca3af !important;
        }

        @media (max-width: 992px) {
            .modal-dialog {
                max-width: calc(100% - 2rem);
                margin: 1rem;
            }

            .modal-body {
                padding: 1.5rem;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .modal-header {
                padding: 1.5rem;
            }

            .modal-title {
                font-size: 1.25rem;
            }

            .modal-title i {
                font-size: 1.5rem;
            }

            .btn-close-modal {
                width: 36px;
                height: 36px;
            }

            .btn-close-modal i {
                font-size: 1.125rem;
            }

            .section-title {
                font-size: 1.125rem;
            }

            .section-title i {
                font-size: 1.25rem;
            }

            .operacional-card,
            .documento-card-enhanced {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .documento-main-content {
                flex-direction: row;
                width: 100%;
            }

            .documento-icon-wrapper {
                width: 48px;
                height: 48px;
                min-width: 48px;
                font-size: 1.25rem;
            }

            .documento-nome-enhanced {
                font-size: 1rem;
            }

            .documento-descricao {
                font-size: 0.8125rem;
            }

            .meta-item {
                font-size: 0.8125rem;
            }

            .badge-comportamento {
                font-size: 0.8125rem;
                padding: 0.4375rem 0.875rem;
            }

            .swal2-popup {
                padding: 1.5rem !important;
                width: calc(100% - 2rem) !important;
                max-width: 500px !important;
            }

            .swal2-title {
                font-size: 1.5rem !important;
                margin-bottom: 1.25rem !important;
            }

            .swal2-html-container {
                font-size: 0.9375rem !important;
            }

            .swal2-actions {
                flex-direction: column !important;
                width: 100% !important;
                gap: 0.75rem !important;
                margin-top: 1.5rem !important;
            }

            .swal-confirm-btn,
            .swal-cancel-btn {
                width: 100% !important;
                min-width: 100% !important;
                padding: 0.875rem 1.5rem !important;
            }

            .cliente-info-box,
            .fornecedor-info-box,
            .info-box,
            .info-box-docs {
                padding: 1rem;
            }

            .cliente-info-box p,
            .fornecedor-info-box p,
            .info-box p {
                font-size: 0.875rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.375rem;
            }

            .cliente-info-box strong,
            .fornecedor-info-box strong {
                min-width: auto;
            }

            .info-box-docs div {
                font-size: 0.8125rem;
            }

            .info-box-docs ul {
                padding-left: 1rem;
            }

            .info-box-docs li {
                margin-bottom: 0.375rem;
            }
        }

        @media (max-width: 576px) {
            .modal-content {
                border-radius: 12px;
            }

            .modal-header {
                border-radius: 12px 12px 0 0;
                padding: 1.25rem;
            }

            .modal-body {
                padding: 1.25rem;
            }

            .detail-item {
                padding: 1rem;
            }

            .operacional-card,
            .documento-card-enhanced {
                padding: 1rem 1.25rem;
            }

            .documento-card-enhanced {
                padding: 1.25rem;
            }

            .documento-main-content {
                gap: 1rem;
            }

            .documento-icon-wrapper {
                width: 44px;
                height: 44px;
                min-width: 44px;
                font-size: 1.125rem;
            }

            .documento-nome-enhanced {
                font-size: 0.9375rem;
            }

            .documento-descricao {
                font-size: 0.8125rem;
                line-height: 1.4;
            }

            .meta-item {
                font-size: 0.8125rem;
            }

            .meta-item i {
                font-size: 0.875rem;
            }

            .badge-comportamento {
                font-size: 0.8125rem;
                padding: 0.375rem 0.75rem;
            }

            .badge-comportamento i {
                font-size: 0.875rem;
            }

            .info-box-docs {
                padding: 1rem;
                margin-top: 1rem;
            }

            .info-box-docs div {
                font-size: 0.8125rem;
            }

            .info-box-docs strong {
                font-size: 0.875rem;
            }

            .info-box-docs ul {
                padding-left: 1rem;
            }

            .info-box-docs li {
                margin-bottom: 0.5rem;
                font-size: 0.8125rem;
            }

            .swal2-popup {
                padding: 1.25rem !important;
            }

            .swal2-title {
                font-size: 1.375rem !important;
                margin-bottom: 1rem !important;
            }

            .swal-confirm-btn,
            .swal-cancel-btn {
                padding: 0.75rem 1.25rem !important;
                font-size: 0.9375rem !important;
            }
        }
    </style>
@endpush