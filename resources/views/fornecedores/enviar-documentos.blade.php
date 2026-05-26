{{-- resources/views/fornecedores/enviar-documentos.blade.php --}}
@extends('layouts.app')

@section('title', 'Enviar Documentos')

@section('content')
    <div class="container-fluid px-4 envdoc-container">
        <div class="page-header">
            <div class="header-card">
                <div class="header-top">
                    <div class="title-wrapper">
                        <a href="{{ route('fornecedor.docs_list.index') }}" class="envdoc-btn-back">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="main-title">Enviar Documentos</h1>
                            <p class="main-subtitle">Envie os documentos necessários para cada PI</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (!empty($fornecedores) && $fornecedores->count() > 1)
            <div class="envdoc-selector">
                <div class="envdoc-selector-inner">
                    <div class="envdoc-selector-icon"><i class="bi bi-building"></i></div>
                    <div class="envdoc-selector-content">
                        <label class="envdoc-selector-label" for="seletor_fornecedor">Fornecedor</label>
                        <select id="seletor_fornecedor" class="envdoc-selector-select" onchange="window.location.href='{{ route('fornecedor.docs_enviar.index') }}?fornecedor_id=' + this.value">
                            @foreach ($fornecedores as $forn)
                                <option value="{{ $forn->id }}" {{ (isset($fornecedorIdAtivo) && $fornecedorIdAtivo == $forn->id) ? 'selected' : '' }}>
                                    {{ $forn->razao_social }}{{ $forn->nome_fantasia ? ' (' . $forn->nome_fantasia . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="envdoc-alert">
                <div class="envdoc-alert-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <div class="envdoc-alert-content">
                    <div class="envdoc-alert-title">Corrija os seguintes erros:</div>
                    @foreach ($errors->all() as $error)
                        <div class="envdoc-alert-item">{{ $error }}</div>
                    @endforeach
                </div>
                <button type="button" class="envdoc-alert-close" onclick="this.parentElement.style.display='none'"><i class="bi bi-x"></i></button>
            </div>
        @endif

        @if ($pisDoFornecedor->count() > 0)
            <div class="envdoc-summary">
                @if ($totais['rejeitados'] > 0)
                    <div class="envdoc-pill envdoc-pill-danger"><span class="envdoc-pill-dot envdoc-dot-danger"></span> <strong>{{ $totais['rejeitados'] }}</strong> {{ $totais['rejeitados'] == 1 ? 'rejeitado' : 'rejeitados' }}</div>
                @endif
                @if ($totais['pendentes'] > 0)
                    <div class="envdoc-pill"><span class="envdoc-pill-dot envdoc-dot-muted"></span> <strong>{{ $totais['pendentes'] }}</strong> {{ $totais['pendentes'] == 1 ? 'pendente' : 'pendentes' }}</div>
                @endif
                @if ($totais['em_analise'] > 0)
                    <div class="envdoc-pill"><span class="envdoc-pill-dot envdoc-dot-warning"></span> <strong>{{ $totais['em_analise'] }}</strong> em análise</div>
                @endif
                @if ($totais['aprovados'] > 0)
                    <div class="envdoc-pill"><span class="envdoc-pill-dot envdoc-dot-success"></span> <strong>{{ $totais['aprovados'] }}</strong> {{ $totais['aprovados'] == 1 ? 'aprovado' : 'aprovados' }}</div>
                @endif
            </div>

            <div class="envdoc-pi-list">
                @foreach ($pisDoFornecedor as $idx => $pi)
                    @php
                        $total = $pi->total_docs ?: 0;
                        $ok = $pi->docs_aprovados ?: 0;
                        $rej = $pi->docs_rejeitados ?: 0;
                        $pend = $pi->docs_pendentes ?: 0;
                        $analise = $pi->docs_em_analise ?: 0;
                        $pct = $total > 0 ? round(($ok / $total) * 100) : 0;
                        $barClass = $pct === 100 ? 'envdoc-bar-green' : ($pct >= 50 ? 'envdoc-bar-amber' : 'envdoc-bar-red');
                        $isUrgent = $rej > 0;
                        $isComplete = $pct === 100;
                        $autoOpen = ($rej > 0 || $pend > 0) && $idx < 3;
                    @endphp
                    <div class="envdoc-pi {{ $isUrgent ? 'envdoc-pi-urgent' : '' }} {{ $isComplete ? 'envdoc-pi-complete' : '' }} {{ $autoOpen ? 'envdoc-pi-open' : '' }}">
                        <div class="envdoc-pi-header" onclick="togglePiAccordion(this)">
                            <div class="envdoc-pi-info">
                                <div class="envdoc-pi-number">{{ $pi->numero_pi }}</div>
                                <div class="envdoc-pi-client">
                                    {{ $pi->cliente->razao_social ?? 'N/A' }}
                                    @if ($pi->tipoVeiculo)
                                        <span class="envdoc-pi-veiculo">{{ $pi->tipoVeiculo->nome }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="envdoc-pi-right">
                                @if ($isComplete)
                                    <span class="envdoc-pi-badge envdoc-badge-green"><i class="bi bi-check-circle-fill"></i> Completo</span>
                                @elseif ($rej > 0)
                                    <span class="envdoc-pi-badge envdoc-badge-red"><i class="bi bi-exclamation-circle-fill"></i> {{ $rej }} {{ $rej == 1 ? 'rejeitado' : 'rejeitados' }}</span>
                                @elseif ($pend > 0)
                                    <span class="envdoc-pi-badge envdoc-badge-muted">{{ $pend }} {{ $pend == 1 ? 'pendente' : 'pendentes' }}</span>
                                @else
                                    <span class="envdoc-pi-badge envdoc-badge-amber"><i class="bi bi-clock"></i> Em análise</span>
                                @endif
                                <div class="envdoc-pi-progress">
                                    <div class="envdoc-pi-bar"><div class="envdoc-pi-fill {{ $barClass }}" style="width: {{ $pct }}%"></div></div>
                                    <span class="envdoc-pi-count">{{ $ok }}/{{ $total }}</span>
                                </div>
                                <i class="bi bi-chevron-down envdoc-pi-chevron"></i>
                            </div>
                        </div>
                        <div class="envdoc-pi-body">
                            @if ($isComplete)
                                <div class="envdoc-pi-done"><i class="bi bi-check-circle-fill"></i> Todos os documentos deste PI foram aprovados</div>
                            @else
                                @php
                                    $docsOrdenados = $pi->piDocumentos->sortBy(function ($piDoc) {
                                        if (!$piDoc->documento_enviado_id) return 1;
                                        $st = $piDoc->documentoEnviado->status->nome ?? '';
                                        if (in_array($st, ['Reprovado', 'Aguardando Reenvio'])) return 0;
                                        if (in_array($st, ['Pendente', 'Aguardando validação'])) return 2;
                                        return 3;
                                    });
                                @endphp
                                @foreach ($docsOrdenados as $piDoc)
                                    @php
                                        $tipoDoc = $piDoc->tipoDocumento;
                                        if (!$piDoc->documento_enviado_id) {
                                            $dotClass = 'envdoc-dot-muted';
                                            $statusLabel = 'Pendente';
                                            $statusClass = 'envdoc-st-muted';
                                            $canSend = true;
                                            $isRejected = false;
                                            $motivo = null;
                                            $docId = null;
                                        } else {
                                            $docStatus = $piDoc->documentoEnviado->status->nome ?? '';
                                            $canSend = false;
                                            $isRejected = false;
                                            $docId = $piDoc->documentoEnviado->id;
                                            $motivo = null;
                                            if ($docStatus === 'Aprovado') {
                                                $dotClass = 'envdoc-dot-success';
                                                $statusLabel = 'Aprovado';
                                                $statusClass = 'envdoc-st-success';
                                            } elseif (in_array($docStatus, ['Reprovado', 'Aguardando Reenvio'])) {
                                                $dotClass = 'envdoc-dot-danger';
                                                $statusLabel = $docStatus;
                                                $statusClass = 'envdoc-st-danger';
                                                $canSend = true;
                                                $isRejected = true;
                                                $ultVal = $piDoc->documentoEnviado->validacoes->first();
                                                $motivo = $ultVal ? $ultVal->observacoes : null;
                                            } else {
                                                $dotClass = 'envdoc-dot-warning';
                                                $statusLabel = 'Em análise';
                                                $statusClass = 'envdoc-st-warning';
                                            }
                                        }
                                    @endphp
                                    <div class="envdoc-doc-row {{ $isRejected ? 'envdoc-doc-rejected' : '' }}">
                                        <span class="envdoc-doc-dot {{ $dotClass }}"></span>
                                        <span class="envdoc-doc-name">{{ $tipoDoc->nome }}</span>
                                        @if ($piDoc->opcional)
                                            <span style="font-size: 0.65rem; font-weight: 600; color: var(--primary-color); background: rgba(0, 94, 130, 0.08); padding: 0.1rem 0.4rem; border-radius: 4px;">Opcional</span>
                                        @endif
                                        <span class="envdoc-doc-status {{ $statusClass }}">{{ $statusLabel }}</span>
                                        @if ($canSend)
                                            <button type="button"
                                                class="envdoc-doc-btn {{ $isRejected ? 'envdoc-btn-resend' : 'envdoc-btn-send' }}"
                                                data-tipo-id="{{ $tipoDoc->id }}"
                                                data-tipo-nome="{{ $tipoDoc->nome }}"
                                                data-validar="{{ $tipoDoc->validar }}"
                                                data-validade-dias="{{ $tipoDoc->validade_dias }}"
                                                data-doc-id="{{ $docId }}"
                                                data-pi-numero="{{ $pi->numero_pi }}"
                                                data-pi-doc-id="{{ $piDoc->id }}">
                                                <i class="bi {{ $isRejected ? 'bi-arrow-repeat' : 'bi-cloud-arrow-up' }}"></i>
                                                {{ $isRejected ? 'Reenviar' : 'Enviar' }}
                                            </button>
                                        @endif
                                    </div>
                                    @if ($motivo)
                                        <div class="envdoc-doc-reason"><i class="bi bi-chat-square-text"></i> {{ $motivo }}</div>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-folder-x"></i></div>
                <h3 class="empty-title">Nenhum PI com documentos pendentes</h3>
                <p class="empty-text">Todos os seus PIs estão com a documentação em dia.</p>
                <a href="{{ route('fornecedor.docs_list.index') }}" class="btn-empty"><i class="bi bi-folder"></i> <span>Ver Meus Documentos</span></a>
            </div>
        @endif
    </div>

    <div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content envdoc-modal">
                <div class="modal-header envdoc-modal-header">
                    <div class="envdoc-modal-title-area">
                        <div class="envdoc-modal-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                        <div>
                            <h5 class="envdoc-modal-title">Enviar Documento</h5>
                            <p class="envdoc-modal-subtitle" id="modalSubtitle"></p>
                        </div>
                    </div>
                    <button type="button" class="envdoc-modal-close" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body envdoc-modal-body">
                    <form action="{{ route('fornecedor.documentos.store') }}" method="POST" id="uploadForm" enctype="multipart/form-data">
                        @csrf
                        @if (isset($fornecedorIdAtivo))
                            <input type="hidden" name="fornecedor_id" value="{{ $fornecedorIdAtivo }}">
                        @endif
                        <input type="hidden" name="tipo_documento_id" id="inputTipoDocId">
                        <input type="hidden" name="pi_documento_id" id="inputPiDocId">

                        <div class="envdoc-form-section">
                            <div class="envdoc-form-section-hdr">
                                <div class="envdoc-form-section-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
                                <div>
                                    <div class="envdoc-form-section-title">Informações</div>
                                    <div class="envdoc-form-section-desc">Tipo de documento e validade</div>
                                </div>
                            </div>
                            <div class="envdoc-form-field">
                                <label class="envdoc-form-label">Tipo de Documento</label>
                                <div class="envdoc-form-readonly" id="tipoDocLabel"></div>
                            </div>
                            <div class="envdoc-form-field" id="campoDataValidade" style="display: none;">
                                <label class="envdoc-form-label">Data de Validade <span class="envdoc-required">*</span></label>
                                <input type="date" name="data_validade_documento" id="inputDataValidade" class="envdoc-form-input" min="{{ date('Y-m-d') }}">
                                <small class="envdoc-form-hint"><i class="bi bi-info-circle"></i> Informe a data de vencimento presente no documento</small>
                            </div>
                        </div>

                        <div class="envdoc-form-section">
                            <div class="envdoc-form-section-hdr">
                                <div class="envdoc-form-section-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                                <div>
                                    <div class="envdoc-form-section-title">Upload do Arquivo</div>
                                    <div class="envdoc-form-section-desc">Arraste ou selecione o PDF</div>
                                </div>
                            </div>
                            <div class="envdoc-upload-area" id="uploadArea">
                                <input type="file" name="arquivo" id="inputArquivo" class="envdoc-file-input" accept=".pdf" required>
                                <div class="envdoc-upload-placeholder" id="uploadPlaceholder">
                                    <div class="envdoc-upload-circle"><i class="bi bi-cloud-arrow-up"></i></div>
                                    <div class="envdoc-upload-text">
                                        <h4>Arraste ou clique para selecionar</h4>
                                        <p><i class="bi bi-file-pdf"></i> PDF <span class="envdoc-upload-sep">-</span> <i class="bi bi-hdd"></i> Máximo 45MB</p>
                                    </div>
                                </div>
                                <div class="envdoc-upload-preview" id="uploadPreview" style="display: none;">
                                    <div class="envdoc-preview-icon"><i class="bi bi-file-pdf-fill"></i></div>
                                    <div class="envdoc-preview-info">
                                        <p class="envdoc-preview-name" id="previewName"></p>
                                        <p class="envdoc-preview-size" id="previewSize"></p>
                                        <div class="envdoc-preview-ok"><i class="bi bi-check-circle-fill"></i> Arquivo carregado</div>
                                    </div>
                                    <button type="button" class="envdoc-preview-remove" id="btnRemoveFile"><i class="bi bi-trash-fill"></i></button>
                                </div>
                            </div>
                            <div id="validationMsg"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer envdoc-modal-footer">
                    <button type="button" class="envdoc-btn-cancel" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Cancelar</button>
                    <button type="button" class="envdoc-btn-submit" id="submitBtn"><i class="bi bi-check-circle"></i> Enviar Documento</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function togglePiAccordion(el) {
            if (event.target.closest('.envdoc-doc-btn')) return;
            el.closest('.envdoc-pi').classList.toggle('envdoc-pi-open');
        }

        $(document).ready(function() {
            var uploadArea = $('#uploadArea');
            var fileInput = $('#inputArquivo');
            var selectedFile = null;
            var dataValidadeNecessaria = false;
            var validacaoRealizada = false;
            var validandoEmAndamento = false;
            var uploadModal = new bootstrap.Modal('#uploadModal');

            $(document).on('click', '.envdoc-doc-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var btn = $(this);
                var tipoId = btn.data('tipo-id');
                var tipoNome = btn.data('tipo-nome');
                var validar = parseInt(btn.data('validar'));
                var validadeDias = parseInt(btn.data('validade-dias'));
                var piNumero = btn.data('pi-numero');
                var piDocId = btn.data('pi-doc-id');

                resetForm();

                $('#inputTipoDocId').val(tipoId);
                $('#inputPiDocId').val(piDocId);
                $('#tipoDocLabel').text(tipoNome);
                $('#modalSubtitle').text('PI: ' + piNumero + ' — ' + tipoNome);

                dataValidadeNecessaria = (validar === 1 && validadeDias > 0);
                if (dataValidadeNecessaria) {
                    $('#campoDataValidade').show();
                    $('#inputDataValidade').prop('required', true);
                    $('#submitBtn').prop('disabled', true).html('<i class="bi bi-x-circle"></i> Preencha a Data');
                } else {
                    $('#campoDataValidade').hide();
                    $('#inputDataValidade').prop('required', false).val('');
                }

                uploadModal.show();
            });

            $('#uploadModal').on('hidden.bs.modal', function() {
                resetForm();
            });

            function resetForm() {
                $('#uploadForm')[0].reset();
                fileInput.val('');
                selectedFile = null;
                $('#uploadPreview').hide();
                $('#uploadPlaceholder').show();
                $('#campoDataValidade').hide();
                $('#inputDataValidade').prop('required', false);
                $('#inputPiDocId').val('');
                $('#submitBtn').prop('disabled', false).html('<i class="bi bi-check-circle"></i> Enviar Documento');
                validacaoRealizada = false;
                validandoEmAndamento = false;
                dataValidadeNecessaria = false;
                $('#validationMsg').empty();
            }

            function showValidation(tipo, msg) {
                $('#validationMsg').empty();
                var cls = tipo === 'success' ? 'envdoc-val-success' : (tipo === 'error' ? 'envdoc-val-error' : (tipo === 'warning' ? 'envdoc-val-warning' : 'envdoc-val-info'));
                var ico = tipo === 'success' ? 'bi-check-circle-fill' : (tipo === 'error' ? 'bi-exclamation-triangle-fill' : (tipo === 'warning' ? 'bi-exclamation-circle-fill' : 'bi-info-circle-fill'));
                $('#validationMsg').html('<div class="envdoc-val ' + cls + '"><i class="bi ' + ico + '"></i> <span>' + msg + '</span></div>');
            }

            function atualizarEstadoBotao() {
                if (!selectedFile) {
                    $('#submitBtn').prop('disabled', false).html('<i class="bi bi-check-circle"></i> Enviar Documento');
                    return;
                }
                if (dataValidadeNecessaria) {
                    var dataVal = $('#inputDataValidade').val();
                    if (!dataVal) {
                        $('#submitBtn').prop('disabled', true).html('<i class="bi bi-x-circle"></i> Preencha a Data');
                    } else if (!validacaoRealizada) {
                        $('#submitBtn').prop('disabled', true).html('<i class="bi bi-x-circle"></i> Validação Pendente');
                    } else {
                        $('#submitBtn').prop('disabled', false).html('<i class="bi bi-check-circle"></i> Enviar Documento');
                    }
                } else {
                    $('#submitBtn').prop('disabled', false).html('<i class="bi bi-check-circle"></i> Enviar Documento');
                }
            }

            $('#inputDataValidade').on('change', function() {
                validacaoRealizada = false;
                $('#validationMsg').empty();
                if (selectedFile && dataValidadeNecessaria && $(this).val()) {
                    validarDataNoPdf();
                } else {
                    atualizarEstadoBotao();
                }
            });

            uploadArea.on('click', function(e) {
                if (!$(e.target).closest('#btnRemoveFile').length) fileInput.click();
            });

            uploadArea.on('dragover', function(e) { e.preventDefault(); e.stopPropagation(); $(this).addClass('envdoc-drag-over'); });
            uploadArea.on('dragleave', function(e) { e.preventDefault(); e.stopPropagation(); $(this).removeClass('envdoc-drag-over'); });
            uploadArea.on('drop', function(e) {
                e.preventDefault(); e.stopPropagation(); $(this).removeClass('envdoc-drag-over');
                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    var dt = new DataTransfer();
                    dt.items.add(files[0]);
                    fileInput[0].files = dt.files;
                    handleFile(files[0]);
                }
            });

            fileInput.on('change', function(e) {
                if (e.target.files[0]) handleFile(e.target.files[0]);
            });

            function handleFile(file) {
                if (file.size > 45 * 1024 * 1024) { toastr.error('Arquivo excede 45MB'); fileInput.val(''); return; }
                if (!file.name.toLowerCase().endsWith('.pdf')) { toastr.error('Apenas PDF permitido'); fileInput.val(''); return; }
                selectedFile = file;
                $('#previewName').text(file.name);
                $('#previewSize').html('<i class="bi bi-hdd"></i> ' + (file.size / 1048576).toFixed(2) + ' MB');
                $('#uploadPlaceholder').hide();
                $('#uploadPreview').show();
                toastr.success('Arquivo selecionado');
                validacaoRealizada = false;
                if (dataValidadeNecessaria) {
                    var dataValidade = $('#inputDataValidade').val();
                    if (dataValidade) {
                        validarDataNoPdf();
                    } else {
                        $('#submitBtn').prop('disabled', true).html('<i class="bi bi-x-circle"></i> Preencha a Data');
                        showValidation('warning', 'Preencha a data de validade para validar o documento.');
                    }
                } else {
                    $('#submitBtn').prop('disabled', false).html('<i class="bi bi-check-circle"></i> Enviar Documento');
                }
            }

            function validarDataNoPdf() {
                var dataVal = $('#inputDataValidade').val();
                var arquivo = fileInput[0].files[0];
                if (!arquivo || !dataVal || validandoEmAndamento) return;
                validandoEmAndamento = true;
                $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Validando...');
                $('#validationMsg').empty();
                showValidation('info', 'Validando documento...');
                var fd = new FormData();
                fd.append('arquivo', arquivo);
                fd.append('data_validade', dataVal);
                fd.append('_token', $('meta[name="csrf-token"]').attr('content'));
                $.ajax({
                    url: "{{ route('fornecedor.documentos.validar_data_pdf') }}",
                    type: 'POST', data: fd, processData: false, contentType: false, timeout: 30000,
                    success: function(r) {
                        validandoEmAndamento = false;
                        validacaoRealizada = true;
                        $('#submitBtn').prop('disabled', false).html('<i class="bi bi-check-circle"></i> Enviar Documento');
                        $('#validationMsg').empty();
                        if (r.warning) {
                            showValidation('warning', '<strong>Documento aceito!</strong> A data será verificada manualmente.');
                        } else {
                            showValidation('success', '<strong>Documento validado!</strong> Data confirmada no PDF.');
                        }
                    },
                    error: function(xhr, status) {
                        validandoEmAndamento = false;
                        validacaoRealizada = false;
                        $('#submitBtn').prop('disabled', true).html('<i class="bi bi-x-circle"></i> Validação Necessária');
                        var msg = status === 'timeout' ? 'Tempo esgotado.' : (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Erro ao validar');
                        $('#validationMsg').empty();
                        showValidation('error', '<strong>Documento inválido</strong><br><small>' + msg + '</small>');
                    }
                });
            }

            $('#btnRemoveFile').on('click', function(e) {
                e.stopPropagation();
                fileInput.val('');
                selectedFile = null;
                $('#uploadPreview').hide();
                $('#uploadPlaceholder').show();
                validacaoRealizada = false;
                validandoEmAndamento = false;
                $('#validationMsg').empty();
                atualizarEstadoBotao();
            });

            $('#submitBtn').on('click', function() {
                var tipoId = $('#inputTipoDocId').val();
                var arquivo = $('#inputArquivo').val();
                var dataReq = $('#inputDataValidade').prop('required');
                var dataVal = $('#inputDataValidade').val();
                if (!tipoId || !arquivo) { toastr.error('Preencha todos os campos'); return false; }
                if (dataReq && !dataVal) { toastr.error('Preencha a data de validade'); return false; }
                if (dataValidadeNecessaria && !validacaoRealizada) { toastr.error('Aguarde a validação da data'); return false; }
                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Enviando...');
                $('#uploadForm').submit();
            });

            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    toastr.error('{{ $error }}');
                @endforeach
            @endif
        });
    </script>
@endpush

@push('styles')
    <style>
        .envdoc-container {
            padding-top: 0.5rem;
            padding-bottom: 2.5rem;
        }

        .envdoc-btn-back {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-body);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-muted);
            font-size: 1.25rem;
            text-decoration: none;
            transition: all var(--transition);
            flex-shrink: 0;
        }

        .envdoc-btn-back:hover {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border-color: var(--primary-color);
            color: white;
            transform: translateX(-3px);
            box-shadow: 0 4px 14px rgba(0, 94, 130, 0.25);
        }

        .envdoc-selector {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .envdoc-selector-inner {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .envdoc-selector-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .envdoc-selector-content {
            flex: 1;
        }

        .envdoc-selector-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
            display: block;
        }

        .envdoc-selector-select {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-color);
            background: var(--bg-body);
            transition: all var(--transition);
            cursor: pointer;
        }

        .envdoc-selector-select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 94, 130, 0.1);
        }

        .envdoc-alert {
            background: rgba(239, 68, 68, 0.08);
            border: 2px solid #fecaca;
            border-radius: var(--radius-md);
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .envdoc-alert-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--danger-color);
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .envdoc-alert-content { flex: 1; }
        .envdoc-alert-title { font-size: 0.9rem; font-weight: 700; color: var(--danger-color); margin-bottom: 0.25rem; }
        .envdoc-alert-item { font-size: 0.85rem; color: #b91c1c; padding: 0.125rem 0; }

        .envdoc-alert-close {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: none;
            border: none;
            color: var(--danger-color);
            cursor: pointer;
            border-radius: var(--radius-sm);
            transition: all var(--transition);
        }

        .envdoc-alert-close:hover { background: #fee2e2; }

        [data-theme="dark"] .envdoc-alert { background: rgba(127, 29, 29, 0.2); border-color: #7f1d1d; }
        [data-theme="dark"] .envdoc-alert-icon { background: rgba(220, 38, 38, 0.15); }
        [data-theme="dark"] .envdoc-alert-title { color: #fca5a5; }
        [data-theme="dark"] .envdoc-alert-item { color: #fca5a5; }
        [data-theme="dark"] .envdoc-alert-close { color: #fca5a5; }

        .envdoc-summary {
            display: flex;
            gap: 0.625rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .envdoc-pill {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-muted);
            padding: 0.5rem 1rem;
            background: var(--bg-card);
            border-radius: 20px;
            border: 1px solid var(--border-color);
            font-weight: 500;
        }

        .envdoc-pill-danger { border-color: rgba(239, 68, 68, 0.3); }

        .envdoc-pill strong { color: var(--text-color); font-weight: 700; }
        .envdoc-pill-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .envdoc-dot-danger { background: var(--danger-color); }
        .envdoc-dot-warning { background: var(--warning-color); }
        .envdoc-dot-muted { background: var(--text-muted); }
        .envdoc-dot-success { background: var(--success-color); }

        .envdoc-pi-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .envdoc-pi {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            transition: all var(--transition);
        }

        .envdoc-pi:hover {
            box-shadow: var(--shadow-md);
        }

        .envdoc-pi-urgent {
            border-left: 4px solid var(--danger-color);
            border-radius: 0;
            border-top-right-radius: var(--radius-md);
            border-bottom-right-radius: var(--radius-md);
        }

        .envdoc-pi-complete { opacity: 0.65; }
        .envdoc-pi-complete:hover { opacity: 1; }

        .envdoc-pi-header {
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            transition: background var(--transition-fast);
        }

        .envdoc-pi-header:hover { background: rgba(0, 94, 130, 0.03); }

        .envdoc-pi-info { flex: 1; min-width: 0; }

        .envdoc-pi-number {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-color);
        }

        .envdoc-pi-client {
            font-size: 0.825rem;
            color: var(--text-muted);
            margin-top: 0.125rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .envdoc-pi-veiculo {
            font-size: 0.7rem;
            padding: 1px 8px;
            border-radius: 10px;
            background: rgba(0, 94, 130, 0.08);
            color: var(--primary-color);
            font-weight: 600;
        }

        [data-theme="dark"] .envdoc-pi-veiculo { background: rgba(0, 136, 184, 0.15); color: var(--primary-light); }

        .envdoc-pi-right {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-shrink: 0;
        }

        .envdoc-pi-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.375rem;
            white-space: nowrap;
        }

        .envdoc-badge-red { background: linear-gradient(135deg, #fee2e2, #fecaca); color: var(--danger-color); }
        .envdoc-badge-green { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669; }
        .envdoc-badge-amber { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706; }
        .envdoc-badge-muted { background: var(--bg-body); color: var(--text-muted); border: 1px solid var(--border-color); }

        [data-theme="dark"] .envdoc-badge-red { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
        [data-theme="dark"] .envdoc-badge-green { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; }
        [data-theme="dark"] .envdoc-badge-amber { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }

        .envdoc-pi-progress {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .envdoc-pi-bar {
            width: 60px;
            height: 6px;
            background: var(--border-color);
            border-radius: 3px;
            overflow: hidden;
        }

        .envdoc-pi-fill { height: 100%; border-radius: 3px; transition: width 0.5s ease; }
        .envdoc-bar-green { background: linear-gradient(90deg, #10b981, #34d399); }
        .envdoc-bar-amber { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .envdoc-bar-red { background: linear-gradient(90deg, #ef4444, #f87171); }

        .envdoc-pi-count {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            min-width: 28px;
            text-align: right;
        }

        .envdoc-pi-chevron {
            font-size: 0.9rem;
            color: var(--text-muted);
            transition: transform var(--transition);
        }

        .envdoc-pi-open .envdoc-pi-chevron { transform: rotate(180deg); }

        .envdoc-pi-body {
            display: none;
            border-top: 1px solid var(--border-color);
            padding: 0.75rem 1.25rem 1rem;
        }

        .envdoc-pi-open .envdoc-pi-body { display: block; }

        .envdoc-pi-done {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: #059669;
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            border-radius: var(--radius);
        }

        [data-theme="dark"] .envdoc-pi-done { background: rgba(16, 185, 129, 0.1); color: #6ee7b7; }

        .envdoc-doc-row {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.625rem 0.75rem;
            border-radius: var(--radius);
            margin-bottom: 0.375rem;
            background: var(--bg-body);
            transition: background var(--transition-fast);
        }

        .envdoc-doc-row:last-child { margin-bottom: 0; }
        .envdoc-doc-row:hover { background: rgba(0, 94, 130, 0.04); }
        .envdoc-doc-rejected { background: rgba(239, 68, 68, 0.04); }
        .envdoc-doc-rejected:hover { background: rgba(239, 68, 68, 0.07); }
        [data-theme="dark"] .envdoc-doc-rejected { background: rgba(239, 68, 68, 0.06); }

        .envdoc-doc-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .envdoc-doc-name { flex: 1; font-size: 0.875rem; color: var(--text-color); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .envdoc-doc-status { font-size: 0.8rem; font-weight: 600; white-space: nowrap; }
        .envdoc-st-success { color: #059669; }
        .envdoc-st-warning { color: #d97706; }
        .envdoc-st-danger { color: var(--danger-color); }
        .envdoc-st-muted { color: var(--text-muted); }
        [data-theme="dark"] .envdoc-st-success { color: #6ee7b7; }
        [data-theme="dark"] .envdoc-st-warning { color: #fcd34d; }
        [data-theme="dark"] .envdoc-st-danger { color: #fca5a5; }

        .envdoc-doc-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.875rem;
            border-radius: var(--radius-sm);
            font-size: 0.8rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all var(--transition);
            flex-shrink: 0;
            white-space: nowrap;
        }

        .envdoc-btn-send {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            box-shadow: 0 2px 8px rgba(0, 94, 130, 0.2);
        }

        .envdoc-btn-send:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 94, 130, 0.3); }

        .envdoc-btn-resend {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.2);
        }

        .envdoc-btn-resend:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3); }

        .envdoc-doc-reason {
            margin: -0.125rem 0 0.375rem 1.125rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.78rem;
            color: var(--danger-color);
            background: rgba(239, 68, 68, 0.05);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: flex-start;
            gap: 0.25rem;
        }

        [data-theme="dark"] .envdoc-doc-reason { color: #fca5a5; background: rgba(239, 68, 68, 0.08); }

        .envdoc-modal {
            border: none;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            background: var(--bg-card);
        }

        .envdoc-modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            padding: 1.5rem 1.75rem;
            border-bottom: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .envdoc-modal-title-area { display: flex; align-items: center; gap: 0.875rem; }

        .envdoc-modal-icon {
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.375rem;
            color: white;
        }

        .envdoc-modal-title { font-size: 1.25rem; font-weight: 700; margin: 0; color: white; line-height: 1.2; }
        .envdoc-modal-subtitle { font-size: 0.875rem; margin: 0.125rem 0 0 0; opacity: 0.9; font-weight: 500; color: white; }

        .envdoc-modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            width: 40px;
            height: 40px;
            border-radius: var(--radius);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition);
            font-size: 1rem;
        }

        .envdoc-modal-close:hover { background: rgba(255, 255, 255, 0.3); transform: scale(1.05); }

        .envdoc-modal-body {
            padding: 1.5rem 1.75rem;
            background: var(--bg-body);
        }

        .envdoc-form-section {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }

        .envdoc-form-section:last-child { margin-bottom: 0; }

        .envdoc-form-section-hdr {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }

        .envdoc-form-section-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .envdoc-form-section-title { font-size: 0.95rem; font-weight: 700; color: var(--text-color); margin: 0; }
        .envdoc-form-section-desc { font-size: 0.8rem; color: var(--text-muted); margin: 0.125rem 0 0 0; }

        .envdoc-form-field { margin-bottom: 1rem; }
        .envdoc-form-field:last-child { margin-bottom: 0; }

        .envdoc-form-label {
            display: block;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.375rem;
            font-size: 0.9rem;
        }

        .envdoc-required { color: var(--danger-color); }

        .envdoc-form-readonly {
            padding: 0.75rem 1rem;
            background: var(--bg-body);
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-color);
            opacity: 0.7;
        }

        .envdoc-form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-color);
            background: var(--bg-card);
            transition: all var(--transition);
        }

        .envdoc-form-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 94, 130, 0.1);
        }

        .envdoc-form-hint {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            margin-top: 0.375rem;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .envdoc-form-hint i { color: var(--primary-color); }

        .envdoc-upload-area {
            position: relative;
            border: 3px dashed var(--border-color);
            border-radius: var(--radius-md);
            padding: 2.5rem 1.5rem;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            background: var(--bg-body);
        }

        .envdoc-upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(0, 94, 130, 0.03);
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0, 94, 130, 0.1);
        }

        .envdoc-upload-area.envdoc-drag-over {
            border-color: var(--primary-light);
            background: rgba(0, 136, 184, 0.05);
            box-shadow: 0 8px 24px rgba(0, 94, 130, 0.15);
        }

        .envdoc-file-input { position: absolute; inset: 0; opacity: 0; cursor: pointer; z-index: 10; }
        .envdoc-upload-placeholder { pointer-events: none; }

        .envdoc-upload-circle {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            box-shadow: 0 6px 20px rgba(0, 94, 130, 0.25);
            animation: envdocFloat 3s ease-in-out infinite;
        }

        @keyframes envdocFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .envdoc-upload-text h4 { font-size: 1rem; font-weight: 700; color: var(--text-color); margin: 0 0 0.5rem; }
        .envdoc-upload-text p { font-size: 0.875rem; color: var(--text-muted); margin: 0; display: flex; align-items: center; justify-content: center; gap: 0.375rem; }
        .envdoc-upload-text p i { color: var(--primary-color); }
        .envdoc-upload-sep { color: var(--text-muted); opacity: 0.5; }

        .envdoc-upload-preview {
            padding: 1.25rem;
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            border-radius: var(--radius-md);
            border: 2px solid var(--success-color);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        [data-theme="dark"] .envdoc-upload-preview { background: rgba(16, 185, 129, 0.1); border-color: #059669; }

        .envdoc-preview-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #059669;
            flex-shrink: 0;
        }

        [data-theme="dark"] .envdoc-preview-icon { background: rgba(16, 185, 129, 0.2); }

        .envdoc-preview-info { flex: 1; text-align: left; }
        .envdoc-preview-name { font-weight: 700; color: #065f46; margin-bottom: 0.125rem; word-break: break-word; }
        [data-theme="dark"] .envdoc-preview-name { color: #a7f3d0; }
        .envdoc-preview-size { color: #047857; font-size: 0.85rem; margin: 0 0 0.25rem; }
        [data-theme="dark"] .envdoc-preview-size { color: #6ee7b7; }
        .envdoc-preview-ok { font-size: 0.8rem; font-weight: 600; color: #059669; display: flex; align-items: center; gap: 0.25rem; }
        [data-theme="dark"] .envdoc-preview-ok { color: #6ee7b7; }

        .envdoc-preview-remove {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: var(--danger-color);
            border: none;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition);
            flex-shrink: 0;
        }

        .envdoc-preview-remove:hover { background: #dc2626; color: white; transform: scale(1.05); }

        .envdoc-val {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border-radius: var(--radius);
            margin-top: 0.75rem;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .envdoc-val i { flex-shrink: 0; margin-top: 0.125rem; }

        .envdoc-val-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .envdoc-val-error { background: rgba(239, 68, 68, 0.08); color: var(--danger-color); border: 1px solid rgba(239, 68, 68, 0.3); }
        .envdoc-val-warning { background: #fffbeb; color: var(--text-on-light-bg); border: 1px solid #fde68a; }
        .envdoc-val-info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }

        [data-theme="dark"] .envdoc-val-success { background: rgba(16, 185, 129, 0.1); color: #6ee7b7; border-color: rgba(16, 185, 129, 0.3); }
        [data-theme="dark"] .envdoc-val-error { background: rgba(239, 68, 68, 0.1); color: #fca5a5; border-color: rgba(239, 68, 68, 0.3); }
        [data-theme="dark"] .envdoc-val-warning { background: rgba(245, 158, 11, 0.1); color: #fcd34d; border-color: rgba(245, 158, 11, 0.3); }
        [data-theme="dark"] .envdoc-val-info { background: rgba(59, 130, 246, 0.1); color: #93c5fd; border-color: rgba(59, 130, 246, 0.3); }

        .envdoc-modal-footer {
            background: var(--bg-body);
            border-top: 1px solid var(--border-color);
            padding: 1.25rem 1.75rem;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .envdoc-btn-cancel {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--bg-card);
            color: var(--text-color);
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all var(--transition);
        }

        .envdoc-btn-cancel:hover { background: var(--bg-body); border-color: var(--primary-color); color: var(--primary-color); }

        .envdoc-btn-submit {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.75rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all var(--transition);
            box-shadow: 0 4px 14px rgba(0, 94, 130, 0.25);
        }

        .envdoc-btn-submit:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0, 94, 130, 0.35); }
        .envdoc-btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }

        @media (max-width: 768px) {
            .envdoc-pi-right { flex-direction: column; align-items: flex-end; gap: 0.5rem; }
            .envdoc-pi-badge { font-size: 0.7rem; }
            .envdoc-doc-row { flex-wrap: wrap; }
            .envdoc-doc-name { width: 100%; order: -1; margin-bottom: 0.25rem; }
            .envdoc-doc-dot { order: -2; }
            .envdoc-modal-footer { flex-direction: column; }
            .envdoc-btn-cancel, .envdoc-btn-submit { width: 100%; justify-content: center; }
            .envdoc-modal-body { padding: 1.25rem; }
            .envdoc-modal-header { padding: 1.25rem 1.5rem; }
            .envdoc-upload-area { padding: 2rem 1.25rem; }
            .envdoc-upload-circle { width: 64px; height: 64px; font-size: 2rem; }
        }

        @media (max-width: 576px) {
            .envdoc-pi-header { flex-direction: column; align-items: flex-start; gap: 0.625rem; }
            .envdoc-pi-right { width: 100%; justify-content: space-between; flex-direction: row; align-items: center; }
            .envdoc-summary { flex-direction: column; }
        }
    </style>
@endpush