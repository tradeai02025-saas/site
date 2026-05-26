{{-- resources/views/pis/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Gerenciar PIs')

@section('content')
    <div class="container-fluid px-0">
        <div class="page-header">
            <div class="header-card">
                <div class="header-top">
                    <div class="title-wrapper">
                        <div class="icon-badge">
                            <i class="bi bi-file-earmark-text-fill"></i>
                        </div>
                        <div>
                            <h1 class="main-title">Gerenciar PIs</h1>
                            <p class="main-subtitle">
                                @hasanyrole('admin|gerencial')
                                    Gestão completa de Pedidos de Inserção
                                @endhasanyrole
                                @hasrole('operacional')
                                    Meus Pedidos de Inserção atribuídos
                                @endhasrole
                                @hasrole('fornecedor')
                                    Meus Pedidos de Inserção
                                @endhasrole
                                @hasrole('representante')
                                    PIs dos Fornecedores Representados
                                @endhasrole
                            </p>
                        </div>
                    </div>
                    <div class="header-actions">
                        @hasanyrole('admin|gerencial')
                            <a href="{{ route('pis.envio_massa') }}" class="btn-reatribuir-header">
                                <i class="bi bi-arrow-left-right"></i>
                                <span>Reatribuir PIs</span>
                            </a>
                        @endhasanyrole
                        @hasanyrole('admin|gerencial|operacional|fornecedor|representante')
                            <a href="{{ route('pis.create') }}" class="btn-create">
                                <i class="bi bi-plus-circle"></i>
                                <span>Registrar PI</span>
                            </a>
                        @endhasanyrole
                    </div>
                </div>

                <div class="stats-inline">
                    @hasanyrole('admin|gerencial')
                        <div class="stat-inline stat-clickable {{ !request('status_filtro') ? 'active' : '' }}"
                            data-filter="all">
                            <span class="stat-value">{{ $totalPis }}</span>
                            <span class="stat-label">Total</span>
                        </div>
                        <div class="stat-separator"></div>
                        <div class="stat-inline stat-clickable {{ request('status_filtro') === 'andamento' ? 'active' : '' }}"
                            data-filter="andamento">
                            <span class="stat-value stat-warning">{{ $pisEmAndamento }}</span>
                            <span class="stat-label">Em Andamento</span>
                        </div>
                        <div class="stat-separator"></div>
                        <div class="stat-inline stat-clickable {{ request('status_filtro') === 'concluidos' ? 'active' : '' }}"
                            data-filter="concluidos">
                            <span class="stat-value stat-success">{{ $pisConcluidos }}</span>
                            <span class="stat-label">Concluídos</span>
                        </div>
                    @endhasanyrole

                    @hasrole('operacional')
                        <div class="stat-inline">
                            <span class="stat-value stat-primary">{{ $meusPis }}</span>
                            <span class="stat-label">Meus PIs</span>
                        </div>
                        <div class="stat-separator"></div>
                        <div class="stat-inline">
                            <span class="stat-value stat-warning">{{ $docsParaValidar }}</span>
                            <span class="stat-label">P/ Validar</span>
                        </div>
                        <div class="stat-separator"></div>
                        <div class="stat-inline">
                            <span class="stat-value stat-danger">{{ $docsComProblema }}</span>
                            <span class="stat-label">Reenvio</span>
                        </div>
                        <div class="stat-separator"></div>
                        <div class="stat-inline">
                            <span class="stat-value stat-info">{{ $mensagensNaoLidas }}</span>
                            <span class="stat-label">Mensagens</span>
                        </div>
                    @endhasrole

                    @hasanyrole('fornecedor|representante')
                        <div class="stat-inline">
                            <span class="stat-value stat-primary">{{ $meusPis }}</span>
                            <span class="stat-label">Meus PIs</span>
                        </div>
                        <div class="stat-separator"></div>
                        <div class="stat-inline">
                            <span class="stat-value stat-warning">{{ $docsRequeridos }}</span>
                            <span class="stat-label">Docs Requeridos</span>
                        </div>
                        <div class="stat-separator"></div>
                        <div class="stat-inline">
                            <span class="stat-value stat-success">{{ $docsAtendidos }}</span>
                            <span class="stat-label">Docs Atendidos</span>
                        </div>
                        <div class="stat-separator"></div>
                        <div class="stat-inline">
                            <span class="stat-value stat-info">{{ $conversasAbertas }}</span>
                            <span class="stat-label">Conversas</span>
                        </div>
                    @endhasanyrole
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('pis.index') }}" id="filterForm">
            <input type="hidden" name="status_filtro" id="statusFiltroInput" value="{{ request('status_filtro') }}">

            <div class="toolbar">
                <div class="toolbar-left">
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" name="search" class="search-input"
                            placeholder="Buscar por número PI, cliente, fornecedor, período..."
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

                    @hasanyrole('admin|gerencial|operacional')
                        <select name="status_id" class="filter-select" onchange="this.form.submit()">
                            <option value="">Todos os Status</option>
                            @foreach ($statusList as $status)
                                <option value="{{ $status->id }}"
                                    {{ request('status_id') == $status->id ? 'selected' : '' }}>
                                    {{ $status->nome }}
                                </option>
                            @endforeach
                        </select>
                    @endhasanyrole

                    @hasanyrole('admin|gerencial|fornecedor')
                        <input type="date" name="data_inicio" class="filter-select" placeholder="Data Início"
                            value="{{ request('data_inicio') }}" onchange="this.form.submit()">
                        <input type="date" name="data_fim" class="filter-select" placeholder="Data Fim"
                            value="{{ request('data_fim') }}" onchange="this.form.submit()">
                    @endhasanyrole

                    @hasrole('gerencial')
                        <label class="toggle-filter">
                            <input type="checkbox" name="apenas_meus" value="1"
                                {{ request('apenas_meus') ? 'checked' : '' }} onchange="this.form.submit()">
                            <span class="toggle-label">Apenas Meus</span>
                        </label>
                    @endhasrole
                </div>

                <div class="toolbar-right">
                    <span class="results-count">
                        {{ $pis->total() }} {{ Str::plural('resultado', $pis->total()) }}
                    </span>
                </div>
            </div>
        </form>

        @if (request('search') ||
                request('status_id') ||
                request('data_inicio') ||
                request('data_fim') ||
                request('apenas_meus'))
            <div class="active-filters-strip">
                <span class="filters-label">Filtros ativos:</span>
                <div class="filter-tags">
                    @if (request('search'))
                        <span class="filter-tag">
                            Busca: {{ request('search') }}
                            <a href="{{ route('pis.index', array_filter(request()->except('search'))) }}"
                                class="remove-filter">×</a>
                        </span>
                    @endif
                    @if (request('status_id'))
                        @php
                            $statusSelecionado = $statusList->firstWhere('id', request('status_id'));
                        @endphp
                        <span class="filter-tag">
                            Status: {{ $statusSelecionado->nome ?? '' }}
                            <a href="{{ route('pis.index', array_filter(request()->except('status_id'))) }}"
                                class="remove-filter">×</a>
                        </span>
                    @endif
                    @if (request('data_inicio'))
                        <span class="filter-tag">
                            Data Início: {{ \Carbon\Carbon::parse(request('data_inicio'))->format('d/m/Y') }}
                            <a href="{{ route('pis.index', array_filter(request()->except('data_inicio'))) }}"
                                class="remove-filter">×</a>
                        </span>
                    @endif
                    @if (request('data_fim'))
                        <span class="filter-tag">
                            Data Fim: {{ \Carbon\Carbon::parse(request('data_fim'))->format('d/m/Y') }}
                            <a href="{{ route('pis.index', array_filter(request()->except('data_fim'))) }}"
                                class="remove-filter">×</a>
                        </span>
                    @endif
                    @if (request('apenas_meus'))
                        <span class="filter-tag">
                            Apenas Meus
                            <a href="{{ route('pis.index', array_filter(request()->except('apenas_meus'))) }}"
                                class="remove-filter">×</a>
                        </span>
                    @endif
                </div>
                <a href="{{ route('pis.index') }}" class="clear-filters-link">
                    Limpar tudo
                </a>
            </div>
        @endif

        <div class="table-container">
            @if ($pis->count() > 0)
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                @hasanyrole('admin|gerencial')
                                    <th class="th-primary">
                                        <span class="th-content">
                                            <i class="bi bi-hash"></i>
                                            Número PI
                                        </span>
                                    </th>
                                    <th class="th-secondary">Cliente</th>
                                    <th class="th-secondary">Fornecedor</th>
                                    <th class="th-secondary">Operacional Principal</th>
                                    <th class="th-secondary">Status</th>
                                    <th class="th-secondary">Data</th>
                                    <th class="th-actions">Ações</th>
                                @endhasanyrole

                                @hasrole('operacional')
                                    <th class="th-primary">
                                        <span class="th-content">
                                            <i class="bi bi-hash"></i>
                                            Número PI
                                        </span>
                                    </th>
                                    <th class="th-secondary">Cliente</th>
                                    <th class="th-secondary">Fornecedor</th>
                                    <th class="th-secondary">Status</th>
                                    <th class="th-secondary">Docs</th>
                                    <th class="th-secondary">Data</th>
                                    <th class="th-actions">Ações</th>
                                @endhasrole

                                @hasrole('fornecedor')
                                    <th class="th-primary">
                                        <span class="th-content">
                                            <i class="bi bi-hash"></i>
                                            Número PI
                                        </span>
                                    </th>
                                    <th class="th-secondary">Cliente</th>
                                    <th class="th-secondary">Status</th>
                                    <th class="th-secondary">Documentos</th>
                                    <th class="th-secondary">Conversas</th>
                                    <th class="th-secondary">Data</th>
                                    <th class="th-actions">Ações</th>
                                @endhasrole

                                @hasrole('representante')
                                    <th class="th-primary">
                                        <span class="th-content">
                                            <i class="bi bi-hash"></i>
                                            Número PI
                                        </span>
                                    </th>
                                    <th class="th-secondary">Cliente</th>
                                    <th class="th-secondary">Fornecedor</th>
                                    <th class="th-secondary">Status</th>
                                    <th class="th-secondary">Documentos</th>
                                    <th class="th-secondary">Data</th>
                                    <th class="th-actions">Ações</th>
                                @endhasrole
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pis as $index => $pi)
                                <tr class="table-row {{ $pi->concluido ? 'row-completed' : '' }} {{ (auth()->user()->hasRole('fornecedor') || auth()->user()->hasRole('representante')) && ($pi->docs_reprovados_count ?? 0) > 0 ? 'row-alert' : '' }}"
                                    data-pi-id="{{ $pi->id }}">
                                    @hasanyrole('admin|gerencial')
                                        <td class="td-primary">
                                            <div class="pi-cell">
                                                <span class="pi-numero">
                                                    {{ $pi->numero_pi }}
                                                    @if ($pi->concluido || $pi->pago)
                                                        <span class="badge-concluido">
                                                            <i class="bi bi-check-circle-fill"></i>
                                                        </span>
                                                    @endif
                                                </span>
                                                @if ($pi->periodo)
                                                    <span class="pi-periodo">{{ $pi->periodo }}</span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="td-secondary">
                                            <div class="entity-info">
                                                <span
                                                    class="entity-name">{{ $pi->cliente->razao_social ?? 'Não informado' }}</span>
                                                @if ($pi->cliente && $pi->cliente->nome_fantasia)
                                                    <span class="entity-subtitle">{{ $pi->cliente->nome_fantasia }}</span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="td-secondary">
                                            @if ($pi->fornecedor)
                                                <div class="entity-info">
                                                    <span class="entity-name">{{ $pi->fornecedor->razao_social }}</span>
                                                    @if ($pi->fornecedor->nome_fantasia)
                                                        <span
                                                            class="entity-subtitle">{{ $pi->fornecedor->nome_fantasia }}</span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted-custom">Não atribuído</span>
                                            @endif
                                        </td>

                                        <td class="td-secondary">
                                            @php
                                                $operacionalPrincipal = $pi->operacional_principal;
                                            @endphp
                                            @if ($operacionalPrincipal)
                                                <div class="user-badge">
                                                    <div class="user-avatar-small">
                                                        {{ substr($operacionalPrincipal->nome_completo, 0, 2) }}
                                                    </div>
                                                    <span
                                                        class="user-name-small">{{ $operacionalPrincipal->nome_completo }}</span>
                                                </div>
                                            @else
                                                <span class="text-muted-custom">Não atribuído</span>
                                            @endif
                                        </td>

                                        <td class="td-secondary">
                                            @if ($pi->status)
                                                <span class="status-badge"
                                                    style="background: {{ $pi->status->cor }}15; color: {{ $pi->status->cor }}; border: 1px solid {{ $pi->status->cor }}30;">
                                                    {{ $pi->status->nome }}
                                                </span>
                                            @else
                                                <span class="status-badge status-inactive">Sem Status</span>
                                            @endif
                                        </td>

                                        <td class="td-secondary">
                                            <div class="pi-dates-cell">
                                                <div class="pi-date-row">
                                                    <span class="pi-date-label">Cadastro:</span>
                                                    <span class="pi-date-value">{{ $pi->created_at->format('d/m/Y') }}</span>
                                                </div>
                                                @if ($pi->data_todos_enviados)
                                                    <div class="pi-date-row">
                                                        <span class="pi-date-label">Docs enviados:</span>
                                                        <span
                                                            class="pi-date-value">{{ \Carbon\Carbon::parse($pi->data_todos_enviados)->format('d/m/Y') }}</span>
                                                    </div>
                                                @elseif ($pi->data_primeiro_envio && $pi->total_docs > 0)
                                                    <div class="pi-date-row">
                                                        <span class="pi-date-label pi-date-partial">Envio parcial
                                                            ({{ $pi->total_docs_enviados }}/{{ $pi->total_docs }}):</span>
                                                        <span
                                                            class="pi-date-value pi-date-partial">{{ \Carbon\Carbon::parse($pi->data_primeiro_envio)->format('d/m/Y') }}</span>
                                                    </div>
                                                @endif
                                                @if ($pi->data_docs_aprovados)
                                                    <div class="pi-date-row">
                                                        <span class="pi-date-label">Docs aprovados:</span>
                                                        <span
                                                            class="pi-date-value pi-date-approved">{{ \Carbon\Carbon::parse($pi->data_docs_aprovados)->format('d/m/Y') }}</span>
                                                    </div>
                                                @endif
                                                @if ($pi->pago && $pi->data_pagamento)
                                                    <div class="pi-date-row">
                                                        <span class="pi-date-label">Pago:</span>
                                                        <span
                                                            class="pi-date-value pi-date-pago">{{ \Carbon\Carbon::parse($pi->data_pagamento)->format('d/m/Y') }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="td-actions">
                                            <button type="button" class="action-menu-btn"
                                                onclick="toggleActionMenu(event, {{ $pi->id }})">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>

                                            <div class="action-menu" id="actionMenu-{{ $pi->id }}">
                                                <button type="button" class="action-menu-item"
                                                    onclick="viewPi({{ $pi->id }})">
                                                    <i class="bi bi-eye"></i>
                                                    <span>Visualizar</span>
                                                </button>
                                                <a href="{{ route('pis.edit', $pi) }}" class="action-menu-item">
                                                    <i class="bi bi-pencil"></i>
                                                    <span>Editar</span>
                                                </a>

                                                <a href="{{ route('pis.enviar_documentos', $pi) }}" class="action-menu-item">
                                                    <i class="bi bi-cloud-arrow-up"></i>
                                                    <span>Enviar Documentos</span>
                                                    @php
                                                        $docsPendentes = $pi->piDocumentos
                                                            ->where('documento_enviado_id', null)
                                                            ->count();
                                                    @endphp
                                                    @if ($docsPendentes > 0)
                                                        <span
                                                            class="menu-badge menu-badge-warning">{{ $docsPendentes }}</span>
                                                    @endif
                                                </a>

                                                @if ($pi->piDocumentos->whereNotNull('documento_enviado_id')->isNotEmpty())
                                                    <a href="{{ route('pis.validar_documentos', $pi) }}"
                                                        class="action-menu-item">
                                                        <i class="bi bi-rocket-takeoff"></i>
                                                        <span>Validar Documentos</span>
                                                    </a>
                                                @endif

                                                <a href="{{ route('pis.baixar_todos_documentos', $pi->id) }}"
                                                    class="action-menu-item">
                                                    <i class="bi bi-download"></i>
                                                    <span>Baixar Documentos</span>
                                                </a>

                                                @if ($pi->concluido && !$pi->pago)
                                                    <button type="button" class="action-menu-item"
                                                        onclick="abrirModalFaturar({{ $pi->id }}, '{{ $pi->numero_pi }}')">
                                                        <i class="bi bi-cash-coin"></i>
                                                        <span>Pagar PI</span>
                                                    </button>
                                                @elseif ($pi->pago)
                                                    <button type="button" class="action-menu-item"
                                                        onclick="abrirModalFaturar({{ $pi->id }}, '{{ $pi->numero_pi }}', '{{ $pi->data_pagamento ? $pi->data_pagamento->format('Y-m-d') : '' }}')">
                                                        <i class="bi bi-pencil-square"></i>
                                                        <span>Editar Pagamento</span>
                                                    </button>
                                                @endif

                                                <div class="action-menu-divider"></div>
                                                <form action="{{ route('pis.destroy', $pi) }}" method="POST"
                                                    class="pi-delete-form" data-pi-numero="{{ $pi->numero_pi }}"
                                                    data-pi-cliente="{{ $pi->cliente->razao_social ?? '' }}"
                                                    onsubmit="return confirmarExclusao(event, this)">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="action-menu-item item-danger">
                                                        <i class="bi bi-trash"></i>
                                                        <span>Excluir</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endhasanyrole

                                    @hasrole('operacional')
                                        <td class="td-primary">
                                            <div class="pi-cell">
                                                <span class="pi-numero">
                                                    {{ $pi->numero_pi }}
                                                    @if ($pi->pago)
                                                        <span class="badge-pago">
                                                            <i class="bi bi-currency-dollar"></i>
                                                        </span>
                                                    @elseif ($pi->concluido)
                                                        <span class="badge-concluido">
                                                            <i class="bi bi-check-circle-fill"></i>
                                                        </span>
                                                    @endif
                                                </span>
                                                @if ($pi->periodo)
                                                    <span class="pi-periodo">{{ $pi->periodo }}</span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="td-secondary">
                                            <div class="entity-info">
                                                <span
                                                    class="entity-name">{{ $pi->cliente->razao_social ?? 'Não informado' }}</span>
                                                @if ($pi->cliente && $pi->cliente->nome_fantasia)
                                                    <span class="entity-subtitle">{{ $pi->cliente->nome_fantasia }}</span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="td-secondary">
                                            @if ($pi->fornecedor)
                                                <div class="entity-info">
                                                    <span class="entity-name">{{ $pi->fornecedor->razao_social }}</span>
                                                </div>
                                            @else
                                                <span class="text-muted-custom">Não atribuído</span>
                                            @endif
                                        </td>

                                        <td class="td-secondary">
                                            @if ($pi->status)
                                                <span class="status-badge"
                                                    style="background: {{ $pi->status->cor }}15; color: {{ $pi->status->cor }}; border: 1px solid {{ $pi->status->cor }}30;">
                                                    {{ $pi->status->nome }}
                                                </span>
                                            @else
                                                <span class="status-badge status-inactive">Sem Status</span>
                                            @endif
                                        </td>

                                        <td class="td-secondary">
                                            <div class="docs-status">
                                                @if (($pi->docs_para_validar_count ?? 0) > 0)
                                                    <span class="docs-badge docs-validar">
                                                        <i class="bi bi-hourglass-split"></i>
                                                        {{ $pi->docs_para_validar_count }} p/ validar
                                                    </span>
                                                @endif
                                                @if (($pi->docs_reprovados_count ?? 0) > 0)
                                                    <span class="docs-badge docs-reprovado">
                                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                                        {{ $pi->docs_reprovados_count }} reenvio
                                                    </span>
                                                @endif
                                                @if (($pi->docs_aprovados_count ?? 0) > 0)
                                                    <span class="docs-badge docs-aprovado">
                                                        <i class="bi bi-check-circle-fill"></i>
                                                        {{ $pi->docs_aprovados_count }}/{{ $pi->docs_total_count ?? 0 }}
                                                    </span>
                                                @endif
                                                @if (
                                                    ($pi->docs_para_validar_count ?? 0) === 0 &&
                                                        ($pi->docs_reprovados_count ?? 0) === 0 &&
                                                        ($pi->docs_aprovados_count ?? 0) === 0)
                                                    @if ($pi->concluido)
                                                        <span class="docs-badge docs-concluido">
                                                            <i class="bi bi-check-circle-fill"></i>
                                                            Concluído
                                                        </span>
                                                    @else
                                                        <span class="docs-badge docs-em-processo">
                                                            <i class="bi bi-circle-fill"></i>
                                                            Em análise
                                                        </span>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>

                                        <td class="td-secondary">
                                            <div class="pi-dates-cell">
                                                <div class="pi-date-row">
                                                    <span class="pi-date-label">Cadastro:</span>
                                                    <span class="pi-date-value">{{ $pi->created_at->format('d/m/Y') }}</span>
                                                </div>
                                                @if ($pi->data_todos_enviados)
                                                    <div class="pi-date-row">
                                                        <span class="pi-date-label">Docs enviados:</span>
                                                        <span
                                                            class="pi-date-value">{{ \Carbon\Carbon::parse($pi->data_todos_enviados)->format('d/m/Y') }}</span>
                                                    </div>
                                                @elseif ($pi->data_primeiro_envio && $pi->total_docs > 0)
                                                    <div class="pi-date-row">
                                                        <span class="pi-date-label pi-date-partial">Envio parcial
                                                            ({{ $pi->total_docs_enviados }}/{{ $pi->total_docs }}):</span>
                                                        <span
                                                            class="pi-date-value pi-date-partial">{{ \Carbon\Carbon::parse($pi->data_primeiro_envio)->format('d/m/Y') }}</span>
                                                    </div>
                                                @endif
                                                @if ($pi->data_docs_aprovados)
                                                    <div class="pi-date-row">
                                                        <span class="pi-date-label">Docs aprovados:</span>
                                                        <span
                                                            class="pi-date-value pi-date-approved">{{ \Carbon\Carbon::parse($pi->data_docs_aprovados)->format('d/m/Y') }}</span>
                                                    </div>
                                                @endif
                                                @if ($pi->pago && $pi->data_pagamento)
                                                    <div class="pi-date-row">
                                                        <span class="pi-date-label">Pago:</span>
                                                        <span
                                                            class="pi-date-value pi-date-pago">{{ \Carbon\Carbon::parse($pi->data_pagamento)->format('d/m/Y') }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="td-actions">
                                            <button type="button" class="action-menu-btn"
                                                onclick="toggleActionMenu(event, {{ $pi->id }})">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>

                                            <div class="action-menu" id="actionMenu-{{ $pi->id }}">
                                                <button type="button" class="action-menu-item"
                                                    onclick="viewPi({{ $pi->id }})">
                                                    <i class="bi bi-eye"></i>
                                                    <span>Visualizar</span>
                                                </button>

                                                <a href="{{ route('pis.edit', $pi) }}" class="action-menu-item">
                                                    <i class="bi bi-pencil"></i>
                                                    <span>Editar</span>
                                                </a>

                                                <a href="{{ route('pis.enviar_documentos', $pi) }}" class="action-menu-item">
                                                    <i class="bi bi-cloud-arrow-up"></i>
                                                    <span>Enviar Documentos</span>
                                                    @php
                                                        $docsPendentes = $pi->piDocumentos
                                                            ->where('documento_enviado_id', null)
                                                            ->count();
                                                    @endphp
                                                    @if ($docsPendentes > 0)
                                                        <span
                                                            class="menu-badge menu-badge-warning">{{ $docsPendentes }}</span>
                                                    @endif
                                                </a>

                                                @if ($pi->piDocumentos->whereNotNull('documento_enviado_id')->isNotEmpty())
                                                    <a href="{{ route('pis.validar_documentos', $pi) }}"
                                                        class="action-menu-item">
                                                        <i class="bi bi-rocket-takeoff"></i>
                                                        <span>Validar Documentos</span>
                                                    </a>
                                                @endif

                                                <a href="{{ route('pis.baixar_todos_documentos', $pi->id) }}"
                                                    class="action-menu-item">
                                                    <i class="bi bi-download"></i>
                                                    <span>Baixar Documentos</span>
                                                </a>

                                                @if ($pi->concluido && !$pi->pago)
                                                    <button type="button" class="action-menu-item"
                                                        onclick="abrirModalFaturar({{ $pi->id }}, '{{ $pi->numero_pi }}')">
                                                        <i class="bi bi-cash-coin"></i>
                                                        <span>Pagar PI</span>
                                                    </button>
                                                @elseif ($pi->pago)
                                                    <button type="button" class="action-menu-item"
                                                        onclick="abrirModalFaturar({{ $pi->id }}, '{{ $pi->numero_pi }}', '{{ $pi->data_pagamento ? $pi->data_pagamento->format('Y-m-d') : '' }}')">
                                                        <i class="bi bi-pencil-square"></i>
                                                        <span>Editar Pagamento</span>
                                                    </button>
                                                @endif

                                                <div class="action-menu-divider"></div>
                                                <form action="{{ route('pis.destroy', $pi) }}" method="POST"
                                                    class="pi-delete-form" data-pi-numero="{{ $pi->numero_pi }}"
                                                    data-pi-cliente="{{ $pi->cliente->razao_social ?? '' }}"
                                                    onsubmit="return confirmarExclusao(event, this)">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="action-menu-item item-danger">
                                                        <i class="bi bi-trash"></i>
                                                        <span>Excluir</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endhasrole

                                    @hasrole('fornecedor')
                                        <td class="td-primary">
                                            <div class="pi-cell">
                                                <span class="pi-numero">
                                                    {{ $pi->numero_pi }}
                                                    @if ($pi->concluido)
                                                        <span class="badge-concluido">
                                                            <i class="bi bi-check-circle-fill"></i>
                                                        </span>
                                                    @endif
                                                    @if (($pi->docs_reprovados_count ?? 0) > 0)
                                                        <span class="badge-alert-pi">
                                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                                            {{ $pi->docs_reprovados_count }}
                                                        </span>
                                                    @elseif(($pi->docs_pendentes_validacao_count ?? 0) > 0)
                                                        <span class="badge-pending-pi">
                                                            <i class="bi bi-clock-history"></i>
                                                            {{ $pi->docs_pendentes_validacao_count }}
                                                        </span>
                                                    @endif
                                                </span>
                                                @if ($pi->periodo)
                                                    <span class="pi-periodo">{{ $pi->periodo }}</span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="td-secondary">
                                            <div class="entity-info">
                                                <span
                                                    class="entity-name">{{ $pi->cliente->razao_social ?? 'Não informado' }}</span>
                                                @if ($pi->cliente && $pi->cliente->nome_fantasia)
                                                    <span class="entity-subtitle">{{ $pi->cliente->nome_fantasia }}</span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="td-secondary">
                                            @if ($pi->status)
                                                <span class="status-badge"
                                                    style="background: {{ $pi->status->cor }}15; color: {{ $pi->status->cor }}; border: 1px solid {{ $pi->status->cor }}30;">
                                                    {{ $pi->status->nome }}
                                                </span>
                                            @else
                                                <span class="status-badge status-inactive">Sem Status</span>
                                            @endif
                                        </td>

                                        <td class="td-secondary">
                                            <div class="docs-status-fornecedor">
                                                <div class="docs-row">
                                                    <span class="docs-badge docs-required">
                                                        <i class="bi bi-file-earmark-text"></i>
                                                        {{ $pi->documentos_requeridos_count ?? 0 }} requeridos
                                                    </span>
                                                </div>

                                                @if (($pi->documentos_atendidos_count ?? 0) > 0)
                                                    <div class="docs-row">
                                                        <span class="docs-badge docs-approved-fornec">
                                                            <i class="bi bi-check-circle-fill"></i>
                                                            {{ $pi->documentos_atendidos_count }} aprovados
                                                        </span>
                                                    </div>
                                                @endif

                                                @if (($pi->docs_reprovados_count ?? 0) > 0)
                                                    <div class="docs-row">
                                                        <span class="docs-badge docs-alert-badge">
                                                            <i class="bi bi-x-circle-fill"></i>
                                                            {{ $pi->docs_reprovados_count }}
                                                            {{ $pi->docs_reprovados_count == 1 ? 'reprovado' : 'reprovados' }}
                                                        </span>
                                                    </div>
                                                @endif

                                                @if (($pi->docs_pendentes_validacao_count ?? 0) > 0)
                                                    <div class="docs-row">
                                                        <span class="docs-badge-fornec docs-pending-fornec">
                                                            <i class="bi bi-clock-history"></i>
                                                            {{ $pi->docs_pendentes_validacao_count }} aguardando validação
                                                        </span>
                                                    </div>
                                                @endif

                                                @if (($pi->docs_nao_enviados_count ?? 0) > 0)
                                                    <div class="docs-row">
                                                        <span class="docs-badge-fornec docs-notsent-fornec">
                                                            <i class="bi bi-hourglass-split"></i>
                                                            {{ $pi->docs_nao_enviados_count }} não enviados
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="td-secondary">
                                            <div class="conversas-status">
                                                @if ($pi->conversas_count > 0)
                                                    <span class="conversas-badge">
                                                        <i class="bi bi-chat-dots"></i>
                                                        {{ $pi->conversas_count }}
                                                        @php
                                                            $mensagensNaoLidas = 0;
                                                            foreach ($pi->conversas as $conversa) {
                                                                $mensagensNaoLidas +=
                                                                    $conversa->mensagens_nao_lidas_count ?? 0;
                                                            }
                                                        @endphp
                                                        @if ($mensagensNaoLidas > 0)
                                                            <span class="conversas-unread">{{ $mensagensNaoLidas }}</span>
                                                        @endif
                                                    </span>
                                                @else
                                                    <span class="text-muted-custom">Nenhuma</span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="td-secondary">
                                            <div class="pi-dates-cell">
                                                <div class="pi-date-row">
                                                    <span class="pi-date-label">Cadastro:</span>
                                                    <span class="pi-date-value">{{ $pi->created_at->format('d/m/Y') }}</span>
                                                </div>
                                                @if ($pi->data_todos_enviados)
                                                    <div class="pi-date-row">
                                                        <span class="pi-date-label">Docs enviados:</span>
                                                        <span
                                                            class="pi-date-value">{{ \Carbon\Carbon::parse($pi->data_todos_enviados)->format('d/m/Y') }}</span>
                                                    </div>
                                                @elseif ($pi->data_primeiro_envio && $pi->total_docs > 0)
                                                    <div class="pi-date-row">
                                                        <span class="pi-date-label pi-date-partial">Envio parcial
                                                            ({{ $pi->total_docs_enviados }}/{{ $pi->total_docs }}):</span>
                                                        <span
                                                            class="pi-date-value pi-date-partial">{{ \Carbon\Carbon::parse($pi->data_primeiro_envio)->format('d/m/Y') }}</span>
                                                    </div>
                                                @endif
                                                @if ($pi->data_docs_aprovados)
                                                    <div class="pi-date-row">
                                                        <span class="pi-date-label">Docs aprovados:</span>
                                                        <span
                                                            class="pi-date-value pi-date-approved">{{ \Carbon\Carbon::parse($pi->data_docs_aprovados)->format('d/m/Y') }}</span>
                                                    </div>
                                                @endif
                                                @if ($pi->pago && $pi->data_pagamento)
                                                    <div class="pi-date-row">
                                                        <span class="pi-date-label">Pago:</span>
                                                        <span
                                                            class="pi-date-value pi-date-pago">{{ \Carbon\Carbon::parse($pi->data_pagamento)->format('d/m/Y') }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="td-actions">
                                            <button type="button" class="action-menu-btn"
                                                onclick="toggleActionMenu(event, {{ $pi->id }})">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>

                                            <div class="action-menu" id="actionMenu-{{ $pi->id }}">
                                                <button type="button" class="action-menu-item"
                                                    onclick="viewPi({{ $pi->id }})">
                                                    <i class="bi bi-eye"></i>
                                                    <span>Visualizar</span>
                                                </button>

                                                <a href="{{ route('pis.edit', $pi) }}" class="action-menu-item">
                                                    <i class="bi bi-pencil"></i>
                                                    <span>Editar</span>
                                                </a>

                                                <a href="{{ route('pis.enviar_documentos', $pi) }}"
                                                    class="action-menu-item {{ ($pi->docs_reprovados_count ?? 0) > 0 ? 'item-urgent' : '' }}">
                                                    <i class="bi bi-cloud-arrow-up"></i>
                                                    <span>Enviar Documentos</span>
                                                    @php
                                                        $docsPendentesTotal =
                                                            ($pi->docs_nao_enviados_count ?? 0) +
                                                            ($pi->docs_reprovados_count ?? 0);
                                                    @endphp
                                                    @if ($docsPendentesTotal > 0)
                                                        <span
                                                            class="menu-badge menu-badge-warning">{{ $docsPendentesTotal }}</span>
                                                    @endif
                                                </a>

                                                <a href="{{ route('pis.baixar_todos_documentos', $pi->id) }}"
                                                    class="action-menu-item">
                                                    <i class="bi bi-download"></i>
                                                    <span>Baixar Documentos</span>
                                                </a>

                                                @if ($pi->conversas_count > 0)
                                                    <div class="action-menu-divider"></div>
                                                    <a href="{{ route('chat.index', ['pi_id' => $pi->id]) }}"
                                                        class="action-menu-item">
                                                        <i class="bi bi-chat-dots"></i>
                                                        <span>Ver Conversas</span>
                                                        @php
                                                            $mensagensNaoLidas = 0;
                                                            foreach ($pi->conversas as $conversa) {
                                                                $mensagensNaoLidas +=
                                                                    $conversa->mensagens_nao_lidas_count ?? 0;
                                                            }
                                                        @endphp
                                                        @if ($mensagensNaoLidas > 0)
                                                            <span class="menu-badge">{{ $mensagensNaoLidas }}</span>
                                                        @endif
                                                    </a>
                                                @endif

                                                @if (!$pi->concluido)
                                                    <div class="action-menu-divider"></div>
                                                    <form action="{{ route('pis.destroy', $pi) }}" method="POST"
                                                        class="pi-delete-form" data-pi-numero="{{ $pi->numero_pi }}"
                                                        data-pi-cliente="{{ $pi->cliente->razao_social ?? '' }}"
                                                        onsubmit="return confirmarExclusao(event, this)">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="action-menu-item item-danger">
                                                            <i class="bi bi-trash"></i>
                                                            <span>Excluir</span>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    @endhasrole

                                    @hasrole('representante')
                                        <td class="td-primary">
                                            <div class="pi-cell">
                                                <span class="pi-numero">
                                                    {{ $pi->numero_pi }}
                                                    @if ($pi->concluido)
                                                        <span class="badge-concluido">
                                                            <i class="bi bi-check-circle-fill"></i>
                                                        </span>
                                                    @endif
                                                    @if (($pi->docs_reprovados_count ?? 0) > 0)
                                                        <span class="badge-alert-pi">
                                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                                            {{ $pi->docs_reprovados_count }}
                                                        </span>
                                                    @elseif(($pi->docs_pendentes_validacao_count ?? 0) > 0)
                                                        <span class="badge-pending-pi">
                                                            <i class="bi bi-clock-history"></i>
                                                            {{ $pi->docs_pendentes_validacao_count }}
                                                        </span>
                                                    @endif
                                                </span>
                                                @if ($pi->periodo)
                                                    <span class="pi-periodo">{{ $pi->periodo }}</span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="td-secondary">
                                            <div class="entity-info">
                                                <span
                                                    class="entity-name">{{ $pi->cliente->razao_social ?? 'Não informado' }}</span>
                                                @if ($pi->cliente && $pi->cliente->nome_fantasia)
                                                    <span class="entity-subtitle">{{ $pi->cliente->nome_fantasia }}</span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="td-secondary">
                                            <div class="entity-info">
                                                @if ($pi->fornecedor)
                                                    <span class="entity-name">{{ $pi->fornecedor->razao_social }}</span>
                                                    @if ($pi->fornecedor->nome_fantasia)
                                                        <span
                                                            class="entity-subtitle">{{ $pi->fornecedor->nome_fantasia }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted-custom">Não vinculado</span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="td-secondary">
                                            @if ($pi->status)
                                                <span class="status-badge"
                                                    style="background: {{ $pi->status->cor }}15; color: {{ $pi->status->cor }}; border: 1px solid {{ $pi->status->cor }}30;">
                                                    {{ $pi->status->nome }}
                                                </span>
                                            @else
                                                <span class="status-badge status-inactive">Sem Status</span>
                                            @endif
                                        </td>

                                        <td class="td-secondary">
                                            <div class="docs-status-fornecedor">
                                                <div class="docs-row">
                                                    <span class="docs-badge docs-required">
                                                        <i class="bi bi-file-earmark-text"></i>
                                                        {{ $pi->documentos_requeridos_count ?? 0 }} requeridos
                                                    </span>
                                                </div>

                                                @if (($pi->documentos_atendidos_count ?? 0) > 0)
                                                    <div class="docs-row">
                                                        <span class="docs-badge docs-approved-fornec">
                                                            <i class="bi bi-check-circle-fill"></i>
                                                            {{ $pi->documentos_atendidos_count }} aprovados
                                                        </span>
                                                    </div>
                                                @endif

                                                @if (($pi->docs_reprovados_count ?? 0) > 0)
                                                    <div class="docs-row">
                                                        <span class="docs-badge docs-alert-badge">
                                                            <i class="bi bi-x-circle-fill"></i>
                                                            {{ $pi->docs_reprovados_count }}
                                                            {{ $pi->docs_reprovados_count == 1 ? 'reprovado' : 'reprovados' }}
                                                        </span>
                                                    </div>
                                                @endif

                                                @if (($pi->docs_nao_enviados_count ?? 0) > 0)
                                                    <div class="docs-row">
                                                        <span class="docs-badge-fornec docs-notsent-fornec">
                                                            <i class="bi bi-hourglass-split"></i>
                                                            {{ $pi->docs_nao_enviados_count }} não enviados
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="td-secondary">
                                            <div class="pi-dates-cell">
                                                <div class="pi-date-row">
                                                    <span class="pi-date-label">Cadastro:</span>
                                                    <span class="pi-date-value">{{ $pi->created_at->format('d/m/Y') }}</span>
                                                </div>
                                                @if ($pi->data_todos_enviados)
                                                    <div class="pi-date-row">
                                                        <span class="pi-date-label">Docs enviados:</span>
                                                        <span
                                                            class="pi-date-value">{{ \Carbon\Carbon::parse($pi->data_todos_enviados)->format('d/m/Y') }}</span>
                                                    </div>
                                                @elseif ($pi->data_primeiro_envio && $pi->total_docs > 0)
                                                    <div class="pi-date-row">
                                                        <span class="pi-date-label pi-date-partial">Envio parcial
                                                            ({{ $pi->total_docs_enviados }}/{{ $pi->total_docs }}):</span>
                                                        <span
                                                            class="pi-date-value pi-date-partial">{{ \Carbon\Carbon::parse($pi->data_primeiro_envio)->format('d/m/Y') }}</span>
                                                    </div>
                                                @endif
                                                @if ($pi->data_docs_aprovados)
                                                    <div class="pi-date-row">
                                                        <span class="pi-date-label">Docs aprovados:</span>
                                                        <span
                                                            class="pi-date-value pi-date-approved">{{ \Carbon\Carbon::parse($pi->data_docs_aprovados)->format('d/m/Y') }}</span>
                                                    </div>
                                                @endif
                                                @if ($pi->pago && $pi->data_pagamento)
                                                    <div class="pi-date-row">
                                                        <span class="pi-date-label">Pago:</span>
                                                        <span
                                                            class="pi-date-value pi-date-pago">{{ \Carbon\Carbon::parse($pi->data_pagamento)->format('d/m/Y') }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="td-actions">
                                            <button type="button" class="action-menu-btn"
                                                onclick="toggleActionMenu(event, {{ $pi->id }})">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>

                                            <div class="action-menu" id="actionMenu-{{ $pi->id }}">
                                                <button type="button" class="action-menu-item"
                                                    onclick="viewPi({{ $pi->id }})">
                                                    <i class="bi bi-eye"></i>
                                                    <span>Visualizar</span>
                                                </button>

                                                <a href="{{ route('pis.enviar_documentos', $pi) }}"
                                                    class="action-menu-item {{ ($pi->docs_reprovados_count ?? 0) > 0 ? 'item-urgent' : '' }}">
                                                    <i class="bi bi-cloud-arrow-up"></i>
                                                    <span>Enviar Documentos</span>
                                                    @php
                                                        $docsPendentesTotal =
                                                            ($pi->docs_nao_enviados_count ?? 0) +
                                                            ($pi->docs_reprovados_count ?? 0);
                                                    @endphp
                                                    @if ($docsPendentesTotal > 0)
                                                        <span
                                                            class="menu-badge menu-badge-warning">{{ $docsPendentesTotal }}</span>
                                                    @endif
                                                </a>

                                                <a href="{{ route('pis.baixar_todos_documentos', $pi->id) }}"
                                                    class="action-menu-item">
                                                    <i class="bi bi-download"></i>
                                                    <span>Baixar Documentos</span>
                                                </a>

                                                @if (!$pi->concluido)
                                                    <div class="action-menu-divider"></div>
                                                    <form action="{{ route('pis.destroy', $pi) }}" method="POST"
                                                        class="pi-delete-form" data-pi-numero="{{ $pi->numero_pi }}"
                                                        data-pi-cliente="{{ $pi->cliente->razao_social ?? '' }}"
                                                        onsubmit="return confirmarExclusao(event, this)">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="action-menu-item item-danger">
                                                            <i class="bi bi-trash"></i>
                                                            <span>Excluir</span>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    @endhasrole
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @include('layouts.trade-pagination', ['paginator' => $pis])
            @else
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <h3 class="empty-title">Nenhum PI encontrado</h3>
                    <p class="empty-text">
                        @if (request()->hasAny(['search', 'status_id', 'data_inicio', 'data_fim', 'apenas_meus']))
                            Não há PIs cadastrados com os filtros selecionados.
                        @else
                            Não há PIs cadastrados no momento.
                        @endif
                    </p>
                    @if (request()->hasAny(['search', 'status_id', 'data_inicio', 'data_fim', 'apenas_meus']))
                        <a href="{{ route('pis.index') }}" class="btn-empty">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            <span>Limpar Filtros</span>
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="piModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-header-content">
                        <div class="modal-icon">
                            <i class="bi bi-file-earmark-text-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title">Detalhes do PI</h5>
                            <p class="modal-subtitle">Visualização completa das informações</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close-modal" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body" id="piModalBody">
                    <div class="loading-container">
                        <div class="spinner-border"></div>
                        <p class="loading-text">Carregando dados do PI...</p>
                    </div>
                </div>
                <div class="modal-footer-actions" id="piModalFooter" style="display: none;">
                    <div class="modal-actions-scroll"></div>
                </div>
            </div>
        </div>
    </div>

    @hasanyrole('admin|gerencial|operacional')
        <div class="modal fade" id="faturarModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-header-content">
                            <div class="modal-icon">
                                <i class="bi bi-cash-coin"></i>
                            </div>
                            <div>
                                <h5 class="modal-title" id="faturarModalTitle">Pagar PI</h5>
                                <p class="modal-subtitle" id="faturarModalSubtitle">Informe a data de pagamento</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close-modal" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="modal-body" style="padding: 2rem;">
                        <div class="faturar-form-group">
                            <label for="inputDataPagamento" class="faturar-form-label">
                                <i class="bi bi-calendar-event"></i>
                                Data de Pagamento
                            </label>
                            <input type="date" class="faturar-form-input" id="inputDataPagamento" required>
                        </div>
                    </div>
                    <div class="faturar-modal-footer">
                        <div>
                            <button type="button" class="faturar-btn faturar-btn-remover" id="btnRemoverPagamento"
                                style="display: none;" onclick="removerPagamento()">
                                <i class="bi bi-x-circle"></i> Remover Pagamento
                            </button>
                        </div>
                        <div class="faturar-footer-actions">
                            <button type="button" class="faturar-btn faturar-btn-cancelar"
                                data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="faturar-btn faturar-btn-confirmar" id="btnConfirmarFaturar"
                                onclick="confirmarFaturar()">
                                <i class="bi bi-check-circle"></i> Confirmar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endhasanyrole
@endsection

@push('styles')
    <link href="{{ asset('css/pages/pis.css') }}" rel="stylesheet">
    <style>
        #faturarModal .modal-content {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
        }

        #faturarModal .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            border-radius: 0;
            padding: 1.5rem 2rem;
            border-bottom: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        #faturarModal .modal-title {
            font-size: 1.25rem;
            font-weight: 800;
            margin: 0;
            color: white;
        }

        #faturarModal .modal-subtitle {
            color: rgba(255, 255, 255, 0.9);
        }

        #faturarModal .modal-icon {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        #faturarModal .modal-body {
            background: var(--bg-card);
            padding: 2rem;
        }

        .faturar-form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .faturar-form-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .faturar-form-label i {
            color: var(--primary-color);
        }

        .faturar-form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.9375rem;
            color: var(--text-primary);
            background: var(--bg-card);
            transition: all 0.2s ease;
        }

        .faturar-form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 94, 130, 0.15);
        }

        .faturar-modal-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 2rem;
            border-top: 1px solid var(--border-color);
            background: var(--bg-secondary, #f8fafc);
            border-radius: 0 0 16px 16px;
        }

        .faturar-footer-actions {
            display: flex;
            gap: 0.5rem;
        }

        .faturar-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.625rem 1.25rem;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .faturar-btn-cancelar {
            background: var(--bg-card);
            color: var(--text-muted);
            border: 1px solid var(--border-color);
        }

        .faturar-btn-cancelar:hover {
            background: var(--bg-secondary, #f1f5f9);
            color: var(--text-primary);
        }

        .faturar-btn-confirmar {
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
            color: white;
        }

        .faturar-btn-confirmar:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
            transform: translateY(-1px);
        }

        .faturar-btn-confirmar:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .faturar-btn-remover {
            background: transparent;
            color: var(--danger-color, #ef4444);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .faturar-btn-remover:hover {
            background: rgba(239, 68, 68, 0.08);
            border-color: var(--danger-color, #ef4444);
        }

        [data-theme="dark"] .faturar-modal-footer {
            background: rgba(30, 41, 59, 0.8);
        }

        [data-theme="dark"] .faturar-form-input {
            background: var(--bg-secondary);
            border-color: var(--border-color);
            color: var(--text-primary);
        }

        [data-theme="dark"] .faturar-btn-cancelar {
            background: var(--bg-secondary);
            border-color: var(--border-color);
            color: var(--text-muted);
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/@floating-ui/core@1.5.2"></script>
    <script src="https://cdn.jsdelivr.net/npm/@floating-ui/dom@1.5.3"></script>

    <script>
        const isFornecedor =
            {{ auth()->user()->hasRole('fornecedor') || auth()->user()->hasRole('representante') ? 'true' : 'false' }};
        let currentOpenMenu = null;
        let currentOpenButton = null;
        let isMobile = window.innerWidth <= 768;

        function closeCurrentMenu() {
            if (currentOpenMenu) {
                currentOpenMenu.classList.remove('active');
                currentOpenMenu.style.display = 'none';
                currentOpenMenu = null;
                currentOpenButton = null;
            }
        }

        async function positionMenu(button, menu) {
            const buttonRect = button.getBoundingClientRect();
            const menuWidth = 220;

            if (typeof FloatingUIDOM !== 'undefined') {
                try {
                    const {
                        x,
                        y
                    } = await FloatingUIDOM.computePosition(button, menu, {
                        placement: 'bottom-end',
                        strategy: 'fixed',
                        middleware: [
                            FloatingUIDOM.offset(8),
                            FloatingUIDOM.flip({
                                fallbackPlacements: ['top-end', 'bottom-start', 'top-start', 'left',
                                    'right'
                                ],
                                padding: 8
                            }),
                            FloatingUIDOM.shift({
                                padding: 8
                            }),
                            FloatingUIDOM.size({
                                apply({
                                    availableHeight,
                                    elements
                                }) {
                                    Object.assign(elements.floating.style, {
                                        maxHeight: `${Math.max(200, availableHeight - 16)}px`,
                                        overflowY: 'auto'
                                    });
                                },
                                padding: 8
                            })
                        ]
                    });

                    Object.assign(menu.style, {
                        position: 'fixed',
                        left: `${x}px`,
                        top: `${y}px`,
                        margin: '0',
                        transform: 'none',
                        width: `${menuWidth}px`
                    });

                    return true;
                } catch (error) {}
            }

            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            const menuMaxHeight = 400;

            let left = buttonRect.right - menuWidth;
            let top = buttonRect.bottom + 8;

            if (left < 8) {
                left = buttonRect.left;
            }
            if (left + menuWidth > viewportWidth - 8) {
                left = viewportWidth - menuWidth - 8;
            }

            const menuHeight = Math.min(menuMaxHeight, menu.scrollHeight);

            if (top + menuHeight > viewportHeight - 8) {
                top = buttonRect.top - menuHeight - 8;

                if (top < 8) {
                    top = 8;
                    menu.style.maxHeight = `${viewportHeight - 16}px`;
                    menu.style.overflowY = 'auto';
                }
            }

            Object.assign(menu.style, {
                position: 'fixed',
                left: `${left}px`,
                top: `${top}px`,
                width: `${menuWidth}px`,
                margin: '0',
                transform: 'none'
            });

            return true;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        document.getElementById('filterForm').submit();
                    }
                });
            }

            const clearBtn = document.querySelector('.search-clear');
            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    document.querySelector('.search-input').value = '';
                    document.getElementById('filterForm').submit();
                });
            }

            const statClickables = document.querySelectorAll('.stat-clickable');
            statClickables.forEach(stat => {
                stat.addEventListener('click', function() {
                    const filter = this.dataset.filter;
                    const input = document.getElementById('statusFiltroInput');
                    input.value = filter === 'all' ? '' : filter;
                    document.getElementById('filterForm').submit();
                });
            });

            document.addEventListener('click', function(e) {
                if (e.target.closest('.action-menu-item') || e.target.closest('.action-menu form button')) {
                    closeCurrentMenu();
                }
            });

            const tableRows = document.querySelectorAll('.table-row');
            tableRows.forEach(row => {
                row.addEventListener('click', function(e) {
                    if (isMobile && !e.target.closest('.action-menu-btn') && !e.target.closest(
                            '.action-menu')) {
                        const piId = this.dataset.piId;
                        viewPi(piId);
                    }
                });
            });

            window.addEventListener('resize', function() {
                isMobile = window.innerWidth <= 768;
                closeCurrentMenu();
            });
        });

        document.addEventListener('click', function(e) {
            if (currentOpenMenu && !e.target.closest('.action-menu') && !e.target.closest('.action-menu-btn')) {
                closeCurrentMenu();
            }
        });

        const tableWrapper = document.querySelector('.table-wrapper');
        if (tableWrapper) {
            tableWrapper.addEventListener('scroll', function() {
                if (currentOpenMenu && currentOpenButton) {
                    positionMenu(currentOpenButton, currentOpenMenu);
                }
            });
        }

        window.addEventListener('scroll', function() {
            if (currentOpenMenu && currentOpenButton) {
                positionMenu(currentOpenButton, currentOpenMenu);
            }
        });

        async function toggleActionMenu(event, piId) {
            event.stopPropagation();
            const button = event.currentTarget;
            const menu = document.getElementById(`actionMenu-${piId}`);

            if (currentOpenMenu && currentOpenMenu !== menu) {
                closeCurrentMenu();
            }

            if (menu.classList.contains('active')) {
                closeCurrentMenu();
            } else {
                menu.style.display = 'block';

                await positionMenu(button, menu);

                requestAnimationFrame(() => {
                    menu.classList.add('active');
                    currentOpenMenu = menu;
                    currentOpenButton = button;
                });
            }
        }

        function viewPi(piId) {
            const modal = new bootstrap.Modal(document.getElementById('piModal'));
            modal.show();
            carregarDetalhesPi(piId);
            closeCurrentMenu();
        }

        function carregarDetalhesPi(piId) {
            const detalhesUrl = "{{ route('pis.detalhes', ':id') }}".replace(':id', piId);

            fetch(detalhesUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderizarDetalhesPi(data.pi, data);
                    }
                })
                .catch(() => {
                    document.getElementById('piModalBody').innerHTML = `
                        <div class="error-message">
                            <i class="bi bi-exclamation-triangle"></i>
                            <p>Erro ao carregar os dados do PI.</p>
                        </div>
                    `;
                    toastr.error('Erro ao carregar dados do PI');
                });
        }

        function renderizarDetalhesPi(pi, data) {
            let html = '';
            let footerHtml = '';

            html += `
                <div class="pi-details-section">
                    <h3 class="section-title">
                        <i class="bi bi-info-circle-fill"></i>
                        Informações Básicas
                    </h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label"><i class="bi bi-hash"></i> Número PI</div>
                            <div class="detail-value">${pi.numero_pi}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label"><i class="bi bi-calendar3"></i> Período</div>
                            <div class="detail-value ${!pi.periodo ? 'empty' : ''}">${pi.periodo || 'Não informado'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label"><i class="bi bi-calendar-check"></i> Data Cadastro</div>
                            <div class="detail-value">${formatarDataHora(pi.data_cadastro)}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label"><i class="bi bi-calendar-event"></i> Data Conclusão</div>
                            <div class="detail-value ${!pi.data_conclusao ? 'empty' : ''}">${pi.data_conclusao ? formatarDataHora(pi.data_conclusao) : 'Não definida'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label"><i class="bi bi-file-text"></i> PIT Nº</div>
                            <div class="detail-value ${!pi.PIT_n ? 'empty' : ''}">${pi.PIT_n || 'Não informado'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label"><i class="bi bi-clipboard-data"></i> Plano Nº</div>
                            <div class="detail-value ${!pi.plano_n ? 'empty' : ''}">${pi.plano_n || 'Não informado'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label"><i class="bi bi-table"></i> Planilha Nº</div>
                            <div class="detail-value ${!pi.planilha_n ? 'empty' : ''}">${pi.planilha_n || 'Não informado'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label"><i class="bi bi-check-circle"></i> Status</div>
                            <div class="detail-value">
                                ${pi.pago ?
                                    '<span class="status-pago"><i class="bi bi-currency-dollar"></i> Pago</span>' :
                                    pi.concluido ?
                                    '<span class="status-concluido"><i class="bi bi-check-circle-fill"></i> Concluído</span>' :
                                    '<span class="status-andamento"><i class="bi bi-clock-history"></i> Em Andamento</span>'}
                            </div>
                        </div>
                    </div>
                </div>
            `;

            const dataPrimeiroEnvio = data.data_primeiro_envio;
            const dataTodosEnviados = data.data_todos_enviados;
            const dataUltimaAprovacao = data.data_ultima_aprovacao;
            const totalDocs = data.total_docs || 0;
            const totalDocsEnviados = data.total_docs_enviados || 0;
            const envioCompleto = dataTodosEnviados ? true : false;
            const envioLabel = envioCompleto ? 'Docs Enviados' : (dataPrimeiroEnvio ?
                `Envio Parcial (${totalDocsEnviados}/${totalDocs})` : 'Docs Enviados');
            const envioData = envioCompleto ? dataTodosEnviados : dataPrimeiroEnvio;

            html += `<div class="pi-details-section">
                <h3 class="section-title">
                    <i class="bi bi-clock-history"></i>
                    Linha do Tempo
                </h3>
                <div class="pi-timeline-bar">
                    <div class="pi-timeline-item">
                        <div class="pi-timeline-icon created"><i class="bi bi-plus-circle-fill"></i></div>
                        <div class="pi-timeline-info">
                            <span class="pi-timeline-label">PI Criado</span>
                            <span class="pi-timeline-date">${formatarData(pi.data_cadastro)}</span>
                        </div>
                    </div>
                    <div class="pi-timeline-connector ${envioCompleto ? 'active' : (dataPrimeiroEnvio ? 'partial' : '')}"></div>
                    <div class="pi-timeline-item ${!envioData ? 'pending' : ''}">
                        <div class="pi-timeline-icon ${envioCompleto ? 'docs-sent' : (dataPrimeiroEnvio ? 'docs-partial' : '')}">
                            <i class="bi ${envioData ? 'bi-file-earmark-arrow-up-fill' : 'bi-file-earmark-arrow-up'}"></i>
                        </div>
                        <div class="pi-timeline-info">
                            <span class="pi-timeline-label ${!envioCompleto && dataPrimeiroEnvio ? 'timeline-partial' : ''}">${envioLabel}</span>
                            <span class="pi-timeline-date ${!envioData ? 'muted' : ''}">${envioData ? formatarData(envioData) : 'Pendente'}</span>
                        </div>
                    </div>
                    <div class="pi-timeline-connector ${dataUltimaAprovacao ? 'active' : ''}"></div>
                    <div class="pi-timeline-item ${!dataUltimaAprovacao ? 'pending' : ''}">
                        <div class="pi-timeline-icon ${dataUltimaAprovacao ? 'docs-approved' : ''}">
                            <i class="bi ${dataUltimaAprovacao ? 'bi-check-circle-fill' : 'bi-check-circle'}"></i>
                        </div>
                        <div class="pi-timeline-info">
                            <span class="pi-timeline-label">Docs Aprovados</span>
                            <span class="pi-timeline-date ${!dataUltimaAprovacao ? 'muted' : ''}">${dataUltimaAprovacao ? formatarData(dataUltimaAprovacao) : 'Pendente'}</span>
                        </div>
                    </div>
                    <div class="pi-timeline-connector ${pi.concluido ? 'active' : ''}"></div>
                    <div class="pi-timeline-item ${!pi.concluido ? 'pending' : ''}">
                        <div class="pi-timeline-icon ${pi.concluido ? 'concluded' : ''}">
                            <i class="bi ${pi.concluido ? 'bi-flag-fill' : 'bi-flag'}"></i>
                        </div>
                        <div class="pi-timeline-info">
                            <span class="pi-timeline-label">Conclusão</span>
                            <span class="pi-timeline-date ${!pi.concluido ? 'muted' : ''}">${pi.concluido && pi.data_conclusao ? formatarData(pi.data_conclusao) : 'Pendente'}</span>
                        </div>
                    </div>
                    <div class="pi-timeline-connector ${pi.pago ? 'active' : ''}"></div>
                    <div class="pi-timeline-item ${!pi.pago ? 'pending' : ''}">
                        <div class="pi-timeline-icon ${pi.pago ? 'paid' : ''}">
                            <i class="bi ${pi.pago ? 'bi-currency-dollar' : 'bi-currency-dollar'}"></i>
                        </div>
                        <div class="pi-timeline-info">
                            <span class="pi-timeline-label">Pagamento</span>
                            <span class="pi-timeline-date ${!pi.pago ? 'muted' : ''}">${pi.pago && pi.data_pagamento ? formatarData(pi.data_pagamento) : 'Pendente'}</span>
                        </div>
                    </div>
                </div>
            </div>`;

            if (pi.cliente) {
                html += `
                    <div class="pi-details-section">
                        <h3 class="section-title">
                            <i class="bi bi-building-fill"></i>
                            Cliente
                        </h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-building"></i> Razão Social</div>
                                <div class="detail-value">${pi.cliente.razao_social}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-shop"></i> Nome Fantasia</div>
                                <div class="detail-value ${!pi.cliente.nome_fantasia ? 'empty' : ''}">${pi.cliente.nome_fantasia || 'Não informado'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-card-text"></i> CNPJ</div>
                                <div class="detail-value">${formatarCNPJ(pi.cliente.cnpj)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-telephone"></i> Telefone</div>
                                <div class="detail-value ${!pi.cliente.telefone ? 'empty' : ''}">${pi.cliente.telefone || 'Não informado'}</div>
                            </div>
                        </div>
                    </div>
                `;
            }

            if (pi.fornecedor) {
                html += `
                    <div class="pi-details-section">
                        <h3 class="section-title">
                            <i class="bi bi-collection-play"></i>
                            Fornecedor
                        </h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-building"></i> Razão Social</div>
                                <div class="detail-value">${pi.fornecedor.razao_social}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-shop"></i> Nome Fantasia</div>
                                <div class="detail-value ${!pi.fornecedor.nome_fantasia ? 'empty' : ''}">${pi.fornecedor.nome_fantasia || 'Não informado'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-card-text"></i> CNPJ</div>
                                <div class="detail-value">${formatarCNPJ(pi.fornecedor.cnpj)}</div>
                            </div>
                            ${!isFornecedor && pi.fornecedor.user?.email ? `
                                                <div class="detail-item">
                                                    <div class="detail-label"><i class="bi bi-envelope"></i> Email</div>
                                                    <div class="detail-value">${pi.fornecedor.user.email}</div>
                                                </div>
                                            ` : ''}
                        </div>
                    </div>
                `;
            }

            if (pi.status) {
                html += `
                    <div class="pi-details-section">
                        <h3 class="section-title">
                            <i class="bi bi-activity"></i>
                            Status ${!isFornecedor && (pi.gerencial || pi.atribuicoes_operacionais) ? 'e Responsáveis' : ''}
                        </h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-tag"></i> Status Atual</div>
                                <div class="detail-value">
                                    <span class="status-badge" style="background: ${pi.status.cor}15; color: ${pi.status.cor}; border: 1px solid ${pi.status.cor}30;">
                                        ${pi.status.nome}
                                    </span>
                                </div>
                            </div>
                `;

                if (!isFornecedor) {
                    if (pi.gerencial) {
                        html += `
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-person-badge"></i> Gerencial</div>
                                <div class="detail-value">${pi.gerencial.nome_completo}</div>
                            </div>
                        `;
                    }
                    if (pi.atribuicoes_operacionais && pi.atribuicoes_operacionais.length > 0) {
                        const operacionalPrincipal = pi.atribuicoes_operacionais.find(a => a.principal && a.operacional);
                        if (operacionalPrincipal) {
                            html += `
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-person-check"></i> Operacional Principal</div>
                                    <div class="detail-value">${operacionalPrincipal.operacional.nome_completo}</div>
                                </div>
                            `;
                        }
                    }
                }

                html += `
                        </div>
                    </div>
                `;
            }

            if (!isFornecedor && pi.atribuicoes_operacionais && pi.atribuicoes_operacionais.length > 0) {
                html += `
                    <div class="pi-details-section">
                        <h3 class="section-title">
                            <i class="bi bi-people-fill"></i>
                            Operacionais Atribuídos (${pi.atribuicoes_operacionais.length})
                        </h3>
                `;

                pi.atribuicoes_operacionais.forEach(atrib => {
                    if (atrib.operacional) {
                        const iniciais = atrib.operacional.nome_completo.split(' ').map(n => n[0]).slice(0, 2).join(
                            '').toUpperCase();
                        const isPrincipal = atrib.principal;

                        html += `
                            <div class="operacional-card ${isPrincipal ? 'principal' : ''}">
                                <div class="operacional-info">
                                    <div class="operacional-avatar">${iniciais}</div>
                                    <div class="operacional-details">
                                        <div class="operacional-nome">${atrib.operacional.nome_completo}</div>
                                        <div class="operacional-email">
                                            <i class="bi bi-envelope"></i>
                                            ${atrib.operacional.user ? atrib.operacional.user.email : 'Email não disponível'}
                                        </div>
                                    </div>
                                </div>
                                ${isPrincipal ? '<div class="badge-principal"><i class="bi bi-star-fill"></i> Principal</div>' : ''}
                            </div>
                        `;
                    }
                });

                html += `</div>`;
            }

            if (pi.pi_documentos && pi.pi_documentos.length > 0) {
                const docsPendentes = pi.pi_documentos.filter(d => !d.documento_enviado).length;
                const docsAtendidos = pi.pi_documentos.filter(d => d.documento_enviado).length;
                const docsReprovados = pi.pi_documentos.filter(d => d.documento_enviado && ['Reprovado',
                    'Aguardando Reenvio'
                ].includes(d.documento_enviado.status.nome)).length;

                html += `
                    <div class="pi-details-section">
                        <h3 class="section-title">
                            <i class="bi bi-file-earmark-text-fill"></i>
                            Documentos do PI (${pi.pi_documentos.length})
                        </h3>
                        <div class="docs-summary-cards">
                            ${docsReprovados > 0 ? `
                                                <div class="docs-summary-card docs-reprovados-card">
                                                    <div class="docs-summary-icon">
                                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                                    </div>
                                                    <div class="docs-summary-info">
                                                        <div class="docs-summary-value">${docsReprovados}</div>
                                                        <div class="docs-summary-label">Reprovados</div>
                                                    </div>
                                                </div>
                                            ` : ''}
                            <div class="docs-summary-card docs-pending-card">
                                <div class="docs-summary-icon">
                                    <i class="bi bi-hourglass-split"></i>
                                </div>
                                <div class="docs-summary-info">
                                    <div class="docs-summary-value">${docsPendentes}</div>
                                    <div class="docs-summary-label">Pendentes</div>
                                </div>
                            </div>
                            <div class="docs-summary-card docs-attended-card">
                                <div class="docs-summary-icon">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div class="docs-summary-info">
                                    <div class="docs-summary-value">${docsAtendidos}</div>
                                    <div class="docs-summary-label">Atendidos</div>
                                </div>
                            </div>
                        </div>
                `;

                if (isFornecedor && docsReprovados > 0) {
                    html += `
                        <div class="modal-alert-box">
                            <div class="modal-alert-icon">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </div>
                            <div class="modal-alert-content">
                                <strong>Atenção!</strong> Você possui ${docsReprovados} ${docsReprovados === 1 ? 'documento reprovado' : 'documentos reprovados'} neste PI que precisa${docsReprovados === 1 ? '' : 'm'} ser reenviado${docsReprovados === 1 ? '' : 's'}.
                            </div>
                        </div>
                    `;
                }

                pi.pi_documentos.forEach(piDoc => {
                    const tipoDoc = piDoc.tipo_documento;
                    const docEnviado = piDoc.documento_enviado;
                    const statusDoc = piDoc.status;

                    html += `
                        <div class="documento-card-pi ${docEnviado && ['Reprovado', 'Aguardando Reenvio'].includes(docEnviado.status.nome) ? 'documento-reprovado' : ''}">
                            <div class="documento-main-content">
                                <div class="documento-icon-wrapper ${docEnviado ? 'attended' : 'pending'}">
                                    <i class="bi ${docEnviado ? 'bi-file-earmark-check-fill' : 'bi-file-earmark-text-fill'}"></i>
                                </div>
                                <div class="documento-text-content">
                                    <div class="documento-nome-pi">${tipoDoc.nome}</div>
                                    ${tipoDoc.descricao ? `<div class="documento-descricao">${tipoDoc.descricao}</div>` : ''}
                                    <div class="documento-meta-info">
                                        ${statusDoc ? `
                                                            <div class="meta-item">
                                                                <span class="status-badge" style="background: ${statusDoc.cor}15; color: ${statusDoc.cor}; border: 1px solid ${statusDoc.cor}30; font-size: 0.75rem; padding: 0.25rem 0.625rem;">
                                                                    ${statusDoc.nome}
                                                                </span>
                                                            </div>
                                                        ` : ''}
                                        ${docEnviado && docEnviado.data_validade_documento ? `
                                                            <div class="meta-item">
                                                                <i class="bi bi-calendar-check" style="color: var(--success-color);"></i>
                                                                <span>Validade: ${new Date(docEnviado.data_validade_documento).toLocaleDateString('pt-BR')}</span>
                                                            </div>
                                                        ` : ''}
                                    </div>
                                    ${docEnviado && docEnviado.validacoes && docEnviado.validacoes.length > 0 && docEnviado.validacoes[0].observacoes && ['Reprovado', 'Aguardando Reenvio'].includes(docEnviado.status.nome) ? `
                                                        <div class="documento-reprovacao-motivo">
                                                            <strong><i class="bi bi-exclamation-circle-fill"></i> Motivo da Reprovação:</strong>
                                                            <p>${escapeHtml(docEnviado.validacoes[0].observacoes)}</p>
                                                        </div>
                                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });

                html += `</div>`;
            }

            if (pi.conversas && pi.conversas.length > 0) {
                html += `
                    <div class="pi-details-section">
                        <h3 class="section-title">
                            <i class="bi bi-chat-dots-fill"></i>
                            Conversas (${pi.conversas.length})
                        </h3>
                `;

                pi.conversas.forEach(conversa => {
                    const statusClass = conversa.status_conversa === 'aberta' ? 'conversa-aberta' :
                        conversa.status_conversa === 'resolvida' ? 'conversa-resolvida' :
                        'conversa-escalada';
                    const statusText = conversa.status_conversa === 'aberta' ? 'Aberta' :
                        conversa.status_conversa === 'resolvida' ? 'Resolvida' :
                        'Escalada';
                    const statusIcon = conversa.status_conversa === 'aberta' ? 'bi-chat-dots' :
                        conversa.status_conversa === 'resolvida' ? 'bi-check-circle' :
                        'bi-arrow-up-circle';

                    html += `
                        <div class="conversa-card ${statusClass}">
                            <div class="conversa-header">
                                <div class="conversa-title">
                                    <i class="bi ${statusIcon}"></i>
                                    ${escapeHtml(conversa.assunto)}
                                </div>
                                <span class="conversa-status-badge">${statusText}</span>
                            </div>
                            <div class="conversa-body">
                                <div class="conversa-info-item">
                                    <i class="bi bi-calendar3"></i>
                                    <span>Criada em: ${new Date(conversa.created_at).toLocaleDateString('pt-BR')} às ${new Date(conversa.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}</span>
                                </div>
                                <div class="conversa-info-item">
                                    <i class="bi bi-chat-square-text"></i>
                                    <span>${conversa.mensagens_count || 0} mensagens</span>
                                </div>
                                ${conversa.ultima_mensagem ? `
                                                    <div class="conversa-ultima-mensagem">
                                                        <strong>Última mensagem:</strong>
                                                        <p>${escapeHtml(conversa.ultima_mensagem.mensagem.substring(0, 100))}${conversa.ultima_mensagem.mensagem.length > 100 ? '...' : ''}</p>
                                                    </div>
                                                ` : ''}
                            </div>
                        </div>
                    `;
                });

                html += `</div>`;
            }

            if (pi.valores) {
                html += `
                    <div class="pi-details-section">
                        <h3 class="section-title">
                            <i class="bi bi-currency-dollar"></i>
                            Valores e Condições Comerciais
                        </h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-cash-stack"></i> Total Bruto</div>
                                <div class="detail-value">${pi.valores.total_bruto ? formatarDinheiro(pi.valores.total_bruto) : '<span class="empty">Não informado</span>'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-percent"></i> Comissão</div>
                                <div class="detail-value">${pi.valores.comissao ? formatarDinheiro(pi.valores.comissao) : '<span class="empty">Não informado</span>'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-calculator"></i> Líquido</div>
                                <div class="detail-value">${pi.valores.liquido ? formatarDinheiro(pi.valores.liquido) : '<span class="empty">Não informado</span>'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-tag"></i> Custo Unitário</div>
                                <div class="detail-value">${pi.valores.custo_unitario ? formatarDinheiro(pi.valores.custo_unitario) : '<span class="empty">Não informado</span>'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-arrow-down-circle"></i> Desconto</div>
                                <div class="detail-value">${pi.valores.desconto ? pi.valores.desconto + '%' : '<span class="empty">Não informado</span>'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-cash"></i> Custo Total</div>
                                <div class="detail-value">${pi.valores.custo_total ? formatarDinheiro(pi.valores.custo_total) : '<span class="empty">Não informado</span>'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-geo-alt"></i> Local Cobrança</div>
                                <div class="detail-value ${!pi.valores.local_cobranca ? 'empty' : ''}">${pi.valores.local_cobranca || 'Não informado'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-clock-history"></i> Prazo Pagamento</div>
                                <div class="detail-value ${!pi.valores.prazo_pagamento ? 'empty' : ''}">${pi.valores.prazo_pagamento || 'Não informado'}</div>
                            </div>
                        </div>
                    </div>
                `;
            }

            if (pi.observacoes) {
                html += `
                    <div class="pi-details-section">
                        <h3 class="section-title">
                            <i class="bi bi-sticky-fill"></i>
                            Observações
                        </h3>
                        <div class="observacoes-box">${escapeHtml(pi.observacoes)}</div>
                    </div>
                `;
            }

            if (pi.file_path) {
                html += `
                    <div class="pi-details-section">
                        <h3 class="section-title">
                            <i class="bi bi-file-pdf-fill"></i>
                            Documento PI
                        </h3>
                        <div class="documento-download-box">
                            <div class="documento-download-info">
                                <i class="bi bi-file-pdf" style="font-size: 2.5rem; color: var(--danger-color);"></i>
                                <div>
                                    <div style="font-weight: 600; color: var(--text-color); margin-bottom: 0.25rem;">Documento PI Anexado</div>
                                    <div style="font-size: 0.875rem; color: var(--text-muted);">Clique no botão para baixar</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            const editarUrl = "{{ route('pis.edit', ':id') }}".replace(':id', pi.id);
            const enviarDocsUrl = "{{ route('pis.enviar_documentos', ':id') }}".replace(':id', pi.id);
            const validarDocsUrl = "{{ route('pis.validar_documentos', ':id') }}".replace(':id', pi.id);
            const baixarDocsUrl = "{{ route('pis.baixar_todos_documentos', ':id') }}".replace(':id', pi.id);
            const downloadDocUrl = "{{ route('pis.download_documento', ':id') }}".replace(':id', pi.id);
            const chatIndexUrl = "{{ route('chat.index') }}?pi_id=" + pi.id;

            if (!isFornecedor) {
                const faturarUrl = "{{ route('pis.pagar', ':id') }}".replace(':id', pi.id);

                footerHtml += `
                    <a href="${editarUrl}" class="btn-footer-action btn-editar">
                        <i class="bi bi-pencil"></i>
                        <span>Editar</span>
                    </a>
                    <a href="${enviarDocsUrl}" class="btn-footer-action btn-enviar-docs">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <span>Enviar Docs</span>
                    </a>
                    ${(pi.documentos_atendidos_count || data.documentos_atendidos_count || 0) > 0 ? `
                                        <a href="${validarDocsUrl}" class="btn-footer-action btn-validar-docs">
                                            <i class="bi bi-rocket-takeoff"></i>
                                            <span>Validar Docs</span>
                                        </a>
                                    ` : ''}
                    <a href="${baixarDocsUrl}" class="btn-footer-action btn-download">
                        <i class="bi bi-download"></i>
                        <span>Baixar Docs</span>
                    </a>
                    ${pi.file_path ? `
                                        <a href="${downloadDocUrl}" class="btn-footer-action btn-download-doc">
                                            <i class="bi bi-file-pdf"></i>
                                            <span>Baixar PI</span>
                                        </a>
                                    ` : ''}
                    ${pi.concluido && !pi.pago ? `
                                        <button type="button" class="btn-footer-action btn-faturar" onclick="abrirModalFaturar(${pi.id}, '${pi.numero_pi}')">
                                            <i class="bi bi-cash-coin"></i>
                                            <span>Pagar PI</span>
                                        </button>
                                    ` : ''}
                    ${pi.pago ? `
                                        <button type="button" class="btn-footer-action btn-faturar" onclick="abrirModalFaturar(${pi.id}, '${pi.numero_pi}', '${pi.data_pagamento ? pi.data_pagamento.split('T')[0] : ''}')">
                                            <i class="bi bi-pencil-square"></i>
                                            <span>Editar Pagamento</span>
                                        </button>
                                    ` : ''}
                `;
            } else {
                footerHtml += `
                    <a href="${editarUrl}" class="btn-footer-action btn-editar">
                        <i class="bi bi-pencil"></i>
                        <span>Editar</span>
                    </a>
                    <a href="${enviarDocsUrl}" class="btn-footer-action btn-enviar-docs">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <span>Enviar Docs</span>
                    </a>
                    <a href="${baixarDocsUrl}" class="btn-footer-action btn-download">
                        <i class="bi bi-download"></i>
                        <span>Baixar Docs</span>
                    </a>
                    ${pi.conversas_count > 0 ? `
                                        <a href="${chatIndexUrl}" class="btn-footer-action btn-conversas">
                                            <i class="bi bi-chat-dots"></i>
                                            <span>Ver Conversas</span>
                                        </a>
                                    ` : ''}
                    ${pi.file_path ? `
                                        <a href="${downloadDocUrl}" class="btn-footer-action btn-download-doc">
                                            <i class="bi bi-file-pdf"></i>
                                            <span>Baixar PI</span>
                                        </a>
                                    ` : ''}
                `;
            }

            document.getElementById('piModalBody').innerHTML = html;

            const modalFooter = document.getElementById('piModalFooter');
            const actionsScroll = modalFooter.querySelector('.modal-actions-scroll');
            actionsScroll.innerHTML = footerHtml;
            modalFooter.style.display = 'flex';
        }

        function confirmarExclusao(event, form) {
            event.preventDefault();
            closeCurrentMenu();

            const piNumero = form.dataset.piNumero;
            const piCliente = form.dataset.piCliente;

            Swal.fire({
                title: '',
                html: `
            <div class="swal-delete-container">
                <div class="swal-delete-header">
                    <div class="swal-delete-icon">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <h2 class="swal-delete-title">Confirmar Exclusão Permanente</h2>
                    <p class="swal-delete-subtitle">Esta ação não poderá ser desfeita</p>
                </div>

                <div class="swal-delete-body">
                    <div class="swal-delete-info">
                        <div class="swal-delete-info-row">
                            <span class="label">Número PI:</span>
                            <span class="value">${piNumero}</span>
                        </div>
                        <div class="swal-delete-info-row">
                            <span class="label">Cliente:</span>
                            <span class="value">${piCliente}</span>
                        </div>
                    </div>

                    <div class="swal-delete-warning-box">
                        <div>
                            <strong>Atenção: Os seguintes dados serão excluídos permanentemente</strong>
                            <ul>
                                <li>Documentos e vínculos</li>
                                <li>Conversas e mensagens</li>
                                <li>Atribuições de operacionais</li>
                                <li>Valores e informações comerciais</li>
                            </ul>
                        </div>
                    </div>

                    <div class="swal-delete-note">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>Os arquivos físicos permanecerão no servidor, mas não poderão ser acessados pelo sistema.</span>
                    </div>
                </div>
            </div>
        `,
                showCancelButton: true,
                confirmButtonText: '<i class="bi bi-trash3-fill"></i> Sim, excluir',
                cancelButtonText: '<i class="bi bi-x-circle-fill"></i> Cancelar',
                customClass: {
                    popup: 'swal-delete-popup',
                    confirmButton: 'swal-delete-btn-confirm',
                    cancelButton: 'swal-delete-btn-cancel',
                    actions: 'swal-delete-actions'
                },
                buttonsStyling: false,
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });

            return false;
        }

        function formatarCNPJ(cnpj) {
            if (!cnpj) return 'Não informado';
            return cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
        }

        function formatarDataHora(dataHora) {
            if (!dataHora) return 'Não informado';
            const data = new Date(dataHora);
            return data.toLocaleDateString('pt-BR') + ' ' + data.toLocaleTimeString('pt-BR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function formatarData(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr);
            return d.toLocaleDateString('pt-BR');
        }

        function formatarDinheiro(valor) {
            if (!valor) return 'R$ 0,00';
            return 'R$ ' + parseFloat(valor).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        @if (session('success'))
            toastr.success('{{ session('success') }}');
        @elseif (session('error'))
            toastr.error('{{ session('error') }}');
        @elseif (session('warning'))
            toastr.warning('{{ session('warning') }}');
        @endif

        let faturarPiId = null;

        function abrirModalFaturar(piId, piNumero, dataAtual) {
            faturarPiId = piId;
            const isEdit = dataAtual && dataAtual.length > 0;

            document.getElementById('faturarModalSubtitle').textContent = 'PI: ' + piNumero;
            document.getElementById('inputDataPagamento').value = isEdit ? dataAtual : new Date().toISOString().split('T')[
                0];
            document.getElementById('btnRemoverPagamento').style.display = isEdit ? 'inline-flex' : 'none';

            document.getElementById('faturarModalTitle').textContent = isEdit ? 'Editar Pagamento' : 'Pagar PI';

            closeCurrentMenu();
            const modal = new bootstrap.Modal(document.getElementById('faturarModal'));
            modal.show();
        }

        function confirmarFaturar() {
            const dataPagamento = document.getElementById('inputDataPagamento').value;
            if (!dataPagamento) {
                toastr.error('Informe a data de pagamento.');
                return;
            }

            const btn = document.getElementById('btnConfirmarFaturar');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processando...';

            const url = "{{ route('pis.pagar', ':id') }}".replace(':id', faturarPiId);

            fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        data_pagamento: dataPagamento
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastr.success(data.message);
                        bootstrap.Modal.getInstance(document.getElementById('faturarModal')).hide();
                        setTimeout(() => window.location.reload(), 800);
                    } else {
                        toastr.error(data.message || 'Erro ao pagar PI.');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-cash-coin"></i> Confirmar Pagamento';
                    }
                })
                .catch(() => {
                    toastr.error('Erro de conexão ao pagar PI.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-cash-coin"></i> Confirmar Pagamento';
                });
        }

        function removerPagamento() {
            Swal.fire({
                title: 'Remover Pagamento',
                text: 'Tem certeza que deseja remover o registro de pagamento deste PI?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sim, remover',
                cancelButtonText: 'Cancelar',
            }).then((result) => {
                if (result.isConfirmed) {
                    const url = "{{ route('pis.pagar', ':id') }}".replace(':id', faturarPiId);

                    fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                remover: true
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                toastr.success(data.message);
                                bootstrap.Modal.getInstance(document.getElementById('faturarModal')).hide();
                                setTimeout(() => window.location.reload(), 800);
                            } else {
                                toastr.error(data.message || 'Erro ao remover pagamento.');
                            }
                        })
                        .catch(() => {
                            toastr.error('Erro de conexão.');
                        });
                }
            });
        }
    </script>
@endpush
