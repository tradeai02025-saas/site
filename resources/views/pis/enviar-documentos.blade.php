{{-- resources/views/pis/enviar-documentos.blade.php --}}

@extends('layouts.app')

@section('title', 'Enviar Documentos do PI')

@section('content')
    <div class="container-fluid px-4 enviar-docs-container">
        <div class="form-header-card enviar-docs-header">
            <div class="header-left">
                <a href="{{ route('pis.index') }}" class="btn-back">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div class="header-text">
                    <h1 class="page-title">Enviar Documentos do PI</h1>
                    <p class="page-subtitle">
                        <span class="enviar-docs-badge">PI: {{ $pi->numero_pi }}</span>
                        <span class="enviar-docs-separator">•</span>
                        <span class="enviar-docs-badge">Cliente: {{ $pi->cliente->razao_social }}</span>
                        @if ($pi->fornecedor)
                            <span class="enviar-docs-separator">•</span>
                            <span class="enviar-docs-badge">Fornecedor: {{ $pi->fornecedor->razao_social }}</span>
                        @endif
                    </p>
                </div>
            </div>
            <div class="enviar-docs-header-actions">
                @if ($pi->file_path)
                    <button type="button" class="enviar-docs-header-btn enviar-docs-btn-view-pi" id="btnVerDocumentoPi">
                        <i class="bi bi-file-earmark-pdf"></i>
                        Ver Documento PI
                    </button>
                @endif
                <button type="button" class="enviar-docs-header-btn enviar-docs-btn-add-avulso"
                    id="btnAdicionarDocumentoAvulso" data-pi-id="{{ $pi->id }}">
                    <i class="bi bi-file-earmark-plus"></i>
                    Adicionar Documento Avulso
                </button>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert-card alert-danger enviar-docs-alert">
                <div class="alert-icon">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="alert-content">
                    <h5 class="alert-title">Atenção! Corrija os seguintes erros:</h5>
                    <ul class="alert-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        @endif

        <div class="row g-4 enviar-docs-stats-row">
            <div class="col-md-3">
                <div class="enviar-docs-stats-card enviar-docs-stats-pending">
                    <div class="enviar-docs-stats-icon">
                        <div class="enviar-docs-icon-wrapper pending">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                    <div class="enviar-docs-stats-content">
                        <div class="enviar-docs-stats-number">{{ $documentosPendentes->count() }}</div>
                        <div class="enviar-docs-stats-label">Documentos Pendentes</div>
                        <div class="enviar-docs-stats-description">Aguardando primeiro envio</div>
                    </div>
                    <div class="enviar-docs-stats-decoration"></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="enviar-docs-stats-card enviar-docs-stats-validating">
                    <div class="enviar-docs-stats-icon">
                        <div class="enviar-docs-icon-wrapper validating">
                            <i class="bi bi-hourglass"></i>
                        </div>
                    </div>
                    <div class="enviar-docs-stats-content">
                        <div class="enviar-docs-stats-number">{{ $documentosAguardandoValidacao->count() }}</div>
                        <div class="enviar-docs-stats-label">Aguardando Validação</div>
                        <div class="enviar-docs-stats-description">Em análise pela equipe</div>
                    </div>
                    <div class="enviar-docs-stats-decoration"></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="enviar-docs-stats-card enviar-docs-stats-rejected">
                    <div class="enviar-docs-stats-icon">
                        <div class="enviar-docs-icon-wrapper rejected">
                            <i class="bi bi-arrow-clockwise"></i>
                        </div>
                    </div>
                    <div class="enviar-docs-stats-content">
                        <div class="enviar-docs-stats-number">{{ $documentosParaReenviar->count() }}</div>
                        <div class="enviar-docs-stats-label">Para Reenviar</div>
                        <div class="enviar-docs-stats-description">Reprovados ou solicitado reenvio</div>
                    </div>
                    <div class="enviar-docs-stats-decoration"></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="enviar-docs-stats-card enviar-docs-stats-completed">
                    <div class="enviar-docs-stats-icon">
                        <div class="enviar-docs-icon-wrapper completed">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                    </div>
                    <div class="enviar-docs-stats-content">
                        <div class="enviar-docs-stats-number">{{ $documentosAtendidos->count() }}</div>
                        <div class="enviar-docs-stats-label">Documentos Aprovados</div>
                        <div class="enviar-docs-stats-description">Validados com sucesso</div>
                    </div>
                    <div class="enviar-docs-stats-decoration"></div>
                </div>
            </div>
        </div>

        <div class="enviar-docs-section-header">
            <div class="enviar-docs-section-title-wrapper">
                <div class="enviar-docs-section-icon">
                    <i class="bi bi-cloud-arrow-up-fill"></i>
                </div>
                <div>
                    <h2 class="enviar-docs-section-title">Documentos Pendentes de Envio</h2>
                    <p class="enviar-docs-section-subtitle">Clique em um documento para iniciar o upload</p>
                </div>
            </div>
        </div>

        <div class="enviar-docs-grid">
            @if ($documentosPendentes->isEmpty() && $documentosParaReenviar->isEmpty())
                <div class="enviar-docs-empty-state success">
                    <div class="enviar-docs-empty-icon">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h3 class="enviar-docs-empty-title">Todos os Documentos Enviados!</h3>
                    <p class="enviar-docs-empty-description">Não há documentos pendentes de envio para este PI.</p>
                </div>
            @elseif($documentosPendentes->isEmpty() && $documentosParaReenviar->isNotEmpty())
                <div class="enviar-docs-empty-state info">
                    <div class="enviar-docs-empty-icon info">
                        <i class="bi bi-info-circle-fill"></i>
                    </div>
                    <h3 class="enviar-docs-empty-title">Nenhum Documento Novo Pendente</h3>
                    <p class="enviar-docs-empty-description">Mas existem documentos que precisam ser reenviados abaixo.</p>
                </div>
            @else
                @foreach ($documentosPendentes as $piDoc)
                    <div class="enviar-docs-card pending-card" data-pi-doc-id="{{ $piDoc->id }}"
                        data-tipo-id="{{ $piDoc->tipo_documento_id }}" data-tipo-nome="{{ $piDoc->tipoDocumento->nome }}"
                        data-validar="{{ $piDoc->tipoDocumento->validar }}"
                        data-validade-dias="{{ $piDoc->tipoDocumento->validade_dias }}">
                        <div class="enviar-docs-status-badge pending">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            Pendente
                        </div>
                        @if ($piDoc->opcional)
                            <div class="enviar-docs-status-badge" style="background: rgba(0, 94, 130, 0.1); color: var(--primary-color); font-size: 0.7rem; padding: 0.2rem 0.5rem; margin-top: 0.25rem;">
                                <i class="bi bi-dash-circle"></i>
                                Opcional
                            </div>
                        @endif
                        <div class="enviar-docs-document-icon">
                            <div class="enviar-docs-icon-bg pending">
                                <i class="bi bi-file-earmark-arrow-up"></i>
                            </div>
                        </div>
                        <div class="enviar-docs-document-content">
                            <h3 class="enviar-docs-document-title">{{ $piDoc->tipoDocumento->nome }}</h3>
                            <p class="enviar-docs-document-description">{{ $piDoc->opcional ? 'Documento opcional — envie se disponível' : 'Clique para fazer upload do documento' }}</p>
                        </div>
                        <div class="enviar-docs-document-action">
                            <button class="enviar-docs-btn-upload">
                                <i class="bi bi-cloud-arrow-up me-2"></i>
                                Enviar Documento
                            </button>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        @if ($documentosAguardandoValidacao->isNotEmpty())
            <div class="enviar-docs-section-header mt-5">
                <div class="enviar-docs-section-title-wrapper">
                    <div class="enviar-docs-section-icon"
                        style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <h2 class="enviar-docs-section-title">Documentos Aguardando Validação</h2>
                        <p class="enviar-docs-section-subtitle">Documentos enviados e aguardando análise da equipe</p>
                    </div>
                </div>
            </div>

            <div class="enviar-docs-grid">
                @foreach ($documentosAguardandoValidacao as $piDoc)
                    <div class="enviar-docs-card validating-card">
                        <div class="enviar-docs-status-badge validating">
                            <i class="bi bi-hourglass-split"></i>
                            {{ $piDoc->documentoEnviado->status->nome }}
                        </div>
                        <div class="enviar-docs-document-icon">
                            <div class="enviar-docs-icon-bg validating">
                                <i class="bi bi-clock-history"></i>
                            </div>
                        </div>
                        <div class="enviar-docs-document-content">
                            <h3 class="enviar-docs-document-title">{{ $piDoc->tipoDocumento->nome }}</h3>
                            <p class="enviar-docs-document-description">
                                Enviado em {{ $piDoc->documentoEnviado->data_envio->format('d/m/Y H:i') }}
                            </p>
                            @if ($piDoc->documentoEnviado->data_validade_documento)
                                <p class="enviar-docs-document-description">
                                    <i class="bi bi-calendar-event"></i>
                                    Validade:
                                    {{ \Carbon\Carbon::parse($piDoc->documentoEnviado->data_validade_documento)->format('d/m/Y') }}
                                </p>
                            @endif
                        </div>
                        <div class="enviar-docs-document-footer">
                            <span class="enviar-docs-info-badge">
                                <i class="bi bi-info-circle"></i>
                                Aguardando análise
                            </span>
                            @if (Auth::user()->hasRole('fornecedor') || Auth::user()->hasRole('representante'))
                                <button type="button" class="btn btn-sm btn-outline-warning mt-2 btn-desfazer-envio"
                                    data-pi-id="{{ $pi->id }}" data-pi-doc-id="{{ $piDoc->id }}"
                                    data-tipo-nome="{{ $piDoc->tipoDocumento->nome }}">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>
                                    Desfazer Envio
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($documentosParaReenviar->isNotEmpty())
            <div class="enviar-docs-section-header mt-5">
                <div class="enviar-docs-section-title-wrapper">
                    <div class="enviar-docs-section-icon rejected">
                        <i class="bi bi-arrow-clockwise"></i>
                    </div>
                    <div>
                        <h2 class="enviar-docs-section-title">Documentos Para Reenviar</h2>
                        <p class="enviar-docs-section-subtitle">Documentos reprovados ou com solicitação de reenvio</p>
                    </div>
                </div>
            </div>

            <div class="enviar-docs-grid">
                @foreach ($documentosParaReenviar as $piDoc)
                    <div class="enviar-docs-card rejected-card" data-pi-doc-id="{{ $piDoc->id }}"
                        data-tipo-id="{{ $piDoc->tipo_documento_id }}"
                        data-tipo-nome="{{ $piDoc->tipoDocumento->nome }}"
                        data-validar="{{ $piDoc->tipoDocumento->validar }}"
                        data-validade-dias="{{ $piDoc->tipoDocumento->validade_dias }}">
                        <div class="enviar-docs-status-badge rejected">
                            <i class="bi bi-x-circle-fill"></i>
                            {{ $piDoc->documentoEnviado->status->nome }}
                        </div>
                        <div class="enviar-docs-document-icon">
                            <div class="enviar-docs-icon-bg rejected">
                                <i class="bi bi-arrow-clockwise"></i>
                            </div>
                        </div>
                        <div class="enviar-docs-document-content">
                            <h3 class="enviar-docs-document-title">{{ $piDoc->tipoDocumento->nome }}</h3>
                            @if ($piDoc->documentoEnviado->validacoes->isNotEmpty())
                                <div class="enviar-docs-rejection-reason">
                                    <strong>Motivo:</strong>
                                    <p>{{ $piDoc->documentoEnviado->validacoes->first()->observacoes }}</p>
                                </div>
                            @endif
                        </div>
                        <div class="enviar-docs-document-action">
                            <button class="enviar-docs-btn-upload rejected">
                                <i class="bi bi-arrow-clockwise me-2"></i>
                                Reenviar Documento
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($documentosAtendidos->isNotEmpty())
            <div class="enviar-docs-section-header mt-5">
                <div class="enviar-docs-section-title-wrapper">
                    <div class="enviar-docs-section-icon completed">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <h2 class="enviar-docs-section-title">Documentos Aprovados</h2>
                        <p class="enviar-docs-section-subtitle">Estes documentos já foram validados com sucesso</p>
                    </div>
                </div>
            </div>

            <div class="enviar-docs-grid enviar-docs-completed-grid">
                @foreach ($documentosAtendidos as $piDoc)
                    <div class="enviar-docs-card completed-card">
                        <div class="enviar-docs-status-badge completed">
                            <i class="bi bi-check-circle-fill"></i>
                            Aprovado
                        </div>
                        <div class="enviar-docs-document-icon">
                            <div class="enviar-docs-icon-bg completed">
                                <i class="bi bi-file-earmark-check"></i>
                            </div>
                        </div>
                        <div class="enviar-docs-document-content">
                            <h3 class="enviar-docs-document-title">{{ $piDoc->tipoDocumento->nome }}</h3>
                            <p class="enviar-docs-document-description">Documento validado e aprovado</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="modal fade" id="documentoModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content enviar-docs-modal">
                    <div class="modal-header enviar-docs-modal-header">
                        <div class="enviar-docs-modal-title-wrapper">
                            <div class="enviar-docs-modal-icon">
                                <i class="bi bi-cloud-arrow-up-fill"></i>
                            </div>
                            <div>
                                <h5 class="enviar-docs-modal-title">Enviar Documento</h5>
                                <p class="enviar-docs-modal-subtitle" id="modalSubtitle">Preencha as informações do
                                    documento</p>
                            </div>
                        </div>
                        <button type="button" class="enviar-docs-btn-close" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="modal-body enviar-docs-modal-body">
                        <form action="{{ route('pis.store_documentos_pi', $pi) }}" method="POST" id="documentoForm"
                            enctype="multipart/form-data">
                            @csrf

                            <div class="enviar-docs-form-section">
                                <div class="enviar-docs-form-section-header">
                                    <div class="enviar-docs-form-section-icon">
                                        <i class="bi bi-file-earmark-text-fill"></i>
                                    </div>
                                    <div>
                                        <h3 class="enviar-docs-form-section-title">Informações do Documento</h3>
                                        <p class="enviar-docs-form-section-description">Tipo de documento e período de
                                            validade</p>
                                    </div>
                                </div>

                                <div class="enviar-docs-form-fields-grid">
                                    <div class="enviar-docs-form-group">
                                        <label class="enviar-docs-form-label">
                                            Tipo de Documento
                                            <span class="enviar-docs-required">*</span>
                                        </label>
                                        <div class="enviar-docs-input-wrapper readonly">
                                            <div class="enviar-docs-input-icon">
                                                <i class="bi bi-file-text-fill"></i>
                                            </div>
                                            <input type="text" id="tipo_documento_nome_display"
                                                class="enviar-docs-form-control" readonly>
                                            <input type="hidden" name="tipo_documento_id" id="tipo_documento_id">
                                        </div>
                                    </div>

                                    <div class="enviar-docs-form-group" id="campo-data-validade" style="display: none;">
                                        <label class="enviar-docs-form-label">
                                            Data de Validade do Documento
                                            <span class="enviar-docs-required">*</span>
                                        </label>
                                        <div class="enviar-docs-input-wrapper">
                                            <div class="enviar-docs-input-icon">
                                                <i class="bi bi-calendar-event"></i>
                                            </div>
                                            <input type="date" name="data_validade_documento"
                                                id="data_validade_documento" class="enviar-docs-form-control"
                                                min="{{ date('Y-m-d') }}">
                                        </div>
                                        <small class="enviar-docs-form-hint">
                                            <i class="bi bi-info-circle"></i>
                                            Informe a data de vencimento presente no documento
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="enviar-docs-form-section">
                                <div class="enviar-docs-form-section-header">
                                    <div class="enviar-docs-form-section-icon">
                                        <i class="bi bi-cloud-arrow-up-fill"></i>
                                    </div>
                                    <div>
                                        <h3 class="enviar-docs-form-section-title">Upload do Arquivo</h3>
                                        <p class="enviar-docs-form-section-description">Arraste ou selecione o arquivo PDF
                                        </p>
                                    </div>
                                </div>

                                <div class="enviar-docs-upload-container">
                                    <div class="enviar-docs-upload-area" id="uploadArea">
                                        <input type="file" name="arquivo" id="arquivo"
                                            class="enviar-docs-file-input" accept=".pdf" required>

                                        <div class="enviar-docs-upload-placeholder" id="uploadPlaceholder">
                                            <div class="enviar-docs-upload-visual">
                                                <div class="enviar-docs-upload-icon-circle">
                                                    <i class="bi bi-cloud-arrow-up"></i>
                                                </div>
                                                <div class="enviar-docs-upload-text-content">
                                                    <h4 class="enviar-docs-upload-title">Arraste seu arquivo ou clique para
                                                        selecionar</h4>
                                                    <p class="enviar-docs-upload-info">
                                                        <span class="enviar-docs-info-item">
                                                            <i class="bi bi-file-pdf"></i>
                                                            Formato: PDF
                                                        </span>
                                                        <span class="enviar-docs-info-separator">•</span>
                                                        <span class="enviar-docs-info-item">
                                                            <i class="bi bi-hdd"></i>
                                                            Máximo: 45MB
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>
                                            <button type="button" class="enviar-docs-btn-select-file">
                                                <i class="bi bi-folder2-open me-2"></i>
                                                Selecionar Arquivo
                                            </button>
                                        </div>

                                        <div class="enviar-docs-upload-preview" id="uploadPreview"
                                            style="display: none;">
                                            <div class="enviar-docs-preview-content">
                                                <div class="enviar-docs-preview-icon-wrapper">
                                                    <i class="bi bi-file-pdf-fill"></i>
                                                </div>
                                                <div class="enviar-docs-preview-info">
                                                    <p class="enviar-docs-preview-filename" id="previewName"></p>
                                                    <p class="enviar-docs-preview-filesize" id="previewSize"></p>
                                                    <div class="enviar-docs-preview-status">
                                                        <i class="bi bi-check-circle-fill"></i>
                                                        Arquivo carregado com sucesso
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="enviar-docs-btn-remove-preview"
                                                id="btnRemoveFile">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div id="validationMessageContainer"></div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer enviar-docs-modal-footer">
                        <button type="button" class="enviar-docs-btn-cancel" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>
                            Cancelar
                        </button>
                        <button type="button" class="enviar-docs-btn-submit" id="submitBtn">
                            <i class="bi bi-check-circle me-2"></i>
                            Enviar Documento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($pi->file_path)
        <div class="modal fade" id="modalDocumentoPi" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
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
                                class="enviar-docs-header-btn enviar-docs-btn-view-pi"
                                style="font-size: 0.8125rem; padding: 0.5rem 0.875rem;">
                                <i class="bi bi-download"></i>
                                Baixar
                            </a>
                            <button type="button" class="validar-docs-btn-close" data-bs-dismiss="modal"
                                aria-label="Fechar modal">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body validar-docs-modal-body p-0" style="height: 75vh;">
                        <iframe id="iframeDocumentoPi" src="" class="validar-docs-iframe"></iframe>
                    </div>
                </div>
            </div>
        </div>
    @endif

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
                        <label class="form-label" for="selectTipoDocumentoAvulsoEnviar">Tipo de Documento</label>
                        <select class="form-select" id="selectTipoDocumentoAvulsoEnviar">
                            <option value="">Carregando tipos disponíveis...</option>
                        </select>
                    </div>
                    <div class="form-group mt-3">
                        <label class="form-label" for="observacoesDocumentoAvulsoEnviar">Observações (opcional)</label>
                        <textarea class="form-control" id="observacoesDocumentoAvulsoEnviar" rows="3"
                            placeholder="Adicione uma observação sobre este documento..." maxlength="1000"></textarea>
                    </div>
                </div>
                <div class="modal-footer validar-docs-modal-footer">
                    <button type="button" class="validar-docs-btn validar-docs-btn-cancel" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i>
                        Cancelar
                    </button>
                    <button type="button" class="validar-docs-btn validar-docs-btn-confirm"
                        id="btnConfirmarDocumentoAvulsoEnviar">
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
        $(document).ready(function() {
            const uploadArea = $('#uploadArea');
            const fileInput = $('#arquivo');
            const uploadPlaceholder = $('#uploadPlaceholder');
            const uploadPreview = $('#uploadPreview');
            const previewName = $('#previewName');
            const previewSize = $('#previewSize');
            const documentoModal = new bootstrap.Modal('#documentoModal');
            let selectedFile = null;
            let dataValidadeNecessaria = false;
            let validacaoRealizada = false;

            $('.enviar-docs-card.pending-card, .enviar-docs-card.rejected-card, .enviar-docs-btn-upload').on(
                'click',
                function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const card = $(this).closest('.enviar-docs-card');
                    const tipoId = card.data('tipo-id');
                    const tipoNome = card.data('tipo-nome');
                    const validar = parseInt(card.data('validar'));
                    const validadeDias = parseInt(card.data('validade-dias'));

                    resetForm();

                    $('#tipo_documento_id').val(tipoId);
                    $('#tipo_documento_nome_display').val(tipoNome);

                    if (card.hasClass('rejected-card')) {
                        $('#modalSubtitle').text('Reenviando: ' + tipoNome);
                    } else {
                        $('#modalSubtitle').text('Enviando: ' + tipoNome);
                    }

                    dataValidadeNecessaria = (validar === 1 && validadeDias > 0);

                    if (dataValidadeNecessaria) {
                        $('#campo-data-validade').show();
                        $('#data_validade_documento').prop('required', true);
                    } else {
                        $('#campo-data-validade').hide();
                        $('#data_validade_documento').prop('required', false);
                        $('#data_validade_documento').val('');
                    }

                    documentoModal.show();
                });

            function resetForm() {
                $('#documentoForm')[0].reset();
                fileInput.val('');
                selectedFile = null;
                uploadPreview.hide();
                uploadPlaceholder.show();
                $('#campo-data-validade').hide();
                $('#data_validade_documento').prop('required', false);
                $('#submitBtn').prop('disabled', false).html(`
                    <i class="bi bi-check-circle me-2"></i>
                    Enviar Documento
                `);
                validacaoRealizada = false;
                dataValidadeNecessaria = false;
                limparMensagensValidacao();
            }

            function limparMensagensValidacao() {
                $('#validationMessageContainer').empty();
            }

            function mostrarMensagemValidacao(tipo, mensagem) {
                limparMensagensValidacao();

                let icone = '';
                let classeAlerta = '';

                if (tipo === 'success') {
                    icone = '<i class="bi bi-check-circle-fill"></i>';
                    classeAlerta = 'alert-success';
                } else if (tipo === 'error') {
                    icone = '<i class="bi bi-exclamation-triangle-fill"></i>';
                    classeAlerta = 'alert-danger';
                } else if (tipo === 'info') {
                    icone = '<i class="bi bi-info-circle-fill"></i>';
                    classeAlerta = 'alert-info';
                } else if (tipo === 'warning') {
                    icone = '<i class="bi bi-exclamation-circle-fill"></i>';
                    classeAlerta = 'alert-warning';
                }

                const alertaHtml = `
                    <div class="enviar-docs-validation-message ${classeAlerta}">
                        ${icone}
                        <span>${mensagem}</span>
                    </div>
                `;

                $('#validationMessageContainer').html(alertaHtml);
            }

            $('#data_validade_documento').on('change', function() {
                validacaoRealizada = false;
                $('#submitBtn').prop('disabled', true).html(`
                    <i class="bi bi-x-circle me-2"></i>
                    Validação Pendente
                `);
                limparMensagensValidacao();

                if (selectedFile && dataValidadeNecessaria && $(this).val()) {
                    validarDataNoPdf();
                }
            });

            $('.enviar-docs-btn-select-file').on('click', function(e) {
                e.preventDefault();
                fileInput.click();
            });

            uploadArea.on('click', function(e) {
                if (!$(e.target).closest('#btnRemoveFile').length) {
                    fileInput.click();
                }
            });

            uploadArea.on('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('drag-over');
            });

            uploadArea.on('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');
            });

            uploadArea.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');

                const files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    fileInput[0].files = files;
                    handleFileSelect(files[0]);
                }
            });

            fileInput.on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    handleFileSelect(file);
                }
            });

            function handleFileSelect(file) {
                const maxSize = 45 * 1024 * 1024;

                if (file.size > maxSize) {
                    toastr.error('O arquivo excede o tamanho máximo de 45MB');
                    fileInput.val('');
                    return;
                }

                if (!file.name.toLowerCase().endsWith('.pdf')) {
                    toastr.error('Apenas arquivos PDF são permitidos');
                    fileInput.val('');
                    return;
                }

                selectedFile = file;
                showPreview(file);

                validacaoRealizada = false;

                if (dataValidadeNecessaria) {
                    const dataValidade = $('#data_validade_documento').val();
                    if (dataValidade) {
                        validarDataNoPdf();
                    } else {
                        $('#submitBtn').prop('disabled', true).html(`
                            <i class="bi bi-x-circle me-2"></i>
                            Preencha a Data de Validade
                        `);
                        mostrarMensagemValidacao('warning',
                            'Preencha a data de validade para validar o documento.');
                    }
                } else {
                    $('#submitBtn').prop('disabled', false).html(`
                        <i class="bi bi-check-circle me-2"></i>
                        Enviar Documento
                    `);
                }
            }

            var validandoEmAndamento = false;

            function validarDataNoPdf() {
                const dataValidade = $('#data_validade_documento').val();
                const arquivo = fileInput[0].files[0];

                if (!arquivo || !dataValidade || validandoEmAndamento) {
                    return;
                }

                validandoEmAndamento = true;

                $('#submitBtn').prop('disabled', true).html(`
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Validando documento...
                `);

                limparMensagensValidacao();

                mostrarMensagemValidacao('info',
                    '<i class="bi bi-hourglass-split me-2"></i>Validando documento PDF, aguarde...'
                );

                const formData = new FormData();
                formData.append('arquivo', arquivo);
                formData.append('data_validade', dataValidade);
                formData.append('_token', '{{ csrf_token() }}');

                $.ajax({
                    url: '{{ route('pis.validar_data_pdf', $pi) }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        validandoEmAndamento = false;
                        validacaoRealizada = true;

                        $('#submitBtn').prop('disabled', false)
                            .html(`<i class="bi bi-check-circle me-2"></i>Enviar Documento`)
                            .removeClass('btn-warning btn-danger').addClass('btn-primary');

                        limparMensagensValidacao();

                        if (response.warning) {
                            mostrarMensagemValidacao('warning',
                                '<strong>Documento aceito!</strong> A data de validade será verificada manualmente pela equipe.'
                            );
                            toastr.warning('Documento aceito para revisão manual.');
                        } else {
                            mostrarMensagemValidacao('success',
                                '<strong>Documento validado com sucesso!</strong> A data de validade foi confirmada no PDF.'
                            );
                            toastr.success('Documento validado com sucesso!');
                        }
                    },
                    error: function(xhr) {
                        validandoEmAndamento = false;
                        validacaoRealizada = false;

                        $('#submitBtn').prop('disabled', true)
                            .html(`<i class="bi bi-x-circle me-2"></i>Validação Necessária`)
                            .removeClass('btn-primary').addClass('btn-danger');

                        const response = xhr.responseJSON;
                        const mensagem = response?.message || 'Erro ao validar o documento';

                        limparMensagensValidacao();
                        mostrarMensagemValidacao('error',
                            `<strong>Documento inválido</strong><br><small class="mt-2 d-block">${mensagem}</small>`
                        );

                        toastr.error(mensagem);
                    }
                });
            }

            function showPreview(file) {
                const fileName = file.name;
                const fileSize = (file.size / 1048576).toFixed(2);
                previewName.text(fileName);
                previewSize.html(`<i class="bi bi-hdd"></i> ${fileSize} MB`);

                uploadPlaceholder.hide();
                uploadPreview.show();

                toastr.success('Arquivo selecionado com sucesso!');
            }

            $('#btnRemoveFile').on('click', function(e) {
                e.stopPropagation();
                fileInput.val('');
                selectedFile = null;
                uploadPreview.hide();
                uploadPlaceholder.show();
                validacaoRealizada = false;
                $('#submitBtn').prop('disabled', false).html(`
                    <i class="bi bi-check-circle me-2"></i>
                    Enviar Documento
                `);
                limparMensagensValidacao();
                toastr.info('Arquivo removido');
            });

            $('#submitBtn').on('click', function() {
                const tipoDocumento = $('#tipo_documento_id').val();
                const arquivo = $('#arquivo').val();
                const dataValidadeRequired = $('#data_validade_documento').prop('required');
                const dataValidade = $('#data_validade_documento').val();

                if (!tipoDocumento || !arquivo) {
                    toastr.error('Preencha todos os campos obrigatórios');
                    return false;
                }

                if (dataValidadeRequired && !dataValidade) {
                    toastr.error('Preencha a data de validade do documento');
                    return false;
                }

                if (dataValidadeNecessaria && !validacaoRealizada) {
                    toastr.error('Aguarde a validação da data no PDF antes de enviar');
                    return false;
                }

                $('#submitBtn').prop('disabled', true).html(`
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Enviando...
                `);

                $('#documentoForm').submit();
            });

            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-top-right',
                timeOut: 5000,
                extendedTimeOut: 2000,
                showEasing: 'swing',
                hideEasing: 'linear',
                showMethod: 'fadeIn',
                hideMethod: 'fadeOut'
            };

            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    toastr.error('{{ $error }}');
                @endforeach
            @endif

            $('#btnVerDocumentoPi').on('click', function() {
                const iframe = document.getElementById('iframeDocumentoPi');
                iframe.src = '{{ route('pis.visualizar_documento', $pi) }}';
                const modal = new bootstrap.Modal(document.getElementById('modalDocumentoPi'));
                modal.show();
            });

            document.getElementById('modalDocumentoPi')?.addEventListener('hidden.bs.modal', function() {
                document.getElementById('iframeDocumentoPi').src = '';
            });

            $('#btnAdicionarDocumentoAvulso').on('click', function() {
                const $select = $('#selectTipoDocumentoAvulsoEnviar');
                $select.html('<option value="">Carregando tipos disponíveis...</option>').prop('disabled',
                    true);
                $('#btnConfirmarDocumentoAvulsoEnviar').prop('disabled', true);
                const modal = new bootstrap.Modal('#modalDocumentoAvulso');
                modal.show();
                $.ajax({
                    url: `{{ route('pis.tipos_documentos_avulsos', ['pi' => $pi->id]) }}`,
                    method: 'GET',
                    success: function(response) {
                        if (response.success && response.tipos_documentos.length > 0) {
                            let options =
                                '<option value="">Selecione o tipo de documento</option>';
                            response.tipos_documentos.forEach(tipo => {
                                const descricao = tipo.descricao ?
                                    ` - ${tipo.descricao}` : '';
                                options +=
                                    `<option value="${tipo.id}">${tipo.nome}${descricao}</option>`;
                            });
                            $select.html(options).prop('disabled', false);
                            $('#btnConfirmarDocumentoAvulsoEnviar').prop('disabled', false);
                        } else {
                            $select.html(
                                '<option value="">Nenhum documento avulso disponível</option>'
                                );
                            toastr.info('Não há tipos de documentos avulsos disponíveis.');
                        }
                    },
                    error: function(xhr) {
                        $select.html('<option value="">Erro ao carregar documentos</option>');
                        toastr.error(xhr.responseJSON?.message ||
                            'Erro ao carregar tipos de documentos.');
                    }
                });
            });

            $('#btnConfirmarDocumentoAvulsoEnviar').on('click', function() {
                const tipoDocumentoId = $('#selectTipoDocumentoAvulsoEnviar').val();
                const observacoes = $('#observacoesDocumentoAvulsoEnviar').val();
                if (!tipoDocumentoId) {
                    toastr.warning('Selecione um tipo de documento.');
                    return;
                }
                const $btn = $(this);
                $btn.prop('disabled', true).html(
                    '<i class="bi bi-hourglass-split spin"></i> Adicionando...');
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
                            toastr.success(response.message);
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            $btn.prop('disabled', false).html(
                                '<i class="bi bi-plus-circle"></i> Adicionar');
                            toastr.error(response.message || 'Erro ao adicionar documento.');
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html(
                            '<i class="bi bi-plus-circle"></i> Adicionar');
                        toastr.error(xhr.responseJSON?.message ||
                            'Erro ao adicionar documento.');
                    }
                });
            });

            $('#modalDocumentoAvulso').on('hidden.bs.modal', function() {
                $('#selectTipoDocumentoAvulsoEnviar').html(
                    '<option value="">Carregando tipos disponíveis...</option>');
                $('#observacoesDocumentoAvulsoEnviar').val('');
                $('#btnConfirmarDocumentoAvulsoEnviar').prop('disabled', false).html(
                    '<i class="bi bi-plus-circle"></i> Adicionar');
            });

            $(document).on('click', '.btn-desfazer-envio', function() {
                var btn = $(this);
                var piId = btn.data('pi-id');
                var piDocId = btn.data('pi-doc-id');
                var tipoNome = btn.data('tipo-nome');
                Swal.fire({
                    title: 'Desfazer Envio?',
                    html: 'Tem certeza que deseja desfazer o envio do documento <strong>' +
                        tipoNome +
                        '</strong>?<br><br><small>O documento será removido e você poderá enviar o arquivo correto.</small>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f59e0b',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sim, desfazer',
                    cancelButtonText: 'Cancelar',
                    background: '#1e293b',
                    color: '#f1f5f9',
                    customClass: {
                        popup: 'swal-dark-popup',
                        title: 'swal-dark-title',
                        htmlContainer: 'swal-dark-content',
                        confirmButton: 'swal-dark-confirm',
                        cancelButton: 'swal-dark-cancel'
                    }
                }).then(function(result) {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/pis/' + piId + '/documentos/' + piDocId +
                                '/desfazer-envio',
                            type: 'POST',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            beforeSend: function() {
                                btn.prop('disabled', true).html(
                                    '<i class="bi bi-hourglass-split me-1"></i> Processando...'
                                    );
                            },
                            success: function(response) {
                                if (response.success) {
                                    toastr.success(response.message);
                                    setTimeout(function() {
                                        location.reload();
                                    }, 1000);
                                } else {
                                    toastr.error(response.message);
                                    btn.prop('disabled', false).html(
                                        '<i class="bi bi-arrow-counterclockwise me-1"></i> Desfazer Envio'
                                        );
                                }
                            },
                            error: function(xhr) {
                                toastr.error(xhr.responseJSON ? xhr.responseJSON
                                    .message : 'Erro ao desfazer envio.');
                                btn.prop('disabled', false).html(
                                    '<i class="bi bi-arrow-counterclockwise me-1"></i> Desfazer Envio'
                                    );
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush

@push('styles')
    <style>
        .enviar-docs-container * {
            box-sizing: border-box;
        }

        .enviar-docs-container {
            padding-top: 1.5rem;
            padding-bottom: 2.5rem;
        }

        .enviar-docs-container .form-header-card {
            background: white;
            border-radius: 16px;
            padding: 1.75rem;
            margin-bottom: 1.75rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
        }

        .enviar-docs-container .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .enviar-docs-container .btn-back {
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
            flex-shrink: 0;
        }

        .enviar-docs-container .btn-back:hover {
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            border-color: #005e82;
            color: white;
            transform: translateX(-3px);
        }

        .enviar-docs-container .page-title {
            font-size: 1.625rem;
            font-weight: 800;
            color: #1a202c;
            margin-bottom: 0.375rem;
            line-height: 1.2;
        }

        .enviar-docs-container .page-subtitle {
            font-size: 0.875rem;
            color: #718096;
            margin: 0;
            line-height: 1.4;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.25rem;
        }

        .enviar-docs-header {
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .enviar-docs-header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .enviar-docs-header-btn {
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
            text-decoration: none;
            white-space: nowrap;
        }

        .enviar-docs-btn-view-pi {
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            color: #fff !important;
            border: 1px solid #005e82 !important;
            box-shadow: 0 2px 8px rgba(0, 94, 130, 0.25);
        }

        .enviar-docs-btn-view-pi:hover {
            background: linear-gradient(135deg, #004a68 0%, #0077a3 100%);
            color: #fff !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 94, 130, 0.35);
        }

        .enviar-docs-btn-add-avulso {
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(0, 94, 130, 0.25);
        }

        .enviar-docs-btn-add-avulso:hover {
            background: linear-gradient(135deg, #004a68 0%, #0077a3 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 94, 130, 0.35);
        }

        [data-theme="dark"] .enviar-docs-btn-view-pi {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.4), rgba(0, 136, 184, 0.3)) !important;
            color: #7dd3fc !important;
            border-color: rgba(0, 136, 184, 0.3) !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        [data-theme="dark"] .enviar-docs-btn-view-pi:hover {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.55), rgba(0, 136, 184, 0.45)) !important;
            color: #bae6fd !important;
        }

        [data-theme="dark"] .enviar-docs-btn-add-avulso {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.4), rgba(0, 136, 184, 0.3));
            color: #7dd3fc;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        [data-theme="dark"] .enviar-docs-btn-add-avulso:hover {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.55), rgba(0, 136, 184, 0.45));
            color: #bae6fd;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }

        @media (max-width: 768px) {
            .enviar-docs-header {
                flex-direction: column;
                align-items: flex-start;
                padding: 1.25rem;
            }

            .enviar-docs-header-actions {
                width: 100%;
                flex-direction: column;
            }

            .enviar-docs-btn-add-avulso,
            .enviar-docs-btn-view-pi {
                width: 100%;
                justify-content: center;
            }
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

        .validar-docs-btn-cancel {
            background: #f3f4f6;
            color: var(--text-color);
            padding: 0.625rem 1.25rem;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .validar-docs-btn-cancel:hover {
            background: #e5e7eb;
        }

        .validar-docs-btn-confirm {
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            color: white;
            padding: 0.625rem 1.25rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 94, 130, 0.25);
        }

        .validar-docs-btn-confirm:hover {
            background: linear-gradient(135deg, #004a68 0%, #0077a3 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 94, 130, 0.35);
        }

        .validar-docs-iframe {
            width: 100%;
            height: 100%;
            border: none;
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

        [data-theme="dark"] .validar-docs-modal-body .form-label {
            color: #f1f5f9;
        }

        [data-theme="dark"] .validar-docs-modal-body .form-select,
        [data-theme="dark"] .validar-docs-modal-body .form-control {
            background: #0f172a;
            color: #f1f5f9;
            border-color: #475569;
        }

        [data-theme="dark"] .validar-docs-modal-footer {
            background: #0f172a;
            border-top-color: #334155;
        }

        [data-theme="dark"] .validar-docs-btn-cancel {
            background: #334155;
            color: #cbd5e1;
            border-color: #475569;
        }

        [data-theme="dark"] .validar-docs-btn-cancel:hover {
            background: #475569;
            color: #f1f5f9;
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

        .enviar-docs-alert {
            margin-bottom: 1.5rem;
        }

        .enviar-docs-container .alert-card {
            background: rgba(239, 68, 68, 0.08);
            border: 2px solid #fecaca;
            border-radius: 14px;
            padding: 1.25rem 1.5rem;
            margin: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            position: relative;
        }

        .enviar-docs-container .alert-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fee2e2;
            border-radius: 10px;
            flex-shrink: 0;
        }

        .enviar-docs-container .alert-icon i {
            font-size: 1.25rem;
            color: var(--danger-color);
        }

        .enviar-docs-container .alert-content {
            flex: 1;
        }

        .enviar-docs-container .alert-title {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--danger-color);
            margin-bottom: 0.5rem;
        }

        .enviar-docs-container .alert-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .enviar-docs-container .alert-list li {
            font-size: 0.875rem;
            color: #b91c1c;
            padding: 0.25rem 0;
            position: relative;
            padding-left: 1rem;
        }

        .enviar-docs-container .alert-list li:before {
            content: '•';
            position: absolute;
            left: 0;
            color: var(--danger-color);
            font-weight: 700;
        }

        .enviar-docs-container .alert-close {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: none;
            border: none;
            color: var(--danger-color);
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.2s ease;
            flex-shrink: 0;
            padding: 0;
        }

        .enviar-docs-container .alert-close:hover {
            background: #fee2e2;
        }

        .enviar-docs-stats-row {
            margin-bottom: 2rem;
        }

        .enviar-docs-badge {
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            color: white;
            padding: 0.35rem 0.875rem;
            border-radius: 18px;
            font-weight: 600;
            font-size: 0.8125rem;
            display: inline-block;
        }

        .enviar-docs-separator {
            color: #a0aec0;
            margin: 0 0.375rem;
            font-weight: 300;
        }

        .enviar-docs-stats-card {
            background: white;
            border-radius: 18px;
            padding: 1.75rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .enviar-docs-stats-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 24px rgba(0, 94, 130, 0.12);
        }

        .enviar-docs-stats-card.enviar-docs-stats-pending {
            border-left: 4px solid #f59e0b;
        }

        .enviar-docs-stats-card.enviar-docs-stats-rejected {
            border-left: 4px solid #ef4444;
        }

        .enviar-docs-stats-card.enviar-docs-stats-completed {
            border-left: 4px solid #10b981;
        }

        .enviar-docs-stats-icon {
            flex-shrink: 0;
        }

        .enviar-docs-icon-wrapper {
            width: 72px;
            height: 72px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.25rem;
            position: relative;
            z-index: 2;
        }

        .enviar-docs-icon-wrapper.pending {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #d97706;
            box-shadow: 0 8px 16px rgba(245, 158, 11, 0.2);
        }

        .enviar-docs-icon-wrapper.rejected {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: var(--danger-color);
            box-shadow: 0 8px 16px rgba(239, 68, 68, 0.2);
        }

        .enviar-docs-icon-wrapper.completed {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #059669;
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.2);
        }

        .enviar-docs-stats-content {
            flex: 1;
        }

        .enviar-docs-stats-number {
            font-size: 2.75rem;
            font-weight: 800;
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 0.375rem;
        }

        .enviar-docs-stats-label {
            font-size: 1.0625rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.2rem;
        }

        .enviar-docs-stats-description {
            font-size: 0.875rem;
            color: #718096;
            font-weight: 500;
        }

        .enviar-docs-stats-decoration {
            position: absolute;
            right: -18px;
            bottom: -18px;
            width: 140px;
            height: 140px;
            background: radial-gradient(circle, rgba(0, 94, 130, 0.04) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .enviar-docs-section-header {
            margin-bottom: 1.5rem;
            padding-bottom: 0.875rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .enviar-docs-section-title-wrapper {
            display: flex;
            align-items: center;
            gap: 0.875rem;
        }

        .enviar-docs-section-icon {
            width: 46px;
            height: 46px;
            border-radius: 11px;
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.375rem;
            box-shadow: 0 3px 10px rgba(0, 94, 130, 0.25);
        }

        .enviar-docs-section-icon.rejected {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            box-shadow: 0 3px 10px rgba(239, 68, 68, 0.25);
        }

        .enviar-docs-section-icon.completed {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 3px 10px rgba(16, 185, 129, 0.25);
        }

        .enviar-docs-section-title {
            font-size: 1.375rem;
            font-weight: 800;
            color: #1a202c;
            margin: 0;
            line-height: 1.2;
        }

        .enviar-docs-section-subtitle {
            font-size: 0.9rem;
            color: #718096;
            margin: 0.2rem 0 0 0;
            font-weight: 500;
        }

        .enviar-docs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.75rem;
        }

        .enviar-docs-card {
            background: white;
            border-radius: 18px;
            padding: 1.75rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 260px;
        }

        .enviar-docs-card.pending-card {
            border: 2px solid #fed7d7;
            cursor: pointer;
        }

        .enviar-docs-card.pending-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(239, 68, 68, 0.15);
            border-color: #ef4444;
        }

        .enviar-docs-card.rejected-card {
            border: 2px solid #fecaca;
            cursor: pointer;
        }

        .enviar-docs-card.rejected-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(239, 68, 68, 0.15);
            border-color: #ef4444;
        }

        .enviar-docs-card.completed-card {
            border: 2px solid #d1fae5;
            opacity: 0.85;
        }

        .enviar-docs-status-badge {
            position: absolute;
            top: 0.875rem;
            right: 0.875rem;
            padding: 0.4rem 0.875rem;
            border-radius: 18px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            display: flex;
            align-items: center;
            gap: 0.375rem;
            z-index: 2;
        }

        .enviar-docs-status-badge.pending {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: var(--text-on-light-bg);
            box-shadow: 0 3px 10px rgba(245, 158, 11, 0.18);
        }

        .enviar-docs-status-badge.rejected {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: var(--danger-color);
            box-shadow: 0 3px 10px rgba(239, 68, 68, 0.18);
        }

        .enviar-docs-status-badge.completed {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            box-shadow: 0 3px 10px rgba(16, 185, 129, 0.18);
        }

        .enviar-docs-document-icon {
            margin-bottom: 1.25rem;
        }

        .enviar-docs-icon-bg {
            width: 72px;
            height: 72px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.25rem;
            margin: 0 auto;
        }

        .enviar-docs-icon-bg.pending {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: var(--danger-color);
        }

        .enviar-docs-icon-bg.rejected {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: var(--danger-color);
        }

        .enviar-docs-icon-bg.completed {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #059669;
        }

        .enviar-docs-document-content {
            flex: 1;
            text-align: center;
            margin-bottom: 1.25rem;
        }

        .enviar-docs-document-title {
            font-size: 1.1875rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 0.4rem;
            line-height: 1.3;
        }

        .enviar-docs-document-description {
            color: #718096;
            font-size: 0.875rem;
            margin: 0;
            font-weight: 500;
        }

        .enviar-docs-rejection-reason {
            background: rgba(239, 68, 68, 0.08);
            padding: 0.75rem;
            border-radius: 8px;
            margin-top: 0.75rem;
            border-left: 3px solid #ef4444;
            text-align: left;
        }

        .enviar-docs-rejection-reason strong {
            display: block;
            color: var(--danger-color);
            font-size: 0.8125rem;
            margin-bottom: 0.375rem;
            font-weight: 700;
        }

        .enviar-docs-rejection-reason p {
            color: var(--danger-color);
            font-size: 0.8125rem;
            margin: 0;
            line-height: 1.5;
        }

        .enviar-docs-document-action {
            text-align: center;
        }

        .enviar-docs-btn-upload {
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            color: white;
            border: none;
            padding: 0.8125rem 1.75rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 3px 10px rgba(0, 94, 130, 0.25);
            width: 100%;
            justify-content: center;
        }

        .enviar-docs-btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 94, 130, 0.35);
            background: linear-gradient(135deg, #004a68 0%, #0077a3 100%);
        }

        .enviar-docs-btn-upload.rejected {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            box-shadow: 0 3px 10px rgba(239, 68, 68, 0.25);
        }

        .enviar-docs-btn-upload.rejected:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            box-shadow: 0 6px 16px rgba(220, 38, 38, 0.35);
        }

        .enviar-docs-empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3.5rem 2rem;
            background: white;
            border-radius: 18px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        }

        .enviar-docs-empty-state.success {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border: 2px solid #10b981;
        }

        .enviar-docs-empty-state.info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border: 2px solid #3b82f6;
        }

        .enviar-docs-empty-icon {
            width: 90px;
            height: 90px;
            margin: 0 auto 1.75rem;
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.75rem;
            color: #059669;
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.18);
        }

        .enviar-docs-empty-icon.info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1d4ed8;
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.18);
        }

        .enviar-docs-empty-title {
            font-size: 1.625rem;
            font-weight: 800;
            color: #065f46;
            margin-bottom: 0.625rem;
        }

        .enviar-docs-empty-state.info .enviar-docs-empty-title {
            color: #1e40af;
        }

        .enviar-docs-empty-description {
            font-size: 1.0625rem;
            color: #047857;
            font-weight: 500;
            margin: 0;
        }

        .enviar-docs-empty-state.info .enviar-docs-empty-description {
            color: #1d4ed8;
        }

        .enviar-docs-completed-grid .enviar-docs-card {
            pointer-events: none;
        }

        .enviar-docs-modal {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
            overflow: hidden;
        }

        .enviar-docs-modal-header {
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            color: white;
            padding: 1.5rem 1.75rem;
            border-bottom: none;
        }

        .enviar-docs-modal-title-wrapper {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            color: white;
        }

        .enviar-docs-modal-icon {
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.375rem;
            backdrop-filter: blur(10px);
            color: white;
        }

        .enviar-docs-modal-title {
            font-size: 1.375rem;
            font-weight: 800;
            margin: 0;
            line-height: 1.2;
            color: white;
        }

        .enviar-docs-modal-subtitle {
            font-size: 0.875rem;
            margin: 0.2rem 0 0 0;
            opacity: 0.95;
            font-weight: 500;
            color: white;
        }

        .enviar-docs-btn-close {
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

        .enviar-docs-btn-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .enviar-docs-modal-body {
            padding: 1.5rem;
            background: #f9fafb;
        }

        .enviar-docs-form-section {
            background: white;
            border-radius: 14px;
            padding: 1.5rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.03);
        }

        .enviar-docs-form-section:last-child {
            margin-bottom: 0;
        }

        .enviar-docs-form-section-header {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            margin-bottom: 1.25rem;
            padding-bottom: 0.875rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .enviar-docs-form-section-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 3px 8px rgba(0, 94, 130, 0.18);
        }

        .enviar-docs-form-section-title {
            font-size: 1.0625rem;
            font-weight: 700;
            color: #1a202c;
            margin: 0;
            line-height: 1.2;
        }

        .enviar-docs-form-section-description {
            font-size: 0.8125rem;
            color: #718096;
            margin: 0.2rem 0 0 0;
            font-weight: 500;
        }

        .enviar-docs-form-fields-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .enviar-docs-form-group {
            margin-bottom: 0;
        }

        .enviar-docs-form-label {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .enviar-docs-required {
            color: #ef4444;
            font-weight: 700;
        }

        .enviar-docs-input-wrapper {
            position: relative;
        }

        .enviar-docs-input-wrapper.readonly {
            opacity: 0.7;
        }

        .enviar-docs-input-icon {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: #005e82;
            font-size: 1.0625rem;
            z-index: 2;
        }

        .enviar-docs-form-control {
            width: 100%;
            padding: 0.8125rem 0.875rem 0.8125rem 2.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 500;
            color: #2d3748;
            transition: all 0.3s ease;
            background: white;
        }

        .enviar-docs-form-control:focus {
            outline: none;
            border-color: #005e82;
            box-shadow: 0 0 0 3px rgba(0, 94, 130, 0.08);
        }

        .enviar-docs-form-control[readonly] {
            background: #f3f4f6;
            cursor: not-allowed;
            color: #4a5568;
        }

        .enviar-docs-form-hint {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.4rem;
            font-size: 0.8125rem;
            color: #718096;
            font-weight: 500;
        }

        .enviar-docs-form-hint i {
            color: #005e82;
            font-size: 0.875rem;
        }

        .enviar-docs-upload-container {
            margin-top: 1.25rem;
        }

        .enviar-docs-upload-area {
            position: relative;
            border: 3px dashed #cbd5e0;
            border-radius: 16px;
            padding: 2.5rem 1.75rem;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            background: #fafbfc;
        }

        .enviar-docs-upload-area:hover {
            border-color: #005e82;
            background: rgba(59, 130, 246, 0.08);
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 94, 130, 0.1);
        }

        .enviar-docs-upload-area.drag-over {
            border-color: #0088b8;
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.05) 0%, rgba(0, 136, 184, 0.05) 100%);
            box-shadow: 0 8px 20px rgba(0, 94, 130, 0.15);
        }

        .enviar-docs-file-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 10;
        }

        .enviar-docs-upload-placeholder {
            pointer-events: none;
        }

        .enviar-docs-upload-visual {
            margin-bottom: 1.25rem;
        }

        .enviar-docs-upload-icon-circle {
            width: 88px;
            height: 88px;
            margin: 0 auto 1.25rem;
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.75rem;
            color: white;
            box-shadow: 0 6px 16px rgba(0, 94, 130, 0.25);
            animation: enviarDocsFloat 3s ease-in-out infinite;
        }

        @keyframes enviarDocsFloat {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        .enviar-docs-upload-text-content {
            max-width: 380px;
            margin: 0 auto;
        }

        .enviar-docs-upload-title {
            font-size: 1.0625rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 0.875rem;
        }

        .enviar-docs-upload-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.625rem;
            flex-wrap: wrap;
            color: #718096;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .enviar-docs-info-item {
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .enviar-docs-info-item i {
            color: #005e82;
            font-size: 0.9375rem;
        }

        .enviar-docs-info-separator {
            color: #cbd5e0;
        }

        .enviar-docs-btn-select-file {
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            color: white;
            border: none;
            padding: 0.8125rem 1.75rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 3px 10px rgba(0, 94, 130, 0.25);
            pointer-events: none;
        }

        .enviar-docs-upload-area:hover .enviar-docs-btn-select-file {
            transform: scale(1.05);
        }

        .enviar-docs-upload-preview {
            padding: 1.5rem;
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border-radius: 14px;
            border: 2px solid #10b981;
            position: relative;
        }

        .enviar-docs-preview-content {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .enviar-docs-preview-icon-wrapper {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.25rem;
            color: #059669;
            box-shadow: 0 3px 10px rgba(16, 185, 129, 0.18);
            flex-shrink: 0;
        }

        .enviar-docs-preview-info {
            flex: 1;
            text-align: left;
        }

        .enviar-docs-preview-filename {
            font-weight: 700;
            color: #065f46;
            font-size: 0.9375rem;
            margin-bottom: 0.375rem;
            word-break: break-word;
        }

        .enviar-docs-preview-filesize {
            color: #047857;
            font-size: 0.875rem;
            margin-bottom: 0.375rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .enviar-docs-preview-status {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            color: #059669;
            font-size: 0.8125rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .enviar-docs-btn-remove-preview {
            position: absolute;
            top: 0.875rem;
            right: 0.875rem;
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
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
            font-size: 1rem;
            box-shadow: 0 3px 10px rgba(239, 68, 68, 0.18);
        }

        .enviar-docs-btn-remove-preview:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            transform: scale(1.1) rotate(10deg);
        }

        #validationMessageContainer {
            margin-top: 1.25rem;
        }

        .enviar-docs-validation-message {
            padding: 1rem 1.25rem;
            border-radius: 14px;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: flex-start;
            gap: 0.875rem;
            animation: enviarDocsSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        @keyframes enviarDocsSlideIn {
            from {
                opacity: 0;
                transform: translateY(-16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .enviar-docs-validation-message.alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border: 2px solid #10b981;
            color: #065f46;
        }

        .enviar-docs-validation-message.alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 2px solid #ef4444;
            color: var(--danger-color);
        }

        .enviar-docs-validation-message.alert-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border: 2px solid #3b82f6;
            color: #1e40af;
        }

        .enviar-docs-validation-message.alert-warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 2px solid #f59e0b;
            color: var(--text-on-light-bg);
        }

        .enviar-docs-validation-message i {
            font-size: 1.375rem;
            flex-shrink: 0;
        }

        .enviar-docs-modal-footer {
            padding: 1.25rem 1.75rem;
            background: #f9fafb;
            border-top: 2px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 0.875rem;
        }

        .enviar-docs-btn-cancel {
            background: white;
            color: #4a5568;
            border: 2px solid #e2e8f0;
            padding: 0.8125rem 1.75rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }

        .enviar-docs-btn-cancel:hover {
            background: var(--bg-body);
            border-color: #cbd5e0;
            transform: translateY(-2px);
        }

        .enviar-docs-btn-submit {
            background: linear-gradient(135deg, #005e82 0%, #0088b8 100%);
            color: white;
            border: none;
            padding: 0.8125rem 1.75rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 3px 10px rgba(0, 94, 130, 0.25);
        }

        .enviar-docs-btn-submit:hover:not(:disabled) {
            background: linear-gradient(135deg, #004a68 0%, #0077a3 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 94, 130, 0.35);
        }

        .enviar-docs-btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }

        @media (max-width: 768px) {
            .enviar-docs-container {
                padding-top: 1.25rem;
                padding-bottom: 2rem;
            }

            .enviar-docs-container .form-header-card {
                padding: 1.5rem;
                border-radius: 14px;
            }

            .enviar-docs-container .page-title {
                font-size: 1.375rem;
            }

            .enviar-docs-container .page-subtitle {
                font-size: 0.8125rem;
                gap: 0.2rem;
            }

            .enviar-docs-container .btn-back {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }

            .enviar-docs-header {
                margin-bottom: 1.25rem;
            }

            .enviar-docs-alert {
                margin-bottom: 1.25rem;
            }

            .enviar-docs-stats-row {
                margin-bottom: 1.5rem;
            }

            .enviar-docs-grid {
                grid-template-columns: 1fr;
            }

            .enviar-docs-stats-card {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem;
            }

            .enviar-docs-stats-number {
                font-size: 2.5rem;
            }

            .enviar-docs-form-fields-grid {
                grid-template-columns: 1fr;
            }

            .enviar-docs-preview-content {
                flex-direction: column;
                text-align: center;
            }

            .enviar-docs-preview-info {
                text-align: center;
            }

            .enviar-docs-btn-remove-preview {
                position: static;
                margin-top: 0.875rem;
            }

            .enviar-docs-modal-body {
                padding: 1.25rem;
            }

            .enviar-docs-form-section {
                padding: 1.25rem;
            }

            .enviar-docs-modal-footer {
                padding: 1rem 1.25rem;
            }

            .enviar-docs-section-title {
                font-size: 1.25rem;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .enviar-docs-header {
                margin-bottom: 1.5rem;
            }

            .enviar-docs-stats-row {
                margin-bottom: 1.75rem;
            }

            .enviar-docs-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .enviar-docs-stats-number {
                font-size: 2.5rem;
            }
        }

        @media (min-width: 1025px) and (max-width: 1399px) {
            .enviar-docs-header {
                margin-bottom: 1.5rem;
            }

            .enviar-docs-stats-row {
                margin-bottom: 2rem;
            }

            .enviar-docs-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 1400px) {
            .enviar-docs-header {
                margin-bottom: 1.5rem;
            }

            .enviar-docs-stats-row {
                margin-bottom: 2rem;
            }

            .enviar-docs-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 576px) {
            .enviar-docs-container {
                padding-top: 1rem;
                padding-bottom: 1.5rem;
            }
        }
    </style>
@endpush