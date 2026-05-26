{{-- resources/views/tipo-documentos/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Tipos de Documentos')

@section('content')
    <link href="{{ asset('css/pages/tipo-doc.css') }}" rel="stylesheet">
    <div class="container-fluid px-0">
        <div class="page-header">
            <div class="header-card">
                <div class="header-top">
                    <div class="title-wrapper">
                        <div class="icon-badge">
                            <i class="bi bi-file-earmark-text-fill"></i>
                        </div>
                        <div>
                            <h1 class="main-title">Tipos de Documentos</h1>
                            <p class="main-subtitle">Gerencie os tipos de documentos aceitos no sistema</p>
                        </div>
                    </div>
                    @hasanyrole('admin|gerencial')
                        <a href="{{ route('tipo_documentos.create') }}" class="btn-create">
                            <i class="bi bi-plus-circle"></i>
                            <span>Novo Tipo</span>
                        </a>
                    @endhasanyrole
                </div>

                <div class="stats-inline">
                    @hasrole('admin')
                        <div class="stat-inline stat-clickable {{ !request('status') && !request('ativo') && !request('validar') && !request('validade_tipo') ? 'active' : '' }}"
                            data-filter="all">
                            <span class="stat-value">{{ $totalGeral }}</span>
                            <span class="stat-label">Total</span>
                        </div>
                        <div class="stat-separator"></div>
                        <div class="stat-inline stat-clickable {{ request('ativo') === '1' ? 'active' : '' }}"
                            data-filter="ativos">
                            <span class="stat-value stat-success">{{ $totalAtivos }}</span>
                            <span class="stat-label">Ativos</span>
                        </div>
                        <div class="stat-separator"></div>
                    @else
                        <div class="stat-inline stat-clickable {{ !request('validar') && !request('validade_tipo') ? 'active' : '' }}"
                            data-filter="all">
                            <span class="stat-value">{{ $totalGeral }}</span>
                            <span class="stat-label">Total</span>
                        </div>
                        <div class="stat-separator"></div>
                    @endhasrole

                    <div class="stat-inline stat-clickable {{ request('validar') === '1' ? 'active' : '' }}"
                        data-filter="com-controle">
                        <span class="stat-value stat-warning">{{ $totalComControle }}</span>
                        <span class="stat-label">Com Controle</span>
                    </div>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('tipo_documentos.index') }}" id="filterForm">
            <input type="hidden" name="status" id="statusFilter" value="{{ request('status') }}">

            <div class="toolbar">
                <div class="toolbar-left">
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" name="search" class="search-input"
                            placeholder="Buscar por nome ou descrição..." value="{{ request('search') }}"
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

                    <select name="validar" class="filter-select">
                        <option value="">Todos os tipos</option>
                        <option value="1" {{ request('validar') === '1' ? 'selected' : '' }}>Com Controle de Validade
                        </option>
                        <option value="0" {{ request('validar') === '0' ? 'selected' : '' }}>Sem Controle (Uso Único)
                        </option>
                    </select>

                    @hasrole('admin')
                        <select name="ativo" class="filter-select">
                            <option value="">Qualquer status</option>
                            <option value="1" {{ request('ativo') === '1' ? 'selected' : '' }}>Ativo</option>
                            <option value="0" {{ request('ativo') === '0' ? 'selected' : '' }}>Inativo</option>
                        </select>
                    @endhasrole

                    <select name="validade_tipo" class="filter-select">
                        <option value="">Qualquer validade</option>
                        <option value="indeterminada" {{ request('validade_tipo') === 'indeterminada' ? 'selected' : '' }}>
                            Validade Indeterminada</option>
                        <option value="com_prazo" {{ request('validade_tipo') === 'com_prazo' ? 'selected' : '' }}>Com
                            Prazo Definido</option>
                    </select>
                </div>

                <div class="toolbar-right">
                    <span class="results-count">
                        {{ $tipoDocumentos->total() }} {{ Str::plural('resultado', $tipoDocumentos->total()) }}
                    </span>
                </div>
            </div>
        </form>

        @if (request('search') ||
                (request('validar') !== null && request('validar') !== '') ||
                (request('ativo') !== null && request('ativo') !== '') ||
                request('status') ||
                request('validade_tipo'))
            <div class="active-filters-strip">
                <span class="filters-label">Filtros ativos:</span>
                <div class="filter-tags">
                    @if (request('search'))
                        <span class="filter-tag">
                            Busca: {{ request('search') }}
                            <button type="button" class="remove-filter" data-filter="search">×</button>
                        </span>
                    @endif

                    @if (request('validar') !== null && request('validar') !== '')
                        <span class="filter-tag">
                            {{ request('validar') === '1' ? 'Com Controle de Validade' : 'Sem Controle (Uso Único)' }}
                            <button type="button" class="remove-filter" data-filter="validar">×</button>
                        </span>
                    @endif

                    @hasrole('admin')
                        @if (request('ativo') !== null && request('ativo') !== '')
                            <span class="filter-tag">
                                {{ request('ativo') === '1' ? 'Ativos' : 'Inativos' }}
                                <button type="button" class="remove-filter" data-filter="ativo">×</button>
                            </span>
                        @endif
                    @endhasrole

                    @if (request('validade_tipo'))
                        <span class="filter-tag">
                            {{ request('validade_tipo') === 'indeterminada' ? 'Validade Indeterminada' : 'Com Prazo Definido' }}
                            <button type="button" class="remove-filter" data-filter="validade_tipo">×</button>
                        </span>
                    @endif

                    @if (request('status'))
                        <span class="filter-tag">
                            Status: {{ ucfirst(request('status')) }}
                            <button type="button" class="remove-filter" data-filter="status">×</button>
                        </span>
                    @endif
                </div>
                <a href="{{ route('tipo_documentos.index') }}" class="clear-filters-link">
                    Limpar tudo
                </a>
            </div>
        @endif

        <div class="table-container">
            @if ($tipoDocumentos->count() > 0)
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="th-primary">
                                    <span class="th-content">
                                        <i class="bi bi-file-text"></i>
                                        Documento
                                    </span>
                                </th>
                                <th class="th-secondary">Validade</th>
                                <th class="th-secondary">Controle Sistema</th>
                                <th class="th-secondary">Tipos de Veículo</th>
                                @hasrole('admin')
                                    <th class="th-secondary">Status</th>
                                @endhasrole
                                <th class="th-secondary">Cadastro</th>
                                <th class="th-actions">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tipoDocumentos as $tipo)
                                <tr class="table-row {{ $tipo->trashed() ? 'row-inactive' : '' }}">
                                    <td class="td-primary">
                                        <div class="tipo-cell">
                                            <div class="tipo-main-info">
                                                <span class="tipo-name-table">{{ $tipo->nome }}</span>
                                                @if ($tipo->descricao)
                                                    <span
                                                        class="tipo-description">{{ Str::limit($tipo->descricao, 80) }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <td class="td-secondary">
                                        @if ($tipo->validade_dias === 0)
                                            <div class="validade-info">
                                                <i class="bi bi-infinity"></i>
                                                <span style="color: var(--info-color); font-weight: 600;">Indeterminada</span>
                                            </div>
                                        @else
                                            <div class="validade-info">
                                                <i class="bi bi-calendar-check"></i>
                                                <span>{{ $tipo->validade_dias }}
                                                    {{ Str::plural('dia', $tipo->validade_dias) }}</span>
                                            </div>
                                        @endif
                                    </td>

                                    <td class="td-secondary">
                                        @if ($tipo->validar)
                                            <span class="obrigatorio-badge badge-obrigatorio">
                                                <i class="bi bi-shield-check"></i> Sim
                                            </span>
                                            @if ($tipo->validade_dias === 0)
                                                <small
                                                    style="display: block; color: var(--text-muted); margin-top: 0.25rem; font-size: 0.75rem;">
                                                    Reutilizável sempre
                                                </small>
                                            @else
                                                <small
                                                    style="display: block; color: var(--text-muted); margin-top: 0.25rem; font-size: 0.75rem;">
                                                    Reutilizável até vencer
                                                </small>
                                            @endif
                                        @else
                                            <span class="obrigatorio-badge badge-opcional">
                                                <i class="bi bi-file-earmark"></i> Não
                                            </span>
                                            <small
                                                style="display: block; color: var(--text-muted); margin-top: 0.25rem; font-size: 0.75rem;">
                                                Uso único
                                            </small>
                                        @endif
                                    </td>

                                    <td class="td-secondary">
                                        @if($tipo->tipoVeiculos && $tipo->tipoVeiculos->count() > 0)
                                            @if($tipo->isTodosVeiculos())
                                                <span class="veiculo-badge badge-veiculo-universal">
                                                    <i class="bi bi-check-all"></i>
                                                    Todos os Veículos
                                                </span>
                                            @else
                                                <div class="veiculos-badges">
                                                    @foreach($tipo->tipoVeiculos as $veiculo)
                                                        @php
                                                            $badgeClass = 'badge-veiculo-default';
                                                            $iconClass = 'bi-check-circle';
                                                            
                                                            if(str_contains($veiculo->nome, 'Rádio') || str_contains($veiculo->nome, 'TV')) {
                                                                $badgeClass = 'badge-veiculo-radiotv';
                                                                $iconClass = 'bi-broadcast';
                                                            } elseif(str_contains($veiculo->nome, 'Internet')) {
                                                                $badgeClass = 'badge-veiculo-internet';
                                                                $iconClass = 'bi-globe';
                                                            } elseif(str_contains($veiculo->nome, 'Jornal') || str_contains($veiculo->nome, 'Revista')) {
                                                                $badgeClass = 'badge-veiculo-impresso';
                                                                $iconClass = 'bi-newspaper';
                                                            }
                                                        @endphp
                                                        <span class="veiculo-badge {{ $badgeClass }}">
                                                            <i class="bi {{ $iconClass }}"></i>
                                                            {{ $veiculo->nome }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @else
                                            <span class="veiculo-badge badge-veiculo-geral">
                                                <i class="bi bi-dash-circle"></i>
                                                Geral
                                            </span>
                                        @endif
                                    </td>

                                    @hasrole('admin')
                                        <td class="td-secondary">
                                            <span
                                                class="status-badge {{ $tipo->ativo ? 'status-active' : 'status-inactive' }}">
                                                {{ $tipo->ativo ? 'Ativo' : 'Inativo' }}
                                            </span>
                                        </td>
                                    @endhasrole

                                    <td class="td-secondary">
                                        <span class="date-info">
                                            {{ $tipo->created_at->format('d/m/Y') }}
                                            <span class="date-relative">{{ $tipo->created_at->diffForHumans() }}</span>
                                        </span>
                                    </td>

                                    <td class="td-actions">
                                        <div class="action-buttons">
                                            @if ($tipo->trashed())
                                                @hasrole('admin')
                                                    <form action="{{ route('tipo_documentos.restore', $tipo->id) }}"
                                                        method="POST" class="d-inline tipos-restore-form"
                                                        data-tipo-nome="{{ $tipo->nome }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="action-btn action-restore"
                                                            title="Restaurar"
                                                            aria-label="Restaurar tipo de documento {{ $tipo->nome }}">
                                                            <i class="bi bi-arrow-counterclockwise"></i>
                                                        </button>
                                                    </form>
                                                @endhasrole
                                            @else
                                                @hasanyrole('admin|gerencial')
                                                    <a href="{{ route('tipo_documentos.edit', $tipo) }}"
                                                        class="action-btn action-edit" title="Editar"
                                                        aria-label="Editar tipo de documento {{ $tipo->nome }}">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form action="{{ route('tipo_documentos.destroy', $tipo) }}"
                                                        method="POST" class="d-inline tipos-delete-form"
                                                        data-tipo-nome="{{ $tipo->nome }}"
                                                        data-tipo-validar="{{ $tipo->validar ? 'Sim' : 'Não' }}"
                                                        data-tipo-ativo="{{ $tipo->ativo ? 'Ativo' : 'Inativo' }}"
                                                        data-tipo-validade="{{ $tipo->validade_dias === 0 ? 'Indeterminada' : $tipo->validade_dias . ' dias' }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="action-btn action-delete"
                                                            title="Excluir"
                                                            aria-label="Excluir tipo de documento {{ $tipo->nome }}">
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

                @include('layouts.trade-pagination', ['paginator' => $tipoDocumentos])
            @else
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <h3 class="empty-title">Nenhum tipo de documento encontrado</h3>
                    <p class="empty-text">
                        Não há tipos de documentos cadastrados com os filtros selecionados.
                    </p>
                    <a href="{{ route('tipo_documentos.index') }}" class="btn-empty">
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
        Object.assign(window.AppRoutes, {
            tipoDocsIndex: "{{ route('tipo_documentos.index') }}",
        });
    </script>
    <script>
        $(document).ready(function() {

            const SwalHelpers = {
                defaultConfig: {
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: 'swal-confirm-btn',
                        cancelButton: 'swal-cancel-btn'
                    },
                    allowEscapeKey: true,
                    width: '600px',
                    padding: '2rem'
                },

                confirm(title, html, confirmText, icon = 'warning') {
                    return Swal.fire({
                        title: title,
                        html: html,
                        icon: icon,
                        showCancelButton: true,
                        confirmButtonText: confirmText,
                        cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                        ...this.defaultConfig
                    });
                },

                loading(title, message) {
                    return Swal.fire({
                        title: title,
                        html: message,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                }
            };

            function getCurrentFilters() {
                const params = new URLSearchParams(window.location.search);
                return {
                    search: params.get('search') || '',
                    validar: params.get('validar') || '',
                    ativo: params.get('ativo') || '',
                    status: params.get('status') || '',
                    validade_tipo: params.get('validade_tipo') || ''
                };
            }

            function buildUrl(newFilters = {}) {
                const params = new URLSearchParams();

                Object.entries(newFilters).forEach(([key, value]) => {
                    const stringValue = String(value).trim();
                    if (stringValue !== '' && stringValue !== 'null' && stringValue !== 'undefined') {
                        params.append(key, stringValue);
                    }
                });

                const queryString = params.toString();
                return `${window.AppRoutes.tipoDocsIndex}${queryString ? '?' + queryString : ''}`;
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
                const filterToRemove = $(this).data('filter');
                const currentFilters = getCurrentFilters();

                delete currentFilters[filterToRemove];

                window.location.href = buildUrl(currentFilters);
            });

            $('.stat-clickable').on('click', function() {
                const filter = $(this).data('filter');

                $('.stat-clickable').removeClass('active');
                $(this).addClass('active');

                $(this).css('transform', 'scale(0.95)');

                setTimeout(() => {
                    $(this).css('transform', '');

                    if (filter === 'all') {
                        window.location.href = window.AppRoutes.tipoDocsIndex;
                    } else if (filter === 'ativos') {
                        window.location.href = buildUrl({
                            ativo: '1'
                        });
                    } else if (filter === 'com-controle') {
                        window.location.href = buildUrl({
                            validar: '1'
                        });
                    }
                }, 200);
            });

            $('.tipos-delete-form').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const $button = $(form).find('.action-btn');
                const tipoNome = $(form).data('tipo-nome');
                const tipoValidar = $(form).data('tipo-validar');
                const tipoAtivo = $(form).data('tipo-ativo');
                const tipoValidade = $(form).data('tipo-validade');

                const modalHtml = `
                    <div style="text-align: left;">
                        <p style="margin-bottom: 1.5rem; font-size: 1.05rem; color: var(--text-color); line-height: 1.6;">
                            <strong>Deseja realmente excluir este tipo de documento?</strong>
                        </p>
                        <div class="tipo-info-box">
                            <p><strong>Nome:</strong> ${tipoNome}</p>
                            <p><strong>Validade:</strong> ${tipoValidade}</p>
                            <p><strong>Controle de Validade:</strong> ${tipoValidar}</p>
                            <p><strong>Status:</strong> ${tipoAtivo}</p>
                        </div>
                        
                    </div>
                `;

                SwalHelpers.confirm(
                    'Confirmar Exclusão',
                    modalHtml,
                    '<i class="bi bi-trash"></i> Sim, excluir'
                ).then((result) => {
                    if (result.isConfirmed) {
                        $button.prop('disabled', true).html(
                            '<i class="bi bi-hourglass-split spin"></i>');

                        SwalHelpers.loading('Processando...',
                            'Aguarde enquanto excluímos o tipo de documento.');

                        form.submit();
                    }
                });
            });

            $('.tipos-restore-form').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const $button = $(form).find('.action-btn');
                const tipoNome = $(form).data('tipo-nome');

                const modalHtml = `
                    <div style="text-align: left;">
                        <p style="margin-bottom: 1.5rem; font-size: 1.05rem; color: var(--text-color); line-height: 1.6;">
                            Deseja realmente restaurar o tipo de documento <strong>${tipoNome}</strong>?
                        </p>
                        <div class="info-box">
                            <p>
                                <i class="bi bi-info-circle-fill"></i>
                                <span>O tipo de documento será reativado e ficará disponível novamente no sistema para uso em novos documentos.</span>
                            </p>
                        </div>
                    </div>
                `;

                Swal.fire({
                    title: 'Confirmar Restauração',
                    html: modalHtml,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Sim, restaurar',
                    cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                    customClass: {
                        confirmButton: 'swal-confirm-btn swal-confirm-restore',
                        cancelButton: 'swal-cancel-btn'
                    },
                    buttonsStyling: false,
                    allowEscapeKey: true,
                    width: '600px',
                    padding: '2rem'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $button.prop('disabled', true).html(
                            '<i class="bi bi-hourglass-split spin"></i>');

                        SwalHelpers.loading('Processando...',
                            'Aguarde enquanto restauramos o tipo de documento.');

                        form.submit();
                    }
                });
            });

            $('select[name="validar"], select[name="ativo"], select[name="validade_tipo"]').on('change',
        function() {
                $('#filterForm').submit();
            });

            @if (session('success'))
                toastr.success('{{ session('success') }}');
            @endif

            @if (session('error'))
                toastr.error('{{ session('error') }}');
            @endif
        });
    </script>
