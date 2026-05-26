{{-- resources/views/users/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Gerenciar Usuários')

@section('content')
    <div class="container-fluid px-0">
        <div class="users-page-header">
            <div class="users-header-card">
                <div class="users-header-top">
                    <div class="users-title-wrapper">
                        <div class="users-icon-badge">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div>
                            <h1 class="users-main-title">Gerenciar Usuários</h1>
                            <p class="users-main-subtitle">Gerencie usuários e permissões do sistema</p>
                        </div>
                    </div>

                    @hasanyrole('admin|gerencial')
                        <a href="{{ route('users.create') }}" class="users-btn-create">
                            <i class="bi bi-plus-circle"></i>
                            <span>Novo Usuário</span>
                        </a>
                    @endhasanyrole
                </div>

                <div class="users-stats-inline">
                    <div class="stat-inline stat-clickable {{ !request('status') ? 'active' : '' }}" data-filter="all">
                        <span class="stat-value">{{ $totalUsuarios }}</span>
                        <span class="stat-label">Total</span>
                    </div>
                    <div class="stat-separator"></div>
                    <div class="stat-inline stat-clickable {{ request('status') === 'ativos' ? 'active' : '' }}"
                        data-filter="ativos">
                        <span class="stat-value stat-success">{{ $usuariosAtivos }}</span>
                        <span class="stat-label">Ativos</span>
                    </div>
                    @hasrole('admin')
                        <div class="stat-separator"></div>
                        <div class="stat-inline stat-clickable {{ request('status') === 'inativos' ? 'active' : '' }}"
                            data-filter="inativos">
                            <span class="stat-value stat-danger">{{ $usuariosInativos }}</span>
                            <span class="stat-label">Inativos</span>
                        </div>
                    @endhasrole
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('users.index') }}" id="filterForm">
            <input type="hidden" name="status" id="statusFilter" value="{{ request('status') }}">

            <div class="users-toolbar">
                <div class="toolbar-left">
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" name="search" class="search-input" placeholder="Buscar por nome ou email..."
                            value="{{ request('search') }}" autocomplete="off">
                        @if (request('search'))
                            <button type="button" class="search-clear">
                                <i class="bi bi-x"></i>
                            </button>
                        @endif
                        <button type="submit" class="search-btn" title="Pesquisar">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>

                    <select name="role" class="filter-select">
                        <option value="">Todos os perfis</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                                {{ ucfirst($role->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="toolbar-right">
                    <span class="results-count">
                        {{ $users->total() }} {{ Str::plural('resultado', $users->total()) }}
                    </span>
                </div>
            </div>
        </form>

        @if (request('search') || request('role') || request('status'))
            <div class="active-filters-strip">
                <span class="filters-label">Filtros ativos:</span>
                <div class="filter-tags">
                    @if (request('search'))
                        <span class="filter-tag">
                            Busca: {{ request('search') }}
                            <button type="button" class="remove-filter" data-filter="search">×</button>
                        </span>
                    @endif
                    @if (request('role'))
                        <span class="filter-tag">
                            Perfil: {{ ucfirst(request('role')) }}
                            <button type="button" class="remove-filter" data-filter="role">×</button>
                        </span>
                    @endif
                    @if (request('status'))
                        <span class="filter-tag">
                            Status: {{ ucfirst(request('status')) }}
                            <button type="button" class="remove-filter" data-filter="status">×</button>
                        </span>
                    @endif
                </div>
                <a href="{{ route('users.index') }}" class="clear-filters-link">
                    Limpar tudo
                </a>
            </div>
        @endif

        <div class="users-table-container">
            @if ($users->count() > 0)
                <div class="users-table-wrapper">
                    <table class="users-data-table">
                        <thead>
                            <tr>
                                <th class="th-primary">
                                    <span class="th-content">
                                        <i class="bi bi-person"></i>
                                        Usuário
                                    </span>
                                </th>
                                <th class="th-secondary">Perfil</th>
                                <th class="th-secondary">Status</th>
                                <th class="th-secondary">Cadastro</th>
                                <th class="th-actions">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr class="users-table-row {{ $user->trashed() ? 'row-inactive' : '' }}">
                                    <td class="td-primary">
                                        <div class="user-cell">
                                            <div class="user-main-info">
                                                <span class="user-name-table">{{ $user->name }}</span>
                                                <span class="user-email">{{ $user->email }}</span>
                                            </div>
                                            @if ($user->genOp || $user->fornecedor)
                                                <div class="user-extra-info">
                                                    <i class="bi bi-building"></i>
                                                    <span>{{ $user->genOp->departamento ?? ($user->fornecedor->razao_social ?? '') }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="td-secondary">
                                        @php
                                            $roleName = $user->roles->first()->name ?? 'default';
                                            $roleClass = match ($roleName) {
                                                'admin' => 'role-admin',
                                                'gerencial' => 'role-gerencial',
                                                'operacional' => 'role-operacional',
                                                'fornecedor' => 'role-fornecedor',
                                                default => 'role-default',
                                            };
                                        @endphp
                                        <span class="role-badge {{ $roleClass }}">
                                            {{ ucfirst($roleName) }}
                                        </span>
                                    </td>

                                    <td class="td-secondary">
                                        <span
                                            class="status-badge {{ $user->trashed() ? 'status-inactive' : 'status-active' }}">
                                            {{ $user->trashed() ? 'Inativo' : 'Ativo' }}
                                        </span>
                                    </td>

                                    <td class="td-secondary">
                                        <span class="date-info">
                                            {{ $user->created_at->format('d/m/Y') }}
                                            <span class="date-relative">{{ $user->created_at->diffForHumans() }}</span>
                                        </span>
                                    </td>

                                    <td class="td-actions">
                                        <div class="action-buttons">
                                            @if ($user->trashed())
                                                @hasrole('admin')
                                                    <form action="{{ route('users.restore', $user->id) }}" method="POST"
                                                        class="d-inline users-restore-form">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="action-btn action-restore"
                                                            title="Restaurar">
                                                            <i class="bi bi-arrow-counterclockwise"></i>
                                                        </button>
                                                    </form>

                                                    <form action="{{ route('users.forceDestroy', $user->id) }}"
                                                        method="POST" class="d-inline users-force-delete-form"
                                                        data-user-name="{{ $user->name }}"
                                                        data-user-role="{{ $roleName }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="action-btn action-force-delete"
                                                            title="Excluir">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                @endhasrole
                                            @else
                                                @php
                                                    $currentUser = auth()->user();
                                                    $userRole = $user->roles->first()->name ?? '';

                                                    $canEdit = false;
                                                    if ($currentUser->hasRole('admin')) {
                                                        $canEdit = true;
                                                    } elseif ($currentUser->hasRole('gerencial')) {
                                                        $canEdit = in_array($userRole, ['operacional', 'fornecedor']);
                                                    }

                                                    $canDelete = $canEdit;
                                                @endphp

                                                @if (!$user->trashed() && $canEdit)
                                                    <a href="{{ route('users.edit', $user) }}"
                                                        class="action-btn action-edit" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                @endif

                                                @if ($canDelete)
                                                    <form action="{{ route('users.destroy', $user) }}" method="POST"
                                                        class="d-inline users-delete-form"
                                                        data-user-name="{{ $user->name }}"
                                                        data-user-role="{{ $roleName }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="action-btn action-delete"
                                                            title="Inativar">
                                                            <i class="bi bi-ban"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @include('layouts.trade-pagination', ['paginator' => $users])
            @else
                <div class="users-empty-state">
                    <div class="users-empty-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3 class="users-empty-title">Nenhum usuário encontrado</h3>
                    <p class="users-empty-text">
                        Não há usuários cadastrados com os filtros selecionados.
                    </p>
                    <a href="{{ route('users.index') }}" class="users-btn-empty">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Limpar Filtros</span>
                    </a>
                </div>
            @endif
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
                    role: params.get('role') || '',
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
                if (filters.role) params.append('role', filters.role);
                if (filters.status) params.append('status', filters.status);

                return `{{ route('users.index') }}${params.toString() ? '?' + params.toString() : ''}`;
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

            $('.users-delete-form').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const userName = $(form).data('user-name');
                const userRole = $(form).data('user-role');

                Swal.fire({
                    title: 'Confirmar Inativação',
                    html: `
                        <div style="text-align: left;">
                            <p style="margin-bottom: 1.5rem; font-size: 1.05rem; color: var(--text-color); line-height: 1.6;">
                                <strong>Deseja realmente inativar este usuário?</strong>
                            </p>
                            <div class="user-info-box">
                                <p><strong>Nome:</strong> ${userName}</p>
                                <p><strong>Perfil:</strong> ${userRole}</p>
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

            $('.users-restore-form').on('submit', function(e) {
                e.preventDefault();
                const form = this;

                Swal.fire({
                    title: 'Confirmar Restauração',
                    html: `
                        <div style="text-align: left;">
                            <p style="font-size: 1.05rem; color: var(--text-color); line-height: 1.6;">
                                Deseja realmente restaurar este usuário? Todos os dados relacionados serão reativados.
                            </p>
                            <div class="info-box">
                                <p>
                                    <i class="bi bi-info-circle-fill"></i>
                                    <span>O usuário poderá acessar o sistema normalmente após a restauração.</span>
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

            $('.users-force-delete-form').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const userName = $(form).data('user-name');
                const userRole = $(form).data('user-role');

                Swal.fire({
                    title: 'Exclusão Permanente',
                    html: `
            <div style="text-align: left;">
                <div class="error-message-main">
                    <strong style="color: var(--danger-color); font-size: 1.1rem; display: block; margin-bottom: 0.75rem;">Esta ação é IRREVERSÍVEL!</strong>
                    <p style="margin: 0; color: var(--text-color); line-height: 1.6;">
                        O usuário e seus dados exclusivos serão excluídos permanentemente.
                    </p>
                </div>
                
                <div class="user-info-box">
                    <p><strong>Nome:</strong> ${userName}</p>
                    <p><strong>Perfil:</strong> ${userRole}</p>
                </div>

                <div class="info-box info-box-danger">
                    <p>
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span><strong>Serão excluídos:</strong> Dados cadastrais, endereços, representantes, dados bancários, documentos sem vínculo com PIs, conversas e mensagens do usuário.</span>
                    </p>
                </div>
            </div>
        `,
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-trash-fill"></i> Sim, excluir',
                    cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                    customClass: {
                        confirmButton: 'swal-confirm-btn swal-confirm-force-delete',
                        cancelButton: 'swal-cancel-btn'
                    },
                    buttonsStyling: false,
                    width: '700px',
                    padding: '2rem'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            $('select[name="role"]').on('change', function() {
                $('#filterForm').submit();
            });

            @if (session('error_type') === 'bloqueio_dependencia')
                const errorDetails = @json(session('error_details', []));

                let htmlContent = `
                    <div style="text-align: left;">
                        <div class="error-message-main">
                            {{ session('error') }}
                        </div>
                `;

                if (errorDetails.pis && errorDetails.pis.length > 0) {
                    htmlContent += `
                        <div class="pi-section">
                            <h4 class="pi-section-title">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <span>PIs em Andamento (${errorDetails.pis.length})</span>
                            </h4>
                            <div class="pi-list">
                    `;

                    errorDetails.pis.forEach((pi, index) => {
                        htmlContent += `
                            <div class="pi-card">
                                <p class="pi-card-title">
                                    <i class="bi bi-file-earmark-text-fill"></i>
                                    <span>PI ${pi.numero_pi}</span>
                                </p>
                        `;

                        if (errorDetails.tipo === 'fornecedor') {
                            htmlContent += `
                                <p class="pi-card-info">
                                    <strong>Gerencial:</strong>
                                    <span>${pi.gerencial}</span>
                                </p>
                                <p class="pi-card-info">
                                    <strong>Operacionais:</strong>
                                    <span>${pi.operacionais}</span>
                                </p>
                            `;
                        } else if (errorDetails.tipo === 'operacional') {
                            htmlContent += `
                                <p class="pi-card-info">
                                    <strong>Fornecedor:</strong>
                                    <span>${pi.fornecedor}</span>
                                </p>
                                <p class="pi-card-info">
                                    <strong>Gerencial:</strong>
                                    <span>${pi.gerencial}</span>
                                </p>
                            `;
                        }

                        htmlContent += `
                                <p class="pi-card-info">
                                    <strong>Cadastrado em:</strong>
                                    <span>${pi.data_cadastro}</span>
                                </p>
                            </div>
                        `;
                    });

                    htmlContent += `
                            </div>
                        </div>
                    `;
                }

                if (errorDetails.clientes && errorDetails.clientes.length > 0) {
                    htmlContent += `
                        <div class="pi-section">
                            <h4 class="pi-section-title">
                                <i class="bi bi-link-45deg"></i>
                                <span>Clientes Vinculados (${errorDetails.clientes.length})</span>
                            </h4>
                            <div class="pi-list">
                    `;

                    errorDetails.clientes.forEach((cliente) => {
                        htmlContent += `
                            <div class="pi-card">
                                <p class="pi-card-title">
                                    <i class="bi bi-building-fill"></i>
                                    <span>${cliente.razao_social}</span>
                                </p>
                                <p class="pi-card-info">
                                    <strong>CNPJ:</strong>
                                    <span>${cliente.cnpj}</span>
                                </p>
                                <p class="pi-card-info">
                                    <strong>Atribuído em:</strong>
                                    <span>${cliente.data_atribuicao}</span>
                                </p>
                            </div>
                        `;
                    });

                    htmlContent += `
                            </div>
                        </div>
                    `;
                }

                htmlContent += `
                    </div>
                `;

                Swal.fire({
                    title: 'Operação Bloqueada',
                    html: htmlContent,
                    icon: 'error',
                    confirmButtonText: '<i class="bi bi-check-circle"></i> Entendi',
                    customClass: {
                        confirmButton: 'swal-confirm-btn'
                    },
                    buttonsStyling: false,
                    width: '750px',
                    padding: '2rem'
                });
            @elseif (session('error_type') === 'bloqueio_force_delete')
                const errorDetailsForce = @json(session('error_details', []));

                let htmlContentForce = `
                    <div style="text-align: left;">
                        <div class="error-message-main">
                            {{ session('error') }}
                        </div>
                `;

                if (errorDetailsForce.pis && errorDetailsForce.pis.length > 0) {
                    htmlContentForce += `
                        <div class="pi-section">
                            <h4 class="pi-section-title">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <span>PIs Vinculados (${errorDetailsForce.pis.length})</span>
                            </h4>
                            <div class="pi-list">
                    `;

                    errorDetailsForce.pis.forEach((pi) => {
                        htmlContentForce += `
                            <div class="pi-card">
                                <p class="pi-card-title">
                                    <i class="bi bi-file-earmark-text-fill"></i>
                                    <span>PI ${pi.numero_pi}</span>
                                </p>
                        `;

                        if (errorDetailsForce.tipo === 'fornecedor_pis') {
                            htmlContentForce += `
                                <p class="pi-card-info">
                                    <strong>Cliente:</strong>
                                    <span>${pi.cliente}</span>
                                </p>
                                <p class="pi-card-info">
                                    <strong>Gerencial:</strong>
                                    <span>${pi.gerencial}</span>
                                </p>
                                <p class="pi-card-info">
                                    <strong>Operacionais:</strong>
                                    <span>${pi.operacionais}</span>
                                </p>
                            `;
                        } else if (errorDetailsForce.tipo === 'gerencial_pis') {
                            htmlContentForce += `
                                <p class="pi-card-info">
                                    <strong>Fornecedor:</strong>
                                    <span>${pi.fornecedor}</span>
                                </p>
                                <p class="pi-card-info">
                                    <strong>Cliente:</strong>
                                    <span>${pi.cliente}</span>
                                </p>
                            `;
                        } else if (errorDetailsForce.tipo === 'operacional_pis') {
                            htmlContentForce += `
                                <p class="pi-card-info">
                                    <strong>Fornecedor:</strong>
                                    <span>${pi.fornecedor}</span>
                                </p>
                                <p class="pi-card-info">
                                    <strong>Cliente:</strong>
                                    <span>${pi.cliente}</span>
                                </p>
                            `;
                        }

                        htmlContentForce += `
                                <p class="pi-card-info">
                                    <strong>Cadastrado em:</strong>
                                    <span>${pi.data_cadastro}</span>
                                </p>
                                <p class="pi-card-info">
                                    <strong>Concluído:</strong>
                                    <span>${pi.concluido}</span>
                                </p>
                                <p class="pi-card-info">
                                    <strong>Status:</strong>
                                    <span>${pi.status}</span>
                                </p>
                            </div>
                        `;
                    });

                    htmlContentForce += `
                            </div>
                        </div>
                    `;
                }

                if (errorDetailsForce.documentos && errorDetailsForce.documentos.length > 0) {
                    htmlContentForce += `
                        <div class="pi-section">
                            <h4 class="pi-section-title">
                                <i class="bi bi-file-earmark-check-fill"></i>
                                <span>Documentos Vinculados (${errorDetailsForce.documentos.length})</span>
                            </h4>
                            <div class="pi-list">
                    `;

                    errorDetailsForce.documentos.forEach((doc) => {
                        htmlContentForce += `
                            <div class="pi-card">
                                <p class="pi-card-title">
                                    <i class="bi bi-file-earmark-fill"></i>
                                    <span>${doc.tipo}</span>
                                </p>
                                <p class="pi-card-info">
                                    <strong>Data Envio:</strong>
                                    <span>${doc.data_envio}</span>
                                </p>
                                <p class="pi-card-info">
                                    <strong>PIs Vinculados:</strong>
                                    <span>${doc.pis_vinculados}</span>
                                </p>
                            </div>
                        `;
                    });

                    htmlContentForce += `
                            </div>
                        </div>
                    `;
                }

                if (errorDetailsForce.tipo === 'operacional_validacoes') {
                    htmlContentForce += `
                        <div class="info-box info-box-danger" style="margin-top: 1.25rem;">
                            <p>
                                <i class="bi bi-shield-check"></i>
                                <span><strong>Validações Realizadas:</strong> ${errorDetailsForce.quantidade_validacoes} validação(ões) de documentos registradas no histórico.</span>
                            </p>
                        </div>
                    `;
                }

                htmlContentForce += `
                    </div>
                `;

                Swal.fire({
                    title: 'Exclusão Bloqueada',
                    html: htmlContentForce,
                    icon: 'error',
                    confirmButtonText: '<i class="bi bi-check-circle"></i> Entendi',
                    customClass: {
                        confirmButton: 'swal-confirm-btn'
                    },
                    buttonsStyling: false,
                    width: '750px',
                    padding: '2rem'
                });
            @elseif (session('success'))
                toastr.success('{{ session('success') }}');
            @elseif (session('error'))
                toastr.error('{{ session('error') }}');
            @endif
        });
    </script>
@endpush

@push('styles')
    <style>
        @keyframes usersFadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes usersFadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
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

        .spin {
            animation: spin 1s linear infinite;
        }

        .users-page-header {
            margin-bottom: 2rem;
            animation: usersFadeInDown 0.6s ease;
        }

        .users-header-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 0;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .users-header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem;
            border-bottom: 1px solid var(--border-color);
        }

        .users-title-wrapper {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .users-icon-badge {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: white;
            box-shadow: 0 6px 20px rgba(0, 94, 130, 0.25);
        }

        .users-main-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--text-color);
            margin: 0;
            line-height: 1.2;
        }

        .users-main-subtitle {
            font-size: 0.95rem;
            color: var(--text-muted);
            margin: 0.25rem 0 0 0;
        }

        .users-btn-create {
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.875rem 1.75rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(0, 94, 130, 0.25);
            transition: all 0.3s ease;
        }

        .users-btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 94, 130, 0.35);
            color: white;
        }

        .users-stats-inline {
            display: flex;
            align-items: center;
            padding: 1.25rem 2rem;
            background: rgba(0, 94, 130, 0.02);
            gap: 2rem;
        }

        .stat-inline {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.2s;
            min-width: 80px;
        }

        .stat-inline:hover {
            background: rgba(0, 94, 130, 0.08);
        }

        .stat-inline.active {
            background: var(--primary-color);
        }

        .stat-inline.active .stat-value,
        .stat-inline.active .stat-label {
            color: white !important;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            line-height: 1;
            color: var(--text-color);
        }

        .stat-value.stat-success {
            color: #059669;
        }

        .stat-value.stat-danger {
            color: var(--danger-color);
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
            font-weight: 600;
        }

        .stat-separator {
            width: 1px;
            height: 40px;
            background: var(--border-color);
        }

        .users-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            margin: 1.5rem 0;
            gap: 1rem;
            animation: usersFadeInUp 0.6s ease 0.2s backwards;
        }

        .toolbar-left {
            display: flex;
            gap: 1rem;
            flex: 1;
        }

        .search-box {
            position: relative;
            flex: 1;
            max-width: 400px;
            display: flex;
            align-items: stretch;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 3rem 0.75rem 2.75rem;
            border: 2px solid var(--border-color);
            border-radius: 10px 0 0 10px;
            font-size: 0.95rem;
            transition: all 0.2s;
            background: var(--bg-body);
            color: var(--text-color);
        }

        .search-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 94, 130, 0.1);
        }

        .search-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 1rem;
            background: var(--primary-color);
            color: #fff;
            border: 2px solid var(--primary-color);
            border-left: none;
            border-radius: 0 10px 10px 0;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .search-btn:hover {
            background: var(--primary-hover, #0284c7);
            border-color: var(--primary-hover, #0284c7);
        }

        .search-clear {
            position: absolute;
            right: 3.25rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.25rem;
            font-size: 1.25rem;
            transition: color 0.2s;
            z-index: 2;
        }

        .search-clear:hover {
            color: var(--text-color);
        }

        .filter-select {
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.95rem;
            background: var(--bg-body);
            color: var(--text-color);
            min-width: 180px;
            transition: all 0.2s;
            cursor: pointer;
        }

        .filter-select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 94, 130, 0.1);
        }

        .results-count {
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 600;
            padding: 0.75rem 1rem;
            background: var(--bg-body);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .active-filters-strip {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 1rem;
            background: rgba(0, 94, 130, 0.05);
            border-radius: 8px;
            margin-bottom: 1.5rem;
            animation: usersFadeInDown 0.3s ease;
        }

        .filters-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        .filter-tags {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.75rem;
            background: white;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .remove-filter {
            background: none;
            border: none;
            color: inherit;
            font-size: 1.125rem;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s;
            padding: 0;
            line-height: 1;
        }

        .remove-filter:hover {
            opacity: 1;
        }

        .clear-filters-link {
            font-size: 0.875rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.2s;
        }

        .clear-filters-link:hover {
            opacity: 0.8;
        }

        .users-table-container {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            animation: usersFadeInUp 0.6s ease 0.3s backwards;
        }

        .users-table-wrapper {
            overflow-x: auto;
        }

        .users-data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .users-data-table thead {
            background: rgba(0, 94, 130, 0.03);
        }

        .users-data-table th {
            padding: 1rem 1.25rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-muted);
            text-align: left;
            border-bottom: 2px solid var(--border-color);
        }

        .th-content {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .th-content i {
            font-size: 1rem;
            opacity: 0.8;
        }

        .th-primary {
            width: 35%;
        }

        .th-secondary {
            width: 15%;
        }

        .th-actions {
            width: 120px;
            text-align: center;
        }

        .users-table-row {
            transition: background 0.15s ease;
        }

        .users-table-row:hover {
            background: rgba(0, 94, 130, 0.02);
        }

        .row-inactive {
            opacity: 0.6;
        }

        .users-data-table td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .td-primary {
            width: 35%;
        }

        .td-secondary {
            width: 15%;
        }

        .td-actions {
            width: 120px;
            text-align: center;
        }

        .user-cell {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .user-main-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .user-name-table {
            font-weight: 600;
            color: var(--text-color);
            font-size: 1rem;
        }

        .user-email {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .user-extra-info {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.8125rem;
            color: var(--text-muted);
            padding-top: 0.25rem;
            border-top: 1px solid var(--border-color);
        }

        .user-extra-info i {
            font-size: 0.75rem;
            opacity: 0.7;
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.375rem 0.875rem;
            border-radius: 8px;
            font-size: 0.8125rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .role-admin {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: var(--danger-color);
        }

        .role-gerencial {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #2563eb;
        }

        .role-operacional {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #d97706;
        }

        .role-fornecedor {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #059669;
        }

        .role-default {
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            color: #475569;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.875rem;
            border-radius: 8px;
            font-size: 0.8125rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-badge.status-active {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #059669;
        }

        .status-badge.status-inactive {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: var(--danger-color);
        }

        .date-info {
            display: flex;
            flex-direction: column;
            gap: 0.125rem;
        }

        .date-relative {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            align-items: center;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            font-size: 0.9375rem;
            font-weight: 600;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .action-btn.action-edit {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #2563eb;
        }

        .action-btn.action-edit:hover {
            background: linear-gradient(135deg, #bfdbfe, #93c5fd);
            color: #1d4ed8;
        }

        .action-btn.action-delete {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: var(--danger-color);
        }

        .action-btn.action-delete:hover {
            background: linear-gradient(135deg, #fecaca, #fca5a5);
            color: #b91c1c;
        }

        .action-btn.action-restore {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #059669;
        }

        .action-btn.action-restore:hover {
            background: linear-gradient(135deg, #a7f3d0, #6ee7b7);
            color: #047857;
        }

        .action-btn.action-force-delete {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: var(--danger-color);
        }

        .action-btn.action-force-delete:hover {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: var(--danger-color);
            box-shadow: 0 4px 12px rgba(127, 29, 29, 0.4);
        }

        .users-pagination-wrapper {
            padding: 1.5rem;
            display: flex;
            justify-content: center;
            border-top: 1px solid var(--border-color);
            background: rgba(0, 94, 130, 0.02);
        }

        .users-empty-state {
            text-align: center;
            padding: 5rem 2rem;
        }

        .users-empty-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.1) 0%, rgba(0, 94, 130, 0.05) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: var(--primary-color);
            opacity: 0.6;
        }

        .users-empty-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.75rem;
        }

        .users-empty-text {
            font-size: 1rem;
            color: var(--text-muted);
            margin-bottom: 2rem;
            max-width: 450px;
            margin-left: auto;
            margin-right: auto;
        }

        .users-btn-empty {
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.875rem 1.75rem;
            background: var(--primary-color);
            color: white;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .users-btn-empty:hover {
            background: var(--primary-dark);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 94, 130, 0.3);
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

        .swal-confirm-force-delete {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
        }

        .swal-confirm-force-delete:hover {
            background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%) !important;
            box-shadow: 0 6px 20px rgba(185, 28, 28, 0.4) !important;
        }

        .pi-card {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 0.75rem;
            border-left: 4px solid #f87171;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }

        .pi-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateX(4px);
        }

        .pi-card:last-child {
            margin-bottom: 0;
        }

        .pi-card-title {
            margin: 0 0 0.625rem 0;
            font-weight: 700;
            color: #111827;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pi-card-title i {
            color: #ef4444;
            font-size: 1.125rem;
        }

        .pi-card-info {
            margin: 0.375rem 0;
            font-size: 0.9375rem;
            color: var(--text-muted);
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .pi-card-info strong {
            color: var(--text-color);
            min-width: 90px;
            font-weight: 600;
        }

        .pi-section {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.25rem;
        }

        .pi-section:last-child {
            margin-bottom: 0;
        }

        .pi-section-title {
            margin: 0 0 1rem 0;
            color: var(--danger-color);
            font-size: 1.05rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.625rem;
        }

        .pi-section-title i {
            font-size: 1.25rem;
        }

        .pi-list {
            max-height: 280px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .pi-list::-webkit-scrollbar {
            width: 6px;
        }

        .pi-list::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 3px;
        }

        .pi-list::-webkit-scrollbar-thumb {
            background: rgba(220, 38, 38, 0.3);
            border-radius: 3px;
        }

        .pi-list::-webkit-scrollbar-thumb:hover {
            background: rgba(220, 38, 38, 0.5);
        }

        .error-message-main {
            margin-bottom: 1.75rem;
            font-size: 1.05rem;
            color: var(--text-color);
            line-height: 1.7;
            padding: 1.25rem;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-radius: 10px;
            border-left: 4px solid #ef4444;
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

        .info-box-danger {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-left-color: var(--danger-color);
        }

        .info-box-danger p {
            color: var(--text-color);
        }

        .info-box-danger i {
            color: var(--danger-color);
        }

        .user-info-box {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            padding: 1.25rem;
            border-radius: 10px;
            margin: 1.25rem 0;
            border-left: 4px solid #f59e0b;
        }

        .user-info-box p {
            margin: 0.5rem 0;
            color: var(--text-color);
            font-size: 0.9375rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-info-box p:first-child {
            margin-top: 0;
        }

        .user-info-box p:last-child {
            margin-bottom: 0;
        }

        .user-info-box strong {
            color: #1f2937;
            min-width: 60px;
            font-weight: 700;
        }

        .swal2-input {
            width: 100% !important;
            padding: 0.75rem 1rem !important;
            border: 2px solid #d1d5db !important;
            border-radius: 8px !important;
            font-size: 1rem !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
            transition: all 0.2s ease !important;
        }

        .swal2-input:focus {
            border-color: #dc2626 !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1) !important;
        }

        .swal2-validation-message {
            background: #fee2e2 !important;
            color: #dc2626 !important;
            border: 1px solid rgba(239, 68, 68, 0.3) !important;
            border-radius: 8px !important;
            padding: 0.75rem !important;
            margin-top: 1rem !important;
            font-weight: 600 !important;
        }

        .swal2-container.swal2-backdrop-show {
            background: rgba(0, 0, 0, 0.5) !important;
            backdrop-filter: blur(4px);
        }

        .swal2-show {
            animation: swalFadeIn 0.3s ease !important;
        }

        [data-theme="dark"] .users-header-card {
            background: var(--bg-card);
        }

        [data-theme="dark"] .users-stats-inline {
            background: rgba(56, 189, 248, 0.05);
        }

        [data-theme="dark"] .stat-inline:hover {
            background: rgba(56, 189, 248, 0.1);
        }

        [data-theme="dark"] .users-data-table thead {
            background: rgba(56, 189, 248, 0.08);
        }

        [data-theme="dark"] .users-table-row:hover {
            background: rgba(56, 189, 248, 0.04);
        }

        [data-theme="dark"] .filter-tag {
            background: var(--bg-card);
        }

        [data-theme="dark"] .active-filters-strip {
            background: rgba(56, 189, 248, 0.08);
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

        [data-theme="dark"] .pi-card {
            background: #374151 !important;
            border-left-color: #f87171 !important;
        }

        [data-theme="dark"] .pi-card-title {
            color: #f9fafb !important;
        }

        [data-theme="dark"] .pi-card-info {
            color: #d1d5db !important;
        }

        [data-theme="dark"] .pi-card-info strong {
            color: #e5e7eb !important;
        }

        [data-theme="dark"] .pi-section {
            background: rgba(239, 68, 68, 0.1) !important;
            border-color: rgba(254, 202, 202, 0.2) !important;
        }

        [data-theme="dark"] .error-message-main {
            background: rgba(239, 68, 68, 0.15) !important;
            border-left-color: #f87171 !important;
            color: #e5e7eb !important;
        }

        [data-theme="dark"] .user-info-box {
            background: rgba(245, 158, 11, 0.1) !important;
            border-left-color: #fbbf24 !important;
        }

        [data-theme="dark"] .user-info-box p {
            color: #d1d5db !important;
        }

        [data-theme="dark"] .user-info-box strong {
            color: #f9fafb !important;
        }

        [data-theme="dark"] .info-box {
            background: rgba(59, 130, 246, 0.1) !important;
            border-left-color: #60a5fa !important;
        }

        [data-theme="dark"] .info-box p {
            color: #d1d5db !important;
        }

        [data-theme="dark"] .info-box-danger {
            background: rgba(239, 68, 68, 0.15) !important;
            border-left-color: #f87171 !important;
        }

        [data-theme="dark"] .swal2-input {
            background: #374151 !important;
            color: #f9fafb !important;
            border-color: #4b5563 !important;
        }

        [data-theme="dark"] .swal2-validation-message {
            background: rgba(239, 68, 68, 0.2) !important;
            border-color: rgba(254, 202, 202, 0.3) !important;
        }

        @media (max-width: 992px) {
            .users-header-top {
                flex-direction: column;
                gap: 1.5rem;
            }

            .users-btn-create {
                width: 100%;
                justify-content: center;
            }

            .toolbar-left {
                flex-direction: column;
                width: 100%;
            }

            .search-box {
                max-width: 100%;
            }

            .filter-select {
                width: 100%;
            }

            .users-toolbar {
                flex-direction: column;
            }

            .toolbar-right {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 768px) {
            .users-stats-inline {
                justify-content: space-around;
                padding: 1rem;
                gap: 1rem;
            }

            .stat-inline {
                min-width: auto;
                padding: 0.375rem 0.75rem;
            }

            .stat-value {
                font-size: 1.5rem;
            }

            .stat-separator {
                height: 30px;
            }

            .th-secondary:last-of-type,
            .td-secondary:last-of-type {
                display: none;
            }

            .users-data-table th,
            .users-data-table td {
                padding: 0.875rem;
            }

            .users-empty-icon {
                width: 100px;
                height: 100px;
                font-size: 3rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 0.375rem;
            }

            .action-btn {
                width: 32px;
                height: 32px;
                font-size: 0.875rem;
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

            .pi-card {
                padding: 0.875rem;
            }

            .pi-card-title {
                font-size: 1rem;
            }

            .pi-card-info {
                font-size: 0.875rem;
                flex-direction: column;
                gap: 0.25rem;
            }

            .pi-card-info strong {
                min-width: auto;
            }

            .pi-section {
                padding: 1rem;
            }

            .pi-section-title {
                font-size: 1rem;
            }

            .pi-list {
                max-height: 220px;
            }

            .error-message-main {
                font-size: 1rem;
                padding: 1rem;
            }

            .user-info-box,
            .info-box {
                padding: 1rem;
            }

            .user-info-box p,
            .info-box p {
                font-size: 0.875rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.375rem;
            }

            .user-info-box strong {
                min-width: auto;
            }
        }

        @media (max-width: 576px) {
            .users-main-title {
                font-size: 1.5rem;
            }

            .users-icon-badge {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }

            .users-header-top {
                padding: 1.5rem;
            }

            .th-secondary:nth-child(3),
            .td-secondary:nth-child(3) {
                display: none;
            }

            .user-name-table {
                font-size: 0.9375rem;
            }

            .user-email {
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

            .pi-card {
                padding: 0.75rem;
            }

            .pi-list {
                max-height: 180px;
            }
        }
    </style>
@endpush