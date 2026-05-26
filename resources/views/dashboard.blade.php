{{-- resources/views/dashboard.blade.php --}}

@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <link href="{{ asset('css/pages/dashboard.css') }}" rel="stylesheet">
    <div class="container-fluid px-0">
        @hasrole('admin')
            <div class="stats-grid">
                <a class="stat-card" href="{{ route('users.index') }}">
                    <div class="stat-top">
                        <div class="stat-icon primary">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="stat-badge trend-up">
                            <i class="bi bi-arrow-up"></i>
                            <span>{{ $usuariosAtivos }}</span>
                        </div>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Total de Usuários</div>
                        <div class="stat-value">{{ $totalUsuarios }}</div>
                        <div class="stat-details">
                            <i class="bi bi-x-circle text-danger"></i>
                            <span>{{ $usuariosInativos }} inativos</span>
                        </div>
                    </div>
                </a>

                <a class="stat-card" href="{{ route('clientes.index') }}">
                    <div class="stat-top">
                        <div class="stat-icon info">
                            <i class="bi bi-building"></i>
                        </div>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Clientes</div>
                        <div class="stat-value">{{ $totalClientes }}</div>
                        <div class="stat-details">
                            <i class="bi bi-check-circle text-success"></i>
                            <span>{{ $clientesAtivos }} ativos</span>
                        </div>
                    </div>
                </a>

                <a class="stat-card" href="{{ route('fornecedores.index') }}">
                    <div class="stat-top">
                        <div class="stat-icon warning">
                            <i class="bi bi-collection-play"></i>
                        </div>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Fornecedores</div>
                        <div class="stat-value">{{ $totalFornecedores }}</div>
                        <div class="stat-details">
                            <i class="bi bi-check-circle text-success"></i>
                            <span>{{ $fornecedoresAtivos }} ativos</span>
                        </div>
                    </div>
                </a>

                <a class="stat-card" href="{{ route('pis.index') }}">
                    <div class="stat-top">
                        <div class="stat-icon success">
                            <i class="bi bi-file-earmark-check-fill"></i>
                        </div>
                        <div class="stat-badge trend-up">
                            <i class="bi bi-arrow-up"></i>
                            <span>{{ $pisConcluidas }}</span>
                        </div>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">PIs Totais</div>
                        <div class="stat-value">{{ $totalPis }}</div>
                        <div class="stat-details">
                            <i class="bi bi-hourglass-split text-warning"></i>
                            <span>{{ $pisPendentes }} pendentes</span>
                        </div>
                    </div>
                </a>

                @if(isset($vinculosPendentes) && $vinculosPendentes > 0)
                    <a class="stat-card" href="{{ route('vinculos.pendentes') }}">
                        <div class="stat-top">
                            <div class="stat-icon warning">
                                <i class="bi bi-person-plus-fill"></i>
                            </div>
                            <div class="stat-badge trend-up">
                                <i class="bi bi-bell-fill"></i>
                                <span>{{ $vinculosPendentes }}</span>
                            </div>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Vínculos Pendentes</div>
                            <div class="stat-value">{{ $vinculosPendentes }}</div>
                            <div class="stat-details">
                                <i class="bi bi-clock text-warning"></i>
                                <span>Aguardando aprovação</span>
                            </div>
                        </div>
                    </a>
                @endif
            </div>
        @endhasrole

        @hasrole('gerencial')
            <div class="stats-grid">
                <a class="stat-card" href="{{ route('pis.index', ['apenas_meus' => 1]) }}">
                    <div class="stat-top">
                        <div class="stat-icon primary">
                            <i class="bi bi-file-earmark-plus-fill"></i>
                        </div>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Meus PIs</div>
                        <div class="stat-value">{{ $minhasPis }}</div>
                        <div class="stat-details">
                            <i class="bi bi-person-check-fill text-success"></i>
                            <span>Sob sua gestão</span>
                        </div>
                    </div>
                </a>

                <a class="stat-card" href="{{ route('pis.index', ['status_filtro' => 'andamento', 'apenas_meus' => 1]) }}">
                    <div class="stat-top">
                        <div class="stat-icon warning">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Em Andamento</div>
                        <div class="stat-value">{{ $minhasPisPendentes }}</div>
                        <div class="stat-details">
                            <i class="bi bi-hourglass-split text-warning"></i>
                            <span>Aguardando conclusão</span>
                        </div>
                    </div>
                </a>

                <a class="stat-card" href="{{ route('pis.index', ['status_filtro' => 'concluidos', 'apenas_meus' => 1]) }}">
                    <div class="stat-top">
                        <div class="stat-icon success">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Finalizados</div>
                        <div class="stat-value">{{ $minhasPisConcluidas }}</div>
                        <div class="stat-details">
                            <i class="bi bi-check-all text-success"></i>
                            <span>Concluídos</span>
                        </div>
                    </div>
                </a>

                <a class="stat-card" href="{{ route('chat.index') }}">
                    <div class="stat-top">
                        <div class="stat-icon info">
                            <i class="bi bi-chat-dots-fill"></i>
                        </div>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Conversas Abertas</div>
                        <div class="stat-value">{{ $conversasAbertas }}</div>
                        <div class="stat-details">
                            <i class="bi bi-chat-left-text text-info"></i>
                            <span>Discussões ativas</span>
                        </div>
                    </div>
                </a>

                @if(isset($vinculosPendentes) && $vinculosPendentes > 0)
                    <a class="stat-card" href="{{ route('vinculos.pendentes') }}">
                        <div class="stat-top">
                            <div class="stat-icon warning">
                                <i class="bi bi-person-plus-fill"></i>
                            </div>
                            <div class="stat-badge trend-up">
                                <i class="bi bi-bell-fill"></i>
                                <span>{{ $vinculosPendentes }}</span>
                            </div>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Vínculos Pendentes</div>
                            <div class="stat-value">{{ $vinculosPendentes }}</div>
                            <div class="stat-details">
                                <i class="bi bi-clock text-warning"></i>
                                <span>Aguardando aprovação</span>
                            </div>
                        </div>
                    </a>
                @endif
            </div>
        @endhasrole

        @hasrole('operacional')
            <div class="stats-grid">
                <a class="stat-card" href="{{ route('pis.index') }}">
                    <div class="stat-top">
                        <div class="stat-icon primary">
                            <i class="bi bi-clipboard-check-fill"></i>
                        </div>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">PIs Atribuídos</div>
                        <div class="stat-value">{{ $minhasPis }}</div>
                        <div class="stat-details">
                            <i class="bi bi-person-badge text-info"></i>
                            <span>{{ $minhasPisPendentes }} em andamento</span>
                        </div>
                    </div>
                </a>

                <a class="stat-card" href="{{ route('pis.index', ['status_filtro' => 'em_processo']) }}">
                    <div class="stat-top">
                        <div class="stat-icon warning">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Docs Pendentes</div>
                        <div class="stat-value">{{ $documentosPendentesValidacao }}</div>
                        <div class="stat-details">
                            <i class="bi bi-clock text-warning"></i>
                            <span>Aguardando sua validação</span>
                        </div>
                    </div>
                </a>

                <a class="stat-card" href="{{ route('pis.index') }}">
                    <div class="stat-top">
                        <div class="stat-icon success">
                            <i class="bi bi-check-all"></i>
                        </div>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Validados Hoje</div>
                        <div class="stat-value">{{ $documentosValidadosHoje }}</div>
                        <div class="stat-details">
                            <i class="bi bi-calendar-check text-success"></i>
                            <span>Documentos aprovados</span>
                        </div>
                    </div>
                </a>

                <a class="stat-card" href="{{ route('pis.index', ['status_filtro' => 'concluidos']) }}">
                    <div class="stat-top">
                        <div class="stat-icon info">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">PIs Concluídos</div>
                        <div class="stat-value">{{ $minhasPisConcluidas }}</div>
                        <div class="stat-details">
                            <i class="bi bi-trophy text-success"></i>
                            <span>Finalizados</span>
                        </div>
                    </div>
                </a>
            </div>
        @endhasrole

        @hasrole('fornecedor')
            <div class="stats-grid">
                <a class="stat-card" href="{{ route('fornecedor.docs_list.index') }}">
                    <div class="stat-top">
                        <div class="stat-icon primary">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Docs Enviados</div>
                        <div class="stat-value">{{ $documentosEnviados }}</div>
                        <div class="stat-details">
                            <i class="bi bi-upload text-primary"></i>
                            <span>Total de documentos</span>
                        </div>
                    </div>
                </a>

                <a class="stat-card" href="{{ url('/fornecedor/documentos-list/valido') }}">
                    <div class="stat-top">
                        <div class="stat-icon success">
                            <i class="bi bi-shield-check"></i>
                        </div>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Docs Válidos</div>
                        <div class="stat-value">{{ $documentosValidos }}</div>
                        <div class="stat-details">
                            <i class="bi bi-check-circle text-success"></i>
                            <span>Dentro do prazo</span>
                        </div>
                    </div>
                </a>

                <a class="stat-card" href="{{ url('/fornecedor/documentos-list/vencendo') }}">
                    <div class="stat-top">
                        <div class="stat-icon warning">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Docs Vencendo</div>
                        <div class="stat-value">{{ $documentosVencendoCount }}</div>
                        <div class="stat-details">
                            <i class="bi bi-exclamation-triangle text-warning"></i>
                            <span>Próximos 30 dias</span>
                        </div>
                    </div>
                </a>

                <a class="stat-card" href="{{ url('/fornecedor/documentos-list/vencido') }}">
                    <div class="stat-top">
                        <div class="stat-icon danger">
                            <i class="bi bi-x-circle"></i>
                        </div>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Docs Vencidos</div>
                        <div class="stat-value">{{ $documentosVencidosCount }}</div>
                        <div class="stat-details">
                            <i class="bi bi-shield-x text-danger"></i>
                            <span>Requer renovação</span>
                        </div>
                    </div>
                </a>
            </div>
        @endhasrole

        @hasrole('representante')
            <div class="stats-grid">
                <a class="stat-card" href="{{ route('pis.index') }}">
                    <div class="stat-top">
                        <div class="stat-icon primary">
                            <i class="bi bi-collection-play"></i>
                        </div>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Fornecedores</div>
                        <div class="stat-value">{{ $totalFornecedores }}</div>
                        <div class="stat-details">
                            <i class="bi bi-link-45deg text-primary"></i>
                            <span>Vinculados</span>
                        </div>
                    </div>
                </a>

                <a class="stat-card" href="{{ route('pis.index') }}">
                    <div class="stat-top">
                        <div class="stat-icon warning">
                            <i class="bi bi-file-earmark-plus"></i>
                        </div>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">PIs Ativos</div>
                        <div class="stat-value">{{ $totalPisAtivos }}</div>
                        <div class="stat-details">
                            <i class="bi bi-check-circle text-success"></i>
                            <span>{{ $totalPisConcluidos }} concluídos</span>
                        </div>
                    </div>
                </a>

                <a class="stat-card" href="{{ route('pis.index') }}">
                    <div class="stat-top">
                        <div class="stat-icon danger">
                            <i class="bi bi-file-earmark-x"></i>
                        </div>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Docs Pendentes</div>
                        <div class="stat-value">{{ $documentosPendentesEnvio }}</div>
                        <div class="stat-details">
                            <i class="bi bi-hourglass-split text-warning"></i>
                            <span>{{ $documentosAguardandoValidacao }} em validação</span>
                        </div>
                    </div>
                </a>

                <a class="stat-card" href="{{ route('pis.index') }}">
                    <div class="stat-top">
                        <div class="stat-icon success">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Atenção</div>
                        <div class="stat-value">{{ $documentosReprovados }}</div>
                        <div class="stat-details">
                            <i class="bi bi-calendar-x text-danger"></i>
                            <span>{{ $documentosVencendoCount }} vencendo</span>
                        </div>
                    </div>
                </a>
            </div>

            @if(isset($fornecedoresVinculados) && $fornecedoresVinculados->count() > 0)
                <div class="activity-section mt-3">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="bi bi-building"></i>
                            <span>Fornecedores Representados</span>
                        </h2>
                    </div>
                    <div class="activity-card">
                        @foreach($fornecedoresVinculados as $forn)
                            <div class="activity-item">
                                <div class="activity-icon primary">
                                    <i class="bi bi-building-fill"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">{{ $forn->razao_social }}</div>
                                    <p class="activity-description">
                                        {{ $forn->nome_fantasia ?? '' }}
                                        @if(isset($pisPorFornecedor[$forn->id]))
                                            | {{ $pisPorFornecedor[$forn->id]->total }} PIs ({{ $pisPorFornecedor[$forn->id]->ativos }} ativos)
                                        @else
                                            | 0 PIs
                                        @endif
                                    </p>
                                </div>
                                <div class="activity-meta">
                                    <button type="button" class="btn btn-sm" style="background: var(--bg-body); color: var(--info-color); border: 1px solid var(--border-color); border-radius: 6px; padding: 4px 10px; cursor: pointer;" onclick="verDetalhesFornecedor({{ $forn->id }})">
                                        <i class="bi bi-eye"></i> Detalhes
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endhasrole

        <div class="activity-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="bi bi-clock-history"></i>
                    <span>
                        @hasrole('admin|gerencial')
                            PIs Recentes
                        @endhasrole
                        @hasrole('fornecedor')
                            Documentos por PI Pendentes
                        @endhasrole
                        @hasrole('operacional')
                            Documentos Pendentes
                        @endhasrole
                        @hasrole('representante')
                            PIs Recentes dos Fornecedores
                        @endhasrole
                    </span>
                </h2>
                @hasrole('admin|gerencial|operacional')
                    <a href="{{ route('pis.index') }}" class="view-all-btn">
                        <span>Ver todas</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                @endhasrole
                @hasrole('fornecedor')
                    <a href="{{ route('fornecedor.docs_list.index') }}" class="view-all-btn">
                        <span>Ver todos</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                @endhasrole
                @hasrole('representante')
                    <a href="{{ route('pis.index') }}" class="view-all-btn">
                        <span>Ver todas</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                @endhasrole
            </div>

            <div class="activity-card">
                @if (isset($pisRecentes) && $pisRecentes->count() > 0)
                    @foreach ($pisRecentes as $pi)
                        <div class="activity-item">
                            <div class="activity-icon {{ $pi->concluido ? 'success' : 'warning' }}">
                                <i class="bi {{ $pi->concluido ? 'bi-check-circle-fill' : 'bi-clock-history' }}"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">PI #{{ $pi->numero_pi }}</div>
                                <p class="activity-description">
                                    Cliente: {{ $pi->cliente->razao_social ?? 'N/A' }} |
                                    Fornecedor: {{ $pi->fornecedor->razao_social ?? 'N/A' }}
                                </p>
                            </div>
                            <div class="activity-meta">
                                <span class="activity-time">
                                    <i class="bi bi-clock"></i>
                                    {{ $pi->created_at->diffForHumans() }}
                                </span>
                                <span class="activity-status {{ $pi->concluido ? 'success' : 'warning' }}">
                                    {{ $pi->status->nome ?? 'Pendente' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                @elseif(isset($documentosPendentesLista) && $documentosPendentesLista->count() > 0)
                    @foreach ($documentosPendentesLista as $documento)
                        <div class="activity-item">
                            <div class="activity-icon warning">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">{{ $documento->tipoDocumento->nome }}</div>
                                <p class="activity-description">
                                    Fornecedor: {{ $documento->fornecedor->razao_social ?? 'N/A' }}
                                    @if ($documento->piDocumentos->first())
                                        | PI: #{{ $documento->piDocumentos->first()->pi->numero_pi ?? 'N/A' }}
                                    @endif
                                </p>
                            </div>
                            <div class="activity-meta">
                                <span class="activity-time">
                                    <i class="bi bi-clock"></i>
                                    {{ $documento->data_envio->diffForHumans() }}
                                </span>
                                <span class="activity-status warning">
                                    {{ $documento->status->nome ?? 'Pendente' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                @elseif(isset($documentoPorPIPendentes) && count($documentoPorPIPendentes) > 0)
                    @foreach ($documentoPorPIPendentes as $documento)
                        <div class="activity-item doc-pendente-item" data-pi-documento-id="{{ $documento->id }}"
                            data-tipo-id="{{ $documento->tipo_documento_id }}"
                            data-tipo-nome="{{ $documento->tipoDocumento->nome ?? 'Documento' }}"
                            data-pi-numero="{{ $documento->pi->numero_pi ?? 'N/A' }}"
                            data-validar="{{ $documento->tipoDocumento->validar ?? 0 }}"
                            data-validade-dias="{{ $documento->tipoDocumento->validade_dias ?? 0 }}"
                            style="cursor: pointer;">
                            <div class="activity-icon warning">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">{{ $documento->tipoDocumento->nome ?? 'Documento' }}</div>
                                <p class="activity-description">
                                    PI: #{{ $documento->pi->numero_pi ?? 'N/A' }}
                                    @if ($documento->pi && $documento->pi->cliente)
                                        | Cliente: {{ $documento->pi->cliente->razao_social }}
                                    @endif
                                </p>
                            </div>
                            <div class="activity-meta">
                                <span class="activity-status warning">
                                    <i class="bi bi-clock-history"></i>
                                    {{ $documento->status->nome ?? 'Pendente de Envio' }}
                                </span>
                                <button type="button" class="btn btn-sm btn-primary">
                                    <i class="bi bi-cloud-upload-fill"></i>
                                    Enviar
                                </button>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="bi bi-inbox"></i>
                        </div>
                        <h5 class="empty-state-title">Nenhuma atividade recente</h5>
                        <p class="empty-state-description">
                            Suas atividades aparecerão aqui quando você começar a usar o sistema
                        </p>
                    </div>
                @endif
            </div>
        </div>


        @hasrole('admin|gerencial|operacional')
            @if (isset($documentosVencendo) && $documentosVencendo->count() > 0)
                <div class="activity-section mt-4">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="bi bi-exclamation-triangle"></i>
                            <span>Documentos Vencendo</span>
                        </h2>
                    </div>

                    <div class="activity-card">
                        @foreach ($documentosVencendo as $documento)
                            <div class="activity-item">
                                <div class="activity-icon danger">
                                    <i class="bi bi-calendar-x"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">{{ $documento->tipoDocumento->nome }}</div>
                                    <p class="activity-description">
                                        Fornecedor: {{ $documento->fornecedor->razao_social ?? 'N/A' }} |
                                        Validade:
                                        {{ \Carbon\Carbon::parse($documento->data_validade_documento)->format('d/m/Y') }}
                                    </p>
                                </div>
                                <div class="activity-meta">
                                    <span class="activity-time">
                                        <i class="bi bi-clock"></i>
                                        Vence em
                                        {{ \Carbon\Carbon::parse($documento->data_validade_documento)->diffForHumans() }}
                                    </span>
                                    <span class="activity-status danger">
                                        Urgente
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endhasrole
    </div>

    @hasrole('fornecedor')
        <div class="modal fade" id="documentoPendenteModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">Enviar Documento</h5>
                            <p class="text-muted mb-0 small" id="modalSubtitlePendente">Preencha as informações do documento
                            </p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('fornecedor.documentos.store') }}" method="POST" id="documentoPendenteForm"
                            enctype="multipart/form-data">
                            @csrf

                            <input type="hidden" name="pi_documento_id" id="pi_documento_id">

                            <div class="alert alert-info mb-3">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                Este documento é necessário para o PI <strong id="piNumeroInfo"></strong>
                            </div>

                            <div class="content-card mb-3">
                                <div class="card-header-section">
                                    <div class="section-icon-wrapper">
                                        <i class="bi bi-file-earmark-arrow-up-fill"></i>
                                    </div>
                                    <div class="section-text">
                                        <h2 class="section-title">Informações do Documento</h2>
                                        <p class="section-description">Tipo de documento e validade</p>
                                    </div>
                                </div>

                                <div class="form-grid">
                                    <div class="form-field">
                                        <label class="field-label">
                                            <span>Tipo de Documento</span>
                                            <span class="required-indicator">*</span>
                                        </label>
                                        <div class="input-container">
                                            <div class="input-icon">
                                                <i class="bi bi-file-text-fill"></i>
                                            </div>
                                            <input type="text" id="tipo_documento_nome_display" class="field-input"
                                                readonly style="background-color: var(--bg-body); cursor: not-allowed;">
                                            <input type="hidden" name="tipo_documento_id" id="tipo_documento_id_pendente">
                                        </div>
                                    </div>

                                    <div class="form-field" id="campo-data-validade-pendente" style="display: none;">
                                        <label class="field-label">
                                            <span>Data de Validade do Documento</span>
                                            <span class="required-indicator">*</span>
                                        </label>
                                        <div class="input-container">
                                            <div class="input-icon">
                                                <i class="bi bi-calendar-event"></i>
                                            </div>
                                            <input type="date" name="data_validade_documento"
                                                id="data_validade_documento_pendente" class="field-input"
                                                min="{{ date('Y-m-d') }}">
                                        </div>
                                        <small class="field-hint">
                                            <i class="bi bi-info-circle"></i>
                                            Informe a data de vencimento do documento
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="content-card">
                                <div class="card-header-section">
                                    <div class="section-icon-wrapper">
                                        <i class="bi bi-cloud-arrow-up-fill"></i>
                                    </div>
                                    <div class="section-text">
                                        <h2 class="section-title">Upload de Arquivo</h2>
                                        <p class="section-description">Selecione o arquivo do documento (PDF, DOC, DOCX, JPG,
                                            PNG)</p>
                                    </div>
                                </div>

                                <div class="upload-section">
                                    <div class="upload-area" id="uploadAreaPendente">
                                        <input type="file" name="arquivo" id="arquivo_pendente" class="file-input"
                                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>

                                        <div class="upload-placeholder" id="uploadPlaceholderPendente">
                                            <div class="upload-icon">
                                                <i class="bi bi-cloud-arrow-up"></i>
                                            </div>
                                            <div class="upload-text">
                                                <p class="upload-title">Clique para selecionar ou arraste o arquivo aqui</p>
                                                <p class="upload-subtitle">Formatos aceitos: PDF, DOC, DOCX, JPG, PNG</p>
                                                <p class="upload-size">Tamanho máximo: 10MB</p>
                                            </div>
                                            <button type="button" class="btn-upload">
                                                <i class="bi bi-folder2-open me-2"></i>
                                                Escolher Arquivo
                                            </button>
                                        </div>

                                        <div class="upload-preview" id="uploadPreviewPendente" style="display: none;">
                                            <div class="preview-icon">
                                                <i class="bi bi-file-earmark-check-fill"></i>
                                            </div>
                                            <div class="preview-details">
                                                <p class="preview-name" id="previewNamePendente"></p>
                                                <p class="preview-size" id="previewSizePendente"></p>
                                            </div>
                                            <button type="button" class="btn-remove-file" id="btnRemoveFilePendente">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" id="submitBtnPendente">
                            <i class="bi bi-check-circle me-2"></i>
                            Enviar Documento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endhasrole
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            @hasrole('fornecedor')
                const uploadAreaPendente = $('#uploadAreaPendente');
                const fileInputPendente = $('#arquivo_pendente');
                const uploadPlaceholderPendente = $('#uploadPlaceholderPendente');
                const uploadPreviewPendente = $('#uploadPreviewPendente');
                const previewNamePendente = $('#previewNamePendente');
                const previewSizePendente = $('#previewSizePendente');
                const documentoPendenteModal = new bootstrap.Modal('#documentoPendenteModal');
                let selectedFilePendente = null;

                // Evento de click nos documentos pendentes
                $('.doc-pendente-item').on('click', function(e) {
                    e.preventDefault();

                    const piDocumentoId = $(this).data('pi-documento-id');
                    const tipoId = $(this).data('tipo-id');
                    const tipoNome = $(this).data('tipo-nome');
                    const piNumero = $(this).data('pi-numero');
                    const validar = parseInt($(this).data('validar'));
                    const validadeDias = parseInt($(this).data('validade-dias'));

                    // Resetar o formulário
                    resetFormPendente();

                    // Preencher os campos
                    $('#pi_documento_id').val(piDocumentoId);
                    $('#tipo_documento_id_pendente').val(tipoId);
                    $('#tipo_documento_nome_display').val(tipoNome);
                    $('#piNumeroInfo').text('#' + piNumero);

                    // Atualizar o subtítulo da modal
                    $('#modalSubtitlePendente').text('Enviando: ' + tipoNome + ' para PI #' + piNumero);

                    // Controlar visibilidade do campo de data de validade
                    if (validar === 1 && validadeDias > 0) {
                        $('#campo-data-validade-pendente').show();
                        $('#data_validade_documento_pendente').prop('required', true);
                    } else {
                        $('#campo-data-validade-pendente').hide();
                        $('#data_validade_documento_pendente').prop('required', false);
                        $('#data_validade_documento_pendente').val('');
                    }

                    // Abrir a modal
                    documentoPendenteModal.show();
                });

                $('#documentoPendenteModal').off('hidden.bs.modal').on('hidden.bs.modal', function() {
                    resetFormPendente();
                });

                $('#submitBtnPendente').off('click').on('click', function() {
                    const tipoDocumento = $('#tipo_documento_id_pendente').val();
                    const arquivo = $('#arquivo_pendente').val();
                    const dataValidadeRequired = $('#data_validade_documento_pendente').prop('required');
                    const dataValidade = $('#data_validade_documento_pendente').val();

                    // Validar campos obrigatórios
                    if (!tipoDocumento || !arquivo) {
                        toastr.error('Preencha todos os campos obrigatórios', 'Erro');
                        return false;
                    }

                    if (dataValidadeRequired && !dataValidade) {
                        toastr.error('Preencha a data de validade do documento', 'Erro');
                        return false;
                    }

                    $('#submitBtnPendente').prop('disabled', true).html(`
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Enviando...
        `);
                    $('#documentoPendenteForm').submit();
                });

                function resetFormPendente() {
                    $('#documentoPendenteForm')[0].reset();
                    fileInputPendente.val('');
                    selectedFilePendente = null;
                    uploadPreviewPendente.hide();
                    uploadPlaceholderPendente.show();
                    $('#campo-data-validade-pendente').hide();
                    $('#data_validade_documento_pendente').prop('required', false);
                    $('#submitBtnPendente').prop('disabled', false).html(`
            <i class="bi bi-check-circle me-2"></i>
            Enviar Documento
        `);
                }

                // Upload de arquivo
                $('.btn-upload').on('click', function(e) {
                    e.preventDefault();
                    fileInputPendente.click();
                });

                uploadAreaPendente.on('click', function(e) {
                    if (!$(e.target).is('#btnRemoveFilePendente, #btnRemoveFilePendente *')) {
                        fileInputPendente.click();
                    }
                });

                uploadAreaPendente.on('dragover', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $(this).addClass('drag-over');
                });

                uploadAreaPendente.on('dragleave', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $(this).removeClass('drag-over');
                });

                uploadAreaPendente.on('drop', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $(this).removeClass('drag-over');

                    const files = e.originalEvent.dataTransfer.files;
                    if (files.length > 0) {
                        fileInputPendente[0].files = files;
                        handleFileSelectPendente(files[0]);
                    }
                });

                fileInputPendente.on('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        handleFileSelectPendente(file);
                    }
                });

                function handleFileSelectPendente(file) {
                    const maxSize = 10 * 1024 * 1024; // 10MB
                    const allowedTypes = ['application/pdf', 'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg',
                        'image/jpg', 'image/png'
                    ];

                    if (file.size > maxSize) {
                        toastr.error('O arquivo excede o tamanho máximo de 10MB', 'Erro');
                        fileInputPendente.val('');
                        return;
                    }

                    if (!allowedTypes.includes(file.type)) {
                        toastr.error('Formato de arquivo não permitido. Use PDF, DOC, DOCX, JPG ou PNG', 'Erro');
                        fileInputPendente.val('');
                        return;
                    }

                    selectedFilePendente = file;
                    showPreviewPendente(file);
                }

                function showPreviewPendente(file) {
                    const fileName = file.name;
                    const fileSize = (file.size / 1048576).toFixed(2);
                    previewNamePendente.text(fileName);
                    previewSizePendente.html(`<i class="bi bi-hdd"></i> ${fileSize} MB`);

                    uploadPlaceholderPendente.hide();
                    uploadPreviewPendente.show();
                }

                $('#btnRemoveFilePendente').on('click', function(e) {
                    e.stopPropagation();
                    fileInputPendente.val('');
                    selectedFilePendente = null;
                    uploadPreviewPendente.hide();
                    uploadPlaceholderPendente.show();
                });
            @endhasrole

            @hasanyrole('admin|gerencial')
                window.carregarVinculosPendentes = function() {
                    $.ajax({
                        url: '/vinculos-representante/pendentes',
                        method: 'GET',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        success: function(response) {
                            if (!response.success || response.pendentes.length === 0) {
                                Swal.fire({
                                    title: 'Vínculos Pendentes',
                                    text: 'Nenhum vínculo pendente de aprovação.',
                                    icon: 'info',
                                    background: 'var(--bg-card)',
                                    color: 'var(--text-color)',
                                    confirmButtonColor: '#0ea5e9',
                                });
                                return;
                            }

                            var html = '<div style="text-align:left; max-height: 400px; overflow-y: auto;">';
                            response.pendentes.forEach(function(v) {
                                html += '<div class="vinculo-item-' + v.id + '" style="background: var(--bg-body); border-radius: 8px; padding: 12px; margin-bottom: 8px;">' +
                                    '<div style="display: flex; justify-content: space-between; align-items: start;">' +
                                    '<div>' +
                                    '<strong style="color: var(--text-color);">' + escapeHtmlDash(v.representante_nome) + '</strong>' +
                                    (v.representante_empresa ? '<br><small style="color: var(--text-muted);">' + escapeHtmlDash(v.representante_empresa) + '</small>' : '') +
                                    '<br><small style="color: var(--text-muted);">→ ' + escapeHtmlDash(v.fornecedor_razao) + '</small>' +
                                    '<br><small style="color: var(--text-muted);">' + escapeHtmlDash(v.data_solicitacao) + '</small>' +
                                    '</div>' +
                                    '<div style="display: flex; gap: 6px;">' +
                                    '<button onclick="aprovarVinculoPendente(' + v.id + ', this)" class="btn btn-sm" style="background:#10b981;color:#fff;border:none;padding:4px 10px;border-radius:6px;">' +
                                    '<i class="bi bi-check-lg"></i></button>' +
                                    '<button onclick="rejeitarVinculoPendente(' + v.id + ', this)" class="btn btn-sm" style="background:#ef4444;color:#fff;border:none;padding:4px 10px;border-radius:6px;">' +
                                    '<i class="bi bi-x-lg"></i></button>' +
                                    '</div></div></div>';
                            });
                            html += '</div>';

                            Swal.fire({
                                title: 'Vínculos Pendentes (' + response.pendentes.length + ')',
                                html: html,
                                showConfirmButton: false,
                                showCloseButton: true,
                                width: '550px',
                                background: 'var(--bg-card)',
                                color: 'var(--text-color)',
                            });
                        }
                    });
                };

                window.aprovarVinculoPendente = function(id, btnEl) {
                    $(btnEl).prop('disabled', true);
                    $.ajax({
                        url: '/vinculos-representante/' + id + '/aprovar',
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                $('.vinculo-item-' + id).fadeOut(300, function() { $(this).remove(); });
                            } else {
                                toastr.error(response.message);
                                $(btnEl).prop('disabled', false);
                            }
                        },
                        error: function(xhr) {
                            toastr.error(xhr.responseJSON?.message || 'Erro ao aprovar.');
                            $(btnEl).prop('disabled', false);
                        }
                    });
                };

                window.rejeitarVinculoPendente = function(id, btnEl) {
                    Swal.fire({
                        title: 'Motivo da rejeição',
                        input: 'textarea',
                        inputPlaceholder: 'Informe o motivo (opcional)',
                        showCancelButton: true,
                        confirmButtonText: 'Rejeitar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#475569',
                        background: 'var(--bg-card)',
                        color: 'var(--text-color)',
                        inputAttributes: {
                            style: 'background: var(--bg-body); color: var(--text-color); border: 1px solid var(--border-color);'
                        },
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            $(btnEl).prop('disabled', true);
                            $.ajax({
                                url: '/vinculos-representante/' + id + '/rejeitar',
                                method: 'POST',
                                data: { motivo: result.value || '' },
                                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                                success: function(response) {
                                    if (response.success) {
                                        toastr.success(response.message);
                                        $('.vinculo-item-' + id).fadeOut(300, function() { $(this).remove(); });
                                    } else {
                                        toastr.error(response.message);
                                        $(btnEl).prop('disabled', false);
                                    }
                                },
                                error: function(xhr) {
                                    toastr.error(xhr.responseJSON?.message || 'Erro ao rejeitar.');
                                    $(btnEl).prop('disabled', false);
                                }
                            });
                        }
                    });
                };

                function escapeHtmlDash(str) {
                    if (!str) return '';
                    var div = document.createElement('div');
                    div.textContent = str;
                    return div.innerHTML;
                }
            @endhasanyrole

            window.verDetalhesFornecedor = function(fornecedorId) {
                $.ajax({
                    url: '/perfil/fornecedor-vinculado/' + fornecedorId,
                    method: 'GET',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(response) {
                        if (!response.success) {
                            toastr.error(response.message);
                            return;
                        }

                        var f = response.fornecedor;
                        var html = '<div style="text-align:left;">';

                        html += '<div style="background: var(--bg-body); border-radius: 8px; padding: 16px; margin-bottom: 12px;">';
                        html += '<h4 style="color: var(--info-color); margin: 0 0 8px 0; font-size: 16px;">' + escapeHtmlDash(f.razao_social) + '</h4>';
                        if (f.nome_fantasia) html += '<p style="color: var(--text-muted); margin: 0 0 4px 0;">Fantasia: ' + escapeHtmlDash(f.nome_fantasia) + '</p>';
                        if (f.cnpj) html += '<p style="color: var(--text-muted); margin: 0 0 4px 0;">CNPJ: ' + escapeHtmlDash(f.cnpj) + '</p>';
                        if (f.telefone) html += '<p style="color: var(--text-muted); margin: 0 0 4px 0;">Tel: ' + escapeHtmlDash(f.telefone) + '</p>';
                        if (f.email_contato) html += '<p style="color: var(--text-muted); margin: 0 0 4px 0;">Email: ' + escapeHtmlDash(f.email_contato) + '</p>';
                        if (f.site) html += '<p style="color: var(--text-muted); margin: 0;">Site: ' + escapeHtmlDash(f.site) + '</p>';
                        html += '</div>';

                        if (f.enderecos && f.enderecos.length > 0) {
                            html += '<div style="background: var(--bg-body); border-radius: 8px; padding: 16px; margin-bottom: 12px;">';
                            html += '<h5 style="color: var(--text-color); margin: 0 0 8px 0; font-size: 14px;"><i class="bi bi-geo-alt"></i> Endereço</h5>';
                            f.enderecos.forEach(function(e) {
                                html += '<p style="color: var(--text-muted); margin: 0;">';
                                html += escapeHtmlDash(e.logradouro || '') + (e.numero ? ', ' + escapeHtmlDash(e.numero) : '');
                                if (e.complemento) html += ' - ' + escapeHtmlDash(e.complemento);
                                html += '<br>' + escapeHtmlDash(e.bairro || '') + ' - ' + escapeHtmlDash(e.cidade || '') + '/' + escapeHtmlDash(e.estado || '');
                                if (e.cep) html += ' - CEP: ' + escapeHtmlDash(e.cep);
                                html += '</p>';
                            });
                            html += '</div>';
                        }

                        if (f.representantes_contato && f.representantes_contato.length > 0) {
                            html += '<div style="background: var(--bg-body); border-radius: 8px; padding: 16px; margin-bottom: 12px;">';
                            html += '<h5 style="color: var(--text-color); margin: 0 0 8px 0; font-size: 14px;"><i class="bi bi-person-badge"></i> Contatos</h5>';
                            f.representantes_contato.forEach(function(r) {
                                html += '<p style="color: var(--text-muted); margin: 0 0 4px 0;">' + escapeHtmlDash(r.nome_completo);
                                if (r.telefone_1) html += ' | ' + escapeHtmlDash(r.telefone_1);
                                if (r.email_1) html += ' | ' + escapeHtmlDash(r.email_1);
                                html += '</p>';
                            });
                            html += '</div>';
                        }

                        html += '<div style="display: flex; gap: 16px; justify-content: center;">';
                        html += '<div style="background: var(--bg-body); border-radius: 8px; padding: 12px 20px; text-align: center;">';
                        html += '<div style="color: var(--info-color); font-size: 20px; font-weight: bold;">' + response.total_pis + '</div>';
                        html += '<div style="color: var(--text-muted); font-size: 12px;">Total PIs</div>';
                        html += '</div>';
                        html += '<div style="background: var(--bg-body); border-radius: 8px; padding: 12px 20px; text-align: center;">';
                        html += '<div style="color: var(--success-color); font-size: 20px; font-weight: bold;">' + response.pis_ativos + '</div>';
                        html += '<div style="color: var(--text-muted); font-size: 12px;">PIs Ativos</div>';
                        html += '</div>';
                        html += '</div>';

                        html += '</div>';

                        Swal.fire({
                            title: 'Dados do Fornecedor',
                            html: html,
                            showConfirmButton: false,
                            showCloseButton: true,
                            width: '550px',
                            background: 'var(--bg-card)',
                            color: 'var(--text-color)',
                        });
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON ? xhr.responseJSON.message : 'Erro ao carregar dados.');
                    }
                });
            };
        });
    </script>
