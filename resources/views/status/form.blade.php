{{-- resources/views/status/form.blade.php --}}

@extends('layouts.app')

@section('title', isset($status) ? 'Editar Status' : 'Novo Status')

@section('content')
    <link href="{{ asset('css/pages/forms.css') }}" rel="stylesheet">
    <link href="{{ asset('css/pages/form-status.css') }}" rel="stylesheet">
    <div class="container-fluid">
        <div class="form-wrapper">
            <div class="form-header-card">
                <div class="header-left">
                    <a href="{{ route('status.index') }}" class="btn-back">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div class="header-text">
                        <h1 class="page-title">{{ isset($status) ? 'Editar Status' : 'Novo Status' }}</h1>
                        <p class="page-subtitle">
                            {{ isset($status) ? 'Atualize as informações do status ' . $status->nome : 'Preencha os dados para criar um novo status no sistema' }}
                        </p>
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

            <form action="{{ isset($status) ? route('status.update', $status) : route('status.store') }}" method="POST"
                id="statusForm">
                @csrf
                @if (isset($status))
                    @method('PUT')
                @endif

                <div class="content-card">
                    <div class="card-header-section">
                        <div class="section-icon-wrapper">
                            <i class="bi bi-info-circle-fill"></i>
                        </div>
                        <div class="section-text">
                            <h2 class="section-title">Informações Básicas</h2>
                            <p class="section-description">Defina o nome e o tipo do status</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-field">
                            <label class="field-label">
                                <span>Nome do Status</span>
                                <span class="required-indicator">*</span>
                            </label>
                            <div class="input-container">
                                <div class="input-icon">
                                    <i class="bi bi-tag-fill"></i>
                                </div>
                                <input type="text" name="nome" id="nome"
                                    class="field-input @error('nome') is-invalid @enderror"
                                    placeholder="Ex: Aguardando Aprovação" value="{{ old('nome', $status->nome ?? '') }}"
                                    required autofocus maxlength="255">
                            </div>
                            @error('nome')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                            <span class="field-hint">
                                <i class="bi bi-lightbulb"></i>
                                Use nomes claros e descritivos para facilitar a identificação
                            </span>
                        </div>

                        <div class="form-field">
                            <label class="field-label">
                                <span>Tipo do Status</span>
                                <span class="required-indicator">*</span>
                            </label>
                            <div class="input-container">
                                <div class="input-icon">
                                    <i class="bi bi-folder-fill"></i>
                                </div>
                                <select name="tipo" id="tipo"
                                    class="field-select @error('tipo') is-invalid @enderror" required>
                                    <option value="">Selecione o tipo</option>
                                    <option value="pi" {{ old('tipo', $status->tipo ?? '') == 'pi' ? 'selected' : '' }}>
                                        <i class="bi bi-file-earmark-plus"></i> PI (Pedido de Inserção)
                                    </option>
                                    <option value="documento"
                                        {{ old('tipo', $status->tipo ?? '') == 'documento' ? 'selected' : '' }}>
                                        <i class="bi bi-file-text"></i> Documento
                                    </option>
                                    <option value="geral"
                                        {{ old('tipo', $status->tipo ?? '') == 'geral' ? 'selected' : '' }}>
                                        <i class="bi bi-gear"></i> Geral
                                    </option>
                                </select>
                            </div>
                            @error('tipo')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                            <span class="field-hint">
                                <i class="bi bi-info-circle"></i>
                                O tipo determina onde este status poderá ser utilizado
                            </span>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header-section">
                        <div class="section-icon-wrapper" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <i class="bi bi-palette-fill"></i>
                        </div>
                        <div class="section-text">
                            <h2 class="section-title">Aparência Visual</h2>
                            <p class="section-description">Escolha a cor de identificação do status</p>
                        </div>
                    </div>

                    <div class="color-picker-section">
                        <div class="color-input-group">
                            <label class="field-label">
                                <span>Cor do Status</span>
                                <span class="required-indicator">*</span>
                            </label>

                            <div class="color-picker-wrapper">
                                <div class="color-preview-box" id="colorPreview"
                                    style="background: {{ old('cor', $status->cor ?? '#005e82') }};"
                                    title="Clique para escolher uma cor">
                                    <div class="color-preview-overlay">
                                        <i class="bi bi-eyedropper"></i>
                                    </div>
                                </div>

                                <div class="color-details">
                                    <input type="color" name="cor" id="cor"
                                        class="color-input @error('cor') is-invalid @enderror"
                                        value="{{ old('cor', $status->cor ?? '#005e82') }}" required>

                                    <input type="text" id="corHex" class="hex-input" placeholder="#000000"
                                        value="{{ old('cor', $status->cor ?? '#005e82') }}" maxlength="7"
                                        pattern="^#[0-9A-Fa-f]{6}$">

                                    <button type="button" class="btn-reset-color" id="resetColor"
                                        title="Restaurar cor padrão">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                </div>
                            </div>

                            @error('cor')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="color-presets">
                            <span class="presets-label">Cores sugeridas:</span>
                            <div class="presets-grid">
                                <button type="button" class="preset-color" data-color="#059669"
                                    style="background: #059669;" title="Verde - Sucesso"></button>
                                <button type="button" class="preset-color" data-color="#0284c7"
                                    style="background: #0284c7;" title="Azul - Informação"></button>
                                <button type="button" class="preset-color" data-color="#d97706"
                                    style="background: #d97706;" title="Laranja - Atenção"></button>
                                <button type="button" class="preset-color" data-color="#dc2626"
                                    style="background: #dc2626;" title="Vermelho - Erro"></button>
                                <button type="button" class="preset-color" data-color="#7c3aed"
                                    style="background: #7c3aed;" title="Roxo"></button>
                                <button type="button" class="preset-color" data-color="#ec4899"
                                    style="background: #ec4899;" title="Rosa"></button>
                                <button type="button" class="preset-color" data-color="#64748b"
                                    style="background: #64748b;" title="Cinza"></button>
                                <button type="button" class="preset-color" data-color="#005e82"
                                    style="background: #005e82;" title="Azul Escuro"></button>
                            </div>
                        </div>

                        <div class="color-preview-card">
                            <span class="preview-label">Prévia do Status:</span>
                            <div class="status-badge-preview" id="statusBadgePreview">
                                <span class="badge-text">{{ old('nome', $status->nome ?? 'Novo Status') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header-section">
                        <div class="section-icon-wrapper" style="background: linear-gradient(135deg, #0284c7, #0ea5e9);">
                            <i class="bi bi-card-text"></i>
                        </div>
                        <div class="section-text">
                            <h2 class="section-title">Detalhes Adicionais</h2>
                            <p class="section-description">Informações complementares sobre o status</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-field-full">
                            <label class="field-label">
                                <span>Descrição</span>
                            </label>
                            <div class="input-container">
                                <div class="input-icon">
                                    <i class="bi bi-text-paragraph"></i>
                                </div>
                                <textarea name="descricao" id="descricao" class="field-input field-textarea @error('descricao') is-invalid @enderror"
                                    rows="4" maxlength="1000" placeholder="Descreva brevemente quando este status deve ser usado...">{{ old('descricao', $status->descricao ?? '') }}</textarea>
                            </div>
                            @error('descricao')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                            <div class="character-count">
                                <span id="charCount">0</span> / 1000 caracteres
                            </div>
                        </div>

                        <div class="form-field-full">
                            <div class="switch-field">
                                <div class="switch-info">
                                    <label class="field-label switch-label">
                                        <i class="bi bi-power"></i>
                                        <span>Status Ativo</span>
                                    </label>
                                    <span class="switch-description">
                                        Quando ativo, este status estará disponível para uso no sistema
                                    </span>
                                </div>
                                <label class="switch">
                                    <input type="hidden" name="ativo" value="0">
                                    <input type="checkbox" name="ativo" value="1" id="ativo"
                                        {{ old('ativo', $status->ativo ?? true) ? 'checked' : '' }}>
                                    <span class="switch-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                @if (isset($status))
                    <div class="info-card info-warning">
                        <div class="info-icon">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div class="info-content">
                            <h4 class="info-title">Atenção ao Editar</h4>
                            <p class="info-text">
                                Este status está sendo usado em <strong>{{ $status->pis_count ?? 0 }} PIs</strong> e
                                <strong>{{ $status->documentos_enviados_count ?? 0 }} documentos</strong>.
                                Alterações no nome ou cor afetarão todos os registros vinculados.
                            </p>
                        </div>
                    </div>
                @endif

                <div class="action-bar">
                    <button type="button" class="btn-secondary"
                        onclick="window.location.href='{{ route('status.index') }}'">
                        <i class="bi bi-x-lg"></i>
                        <span>Cancelar</span>
                    </button>
                    <button type="submit" class="btn-primary" id="submitBtn">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>{{ isset($status) ? 'Atualizar Status' : 'Salvar Status' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/pages/form-status.js') }}"></script>
@endpush
