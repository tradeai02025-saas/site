{{-- resources/views/fornecedores/meus-documentos.blade.php --}}
@extends('layouts.app')

@section('title', 'Meus Documentos')

@section('content')
    <div class="container-fluid px-0">

        <div class="page-header">
            <div class="header-card">
                <div class="header-top">
                    <div class="title-wrapper">
                        <div class="icon-badge"><i class="bi bi-folder-fill"></i></div>
                        <div>
                            <h1 class="main-title">Meus Documentos</h1>
                            <p class="main-subtitle">Gerencie seus documentos enviados e pendentes</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        @if ($totalDocumentos > 0)
                            <a href="{{ route('fornecedor.documentos.baixar_todos_zip') }}" class="btn-secondary" id="btnBaixarTodosZip">
                                <i class="bi bi-file-earmark-zip-fill"></i><span>Baixar Todos</span>
                            </a>
                        @endif
                        <a href="{{ route('fornecedor.docs_enviar.index') }}" class="btn-create">
                            <i class="bi bi-cloud-upload-fill"></i><span>Enviar Documentos</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @php
            $tabAtiva = request('tab');
            if (!$tabAtiva) {
                $tabAtiva = ($acaoNecessariaCount + $docsVencendo->count() > 0) ? 'acao' : 'porpi';
            }
        @endphp

        <div class="tabs-bar">
            <button type="button" class="doc-tab {{ $tabAtiva === 'acao' ? 'active' : '' }}" data-tab="acao">
                <i class="bi bi-exclamation-circle"></i>
                <span class="tab-text">Ação necessária</span>
                @if ($acaoNecessariaCount + $docsVencendo->count() > 0)
                    <span class="tab-badge tab-badge-danger">{{ $acaoNecessariaCount + $docsVencendo->count() }}</span>
                @endif
            </button>
            <button type="button" class="doc-tab {{ $tabAtiva === 'porpi' ? 'active' : '' }}" data-tab="porpi">
                <i class="bi bi-folder2-open"></i>
                <span class="tab-text">Por PI</span>
                <span class="tab-badge tab-badge-info">{{ $pisDoFornecedor->count() }}</span>
            </button>
            <button type="button" class="doc-tab {{ $tabAtiva === 'biblioteca' ? 'active' : '' }}" data-tab="biblioteca">
                <i class="bi bi-archive"></i>
                <span class="tab-text">Meus documentos</span>
                <span class="tab-badge tab-badge-neutral">{{ $totalDocumentos }}</span>
            </button>
        </div>

        <div class="tab-panel {{ $tabAtiva === 'acao' ? 'active' : '' }}" id="panel-acao">
            @if ($acaoNecessariaCount + $docsVencendo->count() === 0)
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-check-circle"></i></div>
                    <h3 class="empty-title">Tudo em dia!</h3>
                    <p class="empty-text">Você não possui documentos pendentes, reprovados ou vencendo.</p>
                </div>
            @else
                <div class="action-stats">
                    @if ($documentosReprovados > 0)
                        <div class="action-stat"><span class="action-dot action-dot-danger"></span> <strong>{{ $documentosReprovados }}</strong> {{ Str::plural('Reprovado', $documentosReprovados) }}</div>
                    @endif
                    @if ($documentosPendentes > 0)
                        <div class="action-stat"><span class="action-dot action-dot-muted"></span> <strong>{{ $documentosPendentes }}</strong> Não {{ $documentosPendentes === 1 ? 'enviado' : 'enviados' }}</div>
                    @endif
                    @if ($docsVencendo->count() > 0)
                        <div class="action-stat"><span class="action-dot action-dot-warning"></span> <strong>{{ $docsVencendo->count() }}</strong> Vencendo</div>
                    @endif
                </div>
                <div class="action-list">
                    @foreach ($pisComReprovados as $piDoc)
                        <div class="action-card action-card-danger">
                            <div class="action-card-left">
                                <div class="action-card-icon action-icon-danger"><i class="bi bi-x-circle-fill"></i></div>
                                <div class="action-card-info">
                                    <div class="action-card-title">{{ $piDoc->tipoDocumento->nome }}</div>
                                    <div class="action-card-meta">
                                        <span><i class="bi bi-file-text"></i> PI: {{ $piDoc->pi->numero_pi }}</span>
                                        <span><i class="bi bi-building"></i> {{ Str::limit($piDoc->pi->cliente->razao_social, 30) }}</span>
                                    </div>
                                    @if ($piDoc->documentoEnviado && $piDoc->documentoEnviado->validacoes->first() && $piDoc->documentoEnviado->validacoes->first()->observacoes)
                                        <div class="action-card-reason"><i class="bi bi-chat-square-text"></i> {{ $piDoc->documentoEnviado->validacoes->first()->observacoes }}</div>
                                    @endif
                                </div>
                            </div>
                            <a href="{{ route('pis.enviar_documentos', $piDoc->pi_id) }}" class="action-card-btn action-btn-danger"><i class="bi bi-arrow-repeat"></i> <span>Reenviar</span></a>
                        </div>
                    @endforeach
                    @foreach ($pisPendentes as $piDoc)
                        <div class="action-card action-card-muted">
                            <div class="action-card-left">
                                <div class="action-card-icon action-icon-muted"><i class="bi bi-file-earmark-arrow-up"></i></div>
                                <div class="action-card-info">
                                    <div class="action-card-title">{{ $piDoc->tipoDocumento->nome }}</div>
                                    <div class="action-card-meta">
                                        <span><i class="bi bi-file-text"></i> PI: {{ $piDoc->pi->numero_pi }}</span>
                                        <span><i class="bi bi-building"></i> {{ Str::limit($piDoc->pi->cliente->razao_social ?? 'N/A', 30) }}</span>
                                    </div>
                                </div>
                            </div>
                            <a href="{{ route('pis.enviar_documentos', $piDoc->pi_id) }}" class="action-card-btn action-btn-primary"><i class="bi bi-cloud-upload"></i> <span>Enviar</span></a>
                        </div>
                    @endforeach
                    @foreach ($docsVencendo as $doc)
                        @php $diasR = max(0, round(now()->diffInDays($doc->data_validade_documento, false))); @endphp
                        <div class="action-card action-card-warning">
                            <div class="action-card-left">
                                <div class="action-card-icon action-icon-warning"><i class="bi bi-exclamation-triangle-fill"></i></div>
                                <div class="action-card-info">
                                    <div class="action-card-title">{{ $doc->tipoDocumento->nome }}</div>
                                    <div class="action-card-meta">
                                        <span><i class="bi bi-calendar-event"></i> Vence em {{ $diasR }} {{ $diasR == 1 ? 'dia' : 'dias' }}</span>
                                        @php $pisV = $doc->piDocumentos->pluck('pi')->filter()->unique('id'); @endphp
                                        @if ($pisV->count() > 0)
                                            <span><i class="bi bi-folder"></i> {{ $pisV->count() }} {{ Str::plural('PI', $pisV->count()) }}</span>
                                        @endif
                                    </div>
                                    <div class="action-card-reason action-reason-warning"><i class="bi bi-info-circle"></i> Validade: {{ \Carbon\Carbon::parse($doc->data_validade_documento)->format('d/m/Y') }}</div>
                                </div>
                            </div>
                            <a href="{{ route('fornecedor.docs_enviar.index') }}" class="action-card-btn action-btn-warning"><i class="bi bi-arrow-repeat"></i> <span>Renovar</span></a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="tab-panel {{ $tabAtiva === 'porpi' ? 'active' : '' }}" id="panel-porpi">
            @if ($pisDoFornecedor->count() === 0 && $pisConcluidosFornecedor->count() === 0)
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-folder-x"></i></div>
                    <h3 class="empty-title">Nenhum PI encontrado</h3>
                    <p class="empty-text">Você ainda não possui PIs vinculados.</p>
                </div>
            @else
                @if ($pisDoFornecedor->count() > 0)
                    <div class="pi-section-label"><i class="bi bi-clock-history"></i> PIs em andamento ({{ $pisDoFornecedor->count() }})</div>
                    <div class="pi-list">
                        @foreach ($pisDoFornecedor as $pi)
                            @php
                                $tDocs = $pi->total_docs ?: 0; $dOk = $pi->docs_atendidos ?: 0;
                                $pct = $tDocs > 0 ? round(($dOk / $tDocs) * 100) : 0;
                                $pCls = $pct === 100 ? 'progress-green' : ($pct >= 50 ? 'progress-amber' : 'progress-red');
                            @endphp
                            <div class="pi-card">
                                <div class="pi-card-header">
                                    <div class="pi-card-title-area">
                                        <span class="pi-card-number">{{ $pi->numero_pi }}</span>
                                        <span class="pi-card-client">{{ $pi->cliente->razao_social ?? 'N/A' }}</span>
                                        @if ($pi->tipoVeiculo)
                                            <span class="pi-card-veiculo">{{ $pi->tipoVeiculo->nome }}</span>
                                        @endif
                                    </div>
                                    <div class="pi-card-progress-badge {{ $pCls }}">{{ $dOk }}/{{ $tDocs }}</div>
                                </div>
                                <div class="pi-progress-bar"><div class="pi-progress-fill {{ $pCls }}" style="width: {{ $pct }}%"></div></div>
                                <div class="pi-docs-list">
                                    @foreach ($pi->piDocumentos as $piDoc)
                                        @php
                                            if (!$piDoc->documento_enviado_id) { $dot='dot-gray'; $sl='Não enviado'; $sc='color-muted'; }
                                            else {
                                                $ds = $piDoc->documentoEnviado->status->nome ?? 'Pendente';
                                                if ($ds==='Aprovado') { $dot='dot-green'; $sl='Aprovado'; $sc='color-success'; }
                                                elseif (in_array($ds, ['Reprovado','Aguardando Reenvio'])) { $dot='dot-red'; $sl=$ds; $sc='color-danger'; }
                                                else { $dot='dot-amber'; $sl='Em análise'; $sc='color-warning'; }
                                            }
                                        @endphp
                                        <div class="pi-doc-item">
                                            <div class="pi-doc-left"><span class="pi-doc-dot {{ $dot }}"></span> <span class="pi-doc-name">{{ $piDoc->tipoDocumento->nome }}</span></div>
                                            <span class="pi-doc-status {{ $sc }}">{{ $sl }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="pi-card-footer">
                                    <a href="{{ route('pis.enviar_documentos', $pi->id) }}" class="pi-card-action"><i class="bi bi-cloud-upload"></i> <span>Enviar documentos</span></a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                @if ($pisConcluidosFornecedor->count() > 0)
                    <div class="pi-section-label pi-section-concluidos">
                        <i class="bi bi-check-circle"></i> PIs concluídos ({{ $pisConcluidosFornecedor->count() }})
                        <button type="button" class="toggle-concluidos" id="toggleConcluidos"><i class="bi bi-chevron-down"></i></button>
                    </div>
                    <div class="pi-list" id="listaConcluidos" style="display: none;">
                        @foreach ($pisConcluidosFornecedor as $pi)
                            <div class="pi-card pi-card-concluido">
                                <div class="pi-card-header">
                                    <div class="pi-card-title-area">
                                        <span class="pi-card-number">{{ $pi->numero_pi }}</span>
                                        <span class="pi-card-client">{{ $pi->cliente->razao_social ?? 'N/A' }}</span>
                                    </div>
                                    <div class="pi-card-progress-badge progress-green"><i class="bi bi-check-lg"></i></div>
                                </div>
                                <div class="pi-progress-bar"><div class="pi-progress-fill progress-green" style="width: 100%"></div></div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>

        <div class="tab-panel {{ $tabAtiva === 'biblioteca' ? 'active' : '' }}" id="panel-biblioteca">
            <div class="bib-stats">
                <div class="bib-stat"><strong>{{ $totalDocumentos }}</strong> enviados</div>
                <div class="bib-stat"><span class="action-dot action-dot-success"></span> <strong>{{ $documentosAprovados }}</strong> aprovados</div>
                <div class="bib-stat"><span class="action-dot action-dot-warning"></span> <strong>{{ $documentosAguardandoValidacao }}</strong> em análise</div>
                <div class="bib-stat"><span class="action-dot action-dot-danger"></span> <strong>{{ $documentosVencidos }}</strong> {{ Str::plural('vencido', $documentosVencidos) }}</div>
            </div>
            <form method="GET" action="{{ route('fornecedor.docs_list.index') }}" id="filterForm">
                <input type="hidden" name="tab" value="biblioteca">
                <div class="bib-toolbar">
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" name="search" class="search-input" placeholder="Buscar documento..." value="{{ request('search') }}" autocomplete="off">
                        @if (request('search'))
                            <button type="button" class="search-clear"><i class="bi bi-x"></i></button>
                        @endif
                        <button type="submit" class="search-btn" title="Pesquisar"><i class="bi bi-search"></i></button>
                    </div>
                    <select name="tipo_documento_id" class="filter-select" onchange="this.form.submit()">
                        <option value="">Todos os tipos</option>
                        @foreach ($tiposDocumentos as $tipo)
                            <option value="{{ $tipo->id }}" {{ request('tipo_documento_id') == $tipo->id ? 'selected' : '' }}>{{ $tipo->nome }}</option>
                        @endforeach
                    </select>
                    <select name="status_filtro" class="filter-select" onchange="this.form.submit()">
                        <option value="">Todos os status</option>
                        <option value="aprovado" {{ request('status_filtro') === 'aprovado' ? 'selected' : '' }}>Aprovados</option>
                        <option value="aguardando" {{ request('status_filtro') === 'aguardando' ? 'selected' : '' }}>Em Análise</option>
                        <option value="reprovado" {{ request('status_filtro') === 'reprovado' ? 'selected' : '' }}>Reprovados</option>
                        <option value="vencendo" {{ request('status_filtro') === 'vencendo' ? 'selected' : '' }}>Vencendo</option>
                        <option value="vencido" {{ request('status_filtro') === 'vencido' ? 'selected' : '' }}>Vencidos</option>
                    </select>
                </div>
            </form>
            @if (request()->hasAny(['search', 'tipo_documento_id', 'status_filtro']))
                <div class="bib-active-filters">
                    <span class="filters-label">Filtros:</span>
                    @if (request('search'))
                        <span class="filter-tag">{{ request('search') }} <button type="button" class="remove-filter" data-filter="search">×</button></span>
                    @endif
                    @if (request('tipo_documento_id'))
                        <span class="filter-tag">{{ $tiposDocumentos->find(request('tipo_documento_id'))->nome ?? '' }} <button type="button" class="remove-filter" data-filter="tipo_documento_id">×</button></span>
                    @endif
                    @if (request('status_filtro'))
                        <span class="filter-tag">{{ ucfirst(request('status_filtro')) }} <button type="button" class="remove-filter" data-filter="status_filtro">×</button></span>
                    @endif
                    <a href="{{ route('fornecedor.docs_list.index', ['tab' => 'biblioteca']) }}" class="clear-all-link">Limpar</a>
                </div>
            @endif
            @if ($documentos->count() > 0)
                <div class="bib-list">
                    @foreach ($documentos as $documento)
                        @php
                            $sN = $documento->status->nome ?? 'Pendente';
                            $pisVinc = $documento->piDocumentos->pluck('pi')->filter()->unique('id');
                            if ($documento->data_validade_documento) {
                                $dV = \Carbon\Carbon::parse($documento->data_validade_documento);
                                $dR = now()->diffInDays($dV, false);
                                if ($dR < 0) { $vC='expired'; $dAbs=abs(round($dR)); $vT='Vencido há '.$dAbs.' '.($dAbs==1?'dia':'dias'); $vI='✕'; }
                                elseif ($dR <= 14) { $vC='expiring'; $dRr=max(0,round($dR)); $vT=$dRr==0?'Vence hoje':'Vence em '.$dRr.' '.($dRr==1?'dia':'dias'); $vI='!'; }
                                else { $vC='valid'; $vT=$dV->format('d/m/Y'); $vI='✓'; }
                            } else { $vC='valid'; $vT='Indeterminada'; $vI='∞'; }
                            if (in_array($sN, ['Reprovado','Aguardando Reenvio'])) { $vC='expired'; $vI='✕'; }
                            elseif (in_array($sN, ['Pendente','Aguardando validação'])) { $vC='expiring'; $vI='…'; }
                        @endphp
                        <div class="bib-card">
                            <div class="bib-icon {{ $vC }}">{{ $vI }}</div>
                            <div class="bib-info">
                                <div class="bib-name">{{ $documento->tipoDocumento->nome }}</div>
                                <div class="bib-detail">Enviado em {{ $documento->data_envio->format('d/m/Y') }}@if ($documento->data_validade_documento) — Val.: {{ \Carbon\Carbon::parse($documento->data_validade_documento)->format('d/m/Y') }}@else — Indeterminada @endif</div>
                                @if (in_array($sN, ['Reprovado','Aguardando Reenvio']))
                                    @php $uv = $documento->validacoes->first(); @endphp
                                    @if ($uv && $uv->observacoes)
                                        <div class="bib-reason"><i class="bi bi-chat-square-text"></i> {{ Str::limit($uv->observacoes, 80) }}</div>
                                    @endif
                                @endif
                            </div>
                            <div class="bib-right">
                                <div class="bib-validity {{ $vC }}">@if (in_array($sN, ['Reprovado','Aguardando Reenvio'])){{ $sN }}@elseif (in_array($sN, ['Pendente','Aguardando validação']))Em análise @else{{ $vT }}@endif</div>
                                @if ($pisVinc->count() > 0)
                                    <div class="bib-pis">Usado em {{ $pisVinc->count() }} {{ Str::plural('PI', $pisVinc->count()) }}</div>
                                @endif
                            </div>
                            <div class="bib-actions">
                                @if ($documento->arquivos->first())
                                    <button type="button" class="bib-action-btn" data-action="preview" data-doc-id="{{ $documento->id }}" data-arquivo-id="{{ $documento->arquivos->first()->id }}" title="Visualizar"><i class="bi bi-eye"></i></button>
                                    <a href="{{ route('fornecedor.documentos.download', [$documento->id, $documento->arquivos->first()->id]) }}" class="bib-action-btn" title="Baixar"><i class="bi bi-download"></i></a>
                                    <button type="button" class="bib-action-btn" data-action="history" data-doc-id="{{ $documento->id }}" title="Histórico"><i class="bi bi-clock-history"></i></button>
                                    @if (in_array($sN, ['Reprovado','Aguardando Reenvio']))
                                        <button type="button" class="bib-action-btn bib-action-resend" data-action="resend" data-doc-id="{{ $documento->id }}" data-tipo-nome="{{ $documento->tipoDocumento->nome }}" data-validar="{{ $documento->tipoDocumento->validar }}" data-validade-dias="{{ $documento->tipoDocumento->validade_dias }}" title="Reenviar"><i class="bi bi-arrow-repeat"></i></button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                @include('layouts.trade-pagination', ['paginator' => $documentos])
            @else
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-file-earmark-x"></i></div>
                    <h3 class="empty-title">Nenhum documento encontrado</h3>
                    <p class="empty-text">{{ request()->hasAny(['search','tipo_documento_id','status_filtro']) ? 'Não há documentos com os filtros selecionados.' : 'Você ainda não enviou nenhum documento.' }}</p>
                    @if (request()->hasAny(['search','tipo_documento_id','status_filtro']))
                        <a href="{{ route('fornecedor.docs_list.index', ['tab'=>'biblioteca']) }}" class="btn-empty"><i class="bi bi-arrow-counterclockwise"></i> <span>Limpar Filtros</span></a>
                    @else
                        <a href="{{ route('fornecedor.docs_enviar.index') }}" class="btn-empty"><i class="bi bi-cloud-upload-fill"></i> <span>Enviar Primeiro Documento</span></a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-eye"></i> Visualizar Documento</h5><button type="button" class="btn-close-modal" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button></div>
            <div class="modal-body p-0"><iframe id="docPreviewFrame" style="width: 100%; height: 70vh; border: none;"></iframe></div>
        </div></div>
    </div>

    <div class="modal fade" id="reenviarModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-arrow-repeat"></i> Reenviar Documento</h5><button type="button" class="btn-close-modal" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button></div>
            <div class="modal-body">
                <div class="modal-subtitle" id="reenviarSubtitulo"></div>
                <form id="reenviarForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="reenviar_doc_id">
                    <div class="form-section" id="reenviar_campo_data" style="display: none;">
                        <label class="form-label"><i class="bi bi-calendar-event"></i> Data de Validade <span class="required">*</span></label>
                        <input type="date" name="data_validade_documento" id="reenviar_data_validade" class="form-control" min="{{ date('Y-m-d') }}">
                        <small class="form-text">Informe a data de vencimento presente no documento</small>
                    </div>
                    <div class="form-section">
                        <label class="form-label"><i class="bi bi-cloud-arrow-up"></i> Novo Arquivo PDF <span class="required">*</span></label>
                        <div class="upload-area" id="reenviarUploadArea">
                            <input type="file" name="arquivo" id="reenviar_arquivo" class="file-input" accept=".pdf" required>
                            <div class="upload-placeholder" id="reenviarPlaceholder"><div class="upload-icon"><i class="bi bi-cloud-arrow-up"></i></div><div class="upload-text"><h4>Clique para selecionar o arquivo</h4><p>Formato: PDF — Tamanho máximo: 45MB</p></div></div>
                            <div class="upload-preview" id="reenviarPreview" style="display: none;"><div class="preview-icon"><i class="bi bi-file-pdf-fill"></i></div><div class="preview-info"><p class="preview-name" id="reenviarFileName"></p><p class="preview-size" id="reenviarFileSize"></p></div><button type="button" class="btn-remove-file" id="reenviarBtnRemove"><i class="bi bi-trash"></i></button></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> <span>Cancelar</span></button>
                <button type="button" class="btn-primary" id="reenviarSubmitBtn"><i class="bi bi-arrow-repeat"></i> <span>Reenviar Documento</span></button>
            </div>
        </div></div>
    </div>

    <div class="modal fade" id="historicoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-clock-history"></i> Histórico de Validações</h5><button type="button" class="btn-close-modal" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button></div>
            <div class="modal-body" id="historicoContent"><div class="loading-state"><div class="spinner-border" role="status"></div><p>Carregando...</p></div></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.doc-tab').forEach(function(t) { t.addEventListener('click', function() { switchTab(this.dataset.tab); }); });
            var cb = document.querySelector('.search-clear');
            if (cb) cb.addEventListener('click', function() { document.querySelector('.search-input').value = ''; document.getElementById('filterForm').submit(); });
            document.querySelectorAll('.search-input').forEach(function(i) { i.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); document.getElementById('filterForm').submit(); } }); });
            document.querySelectorAll('.remove-filter').forEach(function(b) { b.addEventListener('click', function() { var u = new URL(window.location.href); u.searchParams.delete(this.dataset.filter); u.searchParams.set('tab', 'biblioteca'); window.location.href = u.toString(); }); });
            var tc = document.getElementById('toggleConcluidos');
            if (tc) tc.addEventListener('click', function() { var l = document.getElementById('listaConcluidos'); var i = this.querySelector('i'); l.style.display = l.style.display === 'none' ? 'block' : 'none'; i.className = l.style.display === 'none' ? 'bi bi-chevron-down' : 'bi bi-chevron-up'; });
            document.querySelectorAll('[data-action="preview"]').forEach(function(b) { b.addEventListener('click', function() { document.getElementById('docPreviewFrame').src = '/fornecedor/documentos/' + this.dataset.docId + '/arquivo/' + this.dataset.arquivoId + '/visualizar'; new bootstrap.Modal(document.getElementById('previewModal')).show(); }); });
            document.querySelectorAll('[data-action="history"]').forEach(function(b) {
                b.addEventListener('click', function() {
                    var c = document.getElementById('historicoContent');
                    c.innerHTML = '<div class="loading-state"><div class="spinner-border" role="status"></div><p>Carregando...</p></div>';
                    new bootstrap.Modal(document.getElementById('historicoModal')).show();
                    fetch('/fornecedor/documentos/' + this.dataset.docId + '/historico').then(function(r) { return r.json(); }).then(function(d) {
                        if (!d.success || !d.validacoes || d.validacoes.length === 0) { c.innerHTML = '<div class="empty-hist"><i class="bi bi-clock-history"></i><p>Nenhuma validação registrada.</p></div>'; return; }
                        var h = '<div class="timeline">';
                        d.validacoes.forEach(function(v) { var sn = v.status ? v.status.nome : '?'; var bc = sn === 'Aprovado' ? 'bg-success' : (sn === 'Reprovado' ? 'bg-danger' : 'bg-warning'); var op = v.operacional && v.operacional.user ? v.operacional.user.name : 'Sistema'; var dt = new Date(v.created_at).toLocaleString('pt-BR'); h += '<div class="timeline-item"><div class="timeline-badge ' + bc + '"><i class="bi bi-check-lg"></i></div><div class="timeline-content"><h6>' + sn + '</h6><small>' + op + ' — ' + dt + '</small>'; if (v.observacoes) h += '<div class="tl-obs"><i class="bi bi-chat-square-text"></i> ' + v.observacoes + '</div>'; h += '</div></div>'; });
                        c.innerHTML = h + '</div>';
                    }).catch(function() { c.innerHTML = '<div class="empty-hist error"><i class="bi bi-exclamation-triangle"></i><p>Erro ao carregar.</p></div>'; });
                });
            });
            document.querySelectorAll('[data-action="resend"]').forEach(function(b) {
                b.addEventListener('click', function() {
                    document.getElementById('reenviar_doc_id').value = this.dataset.docId;
                    document.getElementById('reenviarSubtitulo').textContent = 'Reenviar: ' + this.dataset.tipoNome;
                    var cd = document.getElementById('reenviar_campo_data'); var vl = this.dataset.validar === '1'; var vd = parseInt(this.dataset.validadeDias) || 0;
                    cd.style.display = (vl && vd > 0) ? 'block' : 'none'; document.getElementById('reenviar_data_validade').required = (vl && vd > 0);
                    document.getElementById('reenviarPlaceholder').style.display = ''; document.getElementById('reenviarPreview').style.display = 'none';
                    document.getElementById('reenviar_arquivo').value = ''; document.getElementById('reenviar_data_validade').value = '';
                    new bootstrap.Modal(document.getElementById('reenviarModal')).show();
                });
            });
            var ra = document.getElementById('reenviar_arquivo');
            if (ra) ra.addEventListener('change', function() { if (this.files && this.files[0]) { document.getElementById('reenviarFileName').textContent = this.files[0].name; document.getElementById('reenviarFileSize').textContent = (this.files[0].size/(1024*1024)).toFixed(2)+' MB'; document.getElementById('reenviarPlaceholder').style.display = 'none'; document.getElementById('reenviarPreview').style.display = 'flex'; } });
            var rr = document.getElementById('reenviarBtnRemove');
            if (rr) rr.addEventListener('click', function() { document.getElementById('reenviar_arquivo').value = ''; document.getElementById('reenviarPlaceholder').style.display = ''; document.getElementById('reenviarPreview').style.display = 'none'; });
            var rs = document.getElementById('reenviarSubmitBtn');
            if (rs) rs.addEventListener('click', function() {
                var di = document.getElementById('reenviar_doc_id').value; var aq = document.getElementById('reenviar_arquivo').files[0];
                if (!aq) { toastr.error('Selecione um arquivo PDF.'); return; }
                if (aq.size > 45*1024*1024) { toastr.error('Arquivo excede 45MB.'); return; }
                var dv = document.getElementById('reenviar_data_validade');
                if (document.getElementById('reenviar_campo_data').style.display !== 'none' && dv.required && !dv.value) { toastr.error('Informe a data de validade.'); return; }
                var bt = this; bt.disabled = true; bt.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Enviando...';
                var fd = new FormData(); fd.append('_token', document.querySelector('meta[name="csrf-token"]').content); fd.append('arquivo', aq);
                if (dv.value) fd.append('data_validade_documento', dv.value);
                fetch('/fornecedor/documentos/' + di + '/reenviar', { method: 'POST', body: fd, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function(r) { return r.json(); }).then(function(d) {
                    if (d.success) { toastr.success(d.message || 'Documento reenviado!'); bootstrap.Modal.getInstance(document.getElementById('reenviarModal')).hide(); setTimeout(function() { location.reload(); }, 1000); }
                    else { toastr.error(d.message || 'Erro ao reenviar.'); bt.disabled = false; bt.innerHTML = '<i class="bi bi-arrow-repeat"></i> <span>Reenviar Documento</span>'; }
                }).catch(function() { toastr.error('Erro de conexão.'); bt.disabled = false; bt.innerHTML = '<i class="bi bi-arrow-repeat"></i> <span>Reenviar Documento</span>'; });
            });
        });
        function switchTab(t) { document.querySelectorAll('.doc-tab').forEach(function(e) { e.classList.remove('active'); }); document.querySelectorAll('.tab-panel').forEach(function(e) { e.classList.remove('active'); }); var tb = document.querySelector('[data-tab="'+t+'"]'); var pn = document.getElementById('panel-'+t); if (tb) tb.classList.add('active'); if (pn) pn.classList.add('active'); }
    </script>