@endpush

@push('styles')
    <style>
        /* Estilos para os cards de documentos pendentes */
        .doc-pendente-item {
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .doc-pendente-item:hover {
            background-color: var(--bg-body);
            border-left-color: #667eea;
            transform: translateX(5px);
        }

        .activity-meta .btn {
            padding: 0.375rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.875rem;
        }

        /* Estilos da modal */
        .content-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .card-header-section {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .section-icon-wrapper {
            width: 48px;
            height: 48px;
            background: var(--primary-gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .section-text {
            flex: 1;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
        }

        .section-description {
            color: #718096;
            margin: 0;
            font-size: 0.875rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .field-label {
            font-weight: 600;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .required-indicator {
            color: var(--danger-color);
        }

        .input-container {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            pointer-events: none;
            z-index: 1;
        }

        .field-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .field-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .field-hint {
            color: #718096;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .upload-section {
            margin-top: 1.5rem;
        }

        .upload-area {
            position: relative;
            border: 3px dashed #cbd5e0;
            border-radius: 16px;
            padding: 3rem 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .upload-area:hover {
            border-color: #667eea;
            background: var(--bg-body);
        }

        .upload-area.drag-over {
            border-color: #667eea;
            background: #eef2ff;
        }

        .file-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .upload-placeholder {
            pointer-events: none;
        }

        .upload-icon {
            font-size: 4rem;
            color: #a0aec0;
            margin-bottom: 1rem;
        }

        .upload-text {
            margin-bottom: 1.5rem;
        }

        .upload-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .upload-subtitle {
            color: #718096;
            margin-bottom: 0.25rem;
        }

        .upload-size {
            color: #a0aec0;
            font-size: 0.85rem;
        }

        .btn-upload {
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .upload-preview {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: var(--bg-body);
            padding: 1.5rem;
            border-radius: 12px;
        }

        .preview-icon {
            font-size: 3rem;
            color: var(--success-color);
        }

        .preview-details {
            flex: 1;
            text-align: left;
        }

        .preview-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .preview-size {
            color: #718096;
            font-size: 0.9rem;
            margin: 0;
        }

        .btn-remove-file {
            background: #fed7d7;
            color: var(--danger-color);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-remove-file:hover {
            background: #fc8181;
            color: white;
        }
    </style>
@endpush