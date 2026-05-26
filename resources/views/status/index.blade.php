{{-- resources/views/status/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Gerenciar Status')

@section('content')
    <link href="{{ asset('css/pages/status.css') }}" rel="stylesheet">
    <div class="container-fluid px-0">
        <div class="page-header">
            <div class="header-card">
                <div class="header-top">
                    <div class="title-wrapper">
                        <div class="icon-badge">
                            <i class="bi bi-flag-fill"></i>
                        </div>
                        <div>
                            <h1 class="main-title">Gerenciar Status</h1>
                            <p class="main-subtitle">Controle os status do sistema para PIs e Documentos</p>
                        </div>
                    </div>
                    <a href="{{ route('status.create') }}" class="btn-create">
                        <i class="bi bi-plus-circle"></i>
                        <span>Novo Status</span>
                    </a>
                </div>

                <div class="stats-inline">
                    <div class="stat-inline stat-clickable" data-filter="all">
                        <span class="stat-value">{{ $totalStatus }}</span>
                        <span class="stat-label">Total</span>
                    </div>
                    <div class="stat-separator"></div>
                    <div class="stat-inline stat-clickable" data-filter="ativos">
                        <span class="stat-value stat-success">{{ $statusAtivos }}</span>
                        <span class="stat-label">Ativos</span>
                    </div>
                    <div class="stat-separator"></div>
                    <div class="stat-inline stat-clickable" data-filter="inativos">
                        <span class="stat-value stat-danger">{{ $statusInativos }}</span>
                        <span class="stat-label">Inativos</span>
                    </div>
                </div>

                @if ($statusPorTipo->isNotEmpty())
                    <div class="type-distribution">
                        <span class="distribution-label">Por tipo:</span>
                        <div class="type-tags">
                            @foreach ($statusPorTipo as $tipo)
                                @php
                                    $typeConfig = [
                                        'pi' => ['icon' => 'bi-file-earmark-plus', 'label' => 'PIs'],
                                        'documento' => ['icon' => 'bi-file-text', 'label' => 'Documentos'],
                                        'geral' => ['icon' => 'bi-gear', 'label' => 'Geral'],
                                    ];
                                    $config = $typeConfig[$tipo->tipo] ?? [
                                        'icon' => 'bi-circle',
                                        'label' => $tipo->tipo,
                                    ];
                                @endphp
                                <span class="type-tag">
                                    <i class="bi {{ $config['icon'] }}"></i>
                                    <span>{{ $config['label'] }}: <strong>{{ $tipo->total }}</strong></span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <form method="GET" action="{{ route('status.index') }}" id="filterForm">
            <input type="hidden" name="status_exibicao" id="statusExibicaoFilter" value="{{ request('status_exibicao') }}">

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

                    <select name="tipo" class="filter-select" id="tipoFilter">
                        <option value="">Todos os tipos</option>
                        <option value="pi" {{ request('tipo') === 'pi' ? 'selected' : '' }}>PIs</option>
                        <option value="documento" {{ request('tipo') === 'documento' ? 'selected' : '' }}>Documentos
                        </option>
                        <option value="geral" {{ request('tipo') === 'geral' ? 'selected' : '' }}>Geral</option>
                    </select>

                    <select name="ativo" class="filter-select" id="ativoFilter">
                        <option value="">Todos os estados</option>
                        <option value="1" {{ request('ativo') === '1' ? 'selected' : '' }}>Ativos</option>
                        <option value="0" {{ request('ativo') === '0' ? 'selected' : '' }}>Inativos</option>
                    </select>
                </div>

                <div class="toolbar-right">
                    <span class="results-count">
                        {{ $status->total() }} {{ Str::plural('resultado', $status->total()) }}
                    </span>
                </div>
            </div>
        </form>

        @if (request('search') ||
                request('tipo') ||
                (request('ativo') !== null && request('ativo') !== '') ||
                request('status_exibicao'))
            <div class="active-filters-strip">
                <span class="filters-label">Filtros ativos:</span>
                <div class="filter-tags">
                    @if (request('search'))
                        <span class="filter-tag">
                            Busca: {{ request('search') }}
                            <button type="button" class="remove-filter" data-filter="search">×</button>
                        </span>
                    @endif
                    @if (request('tipo'))
                        <span class="filter-tag">
                            Tipo: {{ ucfirst(request('tipo')) }}
                            <button type="button" class="remove-filter" data-filter="tipo">×</button>
                        </span>
                    @endif
                    @if (request('ativo') !== null && request('ativo') !== '')
                        <span class="filter-tag">
                            Estado: {{ request('ativo') === '1' ? 'Ativo' : 'Inativo' }}
                            <button type="button" class="remove-filter" data-filter="ativo">×</button>
                        </span>
                    @endif
                    @if (request('status_exibicao'))
                        <span class="filter-tag">
                            Exibição: {{ ucfirst(request('status_exibicao')) }}
                            <button type="button" class="remove-filter" data-filter="status_exibicao">×</button>
                        </span>
                    @endif
                </div>
                <a href="{{ route('status.index') }}" class="clear-filters-link">
                    Limpar tudo
                </a>
            </div>
        @endif

        <div class="table-container">
            @if ($status->count() > 0)
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="th-color">Cor</th>
                                <th class="th-primary">
                                    <span class="th-content">
                                        <i class="bi bi-tag"></i>
                                        Nome
                                    </span>
                                </th>
                                <th class="th-secondary">Tipo</th>
                                <th class="th-secondary">Estado</th>
                                <th class="th-secondary">Uso</th>
                                <th class="th-actions">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($status as $statusItem)
                                <tr class="table-row {{ $statusItem->trashed() ? 'row-inactive' : '' }}">
                                    <td class="td-color">
                                        <div class="color-preview" style="background: {{ $statusItem->cor }};"
                                            title="{{ $statusItem->cor }}"></div>
                                    </td>

                                    <td class="td-primary">
                                        <div class="cell">
                                            <div class="main-info">
                                                <span class="name-table">{{ $statusItem->nome }}</span>
                                                @if ($statusItem->descricao)
                                                    <span
                                                        class="description">{{ Str::limit($statusItem->descricao, 80) }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <td class="td-secondary">
                                        @php
                                            $typeClass = match ($statusItem->tipo) {
                                                'pi' => 'type-pi',
                                                'documento' => 'type-documento',
                                                'geral' => 'type-geral',
                                                default => 'type-default',
                                            };
                                            $typeIcon = match ($statusItem->tipo) {
                                                'pi' => 'bi-file-earmark-plus',
                                                'documento' => 'bi-file-text',
                                                'geral' => 'bi-gear',
                                                default => 'bi-circle',
                                            };
                                        @endphp
                                        <span class="type-badge {{ $typeClass }}">
                                            <i class="bi {{ $typeIcon }}"></i>
                                            {{ ucfirst($statusItem->tipo) }}
                                        </span>
                                    </td>

                                    <td class="td-secondary">
                                        <span
                                            class="status-badge {{ $statusItem->ativo ? 'status-active' : 'status-inactive' }}">
                                            <i
                                                class="bi {{ $statusItem->ativo ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i>
                                            {{ $statusItem->ativo ? 'Ativo' : 'Inativo' }}
                                        </span>
                                    </td>

                                    <td class="td-secondary">
                                        <div class="usage-info">
                                            @php
                                                $totalUso =
                                                    $statusItem->pis_count + $statusItem->documentos_enviados_count;
                                            @endphp
                                            @if ($totalUso > 0)
                                                <span class="usage-badge usage-active"
                                                    title="{{ $statusItem->pis_count }} PIs, {{ $statusItem->documentos_enviados_count }} Documentos">
                                                    <i class="bi bi-link-45deg"></i>
                                                    {{ $totalUso }}
                                                </span>
                                            @else
                                                <span class="usage-badge usage-none">
                                                    <i class="bi bi-dash-circle"></i>
                                                    Sem uso
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="td-actions">
                                        <div class="action-buttons">
                                            @if ($statusItem->trashed())
                                                <form action="{{ route('status.restore', $statusItem->id) }}"
                                                    method="POST" class="d-inline status-restore-form">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="action-btn action-restore"
                                                        title="Restaurar">
                                                        <i class="bi bi-arrow-counterclockwise"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <a href="{{ route('status.edit', $statusItem) }}"
                                                    class="action-btn action-edit" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('status.destroy', $statusItem) }}" method="POST"
                                                    class="d-inline status-delete-form"
                                                    data-status-name="{{ $statusItem->nome }}"
                                                    data-status-tipo="{{ $statusItem->tipo }}"
                                                    data-status-uso="{{ $totalUso }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="action-btn action-delete"
                                                        title="Excluir">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @include('layouts.trade-pagination', ['paginator' => $status])
            @else
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="bi bi-flag"></i>
                    </div>
                    <h3 class="empty-title">Nenhum status encontrado</h3>
                    <p class="empty-text">
                        Não há status cadastrados com os filtros selecionados.
                    </p>
                    <a href="{{ route('status.index') }}" class="btn-empty">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Limpar Filtros</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('styles')
    <style>
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

        .swal2-icon.swal2-info {
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

        .status-info-box {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            padding: 1.25rem;
            border-radius: 10px;
            margin: 1.25rem 0;
            border-left: 4px solid #f59e0b;
        }

        .status-info-box p {
            margin: 0.5rem 0;
            color: var(--text-color);
            font-size: 0.9375rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-info-box p:first-child {
            margin-top: 0;
        }

        .status-info-box p:last-child {
            margin-bottom: 0;
        }

        .status-info-box strong {
            color: #1f2937;
            min-width: 60px;
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

        .warning-box {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            padding: 1.25rem;
            border-radius: 10px;
            margin: 1.25rem 0;
            border-left: 4px solid #f59e0b;
        }

        .warning-box p {
            margin: 0;
            color: var(--text-on-light-bg);
            font-size: 0.9375rem;
            display: flex;
            align-items: flex-start;
            gap: 0.625rem;
            line-height: 1.6;
        }

        .warning-box i {
            color: var(--warning-color);
            font-size: 1.125rem;
            margin-top: 0.125rem;
        }

        .error-box {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            padding: 1.25rem;
            border-radius: 10px;
            margin: 1.25rem 0;
            border-left: 4px solid #ef4444;
        }

        .error-box p {
            margin: 0;
            color: var(--danger-color);
            font-size: 0.9375rem;
            display: flex;
            align-items: flex-start;
            gap: 0.625rem;
            line-height: 1.6;
        }

        .error-box i {
            color: #ef4444;
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

        .swal-confirm-restore {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        }

        .swal-confirm-restore:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.35) !important;
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

        [data-theme="dark"] .status-info-box {
            background: rgba(245, 158, 11, 0.1) !important;
            border-left-color: #fbbf24 !important;
        }

        [data-theme="dark"] .status-info-box p {
            color: #d1d5db !important;
        }

        [data-theme="dark"] .status-info-box strong {
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
            background: rgba(245, 158, 11, 0.1) !important;
            border-left-color: #fbbf24 !important;
        }

        [data-theme="dark"] .warning-box p {
            color: #fde68a !important;
        }

        [data-theme="dark"] .error-box {
            background: rgba(239, 68, 68, 0.1) !important;
            border-left-color: #f87171 !important;
        }

        [data-theme="dark"] .error-box p {
            color: #fecaca !important;
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

            .status-info-box,
            .info-box,
            .warning-box,
            .error-box {
                padding: 1rem;
            }

            .status-info-box p,
            .info-box p,
            .warning-box p,
            .error-box p {
                font-size: 0.875rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.375rem;
            }

            .status-info-box strong {
                min-width: auto;
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
    </style>
@endpush

@push('scripts')
    <script>
        window.AppRoute = {
            statusIndex: "{{ route('status.index') }}",
        };

        $(document).ready(function() {

            function getCurrentFilters() {
                const params = new URLSearchParams(window.location.search);
                return {
                    search: params.get('search') || '',
                    tipo: params.get('tipo') || '',
                    ativo: params.get('ativo') || '',
                    status_exibicao: params.get('status_exibicao') || ''
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
                return `${window.AppRoute.statusIndex}${queryString ? '?' + queryString : ''}`;
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

                $(this).css('transform', 'scale(0.95)');

                setTimeout(() => {
                    $(this).css('transform', '');

                    if (filter === 'all') {
                        window.location.href = window.AppRoute.statusIndex;
                    } else if (filter === 'ativos' || filter === 'inativos') {
                        window.location.href = buildUrl({
                            status_exibicao: filter
                        });
                    }
                }, 200);
            });

            function marcarCardAtivo() {
                const currentFilters = getCurrentFilters();
                $('.stat-clickable').removeClass('active');

                const filtrosAtivos = Object.values(currentFilters).filter(v => v !== '').length;

                if (filtrosAtivos === 0) {
                    $('.stat-clickable[data-filter="all"]').addClass('active');
                } else if (filtrosAtivos === 1 && currentFilters.status_exibicao) {
                    $(`.stat-clickable[data-filter="${currentFilters.status_exibicao}"]`).addClass('active');
                }
            }

            marcarCardAtivo();

            $('#ativoFilter').on('change', function() {
                $('#statusExibicaoFilter').val('');
                $('#filterForm').submit();
            });

            $('#tipoFilter').on('change', function() {
                $('#filterForm').submit();
            });

            $('.status-delete-form').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const statusName = $(form).data('status-name');
                const statusTipo = $(form).data('status-tipo');
                const statusUso = $(form).data('status-uso');

                let usageWarning = '';
                if (statusUso > 0) {
                    usageWarning = `
                        <div class="warning-box">
                            <p>
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <span><strong>Atenção:</strong> Este status está sendo usado em <strong>${statusUso}</strong> registro(s). Ao excluí-lo, os registros associados poderão ser afetados.</span>
                            </p>
                        </div>
                    `;
                }

                Swal.fire({
                    title: 'Confirmar Exclusão',
                    html: `
                        <div style="text-align: left;">
                            <p style="margin-bottom: 1.5rem; font-size: 1.05rem; color: var(--text-color); line-height: 1.6;">
                                <strong>Deseja realmente excluir este status?</strong>
                            </p>
                            <div class="status-info-box">
                                <p><strong>Nome:</strong> ${escapeHtml(statusName)}</p>
                                <p><strong>Tipo:</strong> ${escapeHtml(statusTipo)}</p>
                            </div>
                            ${usageWarning}
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

            $('.status-restore-form').on('submit', function(e) {
                e.preventDefault();
                const form = this;

                Swal.fire({
                    title: 'Confirmar Restauração',
                    html: `
                        <div style="text-align: left;">
                            <p style="font-size: 1.05rem; color: var(--text-color); line-height: 1.6;">
                                Deseja realmente restaurar este status? Ele voltará a ficar disponível no sistema.
                            </p>
                            <div class="info-box">
                                <p>
                                    <i class="bi bi-info-circle-fill"></i>
                                    <span>O status poderá ser utilizado normalmente após a restauração.</span>
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
            @endif

            @if (session('error'))
                toastr.error('{{ session('error') }}');
            @endif
        });
    </script>
@endpush