@endpush

@push('styles')
    <style>
        /* ============================================================
           HEADER
           ============================================================ */

        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.875rem 1.75rem;
            background: var(--bg-card);
            color: var(--text-color);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary i {
            font-size: 1.1rem;
            opacity: 0.8;
        }

        .btn-secondary:hover {
            background: var(--bg-body);
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(0, 94, 130, 0.15);
        }

        /* ============================================================
           TABS
           ============================================================ */

        .tabs-bar {
            display: flex;
            gap: 4px;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 2rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            background: var(--bg-card);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            padding: 0 0.5rem;
            box-shadow: var(--shadow-sm);
        }

        .doc-tab {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 1.25rem;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text-muted);
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            white-space: nowrap;
            transition: all var(--transition);
            margin-bottom: -2px;
        }

        .doc-tab:hover {
            color: var(--text-color);
            background: rgba(0, 94, 130, 0.05);
        }

        .doc-tab.active {
            color: var(--primary-color);
            font-weight: 600;
            border-bottom-color: var(--primary-color);
        }

        .doc-tab i {
            font-size: 1.1rem;
        }

        .tab-badge {
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .tab-badge-danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: var(--danger-color);
        }

        .tab-badge-info {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #2563eb;
        }

        .tab-badge-neutral {
            background: rgba(0, 94, 130, 0.08);
            color: var(--primary-color);
        }

        [data-theme="dark"] .tab-badge-danger {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        [data-theme="dark"] .tab-badge-info {
            background: rgba(59, 130, 246, 0.2);
            color: #93c5fd;
        }

        [data-theme="dark"] .tab-badge-neutral {
            background: rgba(0, 136, 184, 0.15);
            color: var(--primary-light);
        }

        .tab-panel {
            display: none;
            animation: fadeInUp 0.4s ease;
        }

        .tab-panel.active {
            display: block;
        }

        /* ============================================================
           ABA 1: AÇÃO NECESSÁRIA
           ============================================================ */

        .action-stats {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.25rem;
            padding: 1rem 1.25rem;
            background: var(--bg-card);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .action-stat {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .action-stat strong {
            color: var(--text-color);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .action-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .action-dot-danger {
            background: var(--danger-color);
            box-shadow: 0 0 8px rgba(239, 68, 68, 0.4);
        }

        .action-dot-warning {
            background: var(--warning-color);
            box-shadow: 0 0 8px rgba(245, 158, 11, 0.4);
        }

        .action-dot-muted {
            background: var(--text-muted);
        }

        .action-dot-success {
            background: var(--success-color);
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.4);
        }

        .action-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .action-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.25rem 1.5rem;
            background: var(--bg-card);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            border-left: 4px solid;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition);
        }

        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .action-card-danger {
            border-left-color: var(--danger-color);
        }

        .action-card-warning {
            border-left-color: var(--warning-color);
        }

        .action-card-muted {
            border-left-color: var(--text-muted);
        }

        .action-card-left {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            flex: 1;
            min-width: 0;
        }

        .action-card-icon {
            width: 44px;
            height: 44px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.2rem;
        }

        .action-icon-danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: var(--danger-color);
        }

        .action-icon-warning {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #d97706;
        }

        .action-icon-muted {
            background: rgba(0, 94, 130, 0.08);
            color: var(--primary-color);
        }

        [data-theme="dark"] .action-icon-danger {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
        }

        [data-theme="dark"] .action-icon-warning {
            background: rgba(245, 158, 11, 0.15);
            color: #fcd34d;
        }

        [data-theme="dark"] .action-icon-muted {
            background: rgba(0, 136, 184, 0.15);
            color: var(--primary-light);
        }

        .action-card-info {
            min-width: 0;
            flex: 1;
        }

        .action-card-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.375rem;
        }

        .action-card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .action-card-meta i {
            margin-right: 4px;
            opacity: 0.7;
        }

        .action-card-reason {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: var(--danger-color);
            display: flex;
            align-items: flex-start;
            gap: 0.375rem;
            padding: 0.5rem 0.75rem;
            background: rgba(239, 68, 68, 0.05);
            border-radius: var(--radius-sm);
        }

        .action-reason-warning {
            color: var(--warning-color);
            background: rgba(245, 158, 11, 0.05);
        }

        [data-theme="dark"] .action-card-reason {
            color: #fca5a5;
            background: rgba(239, 68, 68, 0.1);
        }

        [data-theme="dark"] .action-reason-warning {
            color: #fcd34d;
            background: rgba(245, 158, 11, 0.1);
        }

        .action-card-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            border-radius: var(--radius);
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
            flex-shrink: 0;
            transition: all var(--transition);
        }

        .action-btn-danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: var(--danger-color);
        }

        .action-btn-danger:hover {
            background: linear-gradient(135deg, #fecaca, #fca5a5);
            color: #b91c1c;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
        }

        .action-btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            box-shadow: 0 4px 14px rgba(0, 94, 130, 0.25);
        }

        .action-btn-primary:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 94, 130, 0.35);
        }

        .action-btn-warning {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #d97706;
        }

        .action-btn-warning:hover {
            background: linear-gradient(135deg, #fde68a, #fcd34d);
            color: #b45309;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
        }

        [data-theme="dark"] .action-btn-danger {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        [data-theme="dark"] .action-btn-warning {
            background: rgba(245, 158, 11, 0.2);
            color: #fcd34d;
        }

        /* ============================================================
           ABA 2: POR PI
           ============================================================ */

        .pi-section-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }

        .pi-section-label i {
            color: var(--primary-color);
        }

        .pi-section-concluidos {
            margin-top: 2rem;
        }

        .toggle-concluidos {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            padding: 0.25rem 0.5rem;
            margin-left: auto;
            border-radius: var(--radius-sm);
            transition: all var(--transition);
            font-size: 1rem;
        }

        .toggle-concluidos:hover {
            background: rgba(0, 94, 130, 0.08);
            color: var(--primary-color);
        }

        .pi-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .pi-card {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition);
        }

        .pi-card:hover {
            border-color: var(--primary-color);
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }

        .pi-card-concluido {
            opacity: 0.6;
        }

        .pi-card-concluido:hover {
            opacity: 1;
        }

        .pi-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .pi-card-title-area {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .pi-card-number {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-color);
        }

        .pi-card-client {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .pi-card-veiculo {
            font-size: 0.7rem;
            padding: 2px 10px;
            border-radius: 20px;
            background: rgba(0, 94, 130, 0.08);
            color: var(--primary-color);
            font-weight: 600;
        }

        [data-theme="dark"] .pi-card-veiculo {
            background: rgba(0, 136, 184, 0.15);
            color: var(--primary-light);
        }

        .pi-card-progress-badge {
            font-size: 0.85rem;
            font-weight: 700;
            padding: 0.375rem 0.875rem;
            border-radius: var(--radius);
        }

        .progress-green {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #059669;
        }

        .progress-amber {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #d97706;
        }

        .progress-red {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: var(--danger-color);
        }

        [data-theme="dark"] .progress-green {
            background: rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
        }

        [data-theme="dark"] .progress-amber {
            background: rgba(245, 158, 11, 0.2);
            color: #fcd34d;
        }

        [data-theme="dark"] .progress-red {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .pi-progress-bar {
            height: 6px;
            background: var(--border-color);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 0.75rem;
        }

        .pi-progress-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.5s ease;
        }

        .pi-progress-fill.progress-green {
            background: linear-gradient(90deg, #10b981, #34d399);
        }

        .pi-progress-fill.progress-amber {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }

        .pi-progress-fill.progress-red {
            background: linear-gradient(90deg, #ef4444, #f87171);
        }

        .pi-docs-list {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }

        .pi-doc-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 0.75rem;
            background: var(--bg-body);
            border-radius: var(--radius);
            font-size: 0.85rem;
            transition: background var(--transition-fast);
        }

        .pi-doc-item:hover {
            background: rgba(0, 94, 130, 0.04);
        }

        .pi-doc-left {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 0;
        }

        .pi-doc-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .dot-green { background: var(--success-color); }
        .dot-amber { background: var(--warning-color); }
        .dot-red { background: var(--danger-color); }
        .dot-gray { background: var(--text-muted); }

        .pi-doc-name {
            color: var(--text-color);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pi-doc-status {
            font-weight: 600;
            white-space: nowrap;
            font-size: 0.8rem;
        }

        .color-success { color: #059669; }
        .color-warning { color: #d97706; }
        .color-danger { color: var(--danger-color); }
        .color-muted { color: var(--text-muted); }

        [data-theme="dark"] .color-success { color: #6ee7b7; }
        [data-theme="dark"] .color-warning { color: #fcd34d; }
        [data-theme="dark"] .color-danger { color: #fca5a5; }

        .pi-card-footer {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--border-color);
        }

        .pi-card-action {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all var(--transition);
        }

        .pi-card-action:hover {
            color: var(--primary-dark);
            transform: translateX(3px);
        }

        .pi-card-action i {
            transition: transform var(--transition);
        }

        .pi-card-action:hover i {
            transform: scale(1.15);
        }

        /* ============================================================
           ABA 3: BIBLIOTECA
           ============================================================ */

        .bib-stats {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 1.25rem;
        }

        .bib-stat {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-muted);
            padding: 0.5rem 1rem;
            background: var(--bg-card);
            border-radius: 20px;
            border: 1px solid var(--border-color);
            font-weight: 500;
        }

        .bib-stat strong {
            color: var(--text-color);
            font-weight: 700;
        }

        .bib-toolbar {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 1.25rem;
        }

        .bib-active-filters {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            padding: 0.75rem 1rem;
            background: rgba(0, 94, 130, 0.04);
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
        }

        [data-theme="dark"] .bib-active-filters {
            background: rgba(56, 189, 248, 0.08);
        }

        .filters-label {
            color: var(--text-muted);
            font-weight: 600;
        }

        .filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.75rem;
            background: rgba(0, 94, 130, 0.1);
            color: var(--primary-color);
            border-radius: var(--radius-sm);
            font-size: 0.8rem;
            font-weight: 600;
        }

        [data-theme="dark"] .filter-tag {
            background: rgba(0, 136, 184, 0.2);
            color: var(--primary-light);
        }

        .filter-tag button {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 1.1rem;
            line-height: 1;
            padding: 0;
            opacity: 0.7;
            transition: opacity var(--transition-fast);
        }

        .filter-tag button:hover {
            opacity: 1;
        }

        .clear-all-link {
            font-size: 0.8rem;
            color: var(--danger-color);
            text-decoration: none;
            font-weight: 600;
            margin-left: auto;
        }

        .clear-all-link:hover {
            color: var(--secondary-dark);
            text-decoration: underline;
        }

        .bib-list {
            display: flex;
            flex-direction: column;
            gap: 0.625rem;
            margin-bottom: 1.5rem;
        }

        .bib-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            transition: all var(--transition);
        }

        .bib-card:hover {
            border-color: var(--primary-color);
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }

        .bib-icon {
            width: 44px;
            height: 44px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 800;
            flex-shrink: 0;
        }

        .bib-icon.valid {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #059669;
        }

        .bib-icon.expiring {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #d97706;
        }

        .bib-icon.expired {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: var(--danger-color);
        }

        [data-theme="dark"] .bib-icon.valid {
            background: rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
        }

        [data-theme="dark"] .bib-icon.expiring {
            background: rgba(245, 158, 11, 0.2);
            color: #fcd34d;
        }

        [data-theme="dark"] .bib-icon.expired {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .bib-info {
            flex: 1;
            min-width: 0;
        }

        .bib-name {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-color);
        }

        .bib-detail {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.125rem;
        }

        .bib-reason {
            font-size: 0.8rem;
            color: var(--danger-color);
            margin-top: 0.375rem;
            display: flex;
            align-items: flex-start;
            gap: 0.25rem;
        }

        [data-theme="dark"] .bib-reason {
            color: #fca5a5;
        }

        .bib-right {
            text-align: right;
            flex-shrink: 0;
            min-width: 110px;
        }

        .bib-validity {
            font-size: 0.85rem;
            font-weight: 700;
        }

        .bib-validity.valid { color: #059669; }
        .bib-validity.expiring { color: #d97706; }
        .bib-validity.expired { color: var(--danger-color); }

        [data-theme="dark"] .bib-validity.valid { color: #6ee7b7; }
        [data-theme="dark"] .bib-validity.expiring { color: #fcd34d; }
        [data-theme="dark"] .bib-validity.expired { color: #fca5a5; }

        .bib-pis {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.125rem;
        }

        .bib-actions {
            display: flex;
            gap: 0.375rem;
            flex-shrink: 0;
        }

        .bib-action-btn {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            background: transparent;
            color: var(--text-muted);
            cursor: pointer;
            text-decoration: none;
            transition: all var(--transition);
            font-size: 0.95rem;
        }

        .bib-action-btn:hover {
            background: rgba(0, 94, 130, 0.08);
            color: var(--primary-color);
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .bib-action-resend {
            color: var(--danger-color);
            border-color: rgba(239, 68, 68, 0.3);
        }

        .bib-action-resend:hover {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #b91c1c;
            border-color: var(--danger-color);
        }

        [data-theme="dark"] .bib-action-btn:hover {
            background: rgba(0, 136, 184, 0.15);
        }

        /* ============================================================
           MODAIS — content, header, body, footer
           ============================================================ */

        #previewModal .modal-content,
        #reenviarModal .modal-content,
        #historicoModal .modal-content {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-xl);
        }

        #previewModal .modal-header,
        #reenviarModal .modal-header,
        #historicoModal .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            border-bottom: none;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        #previewModal .modal-header .modal-title,
        #reenviarModal .modal-header .modal-title,
        #historicoModal .modal-header .modal-title {
            color: white;
            font-weight: 700;
            font-size: 1.125rem;
            display: flex;
            align-items: center;
            gap: 0.625rem;
            margin: 0;
        }

        #previewModal .modal-header .modal-title i,
        #reenviarModal .modal-header .modal-title i,
        #historicoModal .modal-header .modal-title i {
            font-size: 1.25rem;
            opacity: 0.9;
        }

        .btn-close-modal {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition);
            padding: 0;
            font-size: 1rem;
        }

        .btn-close-modal:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.4);
            transform: scale(1.05);
        }

        #reenviarModal .modal-body,
        #historicoModal .modal-body {
            padding: 2rem;
            background: var(--bg-card);
        }

        #reenviarModal .modal-footer {
            background: var(--bg-body);
            border-top: 1px solid var(--border-color);
            padding: 1.25rem 2rem;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        #reenviarModal .modal-footer .btn-secondary {
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        #reenviarModal .modal-footer .btn-secondary:hover {
            background: var(--bg-body);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        #reenviarModal .modal-footer .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            border: none;
            border-radius: var(--radius);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all var(--transition);
            box-shadow: 0 4px 14px rgba(0, 94, 130, 0.25);
        }

        #reenviarModal .modal-footer .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 94, 130, 0.35);
        }

        #reenviarModal .modal-footer .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .modal-subtitle {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 1.5rem;
            padding: 0.75rem 1rem;
            background: var(--bg-body);
            border-radius: var(--radius);
            border-left: 3px solid var(--primary-color);
        }

        /* ============================================================
           FORMULÁRIO (dentro do modal Reenviar)
           ============================================================ */

        .form-section {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-size: 0.95rem;
        }

        .form-label i {
            color: var(--primary-color);
            font-size: 1rem;
        }

        .required {
            color: var(--danger-color);
        }

        .form-text {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.375rem;
        }

        #reenviarModal .form-control {
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 0.95rem;
            background: var(--bg-body);
            color: var(--text-color);
            transition: all var(--transition);
        }

        #reenviarModal .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 94, 130, 0.1);
        }

        /* ============================================================
           UPLOAD AREA
           ============================================================ */

        .upload-area {
            position: relative;
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-md);
            padding: 2.5rem 2rem;
            text-align: center;
            cursor: pointer;
            transition: all var(--transition-slow);
            background: var(--bg-body);
        }

        .upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(0, 94, 130, 0.03);
        }

        .file-input {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .upload-placeholder {
            pointer-events: none;
        }

        .upload-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 20px rgba(0, 94, 130, 0.25);
        }

        .upload-icon i {
            font-size: 2rem;
            color: white;
        }

        .upload-text h4 {
            font-size: 1rem;
            color: var(--text-color);
            margin: 0 0 0.25rem;
            font-weight: 600;
        }

        .upload-text p {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin: 0;
        }

        .upload-preview {
            padding: 1rem;
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            border-radius: var(--radius-md);
            border: 2px solid var(--success-color);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        [data-theme="dark"] .upload-preview {
            background: rgba(16, 185, 129, 0.1);
            border-color: #059669;
        }

        .preview-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        [data-theme="dark"] .preview-icon {
            background: rgba(16, 185, 129, 0.2);
        }

        .preview-icon i {
            font-size: 2rem;
            color: #059669;
        }

        .preview-info {
            flex: 1;
            text-align: left;
        }

        .preview-name {
            font-weight: 700;
            color: #065f46;
            margin-bottom: 0.25rem;
        }

        [data-theme="dark"] .preview-name {
            color: #a7f3d0;
        }

        .preview-size {
            color: #047857;
            font-size: 0.875rem;
            margin: 0;
        }

        [data-theme="dark"] .preview-size {
            color: #6ee7b7;
        }

        .btn-remove-file {
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

        .btn-remove-file:hover {
            background: #dc2626;
            color: white;
            transform: scale(1.05);
        }

        /* ============================================================
           TIMELINE (Modal Histórico)
           ============================================================ */

        .timeline {
            padding: 1rem 0;
        }

        .timeline-item {
            display: flex;
            margin-bottom: 1.5rem;
        }

        .timeline-badge {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }

        .timeline-badge.bg-success {
            background: linear-gradient(135deg, #10b981, #34d399);
        }

        .timeline-badge.bg-danger {
            background: linear-gradient(135deg, #ef4444, #f87171);
        }

        .timeline-badge.bg-warning {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
        }

        .timeline-content {
            margin-left: 1rem;
            flex: 1;
            background: var(--bg-body);
            padding: 1rem 1.25rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }

        .timeline-content h6 {
            margin: 0 0 0.375rem 0;
            color: var(--text-color);
            font-weight: 700;
            font-size: 0.95rem;
        }

        .timeline-content small {
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        .tl-obs {
            margin-top: 0.625rem;
            padding: 0.625rem 0.875rem;
            background: var(--bg-card);
            border-radius: var(--radius);
            font-size: 0.85rem;
            color: var(--text-muted);
            border-left: 3px solid var(--border-color);
        }

        .tl-obs i {
            margin-right: 0.25rem;
            opacity: 0.7;
        }

        /* ============================================================
           LOADING / EMPTY STATES
           ============================================================ */

        .loading-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .loading-state .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: 0.3rem;
            color: var(--primary-color);
        }

        .loading-state p {
            margin-top: 1.5rem;
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .empty-hist {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-muted);
        }

        .empty-hist i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.4;
            display: block;
            color: var(--text-muted);
        }

        .empty-hist.error i {
            color: var(--danger-color);
            opacity: 1;
        }

        .empty-hist p {
            margin: 0;
            font-size: 0.95rem;
        }

        /* ============================================================
           RESPONSIVE
           ============================================================ */

        @media (max-width: 992px) {
            .header-actions {
                flex-direction: column;
                width: 100%;
            }

            .btn-secondary,
            .btn-create {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .tabs-bar {
                gap: 0;
                padding: 0;
            }

            .doc-tab {
                padding: 0.875rem 1rem;
                font-size: 0.85rem;
            }

            .tab-text {
                display: none;
            }

            .doc-tab i {
                font-size: 1.2rem;
            }

            .action-card {
                flex-direction: column;
                align-items: stretch;
            }

            .action-card-btn {
                justify-content: center;
            }

            .action-card-icon {
                display: none;
            }

            .bib-card {
                flex-wrap: wrap;
            }

            .bib-right {
                width: 100%;
                text-align: left;
                margin-top: 0.5rem;
                padding-top: 0.5rem;
                border-top: 1px solid var(--border-color);
                display: flex;
                justify-content: space-between;
            }

            .bib-actions {
                width: 100%;
                justify-content: flex-end;
                margin-top: 0.5rem;
            }

            .bib-toolbar {
                flex-direction: column;
            }

            .filter-select {
                width: 100%;
            }

            #previewModal .modal-header,
            #reenviarModal .modal-header,
            #historicoModal .modal-header {
                padding: 1.25rem 1.5rem;
            }

            #reenviarModal .modal-body,
            #historicoModal .modal-body {
                padding: 1.5rem;
            }

            #reenviarModal .modal-footer {
                padding: 1rem 1.5rem;
            }

            .btn-close-modal {
                width: 36px;
                height: 36px;
            }
        }

        @media (max-width: 576px) {
            .action-stats {
                flex-direction: column;
                gap: 0.5rem;
            }

            .action-card-meta {
                flex-direction: column;
                gap: 0.25rem;
            }

            .pi-card-title-area {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.25rem;
            }

            .pi-card {
                padding: 1rem;
            }

            .bib-stats {
                flex-direction: column;
            }

            .bib-icon {
                display: none;
            }

            #reenviarModal .modal-footer {
                flex-direction: column;
            }

            #reenviarModal .modal-footer .btn-secondary,
            #reenviarModal .modal-footer .btn-primary {
                width: 100%;
                justify-content: center;
            }

            .upload-area {
                padding: 2rem 1.5rem;
            }

            .upload-icon {
                width: 60px;
                height: 60px;
            }

            .upload-icon i {
                font-size: 1.5rem;
            }
        }
    </style>
@endpush