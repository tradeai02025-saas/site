{{-- resources/views/tipo-documentos/form.blade.php --}}

@extends('layouts.app')

@section('title', isset($tipoDocumento) ? 'Editar Tipo de Documento' : 'Novo Tipo de Documento')

@section('content')
    <link href="{{ asset('css/pages/tipo-docs.css') }}" rel="stylesheet">
    <link href="{{ asset('css/pages/forms.css') }}" rel="stylesheet">
    <div class="container-fluid">
        <div class="form-wrapper">
            <div class="form-header-card">
                <div class="header-left">
                    <a href="{{ route('tipo_documentos.index') }}" class="btn-back">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div class="header-text">
                        <h1 class="page-title">
                            {{ isset($tipoDocumento) ? 'Editar Tipo de Documento' : 'Novo Tipo de Documento' }}</h1>
                        <p class="page-subtitle">
                            {{ isset($tipoDocumento) ? 'Atualize as informações do tipo de documento' : 'Configure um novo tipo de documento para o sistema' }}
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

            <form
                action="{{ isset($tipoDocumento) ? route('tipo_documentos.update', $tipoDocumento) : route('tipo_documentos.store') }}"
                method="POST" id="tipoDocumentoForm">
                @csrf
                @if (isset($tipoDocumento))
                    @method('PUT')
                @endif

                <div class="content-card">
                    <div class="card-header-section">
                        <div class="section-icon-wrapper">
                            <i class="bi bi-file-earmark-text-fill"></i>
                        </div>
                        <div class="section-text">
                            <h2 class="section-title">Informações Básicas</h2>
                            <p class="section-description">Defina o nome e descrição do tipo de documento</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-field form-field-full">
                            <label class="field-label">
                                <span>Nome do Tipo de Documento</span>
                                <span class="required-indicator">*</span>
                            </label>
                            <div class="input-container">
                                <div class="input-icon">
                                    <i class="bi bi-tag-fill"></i>
                                </div>
                                <input type="text" name="nome" id="nome"
                                    class="field-input @error('nome') is-invalid @enderror"
                                    placeholder="Ex: Certidão Negativa, Alvará, Contrato Social"
                                    value="{{ old('nome', $tipoDocumento->nome ?? '') }}" required autofocus
                                    maxlength="255">
                            </div>
                            @error('nome')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                            <span class="field-hint">
                                <i class="bi bi-info-circle"></i>
                                Escolha um nome claro e descritivo para facilitar a identificação
                            </span>
                        </div>

                        <div class="form-field form-field-full">
                            <label class="field-label">
                                <span>Descrição</span>
                                <span class="optional-text">(opcional)</span>
                            </label>
                            <div class="input-container">
                                <div class="input-icon">
                                    <i class="bi bi-text-paragraph"></i>
                                </div>
                                <textarea name="descricao" id="descricao" class="field-input field-textarea @error('descricao') is-invalid @enderror"
                                    rows="3"
                                    placeholder="Descreva brevemente sobre este tipo de documento, sua finalidade e quando deve ser solicitado">{{ old('descricao', $tipoDocumento->descricao ?? '') }}</textarea>
                            </div>
                            @error('descricao')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                            <span class="field-hint">
                                <i class="bi bi-info-circle"></i>
                                Informações que ajudarão os usuários a entender quando este documento é necessário
                            </span>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header-section">
                        <div class="section-icon-wrapper" style="background: linear-gradient(135deg, #d97706, #f59e0b);">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div class="section-text">
                            <h2 class="section-title">Período de Validade</h2>
                            <p class="section-description">Configure por quantos dias este documento é válido</p>
                        </div>
                    </div>

                    <div class="config-grid" style="margin-bottom: 1.5rem;">
                        <div class="config-option">
                            <div class="config-header">
                                <div class="config-icon"
                                    style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1e40af;">
                                    <i class="bi bi-infinity"></i>
                                </div>
                                <div class="config-info">
                                    <span class="config-label">Validade Indeterminada</span>
                                    <span class="config-desc">Este documento não possui prazo de validade</span>
                                </div>
                            </div>
                            <div class="toggle-switch">
                                <input type="hidden" name="validade_indeterminada" value="0">
                                <input type="checkbox" name="validade_indeterminada" id="validade_indeterminada"
                                    class="toggle-input" value="1"
                                    {{ old('validade_indeterminada', isset($tipoDocumento) && $tipoDocumento->validade_dias === 0) ? 'checked' : '' }}>
                                <label for="validade_indeterminada" class="toggle-label">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="validadeFieldsContainer">
                        <div class="form-grid">
                            <div class="form-field">
                                <label class="field-label">
                                    <span>Validade (em dias)</span>
                                    <span class="required-indicator" id="validadeRequired">*</span>
                                </label>
                                <div class="input-container">
                                    <div class="input-icon">
                                        <i class="bi bi-hourglass-split"></i>
                                    </div>
                                    <input type="number" name="validade_dias" id="validade_dias"
                                        class="field-input @error('validade_dias') is-invalid @enderror" placeholder="365"
                                        value="{{ old('validade_dias', isset($tipoDocumento) && $tipoDocumento->validade_dias > 0 ? $tipoDocumento->validade_dias : '') }}"
                                        min="1" max="3650">
                                </div>
                                @error('validade_dias')
                                    <span class="field-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-field">
                                <label class="field-label">
                                    <span>Equivalente a</span>
                                </label>
                                <div class="validade-display">
                                    <div class="validade-badge">
                                        <i class="bi bi-calendar3"></i>
                                        <span id="validadeMeses">0</span> meses
                                    </div>
                                    <div class="validade-badge">
                                        <i class="bi bi-calendar2-year"></i>
                                        <span id="validadeAnos">0</span> anos
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="validade-presets">
                            <span class="preset-label">Períodos comuns:</span>
                            <button type="button" class="preset-btn" data-dias="30">30 dias</button>
                            <button type="button" class="preset-btn" data-dias="90">90 dias</button>
                            <button type="button" class="preset-btn" data-dias="180">6 meses</button>
                            <button type="button" class="preset-btn" data-dias="365">1 ano</button>
                            <button type="button" class="preset-btn" data-dias="730">2 anos</button>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header-section">
                        <div class="section-icon-wrapper" style="background: linear-gradient(135deg, #0284c7, #38bdf8);">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="section-text">
                            <h2 class="section-title">Controle de Validade no Sistema</h2>
                            <p class="section-description">Defina se o sistema deve gerenciar automaticamente este
                                documento</p>
                        </div>
                    </div>

                    <div class="config-grid">
                        <div class="config-option">
                            <div class="config-header">
                                <div class="config-icon"
                                    style="background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706;">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <div class="config-info">
                                    <span class="config-label">Controlar Validade</span>
                                    <span class="config-desc">Sistema controla e notifica sobre prazo de validade</span>
                                </div>
                            </div>
                            <div class="toggle-switch">
                                <input type="hidden" name="validar" value="0">
                                <input type="checkbox" name="validar" id="validar" class="toggle-input"
                                    value="1" {{ old('validar', $tipoDocumento->validar ?? true) ? 'checked' : '' }}>
                                <label for="validar" class="toggle-label">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="config-option">
                            <div class="config-header">
                                <div class="config-icon"
                                    style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb;">
                                    <i class="bi bi-file-earmark-plus"></i>
                                </div>
                                <div class="config-info">
                                    <span class="config-label">Documento Avulso</span>
                                    <span class="config-desc">Não vincula a clientes, adiciona direto em PIs
                                        específicas</span>
                                </div>
                            </div>
                            <div class="toggle-switch">
                                <input type="hidden" name="avulso" value="0">
                                <input type="checkbox" name="avulso" id="avulso" class="toggle-input"
                                    value="1" {{ old('avulso', $tipoDocumento->avulso ?? false) ? 'checked' : '' }}>
                                <label for="avulso" class="toggle-label">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="alert"
                        style="margin-top: 1.5rem; padding: 1rem; background: rgba(59, 130, 246, 0.08); border-left: 4px solid var(--info-color); border-radius: 0.5rem;">
                        <div style="display: flex; gap: 0.75rem;">
                            <i class="bi bi-info-circle-fill" style="color: var(--info-color); font-size: 1.25rem;"></i>
                            <div style="flex: 1; color: var(--text-color); font-size: 0.9rem; line-height: 1.6;">
                                <strong style="display: block; margin-bottom: 0.5rem; color: #1e40af;">Entenda as
                                    opções:</strong>
                                <ul style="margin: 0; padding-left: 1.25rem;">
                                    <li><strong>Controlar Validade DESATIVADO:</strong> Documento de uso único. Cada PI
                                        requer um novo documento. Não há controle de prazo pelo sistema.</li>
                                    <li><strong>Controlar Validade ATIVADO + Validade Indeterminada DESATIVADO:</strong>
                                        Documento pode ser reutilizado em várias PIs até expirar. Sistema notifica quando
                                        próximo do vencimento.</li>
                                    <li><strong>Controlar Validade ATIVADO + Validade Indeterminada ATIVADO:</strong>
                                        Documento permanente, pode ser reutilizado em todas as PIs sem expiração.</li>
                                    <li><strong>Documentos avulsos:</strong>
                                        Documentos avulsos não são vinculados a clientes. Eles são adicionados diretamente
                                        em PIs específicas por operacionais, gerenciais ou administradores.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header-section">
                        <div class="section-icon-wrapper" style="background: linear-gradient(135deg, #0891b2, #22d3ee);">
                            <i class="bi bi-broadcast"></i>
                        </div>
                        <div class="section-text">
                            <h2 class="section-title">Tipos de Veículo</h2>
                            <p class="section-description">Defina para quais tipos de mídia este documento é exigido</p>
                        </div>
                    </div>

                    <div class="alert"
                        style="margin-bottom: 1.5rem; padding: 1rem; background: rgba(6, 182, 212, 0.08); border-left: 4px solid #0891b2; border-radius: 0.5rem;">
                        <div style="display: flex; gap: 0.75rem;">
                            <i class="bi bi-info-circle-fill" style="color: #0891b2; font-size: 1.25rem;"></i>
                            <div style="flex: 1; color: var(--text-color); font-size: 0.9rem; line-height: 1.6;">
                                Selecione para quais tipos de veículo este documento é exigido. Se <strong>nenhum</strong> for selecionado, o documento será classificado como <strong>Geral</strong> (sem vínculo com tipo de veículo). Se <strong>todos</strong> forem selecionados, será exibido como <strong>Todos os Veículos</strong>.
                            </div>
                        </div>
                    </div>

                    <div class="veiculos-selection-grid">
                        @if(isset($tipoVeiculos) && $tipoVeiculos->count() > 0)
                            @foreach($tipoVeiculos as $veiculo)
                                @php
                                    $isChecked = false;
                                    if(old('tipo_veiculos')) {
                                        $isChecked = in_array($veiculo->id, old('tipo_veiculos', []));
                                    } elseif(isset($tipoDocumento)) {
                                        $isChecked = $tipoDocumento->tipoVeiculos->contains('id', $veiculo->id);
                                    }
                                    
                                    $iconClass = 'bi-check-circle';
                                    $gradientColors = '#6b7280, #9ca3af';
                                    
                                    if(str_contains(strtolower($veiculo->nome), 'rádio') || str_contains(strtolower($veiculo->nome), 'radio') || str_contains(strtolower($veiculo->nome), 'tv')) {
                                        $iconClass = 'bi-broadcast';
                                        $gradientColors = '#dc2626, #f87171';
                                    } elseif(str_contains(strtolower($veiculo->nome), 'internet')) {
                                        $iconClass = 'bi-globe';
                                        $gradientColors = '#2563eb, #60a5fa';
                                    } elseif(str_contains(strtolower($veiculo->nome), 'jornal') || str_contains(strtolower($veiculo->nome), 'revista')) {
                                        $iconClass = 'bi-newspaper';
                                        $gradientColors = '#0891b2, #22d3ee';
                                    }
                                @endphp
                                <div class="veiculo-card {{ $isChecked ? 'selected' : '' }}" data-veiculo-id="{{ $veiculo->id }}">
                                    <input type="checkbox" 
                                           name="tipo_veiculos[]" 
                                           id="veiculo_{{ $veiculo->id }}"
                                           value="{{ $veiculo->id }}"
                                           class="veiculo-checkbox"
                                           {{ $isChecked ? 'checked' : '' }}>
                                    <div class="veiculo-icon" style="background: linear-gradient(135deg, {{ $gradientColors }});">
                                        <i class="bi {{ $iconClass }}"></i>
                                    </div>
                                    <div class="veiculo-info">
                                        <span class="veiculo-nome">{{ $veiculo->nome }}</span>
                                        <span class="veiculo-status">
                                            <i class="bi bi-check-circle-fill"></i>
                                            Selecionado
                                        </span>
                                    </div>
                                    <div class="veiculo-check">
                                        <i class="bi bi-check-lg"></i>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="empty-veiculos">
                                <i class="bi bi-exclamation-circle"></i>
                                <span>Nenhum tipo de veículo cadastrado no sistema.</span>
                            </div>
                        @endif
                    </div>

                    <div class="veiculos-summary" id="veiculosSummary">
                        <div class="summary-icon">
                            <i class="bi bi-check-all"></i>
                        </div>
                        <span class="summary-text" id="veiculosSummaryText">Documento geral - sem vínculo com tipo de veículo</span>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header-section">
                        <div class="section-icon-wrapper" style="background: linear-gradient(135deg, #059669, #10b981);">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div class="section-text">
                            <h2 class="section-title">Status do Tipo</h2>
                            <p class="section-description">Defina se este tipo está disponível no sistema</p>
                        </div>
                    </div>

                    <div class="config-grid">
                        <div class="config-option">
                            <div class="config-header">
                                <div class="config-icon"
                                    style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669;">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                                <div class="config-info">
                                    <span class="config-label">Tipo de Documento Ativo</span>
                                    <span class="config-desc">Disponível para seleção nos Clientes e Documentos</span>
                                </div>
                            </div>
                            <div class="toggle-switch">
                                <input type="hidden" name="ativo" value="0">
                                <input type="checkbox" name="ativo" id="ativo" class="toggle-input"
                                    value="1" {{ old('ativo', $tipoDocumento->ativo ?? true) ? 'checked' : '' }}>
                                <label for="ativo" class="toggle-label">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="action-bar">
                    <button type="button" class="btn-secondary"
                        onclick="window.location.href='{{ route('tipo_documentos.index') }}'">
                        <i class="bi bi-x-lg"></i>
                        <span>Cancelar</span>
                    </button>
                    <button type="submit" class="btn-primary" id="submitBtn">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>{{ isset($tipoDocumento) ? 'Atualizar Tipo' : 'Salvar Tipo' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .veiculos-selection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
        }

        .veiculo-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            user-select: none;
        }

        .veiculo-card:hover {
            border-color: #22d3ee;
            background: #ecfeff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.15);
        }

        .veiculo-card.selected {
            border-color: #0891b2;
            background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%);
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.2);
        }

        .veiculo-checkbox {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .veiculo-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .veiculo-icon i {
            font-size: 1.5rem;
            color: white;
        }

        .veiculo-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .veiculo-nome {
            font-weight: 600;
            color: #1e293b;
            font-size: 1rem;
        }

        .veiculo-status {
            display: none;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.8rem;
            color: #0891b2;
            font-weight: 500;
        }

        .veiculo-card.selected .veiculo-status {
            display: flex;
        }

        .veiculo-check {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .veiculo-check i {
            font-size: 1rem;
            color: transparent;
            transition: all 0.3s ease;
        }

        .veiculo-card.selected .veiculo-check {
            background: #0891b2;
            border-color: #0891b2;
        }

        .veiculo-card.selected .veiculo-check i {
            color: white;
        }

        .veiculos-summary {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 1.25rem;
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border-radius: 10px;
            border: 1px solid #a7f3d0;
        }

        .veiculos-summary.has-selection {
            background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%);
            border-color: #67e8f9;
        }

        .summary-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #059669;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .veiculos-summary.has-selection .summary-icon {
            background: #0891b2;
        }

        .summary-icon i {
            color: white;
            font-size: 1.1rem;
        }

        .summary-text {
            font-size: 0.95rem;
            color: #065f46;
            font-weight: 500;
        }

        .veiculos-summary.has-selection .summary-text {
            color: #0e7490;
        }

        .empty-veiculos {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 2rem;
            background: rgba(245, 158, 11, 0.12);
            border-radius: 10px;
            color: var(--text-on-light-bg);
        }

        .empty-veiculos i {
            font-size: 1.5rem;
        }

        @media (max-width: 640px) {
            .veiculos-selection-grid {
                grid-template-columns: 1fr;
            }
            
            .veiculo-card {
                padding: 1rem;
            }
            
            .veiculo-icon {
                width: 44px;
                height: 44px;
            }
            
            .veiculo-icon i {
                font-size: 1.25rem;
            }
        }
    </style>