@endpush

@push('styles')
    <style>
        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .spin {
            animation: spin 1s linear infinite;
        }

        .veiculos-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.375rem;
        }

        .veiculo-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .veiculo-badge i {
            font-size: 0.8rem;
        }

        .badge-veiculo-universal {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .badge-veiculo-radiotv {
            background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%);
            color: var(--danger-color);
            border: 1px solid #fca5a5;
        }

        .badge-veiculo-internet {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            color: #2563eb;
            border: 1px solid #93c5fd;
        }

        .badge-veiculo-impresso {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            color: #059669;
            border: 1px solid #6ee7b7;
        }

        .badge-veiculo-default {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: #4b5563;
            border: 1px solid #d1d5db;
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

        .tipo-info-box {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            padding: 1.25rem;
            border-radius: 10px;
            margin: 1.25rem 0;
            border-left: 4px solid #f59e0b;
        }

        .tipo-info-box p {
            margin: 0.5rem 0;
            color: var(--text-color);
            font-size: 0.9375rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tipo-info-box p:first-child {
            margin-top: 0;
        }

        .tipo-info-box p:last-child {
            margin-bottom: 0;
        }

        .tipo-info-box strong {
            color: #1f2937;
            min-width: 140px;
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
            flex-shrink: 0;
        }

        .warning-box {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            padding: 1.25rem;
            border-radius: 10px;
            margin-top: 1.25rem;
            border-left: 4px solid #f59e0b;
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
        }

        .warning-box i {
            color: var(--warning-color);
            font-size: 1.25rem;
            margin-top: 0.125rem;
            flex-shrink: 0;
        }

        .warning-box strong {
            color: #78350f;
            font-weight: 700;
            display: block;
            margin-bottom: 0.5rem;
        }

        .warning-box ul {
            font-size: 0.875rem;
            color: #78350f;
            line-height: 1.6;
            margin: 0.5rem 0 0 0;
            padding-left: 1.25rem;
        }

        .warning-box li {
            margin-bottom: 0.375rem;
        }

        .warning-box li:last-child {
            margin-bottom: 0;
        }

        .swal-confirm-restore {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        }

        .swal-confirm-restore:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.35) !important;
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

        [data-theme="dark"] .tipo-info-box {
            background: rgba(245, 158, 11, 0.1) !important;
            border-left-color: #fbbf24 !important;
        }

        [data-theme="dark"] .tipo-info-box p {
            color: #d1d5db !important;
        }

        [data-theme="dark"] .tipo-info-box strong {
            color: #f9fafb !important;
        }

        [data-theme="dark"] .info-box {
            background: rgba(59, 130, 246, 0.1) !important;
            border-left-color: #60a5fa !important;
        }

        [data-theme="dark"] .info-box p {
            color: #d1d5db !important;
        }

        [data-theme="dark"] .warning-box {
            background: rgba(251, 191, 36, 0.15) !important;
            border-left-color: #fbbf24 !important;
        }

        [data-theme="dark"] .warning-box strong {
            color: #fde68a !important;
        }

        [data-theme="dark"] .warning-box ul {
            color: #fde68a !important;
        }

        [data-theme="dark"] .warning-box i {
            color: #fbbf24 !important;
        }

        [data-theme="dark"] .badge-veiculo-universal {
            background: rgba(5, 150, 105, 0.15) !important;
            color: #34d399 !important;
            border-color: rgba(52, 211, 153, 0.3) !important;
        }

        [data-theme="dark"] .badge-veiculo-radiotv {
            background: rgba(220, 38, 38, 0.15) !important;
            color: #f87171 !important;
            border-color: rgba(248, 113, 113, 0.3) !important;
        }

        [data-theme="dark"] .badge-veiculo-internet {
            background: rgba(37, 99, 235, 0.15) !important;
            color: #60a5fa !important;
            border-color: rgba(96, 165, 250, 0.3) !important;
        }

        [data-theme="dark"] .badge-veiculo-impresso {
            background: rgba(5, 150, 105, 0.15) !important;
            color: #34d399 !important;
            border-color: rgba(52, 211, 153, 0.3) !important;
        }

        [data-theme="dark"] .badge-veiculo-default {
            background: rgba(75, 85, 99, 0.3) !important;
            color: #9ca3af !important;
            border-color: rgba(156, 163, 175, 0.3) !important;
        }

        @media (max-width: 768px) {
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

            .tipo-info-box,
            .info-box,
            .warning-box {
                padding: 1rem;
            }

            .tipo-info-box p,
            .info-box p {
                font-size: 0.875rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.375rem;
            }

            .tipo-info-box strong {
                min-width: auto;
            }

            .warning-box {
                flex-direction: column;
            }

            .warning-box ul {
                font-size: 0.8125rem;
            }

            .veiculos-badges {
                flex-direction: column;
                gap: 0.25rem;
            }

            .veiculo-badge {
                font-size: 0.7rem;
                padding: 0.25rem 0.5rem;
            }
        }

        @media (max-width: 576px) {
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

        @media (max-width: 375px) {
            .swal2-popup {
                width: calc(100% - 1rem) !important;
                padding: 1rem !important;
            }
        }

        .badge-veiculo-geral {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: var(--text-muted);
            border: 1px solid #d1d5db;
        }

        [data-theme="dark"] .badge-veiculo-geral {
            background: rgba(107, 114, 128, 0.2) !important;
            color: #9ca3af !important;
            border-color: rgba(156, 163, 175, 0.3) !important;
        }
    </style>
@endpush