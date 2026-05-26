{{-- resources/views/fornecedores/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Fornecedores')

@section('content')
    <div class="container-fluid px-0">
        <div class="page-header">
            <div class="header-card">
                <div class="header-top">
                    <div class="title-wrapper">
                        <div class="icon-badge">
                            <i class="bi bi-collection-play"></i>
                        </div>
                        <div>
                            <h1 class="main-title">Fornecedores</h1>
                            <p class="main-subtitle">Gestão de fornecedores e parceiros comerciais</p>
                        </div>
                    </div>
                    @hasanyrole('admin|gerencial|operacional')
                        <a href="{{ route('fornecedores.create') }}" class="btn-create">
                            <i class="bi bi-plus-circle"></i>
                            <span>Novo Fornecedor</span>
                        </a>
                    @endhasanyrole
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('fornecedores.index') }}" id="filterForm">
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
                        {{ $fornecedores->total() }} {{ Str::plural('resultado', $fornecedores->total()) }}
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
                <a href="{{ route('fornecedores.index') }}" class="clear-filters-link">
                    Limpar tudo
                </a>
            </div>
        @endif

        <div class="table-container">
            @if ($fornecedores->count() > 0)
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="th-primary">
                                    <span class="th-content">
                                        <i class="bi bi-building"></i>
                                        Fornecedor
                                    </span>
                                </th>
                                <th class="th-secondary">CNPJ</th>
                                <th class="th-secondary">Contato</th>
                                <th class="th-secondary">Status</th>
                                <th class="th-secondary">Cadastro</th>
                                <th class="th-actions">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($fornecedores as $fornecedor)
                                <tr class="table-row {{ $fornecedor->trashed() ? 'row-inactive' : '' }}">
                                    <td class="td-primary">
                                        <div class="fornecedor-cell">
                                            <div class="fornecedor-main-info">
                                                <span class="fornecedor-name-table">{{ $fornecedor->razao_social }}</span>
                                                @if ($fornecedor->nome_fantasia)
                                                    <span
                                                        class="fornecedor-fantasia">{{ $fornecedor->nome_fantasia }}</span>
                                                @endif
                                            </div>
                                            @if ($fornecedor->nome_veiculo)
                                                <div class="fornecedor-extra-info">
                                                    <i class="bi bi-collection-play"></i>
                                                    <span>{{ $fornecedor->nome_veiculo }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="td-secondary">
                                        <span class="cnpj-info">
                                            {{ substr($fornecedor->cnpj, 0, 2) }}.{{ substr($fornecedor->cnpj, 2, 3) }}.{{ substr($fornecedor->cnpj, 5, 3) }}/{{ substr($fornecedor->cnpj, 8, 4) }}-{{ substr($fornecedor->cnpj, 12, 2) }}
                                        </span>
                                    </td>

                                    <td class="td-secondary">
                                        <div class="contact-info">
                                            @if ($fornecedor->telefone)
                                                <span class="contact-item">
                                                    <i class="bi bi-telephone"></i>
                                                    {{ $fornecedor->telefone }}
                                                </span>
                                            @endif
                                            @if ($fornecedor->email_contato)
                                                <span class="contact-item">
                                                    <i class="bi bi-envelope"></i>
                                                    {{ $fornecedor->email_contato }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="td-secondary">
                                        <div class="status-cell">
                                            <span
                                                class="status-badge {{ $fornecedor->trashed() ? 'status-inactive' : 'status-active' }}">
                                                {{ $fornecedor->trashed() ? 'Inativo' : 'Ativo' }}
                                            </span>
                                            @if (!$fornecedor->trashed() && $fornecedor->precisaCompletarCadastro())
                                                <span class="status-badge status-incompleto" title="Campos faltantes: {{ implode(', ', $fornecedor->getCamposFaltantes()) }}">
                                                    <i class="bi bi-exclamation-circle-fill"></i>
                                                    Cadastro Incompleto
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="td-secondary">
                                        <span class="date-info">
                                            {{ $fornecedor->created_at->format('d/m/Y') }}
                                            <span
                                                class="date-relative">{{ $fornecedor->created_at->diffForHumans() }}</span>
                                        </span>
                                    </td>

                                    <td class="td-actions">
                                        <div class="action-buttons">
                                            <button type="button" class="action-btn action-view btn-view-fornecedor"
                                                data-fornecedor-id="{{ $fornecedor->id }}" title="Visualizar">
                                                <i class="bi bi-eye"></i>
                                            </button>

                                            @if ($fornecedor->trashed())
                                                @hasrole('admin')
                                                    <form action="{{ route('fornecedores.restore', $fornecedor->id) }}"
                                                        method="POST" class="d-inline fornecedor-restore-form">
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
                                                    <a href="{{ route('fornecedores.edit', $fornecedor) }}"
                                                        class="action-btn action-edit" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                @endhasanyrole
                                                @hasanyrole('admin|gerencial')
                                                    <button type="button" class="action-btn action-resend btn-reenviar-cred"
                                                        data-fornecedor-id="{{ $fornecedor->id }}"
                                                        data-email="{{ $fornecedor->user->email ?? $fornecedor->email_contato }}"
                                                        data-nome="{{ $fornecedor->razao_social }}"
                                                        title="Reenviar Credenciais">
                                                        <i class="bi bi-envelope-arrow-up"></i>
                                                    </button>
                                                @endhasanyrole
                                                @hasanyrole('admin|gerencial|operacional')
                                                    <form action="{{ route('fornecedores.destroy', $fornecedor) }}"
                                                        method="POST" class="d-inline fornecedor-delete-form"
                                                        data-fornecedor-name="{{ $fornecedor->razao_social }}"
                                                        data-fornecedor-cnpj="{{ substr($fornecedor->cnpj, 0, 2) }}.{{ substr($fornecedor->cnpj, 2, 3) }}.{{ substr($fornecedor->cnpj, 5, 3) }}/{{ substr($fornecedor->cnpj, 8, 4) }}-{{ substr($fornecedor->cnpj, 12, 2) }}">
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

                @include('layouts.trade-pagination', ['paginator' => $fornecedores])
            @else
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="bi bi-collection-play"></i>
                    </div>
                    <h3 class="empty-title">Nenhum fornecedor encontrado</h3>
                    <p class="empty-text">
                        Não há fornecedores cadastrados com os filtros selecionados.
                    </p>
                    <a href="{{ route('fornecedores.index') }}" class="btn-empty">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Limpar Filtros</span>
                    </a>
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="fornecedorModal" tabindex="-1" aria-labelledby="fornecedorModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fornecedorModalLabel">
                        <i class="bi bi-collection-play"></i>
                        Detalhes do Fornecedor
                    </h5>
                    <button type="button" class="btn-close-modal" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body" id="fornecedorModalBody">
                    <div class="loading-container">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="loading-text">Carregando dados do fornecedor...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        Object.assign(window.AppRoutes, {
            fornecedoresIndex: "{{ route('fornecedores.index') }}",
            buscarUsuarios: "{{ route('fornecedores.buscar_usuarios') }}",
            reenviarCredenciais: "{{ route('fornecedores.reenviar_credenciais', ':id') }}",
        });

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

                return `{{ route('fornecedores.index') }}${params.toString() ? '?' + params.toString() : ''}`;
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

            const currentFilters = getCurrentFilters();

            if (currentFilters.status) {
                $(`.stat-clickable[data-filter="${currentFilters.status}"]`).addClass('active');
            } else if (!currentFilters.search) {
                $('.stat-clickable[data-filter="all"]').addClass('active');
            }

            $('.btn-view-fornecedor').on('click', function() {
                const fornecedorId = $(this).data('fornecedor-id');
                const modal = new bootstrap.Modal(document.getElementById('fornecedorModal'));

                modal.show();

                $.ajax({
                    url: `/fornecedores/${fornecedorId}/detalhes`,
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            renderFornecedorDetails(response.fornecedor, response.clientes, response);
                        }
                    },
                    error: function() {
                        $('#fornecedorModalBody').html(`
                            <div class="empty-message">
                                <i class="bi bi-exclamation-triangle"></i>
                                <p>Erro ao carregar os dados do fornecedor.</p>
                            </div>
                        `);
                        toastr.error('Erro ao carregar dados do fornecedor');
                    }
                });
            });

            function renderFornecedorDetails(fornecedor, clientes, response) {
                let html = '';

                html += `
                    <div class="fornecedor-details-section">
                        <h3 class="section-title">
                            <i class="bi bi-info-circle-fill"></i>
                            Dados Gerais
                        </h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-building"></i> Razão Social</div>
                                <div class="detail-value">${fornecedor.razao_social}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-shop"></i> Nome Fantasia</div>
                                <div class="detail-value ${!fornecedor.nome_fantasia ? 'empty' : ''}">${fornecedor.nome_fantasia || 'Não informado'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-collection-play"></i> Nome do Veículo</div>
                                <div class="detail-value ${!fornecedor.nome_veiculo ? 'empty' : ''}">${fornecedor.nome_veiculo || 'Não informado'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-card-text"></i> CNPJ</div>
                                <div class="detail-value">${formatCNPJ(fornecedor.cnpj)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-telephone"></i> Telefone</div>
                                <div class="detail-value ${!fornecedor.telefone ? 'empty' : ''}">${fornecedor.telefone || 'Não informado'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-printer"></i> Fax</div>
                                <div class="detail-value ${!fornecedor.fax ? 'empty' : ''}">${fornecedor.fax || 'Não informado'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-envelope"></i> E-mail</div>
                                <div class="detail-value ${!fornecedor.email_contato ? 'empty' : ''}">${fornecedor.email_contato || 'Não informado'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-globe"></i> Site</div>
                                <div class="detail-value ${!fornecedor.site ? 'empty' : ''}">${fornecedor.site || 'Não informado'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-geo-alt"></i> Praça</div>
                                <div class="detail-value ${!fornecedor.praca ? 'empty' : ''}">${fornecedor.praca || 'Não informado'}</div>
                            </div>
                        </div>
                    </div>
                `;

                if (response && response.cadastro_incompleto) {
                    html += `
                        <div class="fornecedor-details-section">
                            <div class="cadastro-incompleto-alert">
                                <div class="cadastro-incompleto-icon">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                </div>
                                <div class="cadastro-incompleto-content">
                                    <strong>Cadastro Incompleto</strong>
                                    <p>Este fornecedor ainda não completou todos os dados obrigatórios do cadastro.</p>
                                    <div class="cadastro-incompleto-items">
                                        ${response.campos_faltantes.map(campo => `
                                            <span class="campo-faltante">
                                                <i class="bi bi-x-circle"></i>
                                                ${campo}
                                            </span>
                                        `).join('')}
                                    </div>
                                    <div class="cadastro-progresso-bar">
                                        <div class="cadastro-progresso-fill" style="width: ${response.progresso_cadastro}%"></div>
                                    </div>
                                    <span class="cadastro-progresso-text">${response.progresso_cadastro}% completo</span>
                                </div>
                            </div>
                        </div>
                    `;
                }

                if (fornecedor.enderecos && fornecedor.enderecos.length > 0) {
                    const endereco = fornecedor.enderecos[0];
                    html += `
                        <div class="fornecedor-details-section">
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

                if (fornecedor.representantes && fornecedor.representantes.length > 0) {
                    html += `
                        <div class="fornecedor-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-person-badge-fill"></i>
                                Representantes (${fornecedor.representantes.length})
                            </h3>
                    `;

                    fornecedor.representantes.forEach(rep => {
                        html += `
                            <div class="representante-card">
                                <h4 class="card-title">
                                    <i class="bi bi-person-circle"></i>
                                    ${rep.nome_completo}
                                </h4>
                                <div class="card-detail-grid">
                                    <div class="card-detail-item">
                                        <span class="card-detail-label">CPF</span>
                                        <span class="card-detail-value">${rep.cpf ? formatCPF(rep.cpf) : 'Não informado'}</span>
                                    </div>
                                    <div class="card-detail-item">
                                        <span class="card-detail-label">Telefone 1</span>
                                        <span class="card-detail-value">${rep.telefone_1 || 'Não informado'}</span>
                                    </div>
                                    <div class="card-detail-item">
                                        <span class="card-detail-label">Telefone 2</span>
                                        <span class="card-detail-value">${rep.telefone_2 || 'Não informado'}</span>
                                    </div>
                                    <div class="card-detail-item">
                                        <span class="card-detail-label">E-mail 1</span>
                                        <span class="card-detail-value">${rep.email_1 || 'Não informado'}</span>
                                    </div>
                                    <div class="card-detail-item">
                                        <span class="card-detail-label">E-mail 2</span>
                                        <span class="card-detail-value">${rep.email_2 || 'Não informado'}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    html += `</div>`;
                } else {
                    html += `
                        <div class="fornecedor-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-person-badge-fill"></i>
                                Representantes
                            </h3>
                            <div class="empty-message">
                                <i class="bi bi-person-x"></i>
                                <p>Nenhum representante cadastrado</p>
                            </div>
                        </div>
                    `;
                }

                if (fornecedor.dados_bancarios && fornecedor.dados_bancarios.length > 0) {
                    html += `
                        <div class="fornecedor-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-bank"></i>
                                Dados Bancários (${fornecedor.dados_bancarios.length})
                            </h3>
                    `;

                    fornecedor.dados_bancarios.forEach(banco => {
                        html += `
                            <div class="banco-card">
                                <h4 class="card-title">
                                    <i class="bi bi-credit-card"></i>
                                    ${banco.banco}
                                </h4>
                                <div class="card-detail-grid">
                                    <div class="card-detail-item">
                                        <span class="card-detail-label">Agência</span>
                                        <span class="card-detail-value">${banco.agencia}${banco.digito_agencia ? '-' + banco.digito_agencia : ''}</span>
                                    </div>
                                    <div class="card-detail-item">
                                        <span class="card-detail-label">Conta</span>
                                        <span class="card-detail-value">${banco.conta}${banco.digito_conta ? '-' + banco.digito_conta : ''}</span>
                                    </div>
                                    <div class="card-detail-item">
                                        <span class="card-detail-label">Tipo de Conta</span>
                                        <span class="card-detail-value">${banco.tipo_conta === 'corrente' ? 'Corrente' : 'Poupança'}</span>
                                    </div>
                                    <div class="card-detail-item">
                                        <span class="card-detail-label">Titular</span>
                                        <span class="card-detail-value">${banco.titular}</span>
                                    </div>
                                    <div class="card-detail-item">
                                        <span class="card-detail-label">CPF/CNPJ Titular</span>
                                        <span class="card-detail-value">${banco.cpf_cnpj_titular.length === 11 ? formatCPF(banco.cpf_cnpj_titular) : formatCNPJ(banco.cpf_cnpj_titular)}</span>
                                    </div>
                                    ${banco.pix_tipo ? `
                                                    <div class="card-detail-item">
                                                        <span class="card-detail-label">Tipo PIX</span>
                                                        <span class="card-detail-value">${banco.pix_tipo.toUpperCase()}</span>
                                                    </div>
                                                    <div class="card-detail-item">
                                                        <span class="card-detail-label">Chave PIX</span>
                                                        <span class="card-detail-value">${banco.pix_chave}</span>
                                                    </div>
                                                ` : ''}
                                </div>
                            </div>
                        `;
                    });

                    html += `</div>`;
                } else {
                    html += `
                        <div class="fornecedor-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-bank"></i>
                                Dados Bancários
                            </h3>
                            <div class="empty-message">
                                <i class="bi bi-credit-card-2-back"></i>
                                <p>Nenhum dado bancário cadastrado</p>
                            </div>
                        </div>
                    `;
                }

                if (fornecedor.tipo_documentos_padrao && fornecedor.tipo_documentos_padrao.length > 0) {
                    html += `
                        <div class="fornecedor-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-file-earmark-text-fill"></i>
                                Documentos Padrão (${fornecedor.tipo_documentos_padrao.length})
                            </h3>
                            <div class="badge-list">
                    `;

                    fornecedor.tipo_documentos_padrao.forEach(doc => {
                        html += `
                            <span class="tipo-doc-badge">
                                <i class="bi bi-check-circle-fill"></i>
                                ${doc.nome}
                            </span>
                        `;
                    });

                    html += `
                            </div>
                        </div>
                    `;
                } else {
                    html += `
                        <div class="fornecedor-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-file-earmark-text-fill"></i>
                                Documentos Padrão
                            </h3>
                            <div class="empty-message">
                                <i class="bi bi-file-earmark-x"></i>
                                <p>Nenhum documento padrão definido</p>
                            </div>
                        </div>
                    `;
                }

                if (clientes && clientes.length > 0) {
                    html += `
                        <div class="fornecedor-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-briefcase-fill"></i>
                                Clientes Vinculados (${clientes.length})
                            </h3>
                    `;

                    clientes.forEach(cliente => {
                        html += `
                            <div class="cliente-card">
                                <h4 class="card-title">
                                    <i class="bi bi-building"></i>
                                    ${cliente.razao_social}
                                </h4>
                                <div class="card-detail-grid">
                                    ${cliente.nome_fantasia ? `
                                                    <div class="card-detail-item">
                                                        <span class="card-detail-label">Nome Fantasia</span>
                                                        <span class="card-detail-value">${cliente.nome_fantasia}</span>
                                                    </div>
                                                ` : ''}
                                    <div class="card-detail-item">
                                        <span class="card-detail-label">CNPJ</span>
                                        <span class="card-detail-value">${formatCNPJ(cliente.cnpj)}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    html += `</div>`;
                } else {
                    html += `
                        <div class="fornecedor-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-briefcase-fill"></i>
                                Clientes Vinculados
                            </h3>
                            <div class="empty-message">
                                <i class="bi bi-briefcase"></i>
                                <p>Nenhum cliente vinculado</p>
                            </div>
                        </div>
                    `;
                }

                if (fornecedor.observacoes) {
                    html += `
                        <div class="fornecedor-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-sticky-fill"></i>
                                Observações
                            </h3>
                            <div class="detail-item">
                                <div class="detail-value">${escapeHtml(fornecedor.observacoes)}</div>
                            </div>
                        </div>
                    `;
                }

                $('#fornecedorModalBody').html(html);
            }

            function formatCNPJ(cnpj) {
                if (!cnpj) return 'Não informado';
                return cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
            }

            function formatCPF(cpf) {
                if (!cpf) return 'Não informado';
                return cpf.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            }

            function formatCEP(cep) {
                if (!cep) return 'Não informado';
                return cep.replace(/^(\d{5})(\d{3})/, '$1-$2');
            }

            $('.fornecedor-delete-form').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const fornecedorName = $(form).data('fornecedor-name');
                const fornecedorCnpj = $(form).data('fornecedor-cnpj');

                Swal.fire({
                    title: 'Confirmar Exclusão',
                    html: `
                        <div style="text-align: left;">
                            <p style="margin-bottom: 1.5rem; font-size: 1.05rem; color: var(--text-color); line-height: 1.6;">
                                <strong>Deseja realmente excluir este fornecedor?</strong>
                            </p>
                            <div class="fornecedor-info-box">
                                <p><strong>Razão Social:</strong> ${fornecedorName}</p>
                                <p><strong>CNPJ:</strong> ${fornecedorCnpj}</p>
                            </div>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-trash"></i> Sim, excluir',
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

            $('.fornecedor-restore-form').on('submit', function(e) {
                e.preventDefault();
                const form = this;

                Swal.fire({
                    title: 'Confirmar Restauração',
                    html: `
                        <div style="text-align: left;">
                            <p style="font-size: 1.05rem; color: var(--text-color); line-height: 1.6;">
                                Deseja realmente restaurar este fornecedor? Todos os dados relacionados serão reativados.
                            </p>
                            <div class="info-box">
                                <p>
                                    <i class="bi bi-info-circle-fill"></i>
                                    <span>O fornecedor poderá ser utilizado novamente após a restauração.</span>
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

            $('.btn-reenviar-cred').on('click', function() {
                const fornecedorId = $(this).data('fornecedor-id');
                const email = $(this).data('email');
                const nome = $(this).data('nome');

                Swal.fire({
                    title: 'Reenviar Credenciais',
                    html: `
                        <div style="text-align: left;">
                            <p style="margin-bottom: 1.5rem; font-size: 1.05rem; color: var(--text-primary); line-height: 1.6;">
                                Uma nova senha será gerada e enviada para o fornecedor.
                            </p>
                            <div class="fornecedor-info-box">
                                <p><strong>Fornecedor:</strong> ${nome}</p>
                                <p><strong>E-mail:</strong> ${email}</p>
                            </div>
                            <div class="info-box" style="margin-top: 1rem;">
                                <p>
                                    <i class="bi bi-exclamation-triangle-fill" style="color: var(--warning-color);"></i>
                                    <span>A senha atual será invalidada imediatamente.</span>
                                </p>
                            </div>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-envelope-arrow-up"></i> Reenviar',
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
                        const url = window.AppRoutes.reenviarCredenciais.replace(':id', fornecedorId);
                        $.post(url, { _token: $('meta[name="csrf-token"]').attr('content') })
                            .done(function(res) {
                                toastr.success(res.message || 'Credenciais reenviadas com sucesso!');
                            })
                            .fail(function(xhr) {
                                toastr.error(xhr.responseJSON?.message || 'Erro ao reenviar credenciais.');
                            });
                    }
                });
            });
        });
    </script>
@endpush

@push('styles')
    <link href="{{ asset('css/pages/fornecedor.css') }}" rel="stylesheet">
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

        .fornecedor-details-section {
            margin-bottom: 2.5rem;
        }

        .fornecedor-details-section:last-child {
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

        .representante-card,
        .banco-card,
        .cliente-card {
            background: var(--bg-body);
            padding: 1.5rem;
            border-radius: 14px;
            border: 1px solid var(--border-color);
            margin-bottom: 1.25rem;
            transition: all 0.2s ease;
        }

        .representante-card:last-child,
        .banco-card:last-child,
        .cliente-card:last-child {
            margin-bottom: 0;
        }

        .representante-card:hover,
        .banco-card:hover,
        .cliente-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 6px 16px rgba(0, 94, 130, 0.1);
            transform: translateY(-2px);
        }

        .card-title {
            font-size: 1.1875rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .card-title i {
            color: var(--primary-color);
            font-size: 1.25rem;
        }

        .card-detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }

        .card-detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }

        .card-detail-label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-detail-value {
            font-size: 0.9375rem;
            color: var(--text-color);
            font-weight: 500;
            word-break: break-word;
        }

        .badge-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .tipo-doc-badge {
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

        .tipo-doc-badge:hover {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.15) 0%, rgba(0, 94, 130, 0.1) 100%);
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .tipo-doc-badge i {
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

        .fornecedor-details-section {
            animation: modalFadeIn 0.4s ease;
        }

        .fornecedor-details-section:nth-child(1) {
            animation-delay: 0.05s;
        }

        .fornecedor-details-section:nth-child(2) {
            animation-delay: 0.1s;
        }

        .fornecedor-details-section:nth-child(3) {
            animation-delay: 0.15s;
        }

        .fornecedor-details-section:nth-child(4) {
            animation-delay: 0.2s;
        }

        .fornecedor-details-section:nth-child(5) {
            animation-delay: 0.25s;
        }

        [data-theme="dark"] .modal-content {
            background: #1f2937;
            border-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .modal-body {
            background: #1f2937;
        }

        [data-theme="dark"] .detail-item,
        [data-theme="dark"] .representante-card,
        [data-theme="dark"] .banco-card,
        [data-theme="dark"] .cliente-card,
        [data-theme="dark"] .empty-message {
            background: #111827;
            border-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .section-title {
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .card-title {
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .tipo-doc-badge {
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.15) 0%, rgba(56, 189, 248, 0.08) 100%);
            color: #38bdf8;
            border-color: rgba(56, 189, 248, 0.3);
        }

        [data-theme="dark"] .tipo-doc-badge:hover {
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.2) 0%, rgba(56, 189, 248, 0.12) 100%);
            border-color: #38bdf8;
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

        .status-cell {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
            align-items: flex-start;
        }

        .status-incompleto {
            background: rgba(245, 158, 11, 0.12) !important;
            color: #f59e0b !important;
            border: 1px solid rgba(245, 158, 11, 0.25) !important;
            font-size: 0.6875rem !important;
            padding: 0.1875rem 0.5rem !important;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            white-space: nowrap;
        }

        .status-incompleto i {
            font-size: 0.625rem;
        }

        [data-theme="dark"] .status-incompleto {
            background: rgba(245, 158, 11, 0.15) !important;
            color: #fbbf24 !important;
            border-color: rgba(245, 158, 11, 0.3) !important;
        }

        .cadastro-incompleto-alert {
            display: flex;
            gap: 1rem;
            padding: 1.25rem;
            background: rgba(245, 158, 11, 0.08);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: 12px;
            border-left: 4px solid #f59e0b;
        }

        .cadastro-incompleto-icon {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(245, 158, 11, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--warning-color);
            font-size: 1.25rem;
        }

        .cadastro-incompleto-content {
            flex: 1;
        }

        .cadastro-incompleto-content strong {
            font-size: 0.9375rem;
            color: var(--warning-color);
            display: block;
            margin-bottom: 0.25rem;
        }

        .cadastro-incompleto-content p {
            font-size: 0.8125rem;
            color: var(--text-muted);
            margin: 0 0 0.75rem 0;
        }

        .cadastro-incompleto-items {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .campo-faltante {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.625rem;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .campo-faltante i {
            font-size: 0.6875rem;
        }

        .cadastro-progresso-bar {
            height: 6px;
            background: rgba(107, 114, 128, 0.2);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 0.375rem;
        }

        .cadastro-progresso-fill {
            height: 100%;
            background: linear-gradient(90deg, #f59e0b, #10b981);
            border-radius: 3px;
            transition: width 0.5s ease;
        }

        .cadastro-progresso-text {
            font-size: 0.6875rem;
            color: #9ca3af;
            font-weight: 500;
        }

        [data-theme="dark"] .cadastro-incompleto-alert {
            background: rgba(245, 158, 11, 0.08);
            border-color: rgba(245, 158, 11, 0.2);
        }

        [data-theme="dark"] .cadastro-incompleto-content strong {
            color: #fbbf24;
        }

        [data-theme="dark"] .cadastro-incompleto-content p {
            color: #9ca3af;
        }

        [data-theme="dark"] .campo-faltante {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
        }

        [data-theme="dark"] .cadastro-progresso-bar {
            background: rgba(148, 163, 184, 0.15);
        }

        [data-theme="dark"] .cadastro-progresso-text {
            color: #94a3b8;
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

            .card-detail-grid {
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

            .card-title {
                font-size: 1.0625rem;
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
            .info-box {
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

            .representante-card,
            .banco-card,
            .cliente-card {
                padding: 1.25rem;
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