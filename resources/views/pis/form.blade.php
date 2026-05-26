{{-- resources/views/pis/form.blade.php --}}
@extends('layouts.app')

@section('title', isset($pi) ? 'Editar PI' : 'Registrar PI')

@section('content')
    <div class="container-fluid">
        <div class="page-header">
            <div class="page-header-content">
                <div class="page-title-wrapper">
                    <a href="{{ route('pis.index') }}" class="page-back-btn">
                        <i class="bi bi-arrow-left-circle"></i>
                    </a>
                    <div class="page-icon">
                        <i class="bi bi-file-earmark-plus"></i>
                    </div>
                    <div>
                        <h1 class="page-title">
                            {{ isset($pi) ? 'Editar Pedido de Inserção' : 'Registro de Pedido de Inserção' }}
                        </h1>
                        <p class="page-subtitle">
                            {{ isset($pi) ? 'Atualize as informações do PI ' . $pi->numero_pi : 'Preencha os dados para criar um registrar PI no sistema' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert-card alert-danger">
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

        <div class="tabs-nav">
            <button type="button" class="tab-button active" data-tab="essencial">
                <i class="bi bi-file-earmark-text-fill"></i>
                <span>Dados Essenciais</span>
                <span class="tab-required">Obrigatório</span>
            </button>
            <button type="button" class="tab-button" data-tab="complementar">
                <i class="bi bi-info-circle-fill"></i>
                <span>Informações Complementares</span>
                <span class="tab-optional">Opcional</span>
            </button>
            <button type="button" class="tab-button" data-tab="valores" style="display: none;">
                <i class="bi bi-cash-coin"></i>
                <span>Valores Financeiros</span>
                <span class="tab-optional">Opcional</span>
            </button>
        </div>

        <form action="{{ isset($pi) ? route('pis.update', $pi) : route('pis.store') }}" method="POST" id="piForm"
            enctype="multipart/form-data">
            @csrf
            @if (isset($pi))
                @method('PUT')
            @endif

            <div class="tab-content-area active" id="tab-essencial">
                <div class="content-card">
                    <div class="card-header-section">
                        <h2 class="card-title">
                            <i class="bi bi-file-pdf-fill"></i>
                            Documento da PI
                        </h2>
                        <p class="card-subtitle">Upload do documento oficial do Pedido de Inserção</p>
                    </div>

                    <div class="card-content">
                        @if (isset($pi) && $pi->file_path)
                            <div class="current-file-display" id="currentFileDisplay">
                                <div class="file-display-header">
                                    <div class="file-display-icon">
                                        <i class="bi bi-file-pdf-fill"></i>
                                    </div>
                                    <div class="file-display-info">
                                        <h4 class="file-display-name">{{ basename($pi->file_path) }}</h4>
                                        <p class="file-display-meta">
                                            <span>
                                                <i class="bi bi-hdd"></i>
                                                @php
                                                    $filePath = storage_path('app/' . $pi->file_path);
                                                    $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
                                                    $fileSizeMB = number_format($fileSize / 1048576, 2);
                                                @endphp
                                                {{ $fileSizeMB }} MB
                                            </span>
                                            <span>
                                                <i class="bi bi-calendar"></i>
                                                {{ $pi->updated_at->format('d/m/Y H:i') }}
                                            </span>
                                        </p>
                                    </div>
                                    <div class="file-display-actions">
                                        <a href="{{ route('pis.download_documento', $pi) }}"
                                            class="btn-file-action btn-download" target="_blank" title="Baixar documento">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        @hasanyrole('admin|gerencial')
                                            <button type="button" class="btn-file-action btn-replace" id="btnReplaceFile"
                                                title="Substituir documento">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                            <button type="button" class="btn-file-action btn-remove" id="btnRemoveCurrentFile"
                                                title="Remover documento">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endhasanyrole
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="remove_current_file" id="removeCurrentFile" value="0">
                        @endif

                        <div class="upload-container" id="uploadContainer"
                            style="{{ isset($pi) && $pi->file_path ? 'display: none;' : '' }}">
                            <div class="upload-area" id="uploadArea">
                                <input type="file" name="documento_pi" id="documentoPi" class="file-input"
                                    accept="application/pdf">

                                <div class="upload-placeholder" id="uploadPlaceholder">
                                    <div class="upload-icon">
                                        <i class="bi bi-cloud-arrow-up-fill"></i>
                                    </div>
                                    <h3 class="upload-title">Arraste o arquivo PDF aqui</h3>
                                    <p class="upload-text">ou clique para selecionar</p>
                                    <div class="upload-specs">
                                        <span class="spec-item">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                            Apenas PDF
                                        </span>
                                        <span class="spec-item">
                                            <i class="bi bi-hdd"></i>
                                            Máximo 45MB
                                        </span>
                                    </div>
                                    @unless (isset($pi))
                                        <span class="upload-required-badge">
                                            <i class="bi bi-exclamation-circle"></i>
                                            Campo obrigatório
                                        </span>
                                    @endunless
                                </div>

                                <div class="upload-preview" id="uploadPreview" style="display: none;">
                                    <div class="preview-card">
                                        <button type="button" class="btn-remove-preview" id="btnRemovePreview">
                                            <i class="bi bi-x-circle-fill"></i>
                                        </button>
                                        <div class="preview-icon">
                                            <i class="bi bi-file-pdf-fill"></i>
                                        </div>
                                        <div class="preview-info">
                                            <h4 class="preview-name" id="previewName"></h4>
                                            <p class="preview-size" id="previewSize"></p>
                                            <p class="preview-status" id="previewStatus">Arquivo pronto para upload</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @error('documento_pi')
                                <span class="field-error-message">
                                    <i class="bi bi-exclamation-circle"></i>
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header-section">
                        <h2 class="card-title">
                            <i class="bi bi-card-heading"></i>
                            Identificação do PI
                        </h2>
                        <p class="card-subtitle">Dados básicos e obrigatórios do pedido</p>
                    </div>

                    <div class="card-content">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="field-label-new">
                                    <span>Número do PI</span>
                                    <span class="required-star">*</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-hash input-icon-left"></i>
                                    <input type="text" name="numero_pi" id="numero_pi"
                                        class="form-input-new @error('numero_pi') is-invalid @enderror"
                                        placeholder="000000" value="{{ old('numero_pi', $pi->numero_pi ?? '') }}"
                                        required autofocus maxlength="6">
                                </div>
                                @error('numero_pi')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                                <div id="piNumberFeedback" style="display: none; margin-top: 0.5rem;">
                                    <span id="piNumberMessage"></span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="field-label-new">
                                    <span>Cliente</span>
                                    <span class="required-star">*</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-building-fill input-icon-left"></i>
                                    <select name="cliente_id" id="cliente_id"
                                        class="form-select-new @error('cliente_id') is-invalid @enderror" required>
                                        <option value="">Selecione o cliente</option>
                                        @foreach ($clientes as $cliente)
                                            <option value="{{ $cliente->id }}"
                                                {{ old('cliente_id', $pi->cliente_id ?? '') == $cliente->id ? 'selected' : '' }}>
                                                {{ $cliente->razao_social }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('cliente_id')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="field-label-new">
                                    <span>Fornecedor</span>
                                    @if (Auth::user()->hasRole('fornecedor'))
                                        <span class="optional-badge">Preenchido automaticamente</span>
                                    @else
                                        <span class="required-star">*</span>
                                    @endif
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-collection-play input-icon-left"></i>
                                    @if (Auth::user()->hasRole('fornecedor') && Auth::user()->fornecedor)
                                        <input type="hidden" name="fornecedor_id"
                                            value="{{ Auth::user()->fornecedor->id }}">
                                        <input type="text" class="form-select-new"
                                            value="{{ Auth::user()->fornecedor->razao_social }}" disabled>
                                    @elseif (Auth::user()->hasRole('representante'))
                                        <select name="fornecedor_id" id="fornecedor_id"
                                            class="form-select-new @error('fornecedor_id') is-invalid @enderror" required>
                                            <option value="">Selecione o fornecedor representado</option>
                                            @foreach ($fornecedores as $fornecedor)
                                                <option value="{{ $fornecedor->id }}"
                                                    {{ old('fornecedor_id', $pi->fornecedor_id ?? '') == $fornecedor->id ? 'selected' : '' }}>
                                                    {{ $fornecedor->razao_social }}
                                                    @if ($fornecedor->cnpj)
                                                        ({{ substr($fornecedor->cnpj, 0, 2) }}.{{ substr($fornecedor->cnpj, 2, 3) }}.{{ substr($fornecedor->cnpj, 5, 3) }}/{{ substr($fornecedor->cnpj, 8, 4) }}-{{ substr($fornecedor->cnpj, 12, 2) }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    @else
                                        <select name="fornecedor_id" id="fornecedor_id"
                                            class="form-select-new @error('fornecedor_id') is-invalid @enderror" required>
                                            <option value="">Selecione o fornecedor</option>
                                            @foreach ($fornecedores as $fornecedor)
                                                <option value="{{ $fornecedor->id }}"
                                                    {{ old('fornecedor_id', $pi->fornecedor_id ?? '') == $fornecedor->id ? 'selected' : '' }}>
                                                    {{ $fornecedor->razao_social }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                                @error('fornecedor_id')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="col-12" id="tipoVeiculoSection" style="display: none;">
                                <div class="tipo-veiculo-alert">
                                    <div class="tipo-veiculo-alert-icon">
                                        <i class="bi bi-broadcast"></i>
                                    </div>
                                    <div class="tipo-veiculo-alert-content">
                                        <h4 class="tipo-veiculo-alert-title">Tipo de Veículo Obrigatório</h4>
                                        <p class="tipo-veiculo-alert-text">Este cliente exige a seleção do tipo de veículo.
                                            Os documentos requeridos serão filtrados de acordo com o tipo selecionado.</p>
                                    </div>
                                </div>

                                <label class="field-label-new">
                                    <span>Tipo de Veículo</span>
                                    <span class="required-star" id="tipoVeiculoRequired">*</span>
                                </label>

                                <div class="tipo-veiculo-options" id="tipoVeiculoOptions">
                                </div>

                                <input type="hidden" name="tipo_veiculo_id" id="tipo_veiculo_id"
                                    value="{{ old('tipo_veiculo_id', $pi->tipo_veiculo_id ?? '') }}">

                                @error('tipo_veiculo_id')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-footer">
                            <a href="{{ route('pis.index') }}" class="btn-secondary">
                                <i class="bi bi-x-circle"></i>
                                Cancelar
                            </a>
                            <button type="button" class="btn-primary" id="nextToComplementar">
                                <span>Continuar</span>
                                <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-content-area" id="tab-complementar">
                <div class="content-card">
                    <div class="card-header-section">
                        <h2 class="card-title">
                            <i class="bi bi-info-circle-fill"></i>
                            Informações Adicionais
                        </h2>
                        <p class="card-subtitle">Dados complementares e opcionais do PI</p>
                    </div>

                    <div class="card-content">
                        <div class="row g-3">
                            @hasanyrole('admin|gerencial|operacional')
                                <div class="col-md-6">
                                    <label class="field-label-new">
                                        <span>Status</span>
                                        <span class="optional-badge">Opcional</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="bi bi-flag-fill input-icon-left"></i>
                                        <select name="status_id"
                                            class="form-select-new @error('status_id') is-invalid @enderror">
                                            @foreach ($statusList as $status)
                                                <option value="{{ $status->id }}"
                                                    {{ old('status_id', $pi->status_id ?? 2) == $status->id ? 'selected' : '' }}>
                                                    {{ $status->nome }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('status_id')
                                        <span class="field-error-message">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="field-label-new">
                                        <span>Data de Conclusão</span>
                                        <span class="optional-badge">Opcional</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="bi bi-calendar-check input-icon-left"></i>
                                        <input type="date" name="data_conclusao"
                                            class="form-input-new @error('data_conclusao') is-invalid @enderror"
                                            value="{{ old('data_conclusao', isset($pi) && $pi->data_conclusao ? $pi->data_conclusao->format('Y-m-d') : '') }}">
                                    </div>
                                    @error('data_conclusao')
                                        <span class="field-error-message">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="field-label-new">
                                        <span>PIT Nº</span>
                                        <span class="optional-badge">Opcional</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="bi bi-file-earmark input-icon-left"></i>
                                        <input type="text" name="PIT_n"
                                            class="form-input-new @error('PIT_n') is-invalid @enderror"
                                            placeholder="Número do PIT" value="{{ old('PIT_n', $pi->PIT_n ?? '') }}">
                                    </div>
                                    @error('PIT_n')
                                        <span class="field-error-message">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="field-label-new">
                                        <span>Plano Nº</span>
                                        <span class="optional-badge">Opcional</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="bi bi-diagram-3 input-icon-left"></i>
                                        <input type="text" name="plano_n"
                                            class="form-input-new @error('plano_n') is-invalid @enderror"
                                            placeholder="Número do Plano" value="{{ old('plano_n', $pi->plano_n ?? '') }}">
                                    </div>
                                    @error('plano_n')
                                        <span class="field-error-message">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="field-label-new">
                                        <span>Planilha Nº</span>
                                        <span class="optional-badge">Opcional</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="bi bi-table input-icon-left"></i>
                                        <input type="text" name="planilha_n"
                                            class="form-input-new @error('planilha_n') is-invalid @enderror"
                                            placeholder="Número da Planilha"
                                            value="{{ old('planilha_n', $pi->planilha_n ?? '') }}">
                                    </div>
                                    @error('planilha_n')
                                        <span class="field-error-message">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                @hasanyrole('admin|gerencial')
                                    <div class="col-12" id="responsavelSection">
                                        <label class="field-label-new">
                                            <span>Responsável pelo PI</span>
                                            <span class="info-badge" title="Sistema sugere automaticamente, mas você pode trocar">
                                                <i class="bi bi-shield-exclamation"></i>
                                            </span>
                                        </label>

                                        <div class="responsavel-selector-wrapper">
                                            @if (isset($pi))
                                                @php
                                                    $atribuicao = $pi
                                                        ->atribuicoesOperacionais()
                                                        ->where('ativo', true)
                                                        ->with('operacional.user')
                                                        ->first();
                                                @endphp
                                            @endif

                                            <div class="selected-responsavel-preview" id="previewResponsavel"
                                                style="{{ isset($pi) && $atribuicao ? '' : 'display: none;' }}">
                                                <div class="selected-responsavel-avatar" id="responsavelAvatar">
                                                    {{ isset($pi) && $atribuicao ? mb_strtoupper(mb_substr($atribuicao->operacional->nome_completo, 0, 2, 'UTF-8')) : 'OP' }}
                                                </div>
                                                <div class="selected-responsavel-info">
                                                    <div class="selected-responsavel-name" id="responsavelNome">
                                                        {{ isset($pi) && $atribuicao ? $atribuicao->operacional->nome_completo : '' }}
                                                    </div>
                                                    <div class="selected-responsavel-role" id="responsavelDetalhes">
                                                        {{ isset($pi) && $atribuicao ? $atribuicao->operacional->user->email : '' }}
                                                    </div>
                                                    <div class="selected-responsavel-tipo" id="responsavelTipoLabel">
                                                        {{ isset($pi) && $atribuicao ? 'Operacional' : '' }}
                                                    </div>
                                                </div>
                                                <div class="badge-sugerido" id="badgeSugerido" style="display: none;">
                                                    <i class="bi bi-stars"></i>
                                                    <span>Sugerido</span>
                                                </div>
                                                <button type="button" class="btn-remove-responsavel" id="btnRemoveResponsavel">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>

                                            <div class="search-responsavel-wrapper" id="searchResponsavelWrapper"
                                                style="{{ isset($pi) && $atribuicao ? 'display: none;' : '' }}">
                                                <div class="tipo-responsavel-tabs">
                                                    <button type="button" class="tipo-tab active" data-tipo="operacional">
                                                        <i class="bi bi-person-gear"></i>
                                                        <span>Operacional</span>
                                                    </button>
                                                    <button type="button" class="tipo-tab" data-tipo="gerencial">
                                                        <i class="bi bi-person-badge"></i>
                                                        <span>Gerencial</span>
                                                    </button>
                                                </div>

                                                <div class="search-input-wrapper">
                                                    <input type="text" class="search-input-responsavel" id="searchResponsavel"
                                                        placeholder="Digite para buscar..." autocomplete="off">
                                                    <div class="spinner-border spinner-border-sm search-spinner"
                                                        id="loadingResponsavel"></div>
                                                </div>

                                                <div class="dropdown-responsaveis" id="dropdownResponsaveis">
                                                    <div class="dropdown-empty">
                                                        <i class="bi bi-search"></i>
                                                        <p>Carregando sugestão automática...</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <input type="hidden" name="responsavel_id" id="responsavelId"
                                                value="{{ old('responsavel_id', isset($pi) && $atribuicao ? $atribuicao->operacional_id : '') }}">
                                            <input type="hidden" name="responsavel_tipo" id="responsavelTipo"
                                                value="{{ old('responsavel_tipo', isset($pi) && $atribuicao ? 'operacional' : '') }}">

                                            <span class="field-help-text">
                                                <i class="bi bi-lightbulb"></i>
                                                Sistema distribui automaticamente. Você pode trocar o responsável se desejar.
                                            </span>
                                        </div>
                                    </div>
                                @endhasanyrole
                            @endhasanyrole

                            <div class="col-12">
                                <label class="field-label-new">
                                    <span>Observações</span>
                                    <span class="optional-badge">Opcional</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-chat-text-fill input-icon-left"></i>
                                    <textarea name="observacoes" class="form-textarea-new @error('observacoes') is-invalid @enderror" rows="3"
                                        placeholder="Informações adicionais sobre o PI">{{ old('observacoes', $pi->observacoes ?? '') }}</textarea>
                                </div>
                                @error('observacoes')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-footer">
                            <button type="button" class="btn-secondary" id="backToEssencial">
                                <i class="bi bi-arrow-left-circle"></i>
                                Voltar
                            </button>
                            <button type="submit" class="btn-primary" id="submitBtn">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>{{ isset($pi) ? 'Atualizar PI' : 'Registrar PI' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-content-area" id="tab-valores" style="display: none;">
                <div class="content-card">
                    <div class="card-header-section">
                        <h2 class="card-title">
                            <i class="bi bi-cash-coin"></i>
                            Valores Financeiros
                        </h2>
                        <p class="card-subtitle">Todos os campos financeiros são opcionais</p>
                    </div>

                    <div class="card-content">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="field-label-new">
                                    <span>Total Bruto</span>
                                    <span class="optional-badge">Opcional</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-currency-dollar input-icon-left"></i>
                                    <input type="text" name="total_bruto"
                                        class="form-input-new money-mask @error('total_bruto') is-invalid @enderror"
                                        placeholder="R$ 0,00"
                                        value="{{ old('total_bruto', isset($pi) && $pi->valores ? number_format($pi->valores->total_bruto, 2, ',', '.') : '') }}">
                                </div>
                                @error('total_bruto')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="field-label-new">
                                    <span>Comissão</span>
                                    <span class="optional-badge">Opcional</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-cash-stack input-icon-left"></i>
                                    <input type="text" name="comissao"
                                        class="form-input-new money-mask @error('comissao') is-invalid @enderror"
                                        placeholder="R$ 0,00"
                                        value="{{ old('comissao', isset($pi) && $pi->valores ? number_format($pi->valores->comissao, 2, ',', '.') : '') }}">
                                </div>
                                @error('comissao')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="field-label-new">
                                    <span>Líquido</span>
                                    <span class="optional-badge">Opcional</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-calculator input-icon-left"></i>
                                    <input type="text" name="liquido"
                                        class="form-input-new money-mask @error('liquido') is-invalid @enderror"
                                        placeholder="R$ 0,00"
                                        value="{{ old('liquido', isset($pi) && $pi->valores ? number_format($pi->valores->liquido, 2, ',', '.') : '') }}">
                                </div>
                                @error('liquido')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="field-label-new">
                                    <span>Custo Unitário</span>
                                    <span class="optional-badge">Opcional</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-tag input-icon-left"></i>
                                    <input type="text" name="custo_unitario"
                                        class="form-input-new money-mask @error('custo_unitario') is-invalid @enderror"
                                        placeholder="R$ 0,00"
                                        value="{{ old('custo_unitario', isset($pi) && $pi->valores ? number_format($pi->valores->custo_unitario, 2, ',', '.') : '') }}">
                                </div>
                                @error('custo_unitario')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="field-label-new">
                                    <span>Desconto (%)</span>
                                    <span class="optional-badge">Opcional</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-percent input-icon-left"></i>
                                    <input type="text" name="desconto"
                                        class="form-input-new percent-mask @error('desconto') is-invalid @enderror"
                                        placeholder="0%" maxlength="6"
                                        value="{{ old('desconto', isset($pi) && $pi->valores && $pi->valores->desconto ? number_format($pi->valores->desconto, 2, ',', '') : '') }}">
                                </div>
                                @error('desconto')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="field-label-new">
                                    <span>Custo Total</span>
                                    <span class="optional-badge">Opcional</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-receipt input-icon-left"></i>
                                    <input type="text" name="custo_total"
                                        class="form-input-new money-mask @error('custo_total') is-invalid @enderror"
                                        placeholder="R$ 0,00"
                                        value="{{ old('custo_total', isset($pi) && $pi->valores ? number_format($pi->valores->custo_total, 2, ',', '.') : '') }}">
                                </div>
                                @error('custo_total')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="field-label-new">
                                    <span>Local de Cobrança</span>
                                    <span class="optional-badge">Opcional</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-geo-alt input-icon-left"></i>
                                    <input type="text" name="local_cobranca"
                                        class="form-input-new @error('local_cobranca') is-invalid @enderror"
                                        placeholder="Ex: São Paulo - SP"
                                        value="{{ old('local_cobranca', isset($pi) && $pi->valores ? $pi->valores->local_cobranca : '') }}">
                                </div>
                                @error('local_cobranca')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="field-label-new">
                                    <span>Prazo de Pagamento</span>
                                    <span class="optional-badge">Opcional</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-calendar-week input-icon-left"></i>
                                    <input type="text" name="prazo_pagamento"
                                        class="form-input-new days-mask @error('prazo_pagamento') is-invalid @enderror"
                                        placeholder="Ex: 30" maxlength="5"
                                        value="{{ old('prazo_pagamento', isset($pi) && $pi->valores ? str_replace(' dias', '', $pi->valores->prazo_pagamento) : '') }}">
                                    <div class="input-suffix">dias</div>
                                </div>
                                @error('prazo_pagamento')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-footer">
                            <button type="button" class="btn-secondary" id="backToComplementar">
                                <i class="bi bi-arrow-left-circle"></i>
                                Voltar
                            </button>
                            <button type="submit" class="btn-primary" id="submitBtnValores">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>{{ isset($pi) ? 'Atualizar PI' : 'Registrar PI' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="periodo" id="periodoField" value="{{ old('periodo', $pi->periodo ?? '') }}">
            @unless (isset($pi))
                <input type="hidden" name="status_id" value="2">
            @endunless
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        let numeroPiExtraido = null;

        function usarNumeroPiExtraido() {
            if (numeroPiExtraido) {
                $('#numero_pi').val(numeroPiExtraido);
                $('#piNumberMessage').html(
                    '<i class="bi bi-check-circle-fill" style="color: var(--success-color);"></i> ' +
                    '<span style="color: var(--success-color); font-weight: 600;">Número do PI atualizado para: ' +
                    numeroPiExtraido + '</span>'
                );
                toastr.success('Número do PI atualizado!');
            }
        }

        $(document).ready(function() {
            let selectedFile = null;
            const isEditing = {{ isset($pi) ? 'true' : 'false' }};
            const isFornecedor = {{ Auth::user()->hasRole('fornecedor') ? 'true' : 'false' }};
            const isAdminOrGerencial = {{ Auth::user()->hasAnyRole(['admin', 'gerencial']) ? 'true' : 'false' }};
            let clienteExigeVeiculo = false;
            let tipoVeiculoSelecionado = '{{ old('tipo_veiculo_id', $pi->tipo_veiculo_id ?? '') }}';

            $('#piForm').on('keydown', 'input:not([type="submit"]):not([type="file"]):not([type="hidden"])',
                function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        return false;
                    }
                });

            let verificarPiTimeout = null;
            let piDuplicadoEncontrado = false;
            let ultimoNumeroVerificado = null;

            function escapeHtmlPi(str) {
                if (str === null || str === undefined) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function mostrarModalPiDuplicado(p) {
                const cliente = p.cliente ? escapeHtmlPi(p.cliente) : '<span style="color: var(--text-color-light);">Não informado</span>';
                const fornecedor = p.fornecedor ? escapeHtmlPi(p.fornecedor) : '<span style="color: var(--text-color-light);">Não informado</span>';
                const data = p.data_cadastro ? escapeHtmlPi(p.data_cadastro) : '<span style="color: var(--text-color-light);">Não informado</span>';
                const statusBadge = p.concluido
                    ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Concluído</span>'
                    : '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Em andamento</span>';

                const html = `
                    <div style="text-align: left;">
                        <p style="margin-bottom: 1.25rem; color: var(--text-color); line-height: 1.6;">
                            Já existe um PI cadastrado com este número no sistema (considerando que <strong>09898</strong> e <strong>9898</strong> são tratados como o mesmo PI).
                        </p>
                        <div style="background: rgba(220, 53, 69, 0.08); border-left: 4px solid var(--danger-color); padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1rem;">
                            <p style="margin: 0 0 0.5rem 0;"><strong>Número:</strong> ${escapeHtmlPi(p.numero_pi)}</p>
                            <p style="margin: 0 0 0.5rem 0;"><strong>Cliente:</strong> ${cliente}</p>
                            <p style="margin: 0 0 0.5rem 0;"><strong>Fornecedor:</strong> ${fornecedor}</p>
                            <p style="margin: 0 0 0.5rem 0;"><strong>Data de cadastro:</strong> ${data}</p>
                            <p style="margin: 0;"><strong>Status:</strong> ${statusBadge}</p>
                        </div>
                        <p style="margin: 0; color: var(--text-color); font-size: 0.9rem;">
                            <i class="bi bi-info-circle"></i>
                            Confira o número digitado ou abra o PI existente para verificar.
                        </p>
                    </div>
                `;

                Swal.fire({
                    title: 'PI já cadastrado',
                    html: html,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-eye"></i> Ver PI existente',
                    cancelButtonText: '<i class="bi bi-arrow-left"></i> Corrigir número',
                    customClass: {
                        confirmButton: 'swal-confirm-btn',
                        cancelButton: 'swal-cancel-btn'
                    },
                    buttonsStyling: false,
                    reverseButtons: true,
                    width: '600px',
                    allowOutsideClick: true,
                    allowEscapeKey: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        const url = '{{ route('pis.index') }}?abrir_pi=' + encodeURIComponent(p.id);
                        window.open(url, '_blank');
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        $('#numero_pi').focus().select();
                    }
                });
            }

            function verificarNumeroPiNoServidor(numero) {
                if (verificarPiTimeout) {
                    clearTimeout(verificarPiTimeout);
                }

                if (!numero) {
                    piDuplicadoEncontrado = false;
                    ultimoNumeroVerificado = null;
                    $('#numero_pi').removeClass('is-invalid');
                    return;
                }

                if (numero === ultimoNumeroVerificado && piDuplicadoEncontrado) {
                    return;
                }

                verificarPiTimeout = setTimeout(function() {
                    const payload = {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        numero_pi: numero,
                    };

                    @if (isset($pi))
                        payload.pi_id = {{ $pi->id }};
                    @endif

                    $.ajax({
                        url: '{{ route('pis.verificar_numero_pi') }}',
                        method: 'POST',
                        data: payload,
                        dataType: 'json',
                        success: function(response) {
                            ultimoNumeroVerificado = numero;

                            if (response.existe) {
                                piDuplicadoEncontrado = true;
                                $('#numero_pi').addClass('is-invalid');
                                mostrarModalPiDuplicado(response.pi);
                            } else {
                                piDuplicadoEncontrado = false;
                                $('#numero_pi').removeClass('is-invalid');
                            }
                        },
                        error: function() {
                            piDuplicadoEncontrado = false;
                        }
                    });
                }, 350);
            }

            $('#numero_pi').on('input', function() {
                let value = $(this).val().replace(/\D/g, '');
                if (value.length > 6) {
                    value = value.substring(0, 6);
                }
                $(this).val(value);

                piDuplicadoEncontrado = false;
                $('#numero_pi').removeClass('is-invalid');

                if (!numeroPiExtraido) return;

                const valorDigitado = value.trim();
                const feedbackDiv = $('#piNumberFeedback');
                const feedbackMsg = $('#piNumberMessage');

                if (!valorDigitado) {
                    feedbackDiv.hide();
                    return;
                }

                if (valorDigitado === numeroPiExtraido) {
                    feedbackMsg.html(
                        '<i class="bi bi-check-circle-fill" style="color: var(--success-color);"></i> ' +
                        '<span style="color: var(--success-color); font-weight: 600;">Número do PI confere com o documento anexado.</span>'
                    );
                } else {
                    feedbackMsg.html(
                        '<i class="bi bi-exclamation-triangle-fill" style="color: var(--danger-color);"></i> ' +
                        '<span style="color: var(--danger-color); font-weight: 600;">Número diferente do documento (' +
                        numeroPiExtraido + ').</span>' +
                        '<button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="usarNumeroPiExtraido()">' +
                        '<i class="bi bi-arrow-repeat"></i> Usar ' + numeroPiExtraido +
                        '</button>'
                    );
                }
                feedbackDiv.show();
            });

            $('#numero_pi').on('blur', function() {
                let value = $(this).val().replace(/\D/g, '');
                if (value === '') {
                    $(this).val('');
                    piDuplicadoEncontrado = false;
                    return;
                }

                const normalizado = value.replace(/^0+/, '') || '0';
                $(this).val(normalizado);

                verificarNumeroPiNoServidor(normalizado);
            });

            $('.money-mask').mask('#.##0,00', {
                reverse: true,
                placeholder: '0,00'
            });

            $('.percent-mask').on('input', function() {
                let value = $(this).val().replace(/\D/g, '');
                if (value === '') {
                    $(this).val('');
                    return;
                }
                let numValue = parseFloat(value);
                if (numValue > 100) {
                    numValue = 100;
                }
                $(this).val(numValue);
            }).on('blur', function() {
                let value = $(this).val();
                if (value !== '' && value !== '0') {
                    $(this).val(value + '%');
                }
            }).on('focus', function() {
                let value = $(this).val().replace('%', '');
                if (value !== '0') {
                    $(this).val(value);
                }
            });

            $('.days-mask').on('input', function() {
                let value = $(this).val().replace(/\D/g, '');
                if (value.length > 4) {
                    value = value.substring(0, 4);
                }
                $(this).val(value);
            });

            $('#data_cadastro').on('change', function() {
                const data = $(this).val();
                if (data) {
                    const date = new Date(data + 'T00:00:00');
                    const mes = String(date.getMonth() + 1).padStart(2, '0');
                    const ano = date.getFullYear();
                    const meses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho',
                        'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
                    ];
                    const mesNome = meses[parseInt(mes)];
                    const periodo = `${mesNome}/${ano}`;
                    $('#periodoField').val(periodo);
                }
            });

            const dataInicial = $('#data_cadastro').val();
            if (dataInicial) {
                $('#data_cadastro').trigger('change');
            }

            function verificarClienteExigeVeiculo(clienteId) {
                if (!clienteId) {
                    $('#tipoVeiculoSection').hide();
                    clienteExigeVeiculo = false;
                    return;
                }

                $.ajax({
                    url: '{{ route('pis.verificar_exige_veiculo', '__ID__') }}'.replace('__ID__',
                        clienteId),
                    method: 'GET',
                    success: function(response) {
                        if (response.exige_documento_por_veiculo && response.tipo_veiculos && response
                            .tipo_veiculos.length > 0) {
                            clienteExigeVeiculo = true;
                            renderizarTiposVeiculo(response.tipo_veiculos);
                            $('#tipoVeiculoSection').slideDown(300);
                        } else {
                            clienteExigeVeiculo = false;
                            $('#tipoVeiculoSection').slideUp(300);
                            $('#tipo_veiculo_id').val('');
                            tipoVeiculoSelecionado = '';
                        }
                    },
                    error: function() {
                        clienteExigeVeiculo = false;
                        $('#tipoVeiculoSection').hide();
                    }
                });
            }

            function renderizarTiposVeiculo(veiculos) {
                let html = '';
                veiculos.forEach(function(veiculo) {
                    let iconClass = 'bi-check-circle';
                    let gradientColors = '#6b7280, #9ca3af';
                    const nomeLower = veiculo.nome.toLowerCase();

                    if (nomeLower.includes('rádio') || nomeLower.includes('radio') || nomeLower.includes(
                            'tv')) {
                        iconClass = 'bi-broadcast';
                        gradientColors = '#dc2626, #f87171';
                    } else if (nomeLower.includes('internet')) {
                        iconClass = 'bi-globe';
                        gradientColors = '#2563eb, #60a5fa';
                    } else if (nomeLower.includes('jornal') || nomeLower.includes('revista')) {
                        iconClass = 'bi-newspaper';
                        gradientColors = '#0891b2, #22d3ee';
                    }

                    const isSelected = tipoVeiculoSelecionado == veiculo.id;

                    html += `
                        <div class="tipo-veiculo-card ${isSelected ? 'selected' : ''}" data-id="${veiculo.id}">
                            <div class="tipo-veiculo-icon" style="background: linear-gradient(135deg, ${gradientColors});">
                                <i class="bi ${iconClass}"></i>
                            </div>
                            <div class="tipo-veiculo-info">
                                <span class="tipo-veiculo-nome">${veiculo.nome}</span>
                            </div>
                            <div class="tipo-veiculo-check">
                                <i class="bi bi-check-lg"></i>
                            </div>
                        </div>
                    `;
                });

                $('#tipoVeiculoOptions').html(html);
            }

            $(document).on('click', '.tipo-veiculo-card', function() {
                $('.tipo-veiculo-card').removeClass('selected');
                $(this).addClass('selected');
                tipoVeiculoSelecionado = $(this).data('id');
                $('#tipo_veiculo_id').val(tipoVeiculoSelecionado);
            });

            $('#cliente_id').on('change', function() {
                const clienteId = $(this).val();
                verificarClienteExigeVeiculo(clienteId);

                if (isAdminOrGerencial) {
                    if (clienteId) {
                        $('#responsavelSection').show();
                        const temResponsavel = $('#responsavelId').val();
                        if (!temResponsavel) {
                            carregarSugestaoAutomatica(clienteId);
                        }
                    } else {
                        $('#responsavelSection').hide();
                        limparResponsavel();
                    }
                }
            });

            const clienteIdInicial = $('#cliente_id').val();
            if (clienteIdInicial) {
                verificarClienteExigeVeiculo(clienteIdInicial);

                if (isAdminOrGerencial) {
                    $('#responsavelSection').show();
                    const temResponsavel = $('#responsavelId').val();
                    if (!temResponsavel) {
                        carregarSugestaoAutomatica(clienteIdInicial);
                    }
                }
            }

            if (isAdminOrGerencial) {
                let responsavelSelecionado = null;
                let todosOperacionais = [];
                let todosGerenciais = [];
                let tipoAtual = 'operacional';
                let timeoutBuscaResp = null;

                $('.tipo-tab').on('click', function() {
                    const tipo = $(this).data('tipo');
                    tipoAtual = tipo;

                    $('.tipo-tab').removeClass('active');
                    $(this).addClass('active');

                    const lista = tipo === 'operacional' ? todosOperacionais : todosGerenciais;
                    renderizarListaResponsaveis(lista, tipo);
                });

                $('#searchResponsavel').on('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }
                });

                $('#searchResponsavel').on('input', function() {
                    const search = $(this).val().toLowerCase().trim();
                    clearTimeout(timeoutBuscaResp);

                    if (search.length === 0) {
                        const lista = tipoAtual === 'operacional' ? todosOperacionais : todosGerenciais;
                        renderizarListaResponsaveis(lista, tipoAtual);
                        return;
                    }

                    timeoutBuscaResp = setTimeout(() => {
                        const lista = tipoAtual === 'operacional' ? todosOperacionais :
                            todosGerenciais;
                        const filtrados = lista.filter(r =>
                            r.nome.toLowerCase().includes(search) ||
                            r.email.toLowerCase().includes(search)
                        );
                        renderizarListaResponsaveis(filtrados, tipoAtual);
                    }, 300);
                });

                $('#btnRemoveResponsavel').on('click', function(e) {
                    e.preventDefault();
                    limparResponsavel();
                    $('#previewResponsavel').hide();
                    $('#searchResponsavelWrapper').show();
                    $('#searchResponsavel').focus();

                    if (todosOperacionais.length === 0) {
                        const clienteId = $('#cliente_id').val();
                        if (clienteId) {
                            carregarSugestaoAutomatica(clienteId);
                        }
                    }
                });

                $(document).on('click', '.dropdown-item-responsavel', function() {
                    const dados = JSON.parse($(this).attr('data-responsavel'));
                    selecionarResponsavel(dados);
                });

                window.carregarSugestaoAutomatica = function(clienteId) {
                    $('#loadingResponsavel').addClass('show');
                    $('#dropdownResponsaveis').html(`
                        <div class="dropdown-empty">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            <p>Calculando melhor distribuição...</p>
                        </div>
                    `);

                    $.ajax({
                        url: '{{ route('pis.sugerir_operacional') }}',
                        method: 'POST',
                        data: {
                            cliente_id: clienteId,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            $('#loadingResponsavel').removeClass('show');

                            if (response.success && response.sugerido) {
                                todosOperacionais = response.todos_operacionais || [];

                                selecionarResponsavel({
                                    id: response.sugerido.id,
                                    nome: response.sugerido.nome,
                                    email: response.sugerido.email,
                                    tipo: 'operacional',
                                    total_pis: response.sugerido.total_pis,
                                    sugerido: true
                                });

                                renderizarListaResponsaveis(todosOperacionais, 'operacional',
                                    response.sugerido.id);
                            } else {
                                $('#dropdownResponsaveis').html(`
                                    <div class="dropdown-empty">
                                        <i class="bi bi-exclamation-circle"></i>
                                        <p>Este cliente não possui operacionais cadastrados</p>
                                    </div>
                                `);
                            }

                            carregarGerenciais();
                        },
                        error: function() {
                            $('#loadingResponsavel').removeClass('show');
                            $('#dropdownResponsaveis').html(`
                                <div class="dropdown-empty">
                                    <i class="bi bi-x-circle"></i>
                                    <p>Erro ao carregar sugestão</p>
                                </div>
                            `);
                        }
                    });
                }

                function carregarGerenciais() {
                    $.ajax({
                        url: '{{ route('pis.gerenciais_disponiveis') }}',
                        method: 'GET',
                        success: function(response) {
                            if (response.success && response.gerenciais) {
                                todosGerenciais = response.gerenciais.map(g => ({
                                    id: g.id,
                                    nome: g.nome,
                                    email: g.email,
                                    tipo: 'gerencial',
                                    total_pis: g.total_pis
                                }));
                            }
                        }
                    });
                }

                function renderizarListaResponsaveis(lista, tipo, sugeridoId = null) {
                    if (!lista || lista.length === 0) {
                        $('#dropdownResponsaveis').html(`
                            <div class="dropdown-empty">
                                <i class="bi bi-person-x"></i>
                                <p>Nenhum ${tipo} encontrado</p>
                            </div>
                        `);
                        return;
                    }

                    let html = lista.map(r => {
                        const isSugerido = r.id === sugeridoId;
                        return `
                            <div class="dropdown-item-responsavel ${isSugerido ? 'sugerido' : ''}" data-responsavel='${JSON.stringify(r)}'>
                                <div class="dropdown-responsavel-avatar ${tipo === 'gerencial' ? 'gerencial' : ''}">
                                    ${r.nome.substring(0, 2).toUpperCase()}
                                </div>
                                <div class="dropdown-responsavel-info">
                                    <div class="dropdown-responsavel-name">${r.nome}</div>
                                    <div class="dropdown-responsavel-details">${r.email}</div>
                                    <div class="dropdown-responsavel-stats">
                                        <i class="bi bi-briefcase"></i>
                                        ${r.total_pis} PIs atribuídos
                                    </div>
                                </div>
                                ${isSugerido ? '<div class="badge-sugerido-mini"><i class="bi bi-star-fill"></i></div>' : ''}
                            </div>
                        `;
                    }).join('');

                    $('#dropdownResponsaveis').html(html);
                }

                function selecionarResponsavel(dados) {
                    responsavelSelecionado = dados;

                    $('#responsavelId').val(dados.id);
                    $('#responsavelTipo').val(dados.tipo || tipoAtual || 'operacional');
                    $('#responsavelAvatar').text(dados.nome.substring(0, 2).toUpperCase());
                    $('#responsavelNome').text(dados.nome);
                    $('#responsavelDetalhes').text(dados.email);
                    $('#responsavelTipoLabel').text(dados.tipo === 'gerencial' ? 'Gerencial' : 'Operacional');

                    if (dados.tipo === 'gerencial') {
                        $('#responsavelAvatar').addClass('gerencial-avatar');
                    } else {
                        $('#responsavelAvatar').removeClass('gerencial-avatar');
                    }

                    if (dados.sugerido) {
                        $('#badgeSugerido').show();
                    } else {
                        $('#badgeSugerido').hide();
                    }

                    $('#previewResponsavel').show();
                    $('#searchResponsavelWrapper').hide();

                    toastr.success(`${dados.nome} selecionado como responsável`);
                }

                window.limparResponsavel = function() {
                    responsavelSelecionado = null;
                    $('#responsavelId').val('');
                    $('#responsavelTipo').val('');
                    $('#searchResponsavel').val('');
                    tipoAtual = 'operacional';
                    $('.tipo-tab').removeClass('active');
                    $('.tipo-tab[data-tipo="operacional"]').addClass('active');
                }
            }

            const uploadArea = $('#uploadArea');
            const fileInput = $('#documentoPi');
            const uploadPlaceholder = $('#uploadPlaceholder');
            const uploadPreview = $('#uploadPreview');
            const previewName = $('#previewName');
            const previewSize = $('#previewSize');

            uploadArea.on('click', function(e) {
                if ($(e.target).closest('#btnRemovePreview').length) return;
                if (uploadPreview.is(':visible')) return;
                if (e.target === fileInput[0]) return;
                e.stopPropagation();
                fileInput[0].click();
            });

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea[0].addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea[0].addEventListener(eventName, () => {
                    uploadArea.addClass('drag-over');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea[0].addEventListener(eventName, () => {
                    uploadArea.removeClass('drag-over');
                }, false);
            });

            uploadArea[0].addEventListener('drop', function(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                handleFiles(files);
            }, false);

            fileInput.off('change').on('change', function() {
                if (this.files && this.files.length > 0) {
                    handleFiles(this.files);
                } else if (selectedFile) {
                    var dt = new DataTransfer();
                    dt.items.add(selectedFile);
                    fileInput[0].files = dt.files;
                }
            });

            function handleFiles(files) {
                if (files.length === 0) return;
                const file = files[0];
                if (file.type !== 'application/pdf') {
                    toastr.error('Apenas arquivos PDF são permitidos!');
                    fileInput.val('');
                    return;
                }
                const maxSize = 45 * 1024 * 1024;
                if (file.size > maxSize) {
                    toastr.error('O arquivo não pode ser maior que 45MB!');
                    fileInput.val('');
                    return;
                }
                selectedFile = file;

                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput[0].files = dataTransfer.files;

                showPreview(file);
                extrairNumeroPiDoPdf(file);
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

            function extrairNumeroPiDoPdf(file) {
                const formData = new FormData();
                formData.append('documento_pi', file);
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

                const feedbackDiv = $('#piNumberFeedback');
                const feedbackMsg = $('#piNumberMessage');
                const campoNumeroPi = $('#numero_pi');

                feedbackDiv.hide();
                numeroPiExtraido = null;

                $('#previewStatus').html('<i class="bi bi-hourglass-split"></i> Analisando documento...');

                $.ajax({
                    url: '{{ route('pis.extrair_numero_pi') }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    timeout: 30000,
                    success: function(response) {
                        $('#previewStatus').html('Arquivo pronto para upload');

                        if (response.success && response.numero_pi) {
                            numeroPiExtraido = response.numero_pi;
                            const valorAtual = campoNumeroPi.val().trim();

                            if (!valorAtual) {
                                campoNumeroPi.val(response.numero_pi);
                                feedbackMsg.html(
                                    '<i class="bi bi-check-circle-fill" style="color: var(--success-color);"></i> ' +
                                    '<span style="color: var(--success-color); font-weight: 600;">Número do PI identificado no documento: ' +
                                    response.numero_pi + '</span>'
                                );
                                feedbackDiv.show();
                                toastr.success('Número do PI identificado automaticamente: ' + response
                                    .numero_pi);
                            } else if (valorAtual === response.numero_pi) {
                                feedbackMsg.html(
                                    '<i class="bi bi-check-circle-fill" style="color: var(--success-color);"></i> ' +
                                    '<span style="color: var(--success-color); font-weight: 600;">Número do PI confere com o documento anexado.</span>'
                                );
                                feedbackDiv.show();
                            } else {
                                feedbackMsg.html(
                                    '<i class="bi bi-exclamation-triangle-fill" style="color: var(--danger-color);"></i> ' +
                                    '<span style="color: var(--danger-color); font-weight: 600;">Atenção: O número do PI digitado (' +
                                    valorAtual +
                                    ') é diferente do número encontrado no documento (' + response
                                    .numero_pi + ').</span>' +
                                    '<br><button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="usarNumeroPiExtraido()">' +
                                    '<i class="bi bi-arrow-repeat"></i> Usar número do documento (' +
                                    response.numero_pi + ')' +
                                    '</button>'
                                );
                                feedbackDiv.show();
                                toastr.warning('O número do PI digitado não confere com o documento!');
                            }
                        }
                    },
                    error: function() {
                        $('#previewStatus').html('Arquivo pronto para upload');
                    }
                });
            }

            $('#btnRemovePreview').on('click', function(e) {
                e.stopPropagation();
                fileInput.val('');
                selectedFile = null;
                uploadPreview.hide();
                uploadPlaceholder.show();
                numeroPiExtraido = null;
                $('#piNumberFeedback').hide();
                toastr.info('Arquivo removido');
            });

            $('#btnReplaceFile').on('click', function() {
                $('#currentFileDisplay').hide();
                $('#uploadContainer').show();
                $('#removeCurrentFile').val('0');
                toastr.info('Selecione o novo documento para substituir');
            });

            $('#btnRemoveCurrentFile').on('click', function() {
                Swal.fire({
                    title: 'Remover Documento?',
                    html: `
                        <div style="text-align: left;">
                            <p style="margin-bottom: 1.25rem; font-size: 1.05rem; color: var(--text-color); line-height: 1.6;">
                                Deseja realmente remover o documento atual?
                            </p>
                            <div class="warning-box">
                                <p>
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    <span>Esta ação não pode ser desfeita. Você precisará fazer upload de um novo documento.</span>
                                </p>
                            </div>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-trash"></i> Sim, remover',
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
                        $('#removeCurrentFile').val('1');
                        $('#currentFileDisplay').hide();
                        $('#uploadContainer').show();
                        toastr.success('Documento marcado para remoção');
                    }
                });
            });

            $('.tab-button').on('click', function() {
                const tab = $(this).data('tab');
                const currentTab = $('.tab-content-area.active').attr('id').replace('tab-', '');
                if (currentTab === 'essencial') {
                    if (!validateEssentialTab()) {
                        return;
                    }
                }
                switchTab(tab);
            });

            $('#nextToComplementar').on('click', function() {
                if (validateEssentialTab()) {
                    switchTab('complementar');
                }
            });

            $('#backToEssencial').on('click', function() {
                switchTab('essencial');
            });

            $('#nextToValores').on('click', function() {
                switchTab('valores');
            });

            $('#backToComplementar').on('click', function() {
                switchTab('complementar');
            });

            function switchTab(tabName) {
                $('.tab-button').removeClass('active');
                $(`.tab-button[data-tab="${tabName}"]`).addClass('active');
                $('.tab-content-area').removeClass('active');
                $(`#tab-${tabName}`).addClass('active');
                $('html, body').animate({
                    scrollTop: 0
                }, 300);
            }

            function validateEssentialTab() {
                if (piDuplicadoEncontrado) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Número de PI duplicado',
                        text: 'Já existe um PI cadastrado com este número (mesmo desconsiderando zeros à esquerda). Verifique antes de continuar.',
                        confirmButtonText: 'Entendi',
                    });
                    $('#numero_pi').focus();
                    return false;
                }

                let isValid = true;
                let errors = [];

                if (!isEditing) {
                    if (!selectedFile && !fileInput.val()) {
                        isValid = false;
                        errors.push('Documento do PI');
                        $('#uploadArea').addClass('is-invalid');
                    } else {
                        $('#uploadArea').removeClass('is-invalid');
                    }
                }

                const requiredFields = [{
                        id: '#numero_pi',
                        name: 'Número do PI'
                    },
                    {
                        id: '#cliente_id',
                        name: 'Cliente'
                    }
                ];

                if (!isFornecedor) {
                    requiredFields.push({
                        id: '#fornecedor_id',
                        name: 'Fornecedor'
                    });
                }

                requiredFields.forEach(field => {
                    const $field = $(field.id);
                    const value = $field.val();
                    if (!value || value.trim() === '') {
                        isValid = false;
                        $field.addClass('is-invalid');
                        errors.push(field.name);
                    } else {
                        $field.removeClass('is-invalid');
                    }
                });

                if (clienteExigeVeiculo && !tipoVeiculoSelecionado) {
                    isValid = false;
                    errors.push('Tipo de Veículo');
                    $('#tipoVeiculoOptions').addClass('is-invalid');
                } else {
                    $('#tipoVeiculoOptions').removeClass('is-invalid');
                }

                if (!isValid) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Campos obrigatórios incompletos',
                        html: `
                            <div style="text-align: left;">
                                <p style="margin-bottom: 1rem; color: var(--text-color);">
                                    Por favor, preencha os seguintes campos obrigatórios:
                                </p>
                                <ul style="list-style: none; padding-left: 0;">
                                    ${errors.map(field => `
                                                        <li style="padding: 0.5rem; background: rgba(245, 158, 11, 0.12); margin-bottom: 0.5rem; border-radius: 6px; color: var(--text-on-light-bg);">
                                                            <i class="bi bi-exclamation-circle"></i> ${field}
                                                        </li>
                                                    `).join('')}
                                </ul>
                            </div>
                        `,
                        confirmButtonText: 'Entendi',
                        customClass: {
                            confirmButton: 'swal-confirm-btn'
                        },
                        buttonsStyling: false,
                        width: '500px'
                    });

                    const $firstInvalid = $('.is-invalid:first');
                    if ($firstInvalid.length) {
                        $('html, body').animate({
                            scrollTop: $firstInvalid.offset().top - 100
                        }, 300);
                        setTimeout(() => $firstInvalid.focus(), 400);
                    }
                }

                return isValid;
            }

            $('#piForm').on('submit', function(e) {
                if (!validateEssentialTab()) {
                    e.preventDefault();
                    switchTab('essencial');
                    return false;
                }

                if (selectedFile && (!fileInput[0].files || fileInput[0].files.length === 0)) {
                    var dt = new DataTransfer();
                    dt.items.add(selectedFile);
                    fileInput[0].files = dt.files;
                }
                $('#submitBtn').prop('disabled', true).html(`
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Salvando...
                `);
                $('#submitBtnValores').prop('disabled', true).html(`
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Salvando...
                `);
            });

            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-top-right',
                timeOut: 5000
            };
        });
    </script>
@endpush

@push('styles')
    <link href="{{ asset('css/pages/forms.css') }}" rel="stylesheet">
    <style>
        .page-header {
            margin-bottom: 1.25rem;
        }

        .page-header-content {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .page-title-wrapper {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-back-btn {
            width: 48px;
            height: 48px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-color);
            font-size: 1.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .page-back-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(-4px);
        }

        .page-icon {
            width: 56px;
            height: 56px;
            background: var(--primary-color);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            flex-shrink: 0;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-color);
            margin: 0;
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin: 0;
        }

        .alert-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            box-shadow: var(--shadow-sm);
        }

        .alert-danger {
            border-left: 4px solid #ef4444;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.05) 0%, rgba(239, 68, 68, 0.02) 100%);
        }

        .alert-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .alert-icon i {
            color: white;
        }

        .alert-content {
            flex: 1;
        }

        .alert-title {
            font-size: 1rem;
            font-weight: 700;
            color: #ef4444;
            margin: 0 0 0.75rem 0;
        }

        .alert-list {
            margin: 0;
            padding-left: 1.25rem;
            color: var(--danger-color);
        }

        .alert-list li {
            margin-bottom: 0.375rem;
        }

        .alert-close {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .alert-close:hover {
            background: rgba(0, 0, 0, 0.05);
            color: var(--text-color);
        }

        .tabs-nav {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 0.5rem;
            box-shadow: var(--shadow-sm);
            overflow-x: auto;
        }

        .tab-button {
            flex: 1;
            min-width: 150px;
            padding: 0.875rem 1.25rem;
            border: none;
            background: transparent;
            border-radius: 12px;
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            position: relative;
        }

        .tab-button i {
            font-size: 1.25rem;
            transition: transform 0.3s ease;
        }

        .tab-button:hover:not(.active) {
            background: rgba(0, 94, 130, 0.05);
            color: var(--primary-color);
        }

        .tab-button.active {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 94, 130, 0.25);
        }

        .tab-button.active i {
            transform: scale(1.1);
        }

        .tab-optional {
            font-size: 0.7rem;
            padding: 0.125rem 0.5rem;
            background: rgba(100, 116, 139, 0.15);
            border-radius: 4px;
            margin-left: 0.5rem;
        }

        .tab-required {
            font-size: 0.7rem;
            padding: 0.125rem 0.5rem;
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border-radius: 4px;
            margin-left: 0.5rem;
        }

        .tab-content-area {
            display: none;
        }

        .tab-content-area.active {
            display: block;
            animation: fadeInUp 0.4s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .content-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.25rem;
            overflow: hidden;
        }

        .card-header-section {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.625rem;
        }

        .card-title i {
            color: var(--primary-color);
            font-size: 1.25rem;
        }

        .card-subtitle {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin: 0.5rem 0 0 0;
        }

        .card-content {
            padding: 1.5rem;
        }

        .tipo-veiculo-alert {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%);
            border: 2px solid #22d3ee;
            border-radius: 12px;
            margin-bottom: 1.25rem;
        }

        .tipo-veiculo-alert-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #0891b2, #22d3ee);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .tipo-veiculo-alert-icon i {
            font-size: 1.25rem;
            color: white;
        }

        .tipo-veiculo-alert-content {
            flex: 1;
        }

        .tipo-veiculo-alert-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #0e7490;
            margin: 0 0 0.25rem 0;
        }

        .tipo-veiculo-alert-text {
            font-size: 0.85rem;
            color: #155e75;
            margin: 0;
            line-height: 1.5;
        }

        .tipo-veiculo-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.75rem;
        }

        .tipo-veiculo-options.is-invalid .tipo-veiculo-card {
            border-color: #fca5a5;
        }

        .tipo-veiculo-card {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            padding: 1rem;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            user-select: none;
        }

        .tipo-veiculo-card:hover {
            border-color: #22d3ee;
            background: #ecfeff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.15);
        }

        .tipo-veiculo-card.selected {
            border-color: #0891b2;
            background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%);
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.2);
        }

        .tipo-veiculo-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .tipo-veiculo-icon i {
            font-size: 1.25rem;
            color: white;
        }

        .tipo-veiculo-info {
            flex: 1;
        }

        .tipo-veiculo-nome {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.9375rem;
        }

        .tipo-veiculo-check {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            border: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .tipo-veiculo-check i {
            font-size: 0.875rem;
            color: transparent;
            transition: all 0.3s ease;
        }

        .tipo-veiculo-card.selected .tipo-veiculo-check {
            background: #0891b2;
            border-color: #0891b2;
        }

        .tipo-veiculo-card.selected .tipo-veiculo-check i {
            color: white;
        }

        .current-file-display {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 2px solid #fecaca;
            border-radius: 14px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .file-display-header {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .file-display-icon {
            width: 56px;
            height: 56px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--danger-color);
            flex-shrink: 0;
        }

        .file-display-info {
            flex: 1;
        }

        .file-display-name {
            font-size: 1rem;
            font-weight: 600;
            color: var(--danger-color);
            margin: 0 0 0.5rem;
            word-break: break-all;
        }

        .file-display-meta {
            display: flex;
            gap: 1rem;
            margin: 0;
            font-size: 0.875rem;
            color: #b91c1c;
        }

        .file-display-meta span {
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .file-display-actions {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .btn-file-action {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            font-size: 1.125rem;
        }

        .btn-download {
            background: white;
            color: #059669;
            border: 1px solid #d1fae5;
        }

        .btn-download:hover {
            background: #d1fae5;
            border-color: #059669;
            transform: translateY(-2px);
        }

        .btn-replace {
            background: white;
            color: #0284c7;
            border: 1px solid #bae6fd;
        }

        .btn-replace:hover {
            background: #bae6fd;
            border-color: #0284c7;
            transform: translateY(-2px);
        }

        .btn-remove {
            background: white;
            color: var(--danger-color);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .btn-remove:hover {
            background: #fee2e2;
            border-color: var(--danger-color);
            transform: translateY(-2px);
        }

        .upload-container {
            position: relative;
        }

        .upload-area {
            position: relative;
            min-height: 320px;
            border: 3px dashed var(--border-color);
            border-radius: 14px;
            background: var(--bg-body);
            transition: all 0.3s ease;
            cursor: pointer;
            overflow: hidden;
        }

        .upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(0, 94, 130, 0.02);
        }

        .upload-area.drag-over {
            border-color: var(--primary-color);
            background: rgba(0, 94, 130, 0.05);
            transform: scale(1.01);
        }

        .file-input {
            position: absolute;
            width: 0;
            height: 0;
            overflow: hidden;
            opacity: 0;
            pointer-events: none;
        }

        .upload-placeholder {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            width: 100%;
            padding: 1.5rem;
        }

        .upload-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 1.25rem;
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.25rem;
            color: white;
        }

        .upload-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-color);
            margin: 0 0 0.5rem;
        }

        .upload-text {
            font-size: 0.9375rem;
            color: var(--text-muted);
            margin: 0 0 1.25rem;
        }

        .upload-specs {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .spec-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-muted);
            background: var(--bg-card);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .spec-item i {
            font-size: 1rem;
            color: var(--danger-color);
        }

        .upload-required-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--danger-color);
            background: #fee2e2;
            padding: 0.375rem 0.875rem;
            border-radius: 6px;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .upload-preview {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--bg-body);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            z-index: 3;
            box-sizing: border-box;
            animation: previewSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes previewSlideIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .preview-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 2px solid #10b981;
            border-radius: 14px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.875rem;
            max-width: 380px;
            width: 100%;
            max-height: 100%;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.12);
            position: relative;
            animation: cardAppear 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes cardAppear {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(15px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .btn-remove-preview {
            position: absolute;
            top: -10px;
            right: -10px;
            width: 36px;
            height: 36px;
            background: white;
            border: 2.5px solid #dc2626;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            font-size: 1.25rem;
            color: var(--danger-color);
            box-shadow: 0 3px 10px rgba(220, 38, 38, 0.2);
            z-index: 10;
            animation: buttonPop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) 0.3s backwards;
        }

        @keyframes buttonPop {
            0% {
                transform: scale(0) rotate(-180deg);
                opacity: 0;
            }

            100% {
                transform: scale(1) rotate(0deg);
                opacity: 1;
            }
        }

        .btn-remove-preview:hover {
            background: #dc2626;
            color: white;
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.35);
        }

        .btn-remove-preview:active {
            transform: scale(0.95) rotate(90deg);
        }

        .preview-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 6px 18px rgba(220, 38, 38, 0.22);
            animation: iconBounce 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s backwards;
            position: relative;
        }

        @keyframes iconBounce {
            0% {
                transform: scale(0);
                opacity: 0;
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .preview-icon::after {
            content: '\2713';
            position: absolute;
            top: -6px;
            right: -6px;
            width: 26px;
            height: 26px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8125rem;
            font-weight: 700;
            border: 2.5px solid white;
            box-shadow: 0 3px 10px rgba(16, 185, 129, 0.28);
            animation: checkmarkPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) 0.4s backwards;
        }

        @keyframes checkmarkPop {
            0% {
                transform: scale(0) rotate(-180deg);
                opacity: 0;
            }

            100% {
                transform: scale(1) rotate(0deg);
                opacity: 1;
            }
        }

        .preview-info {
            width: 100%;
            text-align: center;
        }

        .preview-name {
            font-size: 0.9375rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 0.5rem;
            word-break: break-word;
            line-height: 1.35;
            animation: fadeInUp 0.5s ease 0.3s backwards;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .preview-size {
            display: inline-flex;
            align-items: center;
            gap: 0.4375rem;
            font-size: 0.8125rem;
            color: var(--text-muted);
            margin: 0 0 0.875rem;
            padding: 0.375rem 0.875rem;
            background: #f3f4f6;
            border-radius: 7px;
            font-weight: 600;
            animation: fadeInUp 0.5s ease 0.4s backwards;
        }

        .preview-size i {
            font-size: 0.875rem;
            color: #9ca3af;
        }

        .preview-status {
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #059669;
            margin: 0;
            padding: 0.625rem 1.25rem;
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-radius: 10px;
            border: 2px solid #6ee7b7;
            animation: fadeInUp 0.5s ease 0.5s backwards;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.12);
        }

        .preview-status::before {
            content: '\2713';
            width: 20px;
            height: 20px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.25);
        }

        .field-label-new {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .field-label-new .required-star {
            color: #ef4444;
            font-size: 1.125rem;
        }

        .field-label-new .optional-badge {
            padding: 0.125rem 0.5rem;
            background: rgba(100, 116, 139, 0.1);
            color: var(--text-muted);
            border-radius: 4px;
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .info-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            background: var(--bg-body);
            border: 1px solid var(--border-color);
            border-radius: 50%;
            color: var(--text-muted);
            font-size: 0.75rem;
            cursor: help;
        }

        .input-with-icon {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon-left {
            position: absolute;
            left: 1rem;
            color: var(--text-muted);
            font-size: 1rem;
            z-index: 1;
            pointer-events: none;
        }

        .form-input-new,
        .form-select-new,
        .form-textarea-new {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: var(--bg-body);
            color: var(--text-color);
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .form-textarea-new {
            min-height: 90px;
            resize: vertical;
        }

        .form-input-new:focus,
        .form-select-new:focus,
        .form-textarea-new:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--bg-card);
            box-shadow: 0 0 0 3px rgba(0, 94, 130, 0.08);
        }

        .form-input-new.is-invalid,
        .form-select-new.is-invalid,
        .form-textarea-new.is-invalid {
            border-color: #ef4444;
        }

        .form-input-new.is-invalid:focus,
        .form-select-new.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.08);
        }

        .field-error-message {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
            color: #ef4444;
            margin-top: 0.375rem;
        }

        .field-error-message i {
            font-size: 0.875rem;
        }

        .input-suffix {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.9375rem;
            font-weight: 600;
            pointer-events: none;
            z-index: 2;
        }

        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding-top: 1.25rem;
            margin-top: 1.25rem;
            border-top: 1px solid var(--border-color);
        }

        .btn-primary,
        .btn-secondary {
            height: 44px;
            padding: 0 1.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.9375rem;
            font-weight: 600;
            border-radius: 11px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(0, 94, 130, 0.2);
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 94, 130, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: var(--bg-body);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--bg-card);
            border-color: var(--text-muted);
        }

        .responsavel-selector-wrapper {
            position: relative;
        }

        .selected-responsavel-preview {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px solid #bae6fd;
            border-radius: 12px;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
        }

        .selected-responsavel-avatar {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.125rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .selected-responsavel-avatar.gerencial-avatar {
            background: linear-gradient(135deg, #7c3aed, #a78bfa);
        }

        .selected-responsavel-info {
            flex: 1;
        }

        .selected-responsavel-name {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }

        .selected-responsavel-role {
            font-size: 0.8125rem;
            color: var(--text-muted);
            margin-bottom: 0.375rem;
        }

        .selected-responsavel-tipo {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--primary-color);
            background: white;
            padding: 0.25rem 0.625rem;
            border-radius: 6px;
        }

        .badge-sugerido {
            position: absolute;
            top: -10px;
            right: 60px;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
        }

        .btn-remove-responsavel {
            width: 36px;
            height: 36px;
            background: white;
            border: 2px solid #dc2626;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            color: var(--danger-color);
            flex-shrink: 0;
        }

        .btn-remove-responsavel:hover {
            background: #dc2626;
            color: white;
            transform: scale(1.1);
        }

        .search-responsavel-wrapper {
            background: var(--bg-body);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem;
        }

        .tipo-responsavel-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            background: white;
            padding: 0.375rem;
            border-radius: 10px;
        }

        .tipo-tab {
            flex: 1;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-muted);
            background: transparent;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tipo-tab.active {
            background: var(--bg-body);
            color: var(--primary-color);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
        }

        .search-input-wrapper {
            position: relative;
            margin-bottom: 1rem;
        }

        .search-input-responsavel {
            width: 100%;
            padding: 0.75rem 2.75rem 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: white;
            color: var(--text-color);
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .search-input-responsavel:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 94, 130, 0.08);
        }

        .search-spinner {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            display: none;
        }

        .search-spinner.show {
            display: block;
        }

        .dropdown-responsaveis {
            max-height: 320px;
            overflow-y: auto;
        }

        .dropdown-item-responsavel {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 0.875rem;
            margin-bottom: 0.625rem;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .dropdown-item-responsavel:hover {
            border-color: var(--primary-color);
            background: rgba(0, 94, 130, 0.02);
            transform: translateX(4px);
        }

        .dropdown-item-responsavel.sugerido {
            border-color: #fbbf24;
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.08) 0%, rgba(251, 191, 36, 0.03) 100%);
        }

        .dropdown-responsavel-avatar {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9375rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .dropdown-responsavel-avatar.gerencial {
            background: linear-gradient(135deg, #7c3aed, #a78bfa);
        }

        .dropdown-responsavel-info {
            flex: 1;
        }

        .dropdown-responsavel-name {
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }

        .dropdown-responsavel-details {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 0.375rem;
        }

        .dropdown-responsavel-stats {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.6875rem;
            font-weight: 600;
            color: var(--text-muted);
            background: var(--bg-body);
            padding: 0.1875rem 0.5rem;
            border-radius: 5px;
        }

        .badge-sugerido-mini {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 22px;
            height: 22px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.625rem;
            box-shadow: 0 2px 6px rgba(245, 158, 11, 0.3);
        }

        .dropdown-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 2rem 1rem;
            color: var(--text-muted);
            font-size: 0.875rem;
            text-align: center;
        }

        .dropdown-empty i {
            font-size: 2rem;
            opacity: 0.5;
        }

        .field-help-text {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }

        .field-help-text i {
            font-size: 0.875rem;
            color: var(--primary-color);
        }

        .swal2-popup {
            font-family: inherit;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .swal2-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1.5rem;
            padding: 0 1rem;
        }

        .swal2-html-container {
            margin: 0;
            padding: 0;
            font-size: 1rem;
            line-height: 1.6;
            color: #4b5563;
        }

        .swal2-icon {
            margin: 1.5rem auto;
            border-width: 3px;
        }

        .swal2-icon.swal2-warning {
            border-color: #fbbf24;
            color: var(--warning-color);
        }

        .swal2-actions {
            margin-top: 2rem;
            gap: 1rem;
            padding: 0 1rem;
        }

        .swal-confirm-btn,
        .swal-cancel-btn {
            margin: 0;
            padding: 0.875rem 1.75rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.625rem;
            transition: all 0.3s ease;
            border: none;
            min-width: 130px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .swal-confirm-btn i,
        .swal-cancel-btn i {
            font-size: 1.125rem;
        }

        .swal-confirm-btn {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .swal-confirm-btn:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.35);
        }

        .swal-cancel-btn {
            background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
            color: white;
        }

        .swal-cancel-btn:hover {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(107, 114, 128, 0.3);
        }

        .warning-box {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            padding: 1.125rem;
            border-radius: 10px;
            margin: 1.125rem 0;
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

        @media (max-width: 992px) {
            .page-title {
                font-size: 1.25rem;
            }

            .card-content {
                padding: 1.25rem;
            }

            .form-footer {
                flex-direction: column;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
                justify-content: center;
            }

            .tipo-veiculo-options {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .upload-preview {
                padding: 1.25rem;
            }

            .preview-card {
                padding: 1.25rem;
                gap: 0.75rem;
                max-width: 340px;
            }

            .btn-remove-preview {
                width: 32px;
                height: 32px;
                font-size: 1.125rem;
                top: -8px;
                right: -8px;
                border-width: 2px;
            }

            .preview-icon {
                width: 62px;
                height: 62px;
                font-size: 2.25rem;
            }

            .preview-icon::after {
                width: 24px;
                height: 24px;
                font-size: 0.75rem;
                top: -5px;
                right: -5px;
                border-width: 2px;
            }

            .preview-name {
                font-size: 0.875rem;
                margin-bottom: 0.4375rem;
            }

            .preview-size {
                font-size: 0.75rem;
                padding: 0.3125rem 0.75rem;
                margin-bottom: 0.75rem;
            }

            .preview-status {
                font-size: 0.75rem;
                padding: 0.5rem 1rem;
            }

            .preview-status::before {
                width: 18px;
                height: 18px;
                font-size: 0.6875rem;
            }
        }

        @media (max-width: 576px) {
            .page-header-content {
                padding: 1.25rem;
            }

            .page-title-wrapper {
                gap: 1rem;
            }

            .page-icon {
                width: 48px;
                height: 48px;
                font-size: 1.5rem;
            }

            .page-title {
                font-size: 1.125rem;
            }

            .page-subtitle {
                font-size: 0.85rem;
            }

            .tabs-nav {
                padding: 0.375rem;
                gap: 0.375rem;
            }

            .tab-button {
                padding: 0.875rem 1rem;
                font-size: 0.875rem;
                min-width: 120px;
            }

            .tab-button i {
                font-size: 1.125rem;
            }

            .file-display-header {
                flex-wrap: wrap;
            }

            .file-display-actions {
                width: 100%;
                justify-content: center;
                margin-top: 1rem;
            }

            .upload-preview {
                padding: 1rem;
            }

            .preview-card {
                padding: 1.125rem;
                gap: 0.625rem;
                border-radius: 12px;
                max-width: 100%;
            }

            .btn-remove-preview {
                width: 30px;
                height: 30px;
                font-size: 1rem;
                top: -7px;
                right: -7px;
            }

            .preview-icon {
                width: 56px;
                height: 56px;
                font-size: 2rem;
                border-radius: 14px;
            }

            .preview-icon::after {
                width: 22px;
                height: 22px;
                font-size: 0.6875rem;
                top: -4px;
                right: -4px;
            }

            .preview-name {
                font-size: 0.8125rem;
                margin-bottom: 0.375rem;
            }

            .preview-size {
                font-size: 0.6875rem;
                padding: 0.25rem 0.625rem;
                margin-bottom: 0.625rem;
            }

            .preview-status {
                font-size: 0.6875rem;
                padding: 0.4375rem 0.875rem;
            }

            .preview-status::before {
                width: 16px;
                height: 16px;
                font-size: 0.625rem;
            }
        }
    </style>
@endpush