@endsection

@push('scripts')
    <script>
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

        @if (session('success'))
            toastr.success("{{ session('success') }}");
        @endif

        @if (session('error'))
            toastr.error("{{ session('error') }}");
        @endif

        @if (session('info'))
            toastr.info("{{ session('info') }}");
        @endif

        @if (session('warning'))
            toastr.warning("{{ session('warning') }}");
        @endif

        $(document).ready(function() {
            const $validadeDias = $('#validade_dias');
            const $validadeMeses = $('#validadeMeses');
            const $validadeAnos = $('#validadeAnos');
            const $validadeIndeterminada = $('#validade_indeterminada');
            const $validadeFieldsContainer = $('#validadeFieldsContainer');
            const $validadeRequired = $('#validadeRequired');
            const $validar = $('#validar');

            function atualizarConversao() {
                const dias = parseInt($validadeDias.val()) || 0;
                const meses = Math.floor(dias / 30);
                const anos = (dias / 365).toFixed(1);

                $validadeMeses.text(meses);
                $validadeAnos.text(anos);
            }

            function toggleValidadeFields() {
                if ($validadeIndeterminada.is(':checked')) {
                    $validadeFieldsContainer.fadeOut(300);
                    $validadeDias.prop('required', false).val('');
                    $validadeRequired.hide();
                } else {
                    $validadeFieldsContainer.fadeIn(300);
                    $validadeDias.prop('required', true);
                    $validadeRequired.show();
                }
            }

            toggleValidadeFields();
            atualizarConversao();

            $validadeIndeterminada.on('change', function() {
                toggleValidadeFields();
            });

            $validadeDias.on('input', atualizarConversao);

            $('.preset-btn').on('click', function() {
                if (!$validadeIndeterminada.is(':checked')) {
                    const dias = $(this).data('dias');
                    $validadeDias.val(dias).trigger('input').focus();

                    $(this).css('transform', 'scale(0.95)');
                    setTimeout(() => {
                        $(this).css('transform', '');
                    }, 150);
                }
            });

            $(document).on('click', '.veiculo-card', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $card = $(this);
                const $checkbox = $card.find('.veiculo-checkbox');
                
                $checkbox.prop('checked', !$checkbox.prop('checked'));
                $card.toggleClass('selected', $checkbox.prop('checked'));
                
                atualizarVeiculosSummary();
            });

            function atualizarVeiculosSummary() {
                const $checkboxes = $('.veiculo-checkbox:checked');
                const $allCheckboxes = $('.veiculo-checkbox');
                const $summary = $('#veiculosSummary');
                const $summaryText = $('#veiculosSummaryText');
                const count = $checkboxes.length;
                const total = $allCheckboxes.length;

                if (count === 0) {
                    $summary.removeClass('has-selection');
                    $summaryText.text('Documento geral - sem vínculo com tipo de veículo');
                } else if (count === total && total > 0) {
                    $summary.addClass('has-selection');
                    $summaryText.text('Todos os veículos selecionados');
                } else {
                    $summary.addClass('has-selection');
                    const nomes = [];
                    $checkboxes.each(function() {
                        nomes.push($(this).closest('.veiculo-card').find('.veiculo-nome').text());
                    });
                    $summaryText.text('Aplica apenas para: ' + nomes.join(', '));
                }
            }

            atualizarVeiculosSummary();

            $('#tipoDocumentoForm').on('submit', function(e) {
                if ($validar.is(':checked') && !$validadeIndeterminada.is(':checked') && !$validadeDias.val()) {
                    e.preventDefault();
                    toastr.error('Informe a validade em dias ou marque como validade indeterminada.');
                    $validadeDias.focus();
                    return false;
                }

                const $submitBtn = $('#submitBtn');
                $submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm" style="width: 1rem; height: 1rem; border-width: 2px;"></span> <span>Salvando...</span>'
                );
            });

            const $nome = $('#nome');
            $nome.on('input', function() {
                const valor = $(this).val();
                if (valor.length > 0) {
                    $(this).val(valor.charAt(0).toUpperCase() + valor.slice(1));
                }
            });
        });
    </script>
@endpush