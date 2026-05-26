{{-- resources/views/pis/validar-documentos.blade.php --}}
@extends('layouts.app')

@section('title', 'Validar Documentos do PI')

@section('content')
    <div class="container-fluid px-4 validar-docs-container">
        <div class="form-header-card validar-docs-header">
            <div class="header-left">
                <a href="{{ route('pis.index') }}" class="btn-back">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div class="header-text">
                    <h1 class="page-title">Validar Documentos do PI</h1>
                    <p class="page-subtitle">
                        <span class="validar-docs-badge">PI: {{ $pi->numero_pi }}</span>
                        <span class="validar-docs-separator">•</span>
                        <span class="validar-docs-badge">Cliente: {{ $pi->cliente->razao_social }}</span>
                        @if ($pi->fornecedor)
                            <span class="validar-docs-separator">•</span>
                            <span class="validar-docs-badge">Fornecedor: {{ $pi->fornecedor->razao_social }}</span>
                        @endif
                    </p>
                </div>
            </div>
            <div class="header-right">
                @if (auth()->user()->hasAnyRole(['admin', 'gerencial', 'operacional']))
                    @if ($pi->file_path)
                        <button type="button" class="validar-docs-btn validar-docs-btn-view-pi" id="btnVerDocumentoPi"
                            onclick="visualizarDocumentoPi()">
                            <i class="bi bi-file-earmark-pdf"></i>
                            Ver Documento PI
                        </button>
                    @endif
                @endif
                <button type="button" class="validar-docs-btn validar-docs-btn-add-avulso" id="btnAdicionarDocumentoAvulso"
                    data-pi-id="{{ $pi->id }}">
                    <i class="bi bi-file-earmark-plus"></i>
                    Adicionar Documento Avulso
                </button>
                @if (auth()->user()->hasAnyRole(['admin', 'gerencial', 'operacional']))
                    @if (!$pi->concluido)
                        @if ($pi->todosDocumentosAprovados() || auth()->user()->hasAnyRole(['admin', 'gerencial']))
                            <button type="button" class="validar-docs-btn validar-docs-btn-concluir" id="btnConcluirPi">
                                <i class="bi bi-check-circle"></i>
                                Concluir PI
                            </button>
                        @endif
                    @endif
                @endif
            </div>
        </div>

        <div class="row g-4 validar-docs-stats-row">
            <div class="col-lg-3 col-md-6">
                <div class="validar-docs-stats-card validar-docs-stats-pending">
                    <div class="validar-docs-stats-icon">
                        <div class="validar-docs-icon-wrapper pending">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                    <div class="validar-docs-stats-content">
                        <div class="validar-docs-stats-number">{{ $documentosPendentes->count() }}</div>
                        <div class="validar-docs-stats-label">Pendentes de Validação</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="validar-docs-stats-card validar-docs-stats-approved">
                    <div class="validar-docs-stats-icon">
                        <div class="validar-docs-icon-wrapper approved">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                    </div>
                    <div class="validar-docs-stats-content">
                        <div class="validar-docs-stats-number">{{ $documentosAprovados->count() }}</div>
                        <div class="validar-docs-stats-label">Aprovados</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="validar-docs-stats-card validar-docs-stats-rejected">
                    <div class="validar-docs-stats-icon">
                        <div class="validar-docs-icon-wrapper rejected">
                            <i class="bi bi-x-circle-fill"></i>
                        </div>
                    </div>
                    <div class="validar-docs-stats-content">
                        <div class="validar-docs-stats-number">{{ $documentosReprovados->count() }}</div>
                        <div class="validar-docs-stats-label">Reprovados</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="validar-docs-stats-card validar-docs-stats-missing">
                    <div class="validar-docs-stats-icon">
                        <div class="validar-docs-icon-wrapper missing">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                    </div>
                    <div class="validar-docs-stats-content">
                        <div class="validar-docs-stats-number">{{ $documentosSemEnvio->count() }}</div>
                        <div class="validar-docs-stats-label">Aguardando Fornecedor</div>
                    </div>
                </div>
            </div>
        </div>

        @if ($dataPrimeiroEnvio || $dataUltimaAprovacao || $pi->data_cadastro)
            <div class="validar-docs-timeline-bar">
                <div class="timeline-bar-inner">
                    <div class="timeline-bar-item">
                        <div class="timeline-bar-icon created">
                            <i class="bi bi-plus-circle-fill"></i>
                        </div>
                        <div class="timeline-bar-info">
                            <span class="timeline-bar-label">PI Criado</span>
                            <span class="timeline-bar-date">{{ $pi->created_at->format('d/m/Y') }}</span>
                        </div>
                    </div>
                    @if ($dataPrimeiroEnvio)
                        <div class="timeline-bar-connector active"></div>
                        <div class="timeline-bar-item">
                            <div class="timeline-bar-icon docs-sent">
                                <i class="bi bi-file-earmark-arrow-up-fill"></i>
                            </div>
                            <div class="timeline-bar-info">
                                <span class="timeline-bar-label">Docs Enviados</span>
                                <span class="timeline-bar-date">{{ \Carbon\Carbon::parse($dataPrimeiroEnvio)->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    @else
                        <div class="timeline-bar-connector"></div>
                        <div class="timeline-bar-item pending">
                            <div class="timeline-bar-icon">
                                <i class="bi bi-file-earmark-arrow-up"></i>
                            </div>
                            <div class="timeline-bar-info">
                                <span class="timeline-bar-label">Docs Enviados</span>
                                <span class="timeline-bar-date muted">Pendente</span>
                            </div>
                        </div>
                    @endif
                    @if ($dataUltimaAprovacao)
                        <div class="timeline-bar-connector active"></div>
                        <div class="timeline-bar-item">
                            <div class="timeline-bar-icon docs-approved">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <div class="timeline-bar-info">
                                <span class="timeline-bar-label">Docs Aprovados</span>
                                <span class="timeline-bar-date">{{ \Carbon\Carbon::parse($dataUltimaAprovacao)->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    @else
                        <div class="timeline-bar-connector"></div>
                        <div class="timeline-bar-item pending">
                            <div class="timeline-bar-icon">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="timeline-bar-info">
                                <span class="timeline-bar-label">Docs Aprovados</span>
                                <span class="timeline-bar-date muted">Pendente</span>
                            </div>
                        </div>
                    @endif
                    @if ($pi->concluido)
                        <div class="timeline-bar-connector active"></div>
                        <div class="timeline-bar-item">
                            <div class="timeline-bar-icon concluded">
                                <i class="bi bi-flag-fill"></i>
                            </div>
                            <div class="timeline-bar-info">
                                <span class="timeline-bar-label">Concluído</span>
                                <span class="timeline-bar-date">{{ $pi->data_conclusao ? \Carbon\Carbon::parse($pi->data_conclusao)->format('d/m/Y') : '-' }}</span>
                            </div>
                        </div>
                    @else
                        <div class="timeline-bar-connector"></div>
                        <div class="timeline-bar-item pending">
                            <div class="timeline-bar-icon">
                                <i class="bi bi-flag"></i>
                            </div>
                            <div class="timeline-bar-info">
                                <span class="timeline-bar-label">Conclusão</span>
                                <span class="timeline-bar-date muted">Pendente</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if ($documentosPendentes->count() > 0)
            <div class="validar-docs-section">
                <div class="validar-docs-section-header">
                    <div class="validar-docs-section-title-wrapper">
                        <div class="validar-docs-section-icon pending">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div>
                            <h2 class="validar-docs-section-title">Documentos Pendentes de Validação</h2>
                            <p class="validar-docs-section-subtitle">Clique em um documento para visualizar e validar</p>
                        </div>
                    </div>
                </div>

                <div class="validar-docs-grid">
                    @foreach ($documentosPendentes as $piDoc)
                        <div class="validar-docs-card pending-card" data-doc-id="{{ $piDoc->id }}">
                            <div class="validar-docs-card-header">
                                <div class="validar-docs-card-status pending">
                                    <i class="bi bi-clock-history"></i>
                                    {{ $piDoc->documentoEnviado->status->nome }}
                                </div>
                                <div class="validar-docs-card-badges">
                                    @if ($piDoc->tipoDocumento->avulso ?? false)
                                        <div class="validar-docs-card-badge avulso">
                                            <i class="bi bi-file-earmark-plus"></i>
                                            Avulso
                                        </div>
                                    @endif
                                    @if ($piDoc->documentoEnviado->isVencendo())
                                        <div class="validar-docs-card-alert warning">
                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                            Vencendo em breve
                                        </div>
                                    @elseif($piDoc->documentoEnviado->isVencido())
                                        <div class="validar-docs-card-alert danger">
                                            <i class="bi bi-exclamation-circle-fill"></i>
                                            Vencido
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="validar-docs-card-body">
                                <div class="validar-docs-card-icon pending">
                                    <i class="bi bi-file-earmark-text-fill"></i>
                                </div>
                                <h3 class="validar-docs-card-title">{{ $piDoc->tipoDocumento->nome }}</h3>
                                @if ($piDoc->tipoDocumento->descricao)
                                    <p class="validar-docs-card-description">{{ $piDoc->tipoDocumento->descricao }}</p>
                                @endif
                                <div class="validar-docs-card-meta">
                                    <div class="meta-item">
                                        <i class="bi bi-calendar-check"></i>
                                        <span>Enviado em:
                                            {{ $piDoc->documentoEnviado->data_envio->format('d/m/Y H:i') }}</span>
                                    </div>
                                    @if ($piDoc->documentoEnviado->data_validade_documento)
                                        <div class="meta-item">
                                            <i class="bi bi-calendar-event"></i>
                                            <span>Validade:
                                                {{ $piDoc->documentoEnviado->data_validade_documento->format('d/m/Y') }}</span>
                                        </div>
                                    @endif
                                    @if ($piDoc->documentoEnviado->arquivos->count() > 0)
                                        <div class="meta-item">
                                            <i class="bi bi-paperclip"></i>
                                            <span>{{ $piDoc->documentoEnviado->arquivos->count() }}
                                                {{ Str::plural('arquivo', $piDoc->documentoEnviado->arquivos->count()) }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="validar-docs-card-footer">
                                <button class="validar-docs-btn validar-docs-btn-view"
                                    onclick="visualizarDocumento({{ $piDoc->id }})"
                                    aria-label="Visualizar documento {{ $piDoc->tipoDocumento->nome }}">
                                    <i class="bi bi-eye"></i>
                                    Visualizar
                                </button>
                                <button class="validar-docs-btn validar-docs-btn-approve"
                                    onclick="aprovarDocumento({{ $piDoc->id }})"
                                    data-doc-nome="{{ $piDoc->tipoDocumento->nome }}"
                                    aria-label="Aprovar documento {{ $piDoc->tipoDocumento->nome }}">
                                    <i class="bi bi-check-circle"></i>
                                    Aprovar
                                </button>
                                <button class="validar-docs-btn validar-docs-btn-reject"
                                    onclick="reprovarDocumento({{ $piDoc->id }})"
                                    data-doc-nome="{{ $piDoc->tipoDocumento->nome }}"
                                    aria-label="Reprovar documento {{ $piDoc->tipoDocumento->nome }}">
                                    <i class="bi bi-x-circle"></i>
                                    Reprovar
                                </button>

                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($documentosAprovados->count() > 0)
            <div class="validar-docs-section">
                <div class="validar-docs-section-header">
                    <div class="validar-docs-section-title-wrapper">
                        <div class="validar-docs-section-icon approved">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div>
                            <h2 class="validar-docs-section-title">Documentos Aprovados</h2>
                            <p class="validar-docs-section-subtitle">Documentos validados e aprovados</p>
                        </div>
                    </div>
                </div>

                <div class="validar-docs-grid">
                    @foreach ($documentosAprovados as $piDoc)
                        <div class="validar-docs-card approved-card">
                            <div class="validar-docs-card-header">
                                <div class="validar-docs-card-status approved">
                                    <i class="bi bi-check-circle-fill"></i>
                                    {{ $piDoc->documentoEnviado->status->nome }}
                                </div>
                                @if ($piDoc->tipoDocumento->avulso ?? false)
                                    <div class="validar-docs-card-badges">
                                        <div class="validar-docs-card-badge avulso">
                                            <i class="bi bi-file-earmark-plus"></i>
                                            Avulso
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="validar-docs-card-body">
                                <div class="validar-docs-card-icon approved">
                                    <i class="bi bi-file-earmark-check-fill"></i>
                                </div>
                                <h3 class="validar-docs-card-title">{{ $piDoc->tipoDocumento->nome }}</h3>
                                @if ($piDoc->tipoDocumento->descricao)
                                    <p class="validar-docs-card-description">{{ $piDoc->tipoDocumento->descricao }}</p>
                                @endif
                                <div class="validar-docs-card-meta">
                                    <div class="meta-item">
                                        <i class="bi bi-calendar-check"></i>
                                        <span>Aprovado em:
                                            {{ $piDoc->documentoEnviado->validacoes->first()->data_validacao->format('d/m/Y H:i') }}</span>
                                    </div>
                                    @if ($piDoc->documentoEnviado->data_validade_documento)
                                        <div class="meta-item">
                                            <i class="bi bi-calendar-event"></i>
                                            <span>Validade:
                                                {{ $piDoc->documentoEnviado->data_validade_documento->format('d/m/Y') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="validar-docs-card-footer">
                                <button class="validar-docs-btn validar-docs-btn-view"
                                    onclick="visualizarDocumento({{ $piDoc->id }})"
                                    aria-label="Visualizar documento {{ $piDoc->tipoDocumento->nome }}">
                                    <i class="bi bi-eye"></i>
                                    Visualizar
                                </button>
                                <button class="validar-docs-btn validar-docs-btn-history"
                                    onclick="verHistorico({{ $piDoc->id }})"
                                    aria-label="Ver histórico do documento {{ $piDoc->tipoDocumento->nome }}">
                                    <i class="bi bi-clock-history"></i>
                                    Histórico
                                </button>
                                @if (
                                    !$pi->concluido ||
                                        auth()->user()->hasAnyRole(['admin', 'gerencial']))
                                    <button class="validar-docs-btn validar-docs-btn-revert"
                                        onclick="reverterValidacao({{ $piDoc->id }}, '{{ $piDoc->tipoDocumento->nome }}', 'Aprovado')"
                                        data-doc-nome="{{ $piDoc->tipoDocumento->nome }}"
                                        aria-label="Reverter aprovação do documento {{ $piDoc->tipoDocumento->nome }}">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                        Reverter
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($documentosReprovados->count() > 0)
            <div class="validar-docs-section">
                <div class="validar-docs-section-header">
                    <div class="validar-docs-section-title-wrapper">
                        <div class="validar-docs-section-icon rejected">
                            <i class="bi bi-x-circle-fill"></i>
                        </div>
                        <div>
                            <h2 class="validar-docs-section-title">Documentos para Reenvio</h2>
                            <p class="validar-docs-section-subtitle">Documentos reprovados ou vencidos que precisam ser reenviados pelo fornecedor</p>
                        </div>
                    </div>
                </div>

                <div class="validar-docs-grid">
                    @foreach ($documentosReprovados as $piDoc)
                        @php
                            $statusNomeDoc = $piDoc->documentoEnviado->status->nome ?? '';
                            $ultimaValidacao = $piDoc->documentoEnviado->validacoes->first();
                            $motivoTexto = $ultimaValidacao && $ultimaValidacao->observacoes ? $ultimaValidacao->observacoes : null;
                            $ehVencido = $statusNomeDoc === 'Aguardando Reenvio' && !$motivoTexto;
                        @endphp
                        <div class="validar-docs-card rejected-card">
                            <div class="validar-docs-card-header">
                                <div class="validar-docs-card-status rejected">
                                    <i class="bi bi-x-circle-fill"></i>
                                    {{ $statusNomeDoc }}
                                </div>
                                @if ($piDoc->tipoDocumento->avulso ?? false)
                                    <div class="validar-docs-card-badges">
                                        <div class="validar-docs-card-badge avulso">
                                            <i class="bi bi-file-earmark-plus"></i>
                                            Avulso
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="validar-docs-card-body">
                                <div class="validar-docs-card-icon rejected">
                                    <i class="bi bi-file-earmark-x-fill"></i>
                                </div>
                                <h3 class="validar-docs-card-title">{{ $piDoc->tipoDocumento->nome }}</h3>
                                @if ($piDoc->tipoDocumento->descricao)
                                    <p class="validar-docs-card-description">{{ $piDoc->tipoDocumento->descricao }}</p>
                                @endif
                                @if ($motivoTexto)
                                    <div class="validar-docs-card-rejection">
                                        <div class="rejection-header">
                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                            Motivo:
                                        </div>
                                        <div class="rejection-text">{{ $motivoTexto }}</div>
                                    </div>
                                @elseif ($ehVencido)
                                    <div class="validar-docs-card-warning">
                                        <i class="bi bi-calendar-x-fill"></i>
                                        Documento vencido — aguardando reenvio pelo fornecedor
                                    </div>
                                @endif
                            </div>
                            <div class="validar-docs-card-footer">
                                <button class="validar-docs-btn validar-docs-btn-view"
                                    onclick="visualizarDocumento({{ $piDoc->id }})"
                                    aria-label="Visualizar documento {{ $piDoc->tipoDocumento->nome }}">
                                    <i class="bi bi-eye"></i>
                                    Visualizar
                                </button>
                                <button class="validar-docs-btn validar-docs-btn-history"
                                    onclick="verHistorico({{ $piDoc->id }})"
                                    aria-label="Ver histórico do documento {{ $piDoc->tipoDocumento->nome }}">
                                    <i class="bi bi-clock-history"></i>
                                    Histórico
                                </button>
                                @if (
                                    !$pi->concluido ||
                                        auth()->user()->hasAnyRole(['admin', 'gerencial']))
                                    <button class="validar-docs-btn validar-docs-btn-revert"
                                        onclick="reverterValidacao({{ $piDoc->id }}, '{{ $piDoc->tipoDocumento->nome }}', '{{ $statusNomeDoc }}')"
                                        data-doc-nome="{{ $piDoc->tipoDocumento->nome }}"
                                        aria-label="Reverter validação do documento {{ $piDoc->tipoDocumento->nome }}">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                        Reverter
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($documentosSemEnvio->count() > 0)
            <div class="validar-docs-section">
                <div class="validar-docs-section-header">
                    <div class="validar-docs-section-title-wrapper">
                        <div class="validar-docs-section-icon missing">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div>
                            <h2 class="validar-docs-section-title">Documentos Sem Envio</h2>
                            <p class="validar-docs-section-subtitle">Documentos requeridos que ainda não foram enviados</p>
                        </div>
                    </div>
                </div>

                <div class="validar-docs-grid">
                    @foreach ($documentosSemEnvio as $piDoc)
                        <div class="validar-docs-card missing-card" data-pi-documento-id="{{ $piDoc->id }}">
                            <div class="validar-docs-card-header">
                                <div class="validar-docs-card-status missing">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    Não Enviado
                                </div>
                                <div class="validar-docs-card-badges">
                                    @if ($piDoc->opcional)
                                        <div class="validar-docs-card-badge" style="background: rgba(0, 94, 130, 0.1); color: var(--primary-color);">
                                            <i class="bi bi-dash-circle"></i>
                                            Opcional
                                        </div>
                                    @endif
                                    @if ($piDoc->tipoDocumento->avulso ?? false)
                                        <div class="validar-docs-card-badge avulso">
                                            <i class="bi bi-file-earmark-plus"></i>
                                            Avulso
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="validar-docs-card-body">
                                <div class="validar-docs-card-icon missing">
                                    <i class="bi bi-file-earmark-excel-fill"></i>
                                </div>
                                <h3 class="validar-docs-card-title">{{ $piDoc->tipoDocumento->nome }}</h3>
                                @if ($piDoc->tipoDocumento->descricao)
                                    <p class="validar-docs-card-description">{{ $piDoc->tipoDocumento->descricao }}</p>
                                @endif
                                <div class="validar-docs-card-warning">
                                    <i class="bi bi-info-circle-fill"></i>
                                    {{ $piDoc->opcional ? 'Documento opcional — não bloqueia a conclusão do PI' : 'Este documento ainda não foi enviado pelo fornecedor' }}
                                </div>
                            </div>
                            @if (
                                ($piDoc->tipoDocumento->avulso ?? false) &&
                                    auth()->user()->hasAnyRole(['admin', 'gerencial', 'operacional']))
                                <div class="validar-docs-card-footer">
                                    <button class="validar-docs-btn validar-docs-btn-remove"
                                        onclick="removerDocumentoAvulso({{ $piDoc->id }}, '{{ $piDoc->tipoDocumento->nome }}')"
                                        aria-label="Remover documento avulso {{ $piDoc->tipoDocumento->nome }}">
                                        <i class="bi bi-trash"></i>
                                        Remover
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($documentosPendentes->count() === 0 && $documentosReprovados->count() === 0 && $documentosSemEnvio->count() === 0)
            <div class="validar-docs-empty-state success">
                <div class="validar-docs-empty-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h3 class="validar-docs-empty-title">Todos os Documentos Validados!</h3>
                <p class="validar-docs-empty-description">Não há documentos pendentes de validação para este PI.</p>
            </div>
        @endif
    </div>

    <div class="modal fade" id="visualizarDocumentoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content validar-docs-modal">
                <div class="modal-header validar-docs-modal-header">
                    <div class="validar-docs-modal-title-wrapper">
                        <div class="validar-docs-modal-icon">
                            <i class="bi bi-file-earmark-text-fill"></i>
                        </div>
                        <div>
                            <h5 class="validar-docs-modal-title" id="modalTituloDocumento">Visualizar Documento</h5>
                            <p class="validar-docs-modal-subtitle" id="modalSubtituloDocumento"></p>
                        </div>
                    </div>
                    <button type="button" class="validar-docs-btn-close" data-bs-dismiss="modal"
                        aria-label="Fechar modal">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body validar-docs-modal-body" id="modalBodyDocumento">
                    <div class="loading-container">
                        <div class="spinner-border text-primary"></div>
                        <p class="loading-text">Carregando documento...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="historicoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content validar-docs-modal">
                <div class="modal-header validar-docs-modal-header">
                    <div class="validar-docs-modal-title-wrapper">
                        <div class="validar-docs-modal-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <h5 class="validar-docs-modal-title">Histórico de Validações</h5>
                            <p class="validar-docs-modal-subtitle" id="modalSubtituloHistorico"></p>
                        </div>
                    </div>
                    <button type="button" class="validar-docs-btn-close" data-bs-dismiss="modal"
                        aria-label="Fechar modal">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body validar-docs-modal-body" id="modalBodyHistorico">
                    <div class="loading-container">
                        <div class="spinner-border text-primary"></div>
                        <p class="loading-text">Carregando histórico...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDocumentoPi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered validar-docs-modal-pi-dialog">
            <div class="modal-content validar-docs-modal">
                <div class="modal-header validar-docs-modal-header">
                    <div class="validar-docs-modal-title-wrapper">
                        <div class="validar-docs-modal-icon">
                            <i class="bi bi-file-earmark-pdf-fill"></i>
                        </div>
                        <div>
                            <h5 class="validar-docs-modal-title">Documento do PI</h5>
                            <p class="validar-docs-modal-subtitle">PI: {{ $pi->numero_pi }}</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('pis.download_documento', $pi) }}"
                            class="validar-docs-btn validar-docs-btn-view" title="Baixar documento">
                            <i class="bi bi-download"></i>
                            Baixar
                        </a>
                        <button type="button" class="validar-docs-btn-close" data-bs-dismiss="modal"
                            aria-label="Fechar modal">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                <div class="modal-body validar-docs-modal-body validar-docs-modal-pi-body p-0">
                    <iframe id="iframeDocumentoPi" src="" class="validar-docs-iframe"></iframe>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDocumentoAvulso" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content validar-docs-modal">
                <div class="modal-header validar-docs-modal-header">
                    <div class="validar-docs-modal-title-wrapper">
                        <div class="validar-docs-modal-icon">
                            <i class="bi bi-file-earmark-plus"></i>
                        </div>
                        <div>
                            <h5 class="validar-docs-modal-title">Adicionar Documento Avulso</h5>
                            <p class="validar-docs-modal-subtitle">Adicione um documento extra a esta PI</p>
                        </div>
                    </div>
                    <button type="button" class="validar-docs-btn-close" data-bs-dismiss="modal"
                        aria-label="Fechar modal">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body validar-docs-modal-body">
                    <div class="avulso-info-box">
                        <i class="bi bi-info-circle-fill"></i>
                        <p>Documentos avulsos são documentos extras que não fazem parte do padrão do cliente. Após
                            adicionado, o fornecedor poderá enviar o documento.</p>
                    </div>
                    <div class="form-group mt-4">
                        <label class="form-label" for="selectTipoDocumentoAvulso">Tipo de Documento</label>
                        <select class="form-select" id="selectTipoDocumentoAvulso">
                            <option value="">Carregando tipos disponíveis...</option>
                        </select>
                    </div>
                    <div class="form-group mt-3">
                        <label class="form-label" for="observacoesDocumentoAvulso">Observações (opcional)</label>
                        <textarea class="form-control" id="observacoesDocumentoAvulso" rows="3"
                            placeholder="Adicione uma observação sobre este documento..." maxlength="1000"></textarea>
                    </div>
                </div>
                <div class="modal-footer validar-docs-modal-footer">
                    <button type="button" class="validar-docs-btn validar-docs-btn-cancel" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i>
                        Cancelar
                    </button>
                    <button type="button" class="validar-docs-btn validar-docs-btn-confirm"
                        id="btnConfirmarDocumentoAvulso">
                        <i class="bi bi-plus-circle"></i>
                        Adicionar
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const piId = {{ $pi->id }};
        const documentos = @json($documentosPendentes->merge($documentosAprovados)->merge($documentosReprovados)->keyBy('id'));

        const SwalHelpers = {
            defaultConfig: {
                buttonsStyling: false,
                allowEscapeKey: true,
                width: '650px',
                padding: '2rem'
            },

            confirm(title, html, confirmText, icon = 'question', confirmClass = 'swal-confirm-btn') {
                return Swal.fire({
                    title: title,
                    html: html,
                    icon: icon,
                    showCancelButton: true,
                    confirmButtonText: confirmText,
                    cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                    customClass: {
                        confirmButton: confirmClass,
                        cancelButton: 'swal-cancel-btn'
                    },
                    ...this.defaultConfig
                });
            },

            confirmWithInput(title, html, confirmText, icon = 'question', confirmClass = 'swal-confirm-btn', inputId =
                'swal-input', required = false) {
                return Swal.fire({
                    title: title,
                    html: html,
                    icon: icon,
                    showCancelButton: true,
                    confirmButtonText: confirmText,
                    cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                    customClass: {
                        confirmButton: confirmClass,
                        cancelButton: 'swal-cancel-btn'
                    },
                    ...this.defaultConfig,
                    preConfirm: () => {
                        const value = document.getElementById(inputId).value;
                        if (required && (!value || value.trim() === '')) {
                            Swal.showValidationMessage('Este campo é obrigatório');
                            return false;
                        }
                        return value;
                    }
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
            },

            success(title, message) {
                return Swal.fire({
                    title: title,
                    text: message,
                    icon: 'success',
                    confirmButtonText: '<i class="bi bi-check-circle"></i> OK',
                    customClass: {
                        confirmButton: 'swal-confirm-btn swal-confirm-success'
                    },
                    buttonsStyling: false,
                    allowEscapeKey: true
                });
            },

            error(title, message) {
                return Swal.fire({
                    title: title,
                    text: message,
                    icon: 'error',
                    confirmButtonText: '<i class="bi bi-x-circle"></i> Fechar',
                    customClass: {
                        confirmButton: 'swal-confirm-btn swal-confirm-danger'
                    },
                    buttonsStyling: false,
                    allowEscapeKey: true
                });
            }
        };

        function visualizarDocumentoPi() {
            const iframe = document.getElementById('iframeDocumentoPi');
            iframe.src = '{{ route('pis.visualizar_documento', $pi) }}';
            const modal = new bootstrap.Modal(document.getElementById('modalDocumentoPi'));
            modal.show();
        }

        document.getElementById('modalDocumentoPi')?.addEventListener('hidden.bs.modal', function() {
            document.getElementById('iframeDocumentoPi').src = '';
        });

        function visualizarDocumento(docId) {
            const doc = documentos[docId];
            if (!doc || !doc.documento_enviado || !doc.documento_enviado.arquivos || doc.documento_enviado.arquivos
                .length === 0) {
                toastr.error('Não há arquivos disponíveis para visualização');
                return;
            }

            const modal = new bootstrap.Modal('#visualizarDocumentoModal');
            const modalBody = document.getElementById('modalBodyDocumento');
            const modalTitulo = document.getElementById('modalTituloDocumento');
            const modalSubtitulo = document.getElementById('modalSubtituloDocumento');

            modalTitulo.textContent = doc.tipo_documento.nome;
            modalSubtitulo.textContent =
                `Enviado em ${new Date(doc.documento_enviado.data_envio).toLocaleDateString('pt-BR')}`;

            modalBody.innerHTML = `
                <div class="loading-container">
                    <div class="spinner-border text-primary"></div>
                    <p class="loading-text">Carregando documento...</p>
                </div>
            `;

            modal.show();

            const arquivo = doc.documento_enviado.arquivos[0];
            const previewUrl = `{{ route('documentos.preview', ['documento' => ':docId', 'arquivo' => ':arquivoId']) }}`
                .replace(':docId', doc.documento_enviado.id)
                .replace(':arquivoId', arquivo.id);

            modalBody.innerHTML = `
                <div class="pdf-viewer-container">
                    <iframe src="${previewUrl}" frameborder="0"></iframe>
                </div>
            `;
        }

        function aprovarDocumento(docId) {
            const $button = $(`.validar-docs-btn-approve[onclick*="${docId}"]`);
            const docNome = $button.data('doc-nome');

            const modalHtml = `
                <div class="swal-modal-content">
                    <p class="swal-modal-text">
                        <strong>Confirma a aprovação deste documento?</strong>
                    </p>
                    <div class="doc-info-box success">
                        <p><strong>Documento:</strong> ${docNome}</p>
                        <p><strong>PI:</strong> {{ $pi->numero_pi }}</p>
                    </div>
                    <div class="swal-modal-field">
                        <label class="swal-modal-label">
                            Observações (opcional):
                        </label>
                        <textarea id="obs-aprovacao" 
                                  class="swal2-textarea swal-modal-textarea" 
                                  placeholder="Adicione observações sobre a aprovação..."></textarea>
                    </div>
                    <div class="info-box success">
                        <p>
                            <i class="bi bi-info-circle-fill"></i>
                            <span>O fornecedor será notificado automaticamente sobre a aprovação do documento.</span>
                        </p>
                    </div>
                </div>
            `;

            SwalHelpers.confirmWithInput(
                'Aprovar Documento',
                modalHtml,
                '<i class="bi bi-check-circle"></i> Aprovar',
                'question',
                'swal-confirm-btn swal-confirm-success',
                'obs-aprovacao',
                false
            ).then((result) => {
                if (result.isConfirmed) {
                    $button.prop('disabled', true).html('<i class="bi bi-hourglass-split spin"></i>');

                    const formData = new FormData();
                    formData.append('observacoes', result.value || 'Documento aprovado');
                    formData.append('_token', '{{ csrf_token() }}');

                    SwalHelpers.loading('Processando...', 'Aguarde enquanto aprovamos o documento.');

                    fetch(`{{ route('pis.aprovar_documento', ['pi' => $pi->id, 'documento' => ':docId']) }}`
                            .replace(':docId', docId), {
                                method: 'POST',
                                body: formData
                            })
                        .then(async response => {
                            const data = await response.json();

                            if (!response.ok) {
                                throw new Error(data.message || 'Erro ao aprovar documento');
                            }

                            return data;
                        })
                        .then(data => {
                            if (data.todos_aprovados) {
                                Swal.fire({
                                    title: 'Todos os Documentos Aprovados!',
                                    html: '<p class="swal-modal-text">Todos os documentos deste PI foram aprovados.<br><strong>Deseja concluir este PI agora?</strong></p>',
                                    icon: 'success',
                                    showCancelButton: true,
                                    confirmButtonText: '<i class="bi bi-check-circle"></i> Sim, concluir PI',
                                    cancelButtonText: '<i class="bi bi-x-circle"></i> Não, concluir depois',
                                    customClass: {
                                        confirmButton: 'swal-confirm-btn swal-confirm-success',
                                        cancelButton: 'swal-cancel-btn'
                                    },
                                    buttonsStyling: false,
                                    allowEscapeKey: false,
                                    allowOutsideClick: false
                                }).then((resultConcluir) => {
                                    if (resultConcluir.isConfirmed) {
                                        SwalHelpers.loading('Concluindo...',
                                            'Aguarde enquanto concluímos o PI.');
                                        executarConclusaoPi(false);
                                    } else {
                                        SwalHelpers.success('Documento Aprovado!',
                                                'O PI pode ser concluído manualmente quando desejar.')
                                            .then(() => {
                                                window.location.reload();
                                            });
                                    }
                                });
                            } else {
                                SwalHelpers.success('Aprovado!', data.message ||
                                    'Documento aprovado com sucesso').then(() => {
                                    window.location.reload();
                                });
                            }
                        })
                        .catch(error => {
                            $button.prop('disabled', false).html('<i class="bi bi-check-circle"></i> Aprovar');
                            SwalHelpers.error('Erro!', error.message);
                        });
                }
            });
        }

        function reprovarDocumento(docId) {
            const $button = $(`.validar-docs-btn-reject[onclick*="${docId}"]`);
            const docNome = $button.data('doc-nome');

            const modalHtml = `
                <div class="swal-modal-content">
                    <p class="swal-modal-text">
                        <strong>Informe o motivo da reprovação:</strong>
                    </p>
                    <div class="doc-info-box danger">
                        <p><strong>Documento:</strong> ${docNome}</p>
                        <p><strong>PI:</strong> {{ $pi->numero_pi }}</p>
                    </div>
                    <div class="swal-modal-field">
                        <label class="swal-modal-label">
                            Motivo da Reprovação <span class="swal-required">*</span>:
                        </label>
                        <textarea id="motivo-reprovacao" 
                                  class="swal2-textarea swal-modal-textarea swal-modal-textarea-lg" 
                                  placeholder="Descreva detalhadamente o motivo da reprovação..." 
                                  required></textarea>
                    </div>
                    <div class="warning-box">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div>
                            <strong>Importante:</strong>
                            <ul>
                                <li>O fornecedor será notificado sobre a reprovação</li>
                                <li>O documento precisará ser reenviado</li>
                                <li>O processo de validação será reiniciado</li>
                            </ul>
                        </div>
                    </div>
                </div>
            `;

            SwalHelpers.confirmWithInput(
                'Reprovar Documento',
                modalHtml,
                '<i class="bi bi-x-circle"></i> Reprovar',
                'warning',
                'swal-confirm-btn swal-confirm-danger',
                'motivo-reprovacao',
                true
            ).then((result) => {
                if (result.isConfirmed) {
                    $button.prop('disabled', true).html('<i class="bi bi-hourglass-split spin"></i>');

                    const formData = new FormData();
                    formData.append('observacoes', result.value);
                    formData.append('_token', '{{ csrf_token() }}');

                    SwalHelpers.loading('Processando...', 'Aguarde enquanto reprovamos o documento.');

                    fetch(`{{ route('pis.reprovar_documento', ['pi' => $pi->id, 'documento' => ':docId']) }}`
                            .replace(':docId', docId), {
                                method: 'POST',
                                body: formData
                            })
                        .then(async response => {
                            const data = await response.json();

                            if (!response.ok) {
                                throw new Error(data.message || 'Erro ao reprovar documento');
                            }

                            return data;
                        })
                        .then(data => {
                            SwalHelpers.success('Reprovado!', data.message ||
                                'Documento reprovado e fornecedor notificado').then(() => {
                                window.location.reload();
                            });
                        })
                        .catch(error => {
                            $button.prop('disabled', false).html('<i class="bi bi-x-circle"></i> Reprovar');
                            SwalHelpers.error('Erro!', error.message);
                        });
                }
            });
        }

        function verHistorico(docId) {
            const doc = documentos[docId];
            if (!doc || !doc.documento_enviado) {
                toastr.error('Documento não encontrado');
                return;
            }

            const modal = new bootstrap.Modal('#historicoModal');
            const modalBody = document.getElementById('modalBodyHistorico');
            const modalSubtitulo = document.getElementById('modalSubtituloHistorico');

            modalSubtitulo.textContent = doc.tipo_documento.nome;

            modalBody.innerHTML = `
                <div class="loading-container">
                    <div class="spinner-border text-primary"></div>
                    <p class="loading-text">Carregando histórico...</p>
                </div>
            `;

            modal.show();

            fetch(`{{ route('pis.historico_validacoes_documento', ['pi' => $pi->id, 'documento' => ':docId']) }}`.replace(
                    ':docId', docId))
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.validacoes.length > 0) {
                        let html = '<div class="historico-timeline">';

                        data.validacoes.forEach(validacao => {
                            const statusClass = validacao.status.nome === 'Aprovado' ? 'approved' :
                                validacao.status.nome === 'Reprovado' ? 'rejected' : 'resend';
                            const statusIcon = validacao.status.nome === 'Aprovado' ? 'check-circle' :
                                validacao.status.nome === 'Reprovado' ? 'x-circle' : 'arrow-repeat';
                            const dataValidacao = new Date(validacao.data_validacao);
                            const iniciais = validacao.operacional.nome_completo.split(' ').map(n => n[0])
                                .slice(0, 2).join('').toUpperCase();

                            html += `
                                <div class="historico-item">
                                    <div class="historico-item-marker ${statusClass}"></div>
                                    <div class="historico-item-content">
                                        <div class="historico-item-header">
                                            <div class="historico-item-status ${statusClass}">
                                                <i class="bi bi-${statusIcon}-fill"></i>
                                                ${validacao.status.nome}
                                            </div>
                                            <div class="historico-item-date">
                                                ${dataValidacao.toLocaleDateString('pt-BR')} às ${dataValidacao.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'})}
                                            </div>
                                        </div>
                                        <div class="historico-item-user">
                                            <div class="historico-user-avatar">${iniciais}</div>
                                            <div class="historico-user-name">${validacao.operacional.nome_completo}</div>
                                        </div>
                                        ${validacao.observacoes ? `
                                                                    <div class="historico-item-obs">
                                                                        ${escapeHtml(validacao.observacoes)}
                                                                    </div>
                                                                ` : ''}
                                    </div>
                                </div>
                            `;
                        });

                        html += '</div>';
                        modalBody.innerHTML = html;
                    } else {
                        modalBody.innerHTML = `
                            <div class="text-center py-5">
                                <i class="bi bi-info-circle historico-empty-icon"></i>
                                <p class="mt-3 text-muted">Nenhuma validação registrada para este documento</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    modalBody.innerHTML = `
                        <div class="text-center py-5">
                            <i class="bi bi-exclamation-triangle historico-error-icon"></i>
                            <p class="mt-3 text-danger">Erro ao carregar histórico</p>
                        </div>
                    `;
                });
        }

        function reverterValidacao(docId, docNome, statusAtual) {
            const piConcluido = {{ $pi->concluido ? 'true' : 'false' }};

            let avisoExtra = '';
            if (piConcluido) {
                avisoExtra = `
            <div class="warning-box swal-modal-mt">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>
                    <strong>Atenção:</strong>
                    <p class="swal-modal-text-sm">
                        Este PI já foi concluído. Ao reverter o documento, o PI será reaberto automaticamente.
                    </p>
                </div>
            </div>
        `;
            }

            const modalHtml = `
        <div class="swal-modal-content">
            <p class="swal-modal-text">
                <strong>Deseja reverter a validação deste documento?</strong>
            </p>
            <div class="doc-info-box warning">
                <p><strong>Documento:</strong> ${docNome}</p>
                <p><strong>Status Atual:</strong> ${statusAtual}</p>
                <p><strong>PI:</strong> {{ $pi->numero_pi }}</p>
            </div>
            <div class="swal-modal-field">
                <label class="swal-modal-label">
                    Motivo da reversão (opcional):
                </label>
                <textarea id="motivo-reversao" 
                          class="swal2-textarea swal-modal-textarea" 
                          placeholder="Descreva o motivo da reversão..."></textarea>
            </div>
            ${avisoExtra}
            <div class="info-box warning swal-modal-mt">
                <p>
                    <i class="bi bi-info-circle-fill"></i>
                    <span>O documento voltará para o status <strong>Pendente</strong> e precisará ser validado novamente.</span>
                </p>
            </div>
        </div>
    `;

            SwalHelpers.confirmWithInput(
                'Reverter Validação',
                modalHtml,
                '<i class="bi bi-arrow-counterclockwise"></i> Reverter',
                'warning',
                'swal-confirm-btn swal-confirm-warning',
                'motivo-reversao',
                false
            ).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('observacoes', result.value || '');
                    formData.append('_token', '{{ csrf_token() }}');

                    SwalHelpers.loading('Processando...', 'Aguarde enquanto revertemos a validação.');

                    fetch(`{{ url('pis') }}/${piId}/documentos/${docId}/reverter`, {
                            method: 'POST',
                            body: formData
                        })
                        .then(async response => {
                            const data = await response.json();

                            if (!response.ok) {
                                throw new Error(data.message || 'Erro ao reverter validação');
                            }

                            return data;
                        })
                        .then(data => {
                            let mensagemSucesso = data.message || 'Validação revertida com sucesso!';

                            if (data.pi_reaberto) {
                                SwalHelpers.success('Revertido!', mensagemSucesso).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                SwalHelpers.success('Revertido!', mensagemSucesso).then(() => {
                                    window.location.reload();
                                });
                            }
                        })
                        .catch(error => {
                            SwalHelpers.error('Erro!', error.message);
                        });
                }
            });
        }

        $('#btnAdicionarDocumentoAvulso').on('click', function() {
            const $select = $('#selectTipoDocumentoAvulso');

            $select.html('<option value="">Carregando tipos disponíveis...</option>').prop('disabled', true);
            $('#btnConfirmarDocumentoAvulso').prop('disabled', true);

            const modal = new bootstrap.Modal('#modalDocumentoAvulso');
            modal.show();

            $.ajax({
                url: `{{ route('pis.tipos_documentos_avulsos', ['pi' => $pi->id]) }}`,
                method: 'GET',
                success: function(response) {
                    if (response.success && response.tipos_documentos.length > 0) {
                        let options = '<option value="">Selecione o tipo de documento</option>';
                        response.tipos_documentos.forEach(tipo => {
                            const descricao = tipo.descricao ? ` - ${tipo.descricao}` : '';
                            options +=
                                `<option value="${tipo.id}">${tipo.nome}${descricao}</option>`;
                        });
                        $select.html(options).prop('disabled', false);
                        $('#btnConfirmarDocumentoAvulso').prop('disabled', false);
                    } else {
                        $select.html('<option value="">Nenhum documento avulso disponível</option>');
                        toastr.info('Não há tipos de documentos avulsos disponíveis para adicionar.');
                    }
                },
                error: function(xhr) {
                    $select.html('<option value="">Erro ao carregar documentos</option>');
                    let msg = 'Erro ao carregar tipos de documentos.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    toastr.error(msg);
                }
            });
        });

        $('#btnConfirmarDocumentoAvulso').on('click', function() {
            const tipoDocumentoId = $('#selectTipoDocumentoAvulso').val();
            const tipoDocumentoNome = $('#selectTipoDocumentoAvulso option:selected').text();
            const observacoes = $('#observacoesDocumentoAvulso').val();

            if (!tipoDocumentoId) {
                toastr.warning('Selecione um tipo de documento.');
                return;
            }

            const $btn = $(this);
            $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split spin"></i> Adicionando...');

            $.ajax({
                url: `{{ route('pis.adicionar_documento_avulso', ['pi' => $pi->id]) }}`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: {
                    tipo_documento_id: tipoDocumentoId,
                    observacoes: observacoes
                },
                success: function(response) {
                    if (response.success) {
                        bootstrap.Modal.getInstance('#modalDocumentoAvulso').hide();

                        SwalHelpers.success('Sucesso!', response.message).then(() => {
                            window.location.reload();
                        });
                    } else {
                        $btn.prop('disabled', false).html(
                            '<i class="bi bi-plus-circle"></i> Adicionar');
                        toastr.error(response.message || 'Erro ao adicionar documento.');
                    }
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).html('<i class="bi bi-plus-circle"></i> Adicionar');
                    let msg = 'Erro ao adicionar documento.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    toastr.error(msg);
                }
            });
        });

        function executarConclusaoPi(forcar) {
            var body = forcar ? JSON.stringify({ forcar: true }) : undefined;

            fetch('{{ route('pis.concluir', $pi) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: body,
            })
            .then(async response => {
                var data = await response.json();
                if (!response.ok) {
                    var err = new Error(data.message);
                    err.podeForcar = data.pode_forcar || false;
                    throw err;
                }
                return data;
            })
            .then(data => {
                SwalHelpers.success('PI Concluído!', data.message).then(() => {
                    window.location.href = '{{ route('pis.index') }}';
                });
            })
            .catch(error => {
                if (error.podeForcar) {
                    Swal.fire({
                        title: 'Documentos Pendentes',
                        html: '<p class="swal-modal-text">' + error.message + '<br><br><strong>Deseja forçar a conclusão mesmo assim?</strong><br><small>Esta ação será registrada no log do sistema.</small></p>',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '<i class="bi bi-exclamation-triangle"></i> Sim, forçar conclusão',
                        cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                        customClass: {
                            confirmButton: 'swal-confirm-btn swal-confirm-warning',
                            cancelButton: 'swal-cancel-btn'
                        },
                        buttonsStyling: false,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            executarConclusaoPi(true);
                        } else {
                            window.location.reload();
                        }
                    });
                } else {
                    SwalHelpers.error('Erro!', error.message).then(() => {
                        window.location.reload();
                    });
                }
            });
        }

        $('#btnConcluirPi').on('click', function() {
            SwalHelpers.confirm(
                'Concluir PI?',
                '<p class="swal-modal-text">Deseja marcar este PI como concluído?</p>',
                '<i class="bi bi-check-circle"></i> Sim, concluir',
                'question',
                'swal-confirm-btn swal-confirm-success'
            ).then((result) => {
                if (result.isConfirmed) {
                    executarConclusaoPi(false);
                }
            });
        });

        $('#modalDocumentoAvulso').on('hidden.bs.modal', function() {
            $('#selectTipoDocumentoAvulso').html('<option value="">Carregando tipos disponíveis...</option>');
            $('#observacoesDocumentoAvulso').val('');
            $('#btnConfirmarDocumentoAvulso').prop('disabled', false).html(
                '<i class="bi bi-plus-circle"></i> Adicionar');
        });

        function removerDocumentoAvulso(piDocumentoId, docNome) {
            const modalHtml = `
                <div class="swal-modal-content">
                    <p class="swal-modal-text">
                        <strong>Deseja remover este documento avulso da PI?</strong>
                    </p>
                    <div class="doc-info-box danger">
                        <p><strong>Documento:</strong> ${docNome}</p>
                        <p><strong>PI:</strong> {{ $pi->numero_pi }}</p>
                    </div>
                    <div class="warning-box">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div>
                            <strong>Atenção:</strong>
                            <p class="swal-modal-text-sm">
                                Esta ação não pode ser desfeita. O documento será removido permanentemente desta PI.
                            </p>
                        </div>
                    </div>
                </div>
            `;

            SwalHelpers.confirm(
                'Remover Documento Avulso',
                modalHtml,
                '<i class="bi bi-trash"></i> Remover',
                'warning',
                'swal-confirm-btn swal-confirm-danger'
            ).then((result) => {
                if (result.isConfirmed) {
                    SwalHelpers.loading('Removendo...', 'Aguarde enquanto removemos o documento.');

                    $.ajax({
                        url: `{{ url('pis') }}/${piId}/documento-avulso/${piDocumentoId}`,
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                SwalHelpers.success('Removido!', response.message).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                SwalHelpers.error('Erro!', response.message ||
                                    'Erro ao remover documento.');
                            }
                        },
                        error: function(xhr) {
                            let msg = 'Erro ao remover documento.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            SwalHelpers.error('Erro!', msg);
                        }
                    });
                }
            });
        }

        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 5000
        };

        @if (session('success'))
            toastr.success('{{ session('success') }}');
        @elseif (session('error'))
            toastr.error('{{ session('error') }}');
        @endif
    </script>
@endpush

@push('styles')
    <style>
        .validar-docs-container {
            padding-top: 1.5rem;
            padding-bottom: 2.5rem;
        }

        .validar-docs-header {
            background: white;
            border-radius: 16px;
            padding: 1.75rem;
            margin-bottom: 1.75rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-back {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-body);
            border: 1px solid #e2e8f0;
            border-radius: 11px;
            color: #4a5568;
            font-size: 1.125rem;
            text-decoration: none;
            transition: all 0.25s ease;
        }

        .btn-back:hover {
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            border-color: #005e82;
            color: white;
            transform: translateX(-3px);
        }

        .page-title {
            font-size: 1.625rem;
            font-weight: 800;
            color: #1a202c;
            margin-bottom: 0.375rem;
            line-height: 1.2;
        }

        .page-subtitle {
            font-size: 0.875rem;
            color: #718096;
            margin: 0;
            line-height: 1.4;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.25rem;
        }

        .validar-docs-badge {
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            color: white;
            padding: 0.35rem 0.875rem;
            border-radius: 18px;
            font-weight: 600;
            font-size: 0.8125rem;
            display: inline-block;
        }

        .validar-docs-separator {
            color: #a0aec0;
            margin: 0 0.375rem;
            font-weight: 300;
        }

        .validar-docs-btn-add-avulso {
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            color: white;
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9375rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 94, 130, 0.25);
        }

        .validar-docs-btn-add-avulso:hover {
            background: linear-gradient(135deg, #004a68 0%, #0077a3 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 94, 130, 0.35);
        }

        .validar-docs-stats-row {
            margin-bottom: 2rem;
        }

        .validar-docs-stats-card {
            background: white;
            border-radius: 18px;
            padding: 1.75rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            gap: 1.25rem;
            height: 100%;
            transition: all 0.3s ease;
        }

        .validar-docs-stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 94, 130, 0.12);
        }

        .validar-docs-stats-pending {
            border-left: 4px solid #f59e0b;
        }

        .validar-docs-stats-approved {
            border-left: 4px solid #10b981;
        }

        .validar-docs-stats-rejected {
            border-left: 4px solid #ef4444;
        }

        .validar-docs-stats-missing {
            border-left: 4px solid #6b7280;
        }

        .validar-docs-icon-wrapper {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            flex-shrink: 0;
        }

        .validar-docs-icon-wrapper.pending {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #d97706;
        }

        .validar-docs-icon-wrapper.approved {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #059669;
        }

        .validar-docs-icon-wrapper.rejected {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: var(--danger-color);
        }

        .validar-docs-icon-wrapper.missing {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: var(--text-muted);
        }

        .validar-docs-stats-content {
            flex: 1;
        }

        .validar-docs-stats-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 0.375rem;
        }

        .validar-docs-stats-label {
            font-size: 0.9375rem;
            font-weight: 700;
            color: #2d3748;
        }

        .validar-docs-section {
            margin-bottom: 3rem;
        }

        .validar-docs-section-header {
            margin-bottom: 1.5rem;
            padding-bottom: 0.875rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .validar-docs-section-title-wrapper {
            display: flex;
            align-items: center;
            gap: 0.875rem;
        }

        .validar-docs-section-icon {
            width: 46px;
            height: 46px;
            border-radius: 11px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.375rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
        }

        .validar-docs-section-icon.pending {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .validar-docs-section-icon.approved {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .validar-docs-section-icon.rejected {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .validar-docs-section-icon.missing {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
        }

        .validar-docs-section-title {
            font-size: 1.375rem;
            font-weight: 800;
            color: #1a202c;
            margin: 0;
            line-height: 1.2;
        }

        .validar-docs-section-subtitle {
            font-size: 0.875rem;
            color: #718096;
            margin: 0.2rem 0 0 0;
            font-weight: 500;
        }

        .validar-docs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .validar-docs-card {
            background: white;
            border-radius: 18px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .validar-docs-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.1);
        }

        .pending-card {
            border: 2px solid #fde68a;
        }

        .approved-card {
            border: 2px solid #a7f3d0;
        }

        .rejected-card {
            border: 2px solid #fecaca;
        }

        .missing-card {
            border: 2px solid #e5e7eb;
        }

        .validar-docs-card-header {
            padding: 1rem 1.25rem;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .validar-docs-card-badges {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .validar-docs-card-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.875rem;
            border-radius: 20px;
            font-size: 0.8125rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .validar-docs-card-status.pending {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: var(--text-on-light-bg);
        }

        .validar-docs-card-status.approved {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }

        .validar-docs-card-status.rejected {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: var(--danger-color);
        }

        .validar-docs-card-status.missing {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: var(--text-color);
        }

        .validar-docs-card-badge.avulso {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .validar-docs-card-alert {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.625rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .validar-docs-card-alert.warning {
            background: rgba(245, 158, 11, 0.12);
            color: var(--text-on-light-bg);
        }

        .validar-docs-card-alert.danger {
            background: #fee2e2;
            color: var(--danger-color);
        }

        .validar-docs-card-body {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .validar-docs-card-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 1.25rem;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }

        .validar-docs-card-icon.pending {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #d97706;
        }

        .validar-docs-card-icon.approved {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #059669;
        }

        .validar-docs-card-icon.rejected {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: var(--danger-color);
        }

        .validar-docs-card-icon.missing {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: var(--text-muted);
        }

        .validar-docs-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 0.5rem;
            text-align: center;
            line-height: 1.3;
        }

        .validar-docs-card-description {
            font-size: 0.875rem;
            color: #718096;
            text-align: center;
            margin-bottom: 1rem;
        }

        .validar-docs-card-meta {
            display: flex;
            flex-direction: column;
            gap: 0.625rem;
            margin-top: auto;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #4a5568;
        }

        .meta-item i {
            color: #718096;
            font-size: 1rem;
        }

        .validar-docs-card-rejection {
            margin-top: 1rem;
            padding: 1rem;
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-radius: 10px;
            border-left: 4px solid #dc2626;
        }

        .rejection-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            color: var(--danger-color);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .rejection-text {
            color: #7f1d1d;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .validar-docs-card-warning {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(245, 158, 11, 0.12);
            border-radius: 10px;
            border-left: 4px solid #f59e0b;
            display: flex;
            align-items: center;
            gap: 0.625rem;
            font-size: 0.875rem;
            color: var(--text-on-light-bg);
        }

        .validar-docs-card-footer {
            padding: 1rem 1.25rem;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            display: flex;
            flex-wrap: wrap;
            gap: 0.625rem;
        }

        .validar-docs-btn {
            flex: 1;
            min-width: 120px;
            padding: 0.625rem 1rem;
            border: none;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .validar-docs-btn-view {
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            color: white;
        }

        .validar-docs-btn-view:hover {
            background: linear-gradient(135deg, #004a68 0%, #0077a3 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 94, 130, 0.3);
            color: white;
        }

        .validar-docs-btn-approve {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .validar-docs-btn-approve:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
        }

        .validar-docs-btn-reject {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .validar-docs-btn-reject:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }


        .validar-docs-btn-history {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
        }

        .validar-docs-btn-history:hover {
            background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(75, 85, 99, 0.3);
        }

        .validar-docs-btn-remove {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: var(--danger-color);
            border: 1px solid #fca5a5;
        }

        .validar-docs-btn-remove:hover {
            background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
            color: #b91c1c;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
        }

        .validar-docs-btn-cancel {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: #4b5563;
            border: 1px solid #d1d5db;
        }

        .validar-docs-btn-cancel:hover {
            background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
            color: var(--text-color);
        }

        .validar-docs-btn-confirm {
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            color: white;
        }

        .validar-docs-btn-confirm:hover {
            background: linear-gradient(135deg, #004a68 0%, #0077a3 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 94, 130, 0.3);
        }

        .validar-docs-btn-confirm:disabled,
        .validar-docs-btn-cancel:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .validar-docs-empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 18px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        }

        .validar-docs-empty-state.success {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border: 2px solid #10b981;
        }

        .validar-docs-empty-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 1.75rem;
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #059669;
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.18);
        }

        .validar-docs-empty-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: #065f46;
            margin-bottom: 0.75rem;
        }

        .validar-docs-empty-description {
            font-size: 1.125rem;
            color: #047857;
            font-weight: 500;
            margin: 0;
        }

        .validar-docs-modal {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
            overflow: hidden;
        }

        .validar-docs-modal-header {
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            color: white;
            padding: 1.5rem 1.75rem;
            border-bottom: none;
        }

        .validar-docs-modal-title-wrapper {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            color: white;
        }

        .validar-docs-modal-icon {
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.375rem;
            backdrop-filter: blur(10px);
        }

        .validar-docs-modal-title {
            font-size: 1.375rem;
            font-weight: 800;
            margin: 0;
            line-height: 1.2;
            color: white;
        }

        .validar-docs-modal-subtitle {
            font-size: 0.875rem;
            margin: 0.2rem 0 0 0;
            opacity: 0.95;
            font-weight: 500;
            color: white;
        }

        .validar-docs-btn-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 9px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            font-size: 1.1rem;
        }

        .validar-docs-btn-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .validar-docs-modal-body {
            padding: 2rem;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }

        .validar-docs-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding: 1.25rem 1.75rem;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .avulso-info-box {
            display: flex;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-radius: 10px;
            border-left: 4px solid #3b82f6;
        }

        .avulso-info-box i {
            color: #2563eb;
            font-size: 1.25rem;
            flex-shrink: 0;
            margin-top: 0.125rem;
        }

        .avulso-info-box p {
            margin: 0;
            color: #1e40af;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .loading-container {
            text-align: center;
            padding: 3rem 2rem;
        }

        .loading-text {
            margin-top: 1rem;
            color: #718096;
            font-size: 1rem;
            font-weight: 600;
        }

        .pdf-viewer-container {
            width: 100%;
            height: 70vh;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
        }

        .pdf-viewer-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .validar-docs-modal-pi-dialog {
            max-width: 90vw;
        }

        .validar-docs-modal-pi-body {
            height: 80vh;
        }

        .validar-docs-iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .historico-timeline {
            position: relative;
            padding-left: 2rem;
        }

        .historico-timeline::before {
            content: '';
            position: absolute;
            left: 0.625rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e5e7eb;
        }

        .historico-item {
            position: relative;
            padding-bottom: 1.5rem;
        }

        .historico-item:last-child {
            padding-bottom: 0;
        }

        .historico-item-marker {
            position: absolute;
            left: -1.375rem;
            top: 0.25rem;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 0 0 2px currentColor;
        }

        .historico-item-marker.approved {
            color: var(--success-color);
        }

        .historico-item-marker.rejected {
            color: #ef4444;
        }

        .historico-item-marker.resend {
            color: var(--warning-color);
        }

        .historico-item-content {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.25rem;
        }

        .historico-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .historico-item-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.875rem;
            border-radius: 16px;
            font-size: 0.8125rem;
            font-weight: 700;
        }

        .historico-item-status.approved {
            background: #d1fae5;
            color: #065f46;
        }

        .historico-item-status.rejected {
            background: #fee2e2;
            color: var(--danger-color);
        }

        .historico-item-status.resend {
            background: rgba(245, 158, 11, 0.12);
            color: var(--text-on-light-bg);
        }

        .historico-item-date {
            font-size: 0.875rem;
            color: #718096;
            font-weight: 500;
        }

        .historico-item-user {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            margin-bottom: 0.75rem;
        }

        .historico-user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #005e82, #0088b8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.875rem;
        }

        .historico-user-name {
            font-weight: 600;
            color: #1a202c;
            font-size: 0.9375rem;
        }

        .historico-item-obs {
            padding: 0.875rem;
            background: white;
            border-radius: 8px;
            border-left: 3px solid #e5e7eb;
            font-size: 0.875rem;
            color: #4a5568;
            line-height: 1.5;
        }

        @media (max-width: 1024px) {
            .validar-docs-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .validar-docs-container {
                padding-top: 1rem;
                padding-bottom: 1.5rem;
            }

            .validar-docs-header {
                flex-direction: column;
                align-items: flex-start;
                padding: 1.25rem;
            }

            .header-right {
                width: 100%;
                flex-direction: column;
            }

            .validar-docs-btn-add-avulso,
            .validar-docs-btn-view-pi,
            .validar-docs-btn-concluir {
                width: 100%;
                justify-content: center;
            }

            .page-title {
                font-size: 1.375rem;
            }

            .page-subtitle {
                flex-direction: column;
                align-items: flex-start;
            }

            .validar-docs-separator {
                display: none;
            }

            .validar-docs-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .validar-docs-stats-card {
                padding: 1.25rem;
            }

            .validar-docs-stats-number {
                font-size: 2rem;
            }

            .validar-docs-icon-wrapper {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }

            .validar-docs-section-icon {
                width: 38px;
                height: 38px;
                font-size: 1.125rem;
            }

            .validar-docs-section-title {
                font-size: 1.125rem;
            }

            .validar-docs-card-footer {
                flex-direction: column;
            }

            .validar-docs-btn {
                width: 100%;
                min-height: 44px;
            }

            .validar-docs-card-body {
                padding: 1.25rem;
            }

            .validar-docs-card-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }

            .validar-docs-modal-footer {
                flex-direction: column;
            }

            .validar-docs-btn-cancel,
            .validar-docs-btn-confirm {
                width: 100%;
                justify-content: center;
                min-height: 44px;
            }

            .validar-docs-modal-body {
                padding: 1.25rem;
            }

            .validar-docs-modal-pi-dialog {
                max-width: 100vw;
                margin: 0.5rem;
            }

            .validar-docs-modal-pi-body {
                height: 60vh;
            }

            .validar-docs-empty-state {
                padding: 2.5rem 1.5rem;
            }

            .validar-docs-empty-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }

            .validar-docs-empty-title {
                font-size: 1.375rem;
            }

            .validar-docs-empty-description {
                font-size: 1rem;
            }

            .historico-item-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.375rem;
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

            .doc-info-box,
            .info-box,
            .warning-box {
                padding: 1rem;
            }

            .doc-info-box p,
            .info-box p {
                font-size: 0.875rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.375rem;
            }

            .doc-info-box strong {
                min-width: auto;
            }

            .warning-box {
                flex-direction: column;
            }

            .warning-box ul {
                font-size: 0.8125rem;
            }

            .swal2-textarea {
                min-height: 90px !important;
                font-size: 0.875rem !important;
            }
        }

        @media (max-width: 576px) {
            .validar-docs-header {
                padding: 1rem;
            }

            .page-title {
                font-size: 1.25rem;
            }

            .validar-docs-badge {
                font-size: 0.75rem;
                padding: 0.25rem 0.625rem;
            }

            .validar-docs-stats-card {
                padding: 1rem;
                gap: 0.875rem;
            }

            .validar-docs-stats-number {
                font-size: 1.75rem;
            }

            .validar-docs-icon-wrapper {
                width: 44px;
                height: 44px;
                font-size: 1.25rem;
                border-radius: 12px;
            }

            .validar-docs-stats-label {
                font-size: 0.8125rem;
            }

            .validar-docs-section {
                margin-bottom: 2rem;
            }

            .validar-docs-card-header {
                padding: 0.75rem 1rem;
            }

            .validar-docs-card-body {
                padding: 1rem;
            }

            .validar-docs-card-footer {
                padding: 0.75rem 1rem;
            }

            .validar-docs-card-icon {
                width: 44px;
                height: 44px;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }

            .validar-docs-card-title {
                font-size: 1rem;
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

            .swal2-textarea {
                min-height: 80px !important;
            }

            .validar-docs-modal-pi-body {
                height: 50vh;
            }
        }

        @media (max-width: 425px) {
            .validar-docs-grid {
                gap: 0.75rem;
            }

            .validar-docs-stats-row {
                margin-bottom: 1.25rem;
            }

            .validar-docs-section-title-wrapper {
                gap: 0.625rem;
            }

            .validar-docs-section-icon {
                width: 34px;
                height: 34px;
                font-size: 1rem;
            }

            .validar-docs-section-title {
                font-size: 1rem;
            }

            .validar-docs-section-subtitle {
                font-size: 0.8125rem;
            }

            .validar-docs-card-status {
                font-size: 0.75rem;
                padding: 0.25rem 0.625rem;
            }

            .meta-item {
                font-size: 0.8125rem;
            }
        }

        @media (max-width: 375px) {
            .validar-docs-header {
                padding: 0.875rem;
            }

            .page-title {
                font-size: 1.125rem;
            }

            .validar-docs-badge {
                font-size: 0.6875rem;
                padding: 0.2rem 0.5rem;
            }

            .validar-docs-stats-number {
                font-size: 1.5rem;
            }

            .validar-docs-icon-wrapper {
                width: 40px;
                height: 40px;
                font-size: 1.125rem;
            }

            .swal2-popup {
                width: calc(100% - 1rem) !important;
                padding: 1rem !important;
            }

            .swal2-title {
                font-size: 1.25rem !important;
            }

            .validar-docs-modal-pi-body {
                height: 45vh;
            }
        }

        @media (max-width: 320px) {
            .validar-docs-container {
                padding-top: 0.5rem;
                padding-bottom: 1rem;
            }

            .validar-docs-header {
                padding: 0.75rem;
                gap: 0.75rem;
            }

            .header-right {
                gap: 0.5rem;
            }

            .page-title {
                font-size: 1rem;
            }

            .validar-docs-stats-card {
                padding: 0.875rem;
            }

            .validar-docs-stats-number {
                font-size: 1.375rem;
            }

            .validar-docs-stats-label {
                font-size: 0.75rem;
            }

            .validar-docs-icon-wrapper {
                width: 36px;
                height: 36px;
                font-size: 1rem;
                border-radius: 10px;
            }

            .validar-docs-card-body {
                padding: 0.875rem;
            }

            .validar-docs-card-footer {
                padding: 0.625rem 0.875rem;
            }

            .validar-docs-card-title {
                font-size: 0.9375rem;
            }

            .validar-docs-btn {
                font-size: 0.8125rem;
                padding: 0.5rem 0.75rem;
            }

            .validar-docs-empty-state {
                padding: 2rem 1rem;
            }

            .validar-docs-empty-icon {
                width: 56px;
                height: 56px;
                font-size: 1.5rem;
            }

            .validar-docs-empty-title {
                font-size: 1.125rem;
            }

            .validar-docs-modal-pi-body {
                height: 40vh;
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

        .spin {
            animation: spin 1s linear infinite;
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

        .swal2-icon.swal2-info {
            border-color: #60a5fa !important;
            color: #3b82f6 !important;
        }

        .swal2-icon.swal2-success {
            border-color: #6ee7b7 !important;
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
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            color: white !important;
        }

        .swal-confirm-btn:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.35) !important;
        }

        .swal-confirm-btn:active {
            transform: translateY(0) !important;
        }

        .swal-confirm-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white !important;
        }

        .swal-confirm-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.35) !important;
        }

        .swal-confirm-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            color: white !important;
        }

        .swal-confirm-danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.35) !important;
        }

        .swal-confirm-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
            color: white !important;
        }

        .swal-confirm-warning:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%) !important;
            box-shadow: 0 6px 20px rgba(217, 119, 6, 0.35) !important;
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

        .swal2-textarea {
            font-family: inherit !important;
            color: var(--text-color) !important;
        }

        .swal2-textarea:focus {
            border-color: #3b82f6 !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }

        .swal2-validation-message {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%) !important;
            color: var(--danger-color) !important;
            border: none !important;
            padding: 0.875rem 1.25rem !important;
            border-radius: 10px !important;
            margin: 1rem 0 0 0 !important;
            border-left: 4px solid #ef4444 !important;
            font-weight: 600 !important;
        }

        .swal-modal-content {
            text-align: left;
        }

        .swal-modal-text {
            margin-bottom: 1.5rem;
            font-size: 1.05rem;
            color: var(--text-color);
            line-height: 1.6;
        }

        .swal-modal-text strong {
            color: #1f2937;
        }

        .swal-modal-label {
            display: block;
            margin-bottom: 0.625rem;
            font-weight: 600;
            color: #1f2937;
            font-size: 0.9375rem;
        }

        .swal-required {
            color: #ef4444;
        }

        .swal-modal-field {
            margin-top: 1.25rem;
        }

        .swal-modal-textarea {
            width: 100%;
            min-height: 100px;
            padding: 0.875rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.9375rem;
            font-family: inherit;
            resize: vertical;
            color: var(--text-color);
            background: #fff;
        }

        .swal-modal-textarea-lg {
            min-height: 120px;
        }

        .swal-modal-textarea:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .swal-modal-text-sm {
            margin: 0.5rem 0 0 0;
            font-size: 0.875rem;
        }

        .swal-modal-mt {
            margin-top: 1rem;
        }

        .historico-empty-icon {
            font-size: 3rem;
            color: #cbd5e0;
        }

        .historico-error-icon {
            font-size: 3rem;
            color: #ef4444;
        }

        .doc-info-box {
            padding: 1.25rem;
            border-radius: 10px;
            margin: 1.25rem 0;
        }

        .doc-info-box.success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-left: 4px solid #10b981;
        }

        .doc-info-box.danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-left: 4px solid #ef4444;
        }

        .doc-info-box.warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
        }

        .doc-info-box p {
            margin: 0.5rem 0;
            color: var(--text-color);
            font-size: 0.9375rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .doc-info-box p:first-child {
            margin-top: 0;
        }

        .doc-info-box p:last-child {
            margin-bottom: 0;
        }

        .doc-info-box strong {
            color: #1f2937;
            min-width: 90px;
            font-weight: 700;
        }

        .info-box {
            padding: 1.25rem;
            border-radius: 10px;
            margin-top: 1.5rem;
        }

        .info-box.success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-left: 4px solid #10b981;
        }

        .info-box.warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
        }

        .info-box p {
            margin: 0;
            color: var(--text-color);
            font-size: 0.9375rem;
            display: flex;
            align-items: flex-start;
            gap: 0.625rem;
            line-height: 1.6;
        }

        .info-box i {
            font-size: 1.125rem;
            margin-top: 0.125rem;
            flex-shrink: 0;
        }

        .info-box.success i {
            color: #059669;
        }

        .info-box.warning i {
            color: #d97706;
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

        .validar-docs-btn-revert {
            background: linear-gradient(135deg, #fef3c7, #fde68a) !important;
            color: var(--text-on-light-bg) !important;
            border: 1px solid #fcd34d !important;
        }

        .validar-docs-btn-revert:hover {
            background: linear-gradient(135deg, #fde68a, #fcd34d) !important;
            color: #78350f !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3);
        }

        .validar-docs-btn-revert i {
            font-size: 1rem;
        }

        .validar-docs-btn-view-pi {
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            color: #fff !important;
            border: 1px solid #005e82 !important;
            box-shadow: 0 2px 8px rgba(0, 94, 130, 0.25);
        }

        .validar-docs-btn-view-pi:hover {
            background: linear-gradient(135deg, #004a68 0%, #0077a3 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 94, 130, 0.35);
        }

        .validar-docs-btn-concluir {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: #fff !important;
            border: 1px solid #059669 !important;
            box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25);
        }

        .validar-docs-btn-concluir:hover {
            background: linear-gradient(135deg, #047857 0%, #059669 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35);
        }

        [data-theme="dark"] .validar-docs-header {
            background: #1e293b;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        [data-theme="dark"] .btn-back {
            background: #0f172a;
            border-color: #334155;
            color: #94a3b8;
        }

        [data-theme="dark"] .btn-back:hover {
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            border-color: #005e82;
            color: #fff;
        }

        [data-theme="dark"] .page-title {
            color: #f1f5f9;
        }

        [data-theme="dark"] .page-subtitle {
            color: #94a3b8;
        }

        [data-theme="dark"] .validar-docs-badge {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.35), rgba(0, 136, 184, 0.25));
            color: #7dd3fc;
        }

        [data-theme="dark"] .validar-docs-separator {
            color: #475569;
        }

        [data-theme="dark"] .validar-docs-btn-add-avulso {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.4), rgba(0, 136, 184, 0.3));
            color: #7dd3fc;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        [data-theme="dark"] .validar-docs-btn-add-avulso:hover {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.55), rgba(0, 136, 184, 0.45));
            color: #bae6fd;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }

        [data-theme="dark"] .validar-docs-btn-view-pi {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.4), rgba(0, 136, 184, 0.3)) !important;
            color: #7dd3fc !important;
            border-color: rgba(0, 136, 184, 0.3) !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        [data-theme="dark"] .validar-docs-btn-view-pi:hover {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.55), rgba(0, 136, 184, 0.45)) !important;
            color: #bae6fd !important;
        }

        [data-theme="dark"] .validar-docs-btn-concluir {
            background: linear-gradient(135deg, rgba(5, 150, 105, 0.35), rgba(16, 185, 129, 0.25)) !important;
            color: #6ee7b7 !important;
            border-color: rgba(16, 185, 129, 0.3) !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        [data-theme="dark"] .validar-docs-btn-concluir:hover {
            background: linear-gradient(135deg, rgba(5, 150, 105, 0.5), rgba(16, 185, 129, 0.4)) !important;
            color: #a7f3d0 !important;
        }

        [data-theme="dark"] .validar-docs-stats-card {
            background: #1e293b;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
        }

        [data-theme="dark"] .validar-docs-stats-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        }

        [data-theme="dark"] .validar-docs-stats-pending {
            border-left-color: #fbbf24;
        }

        [data-theme="dark"] .validar-docs-stats-approved {
            border-left-color: #34d399;
        }

        [data-theme="dark"] .validar-docs-stats-rejected {
            border-left-color: #f87171;
        }

        [data-theme="dark"] .validar-docs-stats-missing {
            border-left-color: #64748b;
        }

        [data-theme="dark"] .validar-docs-icon-wrapper.pending {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.2), rgba(245, 158, 11, 0.15));
            color: #fbbf24;
        }

        [data-theme="dark"] .validar-docs-icon-wrapper.approved {
            background: linear-gradient(135deg, rgba(52, 211, 153, 0.2), rgba(16, 185, 129, 0.15));
            color: #34d399;
        }

        [data-theme="dark"] .validar-docs-icon-wrapper.rejected {
            background: linear-gradient(135deg, rgba(248, 113, 113, 0.2), rgba(239, 68, 68, 0.15));
            color: #f87171;
        }

        [data-theme="dark"] .validar-docs-icon-wrapper.missing {
            background: linear-gradient(135deg, rgba(100, 116, 139, 0.2), rgba(71, 85, 105, 0.15));
            color: #94a3b8;
        }

        [data-theme="dark"] .validar-docs-stats-number {
            background: linear-gradient(135deg, #38bdf8 0%, #7dd3fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        [data-theme="dark"] .validar-docs-stats-label {
            color: #94a3b8;
        }

        [data-theme="dark"] .validar-docs-section-header {
            border-bottom-color: #334155;
        }

        [data-theme="dark"] .validar-docs-section-title {
            color: #f1f5f9;
        }

        [data-theme="dark"] .validar-docs-section-subtitle {
            color: #94a3b8;
        }

        [data-theme="dark"] .validar-docs-card {
            background: #1e293b;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
        }

        [data-theme="dark"] .validar-docs-card:hover {
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.4);
        }

        [data-theme="dark"] .pending-card {
            border-color: rgba(251, 191, 36, 0.3);
        }

        [data-theme="dark"] .approved-card {
            border-color: rgba(52, 211, 153, 0.3);
        }

        [data-theme="dark"] .rejected-card {
            border-color: rgba(248, 113, 113, 0.3);
        }

        [data-theme="dark"] .missing-card {
            border-color: #334155;
        }

        [data-theme="dark"] .validar-docs-card-header {
            background: #0f172a;
            border-bottom-color: #334155;
        }

        [data-theme="dark"] .validar-docs-card-status.pending {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.2), rgba(245, 158, 11, 0.15));
            color: #fbbf24;
        }

        [data-theme="dark"] .validar-docs-card-status.approved {
            background: linear-gradient(135deg, rgba(52, 211, 153, 0.2), rgba(16, 185, 129, 0.15));
            color: #34d399;
        }

        [data-theme="dark"] .validar-docs-card-status.rejected {
            background: linear-gradient(135deg, rgba(248, 113, 113, 0.2), rgba(239, 68, 68, 0.15));
            color: #f87171;
        }

        [data-theme="dark"] .validar-docs-card-status.missing {
            background: linear-gradient(135deg, rgba(100, 116, 139, 0.2), rgba(71, 85, 105, 0.15));
            color: #94a3b8;
        }

        [data-theme="dark"] .validar-docs-card-badge.avulso {
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.2), rgba(14, 165, 233, 0.15));
            color: #38bdf8;
        }

        [data-theme="dark"] .validar-docs-card-alert.warning {
            background: rgba(251, 191, 36, 0.15);
            color: #fbbf24;
        }

        [data-theme="dark"] .validar-docs-card-alert.danger {
            background: rgba(248, 113, 113, 0.15);
            color: #f87171;
        }

        [data-theme="dark"] .validar-docs-card-icon.pending {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.2), rgba(245, 158, 11, 0.15));
            color: #fbbf24;
        }

        [data-theme="dark"] .validar-docs-card-icon.approved {
            background: linear-gradient(135deg, rgba(52, 211, 153, 0.2), rgba(16, 185, 129, 0.15));
            color: #34d399;
        }

        [data-theme="dark"] .validar-docs-card-icon.rejected {
            background: linear-gradient(135deg, rgba(248, 113, 113, 0.2), rgba(239, 68, 68, 0.15));
            color: #f87171;
        }

        [data-theme="dark"] .validar-docs-card-icon.missing {
            background: linear-gradient(135deg, rgba(100, 116, 139, 0.2), rgba(71, 85, 105, 0.15));
            color: #94a3b8;
        }

        [data-theme="dark"] .validar-docs-card-title {
            color: #f1f5f9;
        }

        [data-theme="dark"] .validar-docs-card-description {
            color: #94a3b8;
        }

        [data-theme="dark"] .meta-item {
            color: #94a3b8;
        }

        [data-theme="dark"] .meta-item i {
            color: #64748b;
        }

        [data-theme="dark"] .validar-docs-card-rejection {
            background: linear-gradient(135deg, rgba(248, 113, 113, 0.12), rgba(239, 68, 68, 0.08));
            border-left-color: #f87171;
        }

        [data-theme="dark"] .rejection-header {
            color: #f87171;
        }

        [data-theme="dark"] .rejection-text {
            color: #fca5a5;
        }

        [data-theme="dark"] .validar-docs-card-warning {
            background: rgba(251, 191, 36, 0.12);
            border-left-color: #fbbf24;
            color: #fbbf24;
        }

        [data-theme="dark"] .validar-docs-card-footer {
            background: #0f172a;
            border-top-color: #334155;
        }

        [data-theme="dark"] .validar-docs-btn-view {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.4), rgba(0, 136, 184, 0.3));
            color: #7dd3fc;
        }

        [data-theme="dark"] .validar-docs-btn-view:hover {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.55), rgba(0, 136, 184, 0.45));
            color: #bae6fd;
        }

        [data-theme="dark"] .validar-docs-btn-approve {
            background: linear-gradient(135deg, rgba(52, 211, 153, 0.3), rgba(16, 185, 129, 0.2));
            color: #6ee7b7;
        }

        [data-theme="dark"] .validar-docs-btn-approve:hover {
            background: linear-gradient(135deg, rgba(52, 211, 153, 0.45), rgba(16, 185, 129, 0.35));
            color: #a7f3d0;
        }

        [data-theme="dark"] .validar-docs-btn-reject {
            background: linear-gradient(135deg, rgba(248, 113, 113, 0.3), rgba(239, 68, 68, 0.2));
            color: #fca5a5;
        }

        [data-theme="dark"] .validar-docs-btn-reject:hover {
            background: linear-gradient(135deg, rgba(248, 113, 113, 0.45), rgba(239, 68, 68, 0.35));
            color: #fed7d7;
        }


        [data-theme="dark"] .validar-docs-btn-history {
            background: linear-gradient(135deg, rgba(100, 116, 139, 0.3), rgba(71, 85, 105, 0.2));
            color: #cbd5e1;
        }

        [data-theme="dark"] .validar-docs-btn-history:hover {
            background: linear-gradient(135deg, rgba(100, 116, 139, 0.45), rgba(71, 85, 105, 0.35));
            color: #e2e8f0;
        }

        [data-theme="dark"] .validar-docs-btn-remove {
            background: linear-gradient(135deg, rgba(248, 113, 113, 0.15), rgba(239, 68, 68, 0.1));
            color: #f87171;
            border-color: rgba(248, 113, 113, 0.3);
        }

        [data-theme="dark"] .validar-docs-btn-remove:hover {
            background: linear-gradient(135deg, rgba(248, 113, 113, 0.25), rgba(239, 68, 68, 0.2));
            color: #fca5a5;
        }

        [data-theme="dark"] .validar-docs-btn-cancel {
            background: linear-gradient(135deg, rgba(100, 116, 139, 0.2), rgba(71, 85, 105, 0.15));
            color: #94a3b8;
            border-color: #334155;
        }

        [data-theme="dark"] .validar-docs-btn-cancel:hover {
            background: linear-gradient(135deg, rgba(100, 116, 139, 0.3), rgba(71, 85, 105, 0.25));
            color: #cbd5e1;
        }

        [data-theme="dark"] .validar-docs-btn-confirm {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.4), rgba(0, 136, 184, 0.3));
            color: #7dd3fc;
        }

        [data-theme="dark"] .validar-docs-btn-confirm:hover {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.55), rgba(0, 136, 184, 0.45));
            color: #bae6fd;
        }

        [data-theme="dark"] .validar-docs-btn-revert {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.2), rgba(245, 158, 11, 0.15)) !important;
            color: #fcd34d !important;
            border-color: rgba(251, 191, 36, 0.3) !important;
        }

        [data-theme="dark"] .validar-docs-btn-revert:hover {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.35), rgba(245, 158, 11, 0.25)) !important;
            color: #fde68a !important;
        }

        [data-theme="dark"] .validar-docs-empty-state {
            background: #1e293b;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
        }

        [data-theme="dark"] .validar-docs-empty-state.success {
            background: linear-gradient(135deg, rgba(52, 211, 153, 0.1), rgba(16, 185, 129, 0.06));
            border-color: rgba(52, 211, 153, 0.3);
        }

        [data-theme="dark"] .validar-docs-empty-icon {
            background: linear-gradient(135deg, rgba(52, 211, 153, 0.2), rgba(16, 185, 129, 0.15));
            color: #34d399;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
        }

        [data-theme="dark"] .validar-docs-empty-title {
            color: #6ee7b7;
        }

        [data-theme="dark"] .validar-docs-empty-description {
            color: #94a3b8;
        }

        [data-theme="dark"] .validar-docs-modal {
            background: #1e293b;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }

        [data-theme="dark"] .validar-docs-modal-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-bottom: 1px solid #334155;
        }

        [data-theme="dark"] .validar-docs-modal-body {
            background: #1e293b;
            color: #f1f5f9;
        }

        [data-theme="dark"] .validar-docs-modal-footer {
            background: #0f172a;
            border-top-color: #334155;
        }

        [data-theme="dark"] .avulso-info-box {
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.12), rgba(14, 165, 233, 0.08));
            border-left-color: #38bdf8;
        }

        [data-theme="dark"] .avulso-info-box i {
            color: #38bdf8;
        }

        [data-theme="dark"] .avulso-info-box p {
            color: #7dd3fc;
        }

        [data-theme="dark"] .warning-box {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.12), rgba(245, 158, 11, 0.08));
            border-left-color: #fbbf24;
        }

        [data-theme="dark"] .warning-box i {
            color: #fbbf24;
        }

        [data-theme="dark"] .warning-box strong {
            color: #fcd34d;
        }

        [data-theme="dark"] .warning-box p {
            color: #fde68a;
        }

        [data-theme="dark"] .warning-box ul {
            color: #fde68a;
        }

        [data-theme="dark"] .loading-text {
            color: #94a3b8;
        }

        [data-theme="dark"] .pdf-viewer-container {
            border-color: #334155;
        }

        [data-theme="dark"] .historico-timeline::before {
            background: #334155;
        }

        [data-theme="dark"] .historico-item-marker {
            border-color: #1e293b;
        }

        [data-theme="dark"] .historico-item-content {
            background: #0f172a;
            border-color: #334155;
        }

        [data-theme="dark"] .historico-item-status.approved {
            background: rgba(52, 211, 153, 0.15);
            color: #34d399;
        }

        [data-theme="dark"] .historico-item-status.rejected {
            background: rgba(248, 113, 113, 0.15);
            color: #f87171;
        }

        [data-theme="dark"] .historico-item-status.resend {
            background: rgba(251, 191, 36, 0.15);
            color: #fbbf24;
        }

        [data-theme="dark"] .historico-item-date {
            color: #64748b;
        }

        [data-theme="dark"] .historico-user-name {
            color: #f1f5f9;
        }

        [data-theme="dark"] .historico-item-obs {
            background: #1e293b;
            border-left-color: #334155;
            color: #94a3b8;
        }

        [data-theme="dark"] .swal2-popup {
            background: #1e293b !important;
            color: #f1f5f9 !important;
        }

        [data-theme="dark"] .swal2-title {
            color: #f1f5f9 !important;
        }

        [data-theme="dark"] .swal2-html-container {
            color: #94a3b8 !important;
        }

        [data-theme="dark"] .swal2-textarea {
            background: #0f172a !important;
            border-color: #334155 !important;
            color: #f1f5f9 !important;
        }

        [data-theme="dark"] .swal2-textarea:focus {
            border-color: #38bdf8 !important;
        }

        [data-theme="dark"] .swal2-input {
            background: #0f172a !important;
            border-color: #334155 !important;
            color: #f1f5f9 !important;
        }

        [data-theme="dark"] .swal2-select {
            background: #0f172a !important;
            border-color: #334155 !important;
            color: #f1f5f9 !important;
        }

        [data-theme="dark"] .doc-info-box,
        [data-theme="dark"] .info-box {
            background: #0f172a;
            border-color: #334155;
        }

        [data-theme="dark"] .doc-info-box p,
        [data-theme="dark"] .info-box p {
            color: #94a3b8;
        }

        [data-theme="dark"] .doc-info-box strong,
        [data-theme="dark"] .info-box strong {
            color: #f1f5f9;
        }

        [data-theme="dark"] .historico-empty-icon {
            color: #475569;
        }

        [data-theme="dark"] .historico-error-icon {
            color: #f87171;
        }

        [data-theme="dark"] .swal-confirm-btn {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.6), rgba(0, 136, 184, 0.5)) !important;
            color: #bae6fd !important;
        }

        [data-theme="dark"] .swal-confirm-btn:hover {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.75), rgba(0, 136, 184, 0.65)) !important;
        }

        [data-theme="dark"] .swal-confirm-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.5), rgba(5, 150, 105, 0.4)) !important;
            color: #a7f3d0 !important;
        }

        [data-theme="dark"] .swal-confirm-success:hover {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.65), rgba(5, 150, 105, 0.55)) !important;
        }

        [data-theme="dark"] .swal-confirm-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.5), rgba(220, 38, 38, 0.4)) !important;
            color: #fecaca !important;
        }

        [data-theme="dark"] .swal-confirm-danger:hover {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.65), rgba(220, 38, 38, 0.55)) !important;
        }

        [data-theme="dark"] .swal-confirm-warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.5), rgba(217, 119, 6, 0.4)) !important;
            color: #fde68a !important;
        }

        [data-theme="dark"] .swal-confirm-warning:hover {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.65), rgba(217, 119, 6, 0.55)) !important;
        }

        [data-theme="dark"] .swal-cancel-btn {
            background: linear-gradient(135deg, rgba(100, 116, 139, 0.4), rgba(71, 85, 105, 0.3)) !important;
            color: #cbd5e1 !important;
        }

        [data-theme="dark"] .swal-cancel-btn:hover {
            background: linear-gradient(135deg, rgba(100, 116, 139, 0.55), rgba(71, 85, 105, 0.45)) !important;
        }

        [data-theme="dark"] .swal2-validation-message {
            background: linear-gradient(135deg, rgba(248, 113, 113, 0.15), rgba(239, 68, 68, 0.1)) !important;
            color: #fca5a5 !important;
            border-left-color: #f87171 !important;
        }

        [data-theme="dark"] .swal-modal-text {
            color: #cbd5e1;
        }

        [data-theme="dark"] .swal-modal-text strong {
            color: #f1f5f9;
        }

        [data-theme="dark"] .swal-modal-label {
            color: #e2e8f0;
        }

        [data-theme="dark"] .swal-required {
            color: #f87171;
        }

        [data-theme="dark"] .swal-modal-textarea {
            background: #0f172a;
            border-color: #334155;
            color: #f1f5f9;
        }

        [data-theme="dark"] .swal-modal-textarea:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
        }

        [data-theme="dark"] .swal-modal-textarea::placeholder {
            color: #64748b;
        }

        [data-theme="dark"] .doc-info-box.success {
            background: linear-gradient(135deg, rgba(52, 211, 153, 0.12), rgba(16, 185, 129, 0.08));
            border-left-color: #34d399;
        }

        [data-theme="dark"] .doc-info-box.danger {
            background: linear-gradient(135deg, rgba(248, 113, 113, 0.12), rgba(239, 68, 68, 0.08));
            border-left-color: #f87171;
        }

        [data-theme="dark"] .doc-info-box.warning {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.12), rgba(245, 158, 11, 0.08));
            border-left-color: #fbbf24;
        }

        [data-theme="dark"] .info-box.success {
            background: linear-gradient(135deg, rgba(52, 211, 153, 0.12), rgba(16, 185, 129, 0.08));
            border-left-color: #34d399;
        }

        [data-theme="dark"] .info-box.warning {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.12), rgba(245, 158, 11, 0.08));
            border-left-color: #fbbf24;
        }

        [data-theme="dark"] .info-box.success i {
            color: #34d399;
        }

        [data-theme="dark"] .info-box.warning i {
            color: #fbbf24;
        }

        [data-theme="dark"] .form-label {
            color: #e2e8f0;
        }

        [data-theme="dark"] .form-select {
            background-color: #0f172a;
            border-color: #334155;
            color: #f1f5f9;
        }

        [data-theme="dark"] .form-select:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
        }

        [data-theme="dark"] .form-control {
            background-color: #0f172a;
            border-color: #334155;
            color: #f1f5f9;
        }

        [data-theme="dark"] .form-control:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
        }

        [data-theme="dark"] .form-control::placeholder {
            color: #64748b;
        }

        [data-theme="dark"] .form-label {
            color: #f1f5f9;
        }

        [data-theme="dark"] .form-group {
            color: #f1f5f9;
        }

        [data-theme="dark"] .text-muted {
            color: #64748b !important;
        }

        [data-theme="dark"] .text-danger {
            color: #f87171 !important;
        }

        [data-theme="dark"] .spinner-border.text-primary {
            color: #38bdf8 !important;
        }

        [data-theme="dark"] .swal-modal-text-sm {
            color: #94a3b8;
        }

        [data-theme="dark"] .swal2-popup {
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6) !important;
        }

        [data-theme="dark"] .swal2-icon.swal2-warning {
            border-color: rgba(251, 191, 36, 0.5) !important;
            color: #fbbf24 !important;
        }

        [data-theme="dark"] .swal2-icon.swal2-error {
            border-color: rgba(248, 113, 113, 0.5) !important;
            color: #f87171 !important;
        }

        [data-theme="dark"] .swal2-icon.swal2-question {
            border-color: rgba(56, 189, 248, 0.5) !important;
            color: #38bdf8 !important;
        }

        [data-theme="dark"] .swal2-icon.swal2-info {
            border-color: rgba(56, 189, 248, 0.5) !important;
            color: #38bdf8 !important;
        }

        [data-theme="dark"] .swal2-icon.swal2-success {
            border-color: rgba(110, 231, 183, 0.5) !important;
            color: #34d399 !important;
        }

        [data-theme="dark"] .swal2-icon.swal2-success [class^=swal2-success-line] {
            background-color: #34d399 !important;
        }

        [data-theme="dark"] .swal2-icon.swal2-success .swal2-success-ring {
            border-color: rgba(52, 211, 153, 0.3) !important;
        }

        [data-theme="dark"] .swal2-container.swal2-backdrop-show {
            background: rgba(0, 0, 0, 0.7) !important;
        }

        [data-theme="dark"] .swal2-styled:focus {
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.3) !important;
        }

        [data-theme="dark"] .validar-docs-modal-pi-body {
            background: #0f172a;
        }

        [data-theme="dark"] .pdf-viewer-container {
            border-color: #334155;
            background: #0f172a;
        }

        .validar-docs-timeline-bar {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            overflow-x: auto;
        }

        .timeline-bar-inner {
            display: flex;
            align-items: center;
            min-width: max-content;
            gap: 0;
        }

        .timeline-bar-item {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            flex-shrink: 0;
        }

        .timeline-bar-item.pending {
            opacity: 0.5;
        }

        .timeline-bar-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
            background: rgba(107, 114, 128, 0.1);
            color: var(--text-muted);
        }

        .timeline-bar-icon.created {
            background: rgba(59, 130, 246, 0.12);
            color: #3b82f6;
        }

        .timeline-bar-icon.docs-sent {
            background: rgba(245, 158, 11, 0.12);
            color: var(--warning-color);
        }

        .timeline-bar-icon.docs-approved {
            background: rgba(16, 185, 129, 0.12);
            color: var(--success-color);
        }

        .timeline-bar-icon.concluded {
            background: rgba(139, 92, 246, 0.12);
            color: #8b5cf6;
        }

        .timeline-bar-info {
            display: flex;
            flex-direction: column;
            gap: 0.125rem;
        }

        .timeline-bar-label {
            font-size: 0.6875rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .timeline-bar-date {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #1f2937;
        }

        .timeline-bar-date.muted {
            color: #9ca3af;
            font-weight: 400;
            font-style: italic;
        }

        .timeline-bar-connector {
            width: 40px;
            height: 2px;
            background: #e5e7eb;
            margin: 0 0.5rem;
            flex-shrink: 0;
            border-radius: 1px;
        }

        .timeline-bar-connector.active {
            background: linear-gradient(90deg, #10b981, #0ea5e9);
        }

        [data-theme="dark"] .validar-docs-timeline-bar {
            background: #1e293b;
            border-color: #334155;
        }

        [data-theme="dark"] .timeline-bar-date {
            color: #f1f5f9;
        }

        [data-theme="dark"] .timeline-bar-date.muted {
            color: #64748b;
        }

        [data-theme="dark"] .timeline-bar-label {
            color: #94a3b8;
        }

        [data-theme="dark"] .timeline-bar-connector {
            background: #334155;
        }

        [data-theme="dark"] .timeline-bar-icon {
            background: rgba(148, 163, 184, 0.1);
            color: #64748b;
        }

        [data-theme="dark"] .timeline-bar-icon.created {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
        }

        [data-theme="dark"] .timeline-bar-icon.docs-sent {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
        }

        [data-theme="dark"] .timeline-bar-icon.docs-approved {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
        }

        [data-theme="dark"] .timeline-bar-icon.concluded {
            background: rgba(139, 92, 246, 0.15);
            color: #a78bfa;
        }

        @media (max-width: 768px) {
            .validar-docs-timeline-bar {
                padding: 1rem;
            }

            .timeline-bar-icon {
                width: 32px;
                height: 32px;
                font-size: 0.875rem;
            }

            .timeline-bar-connector {
                width: 24px;
            }

            .timeline-bar-label {
                font-size: 0.625rem;
            }

            .timeline-bar-date {
                font-size: 0.75rem;
            }
        }

        @media (max-width: 425px) {
            .timeline-bar-icon {
                width: 28px;
                height: 28px;
                font-size: 0.8125rem;
                border-radius: 8px;
            }

            .timeline-bar-connector {
                width: 16px;
                margin: 0 0.25rem;
            }

            .timeline-bar-label {
                font-size: 0.5625rem;
            }

            .timeline-bar-date {
                font-size: 0.6875rem;
            }

            .timeline-bar-item {
                gap: 0.375rem;
            }
        }
    </style>
@endpush