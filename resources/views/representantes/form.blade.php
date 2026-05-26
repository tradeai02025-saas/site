{{-- resources/views/representantes/form.blade.php --}}

@extends('layouts.app')

@section('title', isset($representante) ? 'Editar Representante' : 'Novo Representante')

@section('content')
    <div class="container-fluid">
        <div class="page-header">
            <div class="page-header-content">
                <div class="page-title-wrapper">
                    <a href="{{ route('representantes.index') }}" class="page-back-btn">
                        <i class="bi bi-arrow-left-circle"></i>
                    </a>
                    <div class="page-icon">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                    <div>
                        <h1 class="page-title">{{ isset($representante) ? 'Editar Representante' : 'Novo Representante' }}</h1>
                        <p class="page-subtitle">
                            @if (isset($representante))
                                Atualize as informações de {{ $representante->user->name ?? $representante->nome_empresa ?? 'Representante' }}
                            @else
                                Informe os dados do representante. Uma senha temporária será gerada e enviada por e-mail.
                            @endif
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

        <form action="{{ isset($representante) ? route('representantes.update', $representante->id) : route('representantes.store') }}"
            method="POST" id="repForm" autocomplete="off">
            @csrf
            @if (isset($representante))
                @method('PUT')
            @endif

            <div class="content-card">
                <div class="card-header-section">
                    <h2 class="card-title">
                        <i class="bi bi-person-vcard-fill"></i>
                        Dados de Acesso
                    </h2>
                    <p class="card-subtitle">
                        @if (isset($representante))
                            Nome e e-mail de login do representante
                        @else
                            Uma senha temporária será gerada e enviada por e-mail. No primeiro login, o representante deverá alterá-la.
                        @endif
                    </p>
                </div>

                <div class="card-content">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="field-label-new">
                                <span>Nome</span>
                                <span class="required-star">*</span>
                            </label>
                            <div class="input-with-icon">
                                <i class="bi bi-person input-icon-left"></i>
                                <input type="text" name="name" class="form-input-new @error('name') is-invalid @enderror"
                                    placeholder="Nome completo do representante"
                                    value="{{ old('name', isset($representante) ? ($representante->user->name ?? '') : '') }}" required autofocus>
                            </div>
                            @error('name')
                                <span class="field-error-message"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="field-label-new">
                                <span>E-mail</span>
                                <span class="required-star">*</span>
                            </label>
                            <div class="input-with-icon">
                                <i class="bi bi-envelope input-icon-left"></i>
                                <input type="email" name="email" id="emailInput" class="form-input-new @error('email') is-invalid @enderror"
                                    placeholder="email@exemplo.com"
                                    value="{{ old('email', isset($representante) ? ($representante->user->email ?? '') : '') }}" required>
                            </div>
                            <span id="emailFeedback" class="field-feedback"></span>
                            @error('email')
                                <span class="field-error-message"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header-section">
                    <h2 class="card-title">
                        <i class="bi bi-building"></i>
                        Dados da Empresa
                    </h2>
                    <p class="card-subtitle">
                        @if (isset($representante))
                            Informações comerciais do representante
                        @else
                            Opcional — o representante também poderá preencher no primeiro acesso
                        @endif
                    </p>
                </div>

                <div class="card-content">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="field-label-new"><span>Nome da Empresa</span></label>
                            <div class="input-with-icon">
                                <i class="bi bi-building input-icon-left"></i>
                                <input type="text" name="nome_empresa"
                                    class="form-input-new @error('nome_empresa') is-invalid @enderror"
                                    placeholder="Nome da empresa representada"
                                    value="{{ old('nome_empresa', $representante->nome_empresa ?? '') }}">
                            </div>
                            @error('nome_empresa')
                                <span class="field-error-message"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="field-label-new"><span>CNPJ</span></label>
                            <div class="input-with-icon">
                                <i class="bi bi-card-text input-icon-left"></i>
                                <input type="text" name="cnpj" id="cnpjInput"
                                    class="form-input-new cnpj-mask @error('cnpj') is-invalid @enderror"
                                    placeholder="00.000.000/0000-00"
                                    value="{{ old('cnpj', isset($representante) && $representante->cnpj && strlen($representante->cnpj) === 14 ? substr($representante->cnpj, 0, 2) . '.' . substr($representante->cnpj, 2, 3) . '.' . substr($representante->cnpj, 5, 3) . '/' . substr($representante->cnpj, 8, 4) . '-' . substr($representante->cnpj, 12, 2) : '') }}">
                            </div>
                            <span id="cnpjFeedback" class="field-feedback"></span>
                            @error('cnpj')
                                <span class="field-error-message"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="field-label-new"><span>Telefone</span></label>
                            <div class="input-with-icon">
                                <i class="bi bi-telephone input-icon-left"></i>
                                <input type="text" name="telefone" id="telefoneInput"
                                    class="form-input-new telefone-mask @error('telefone') is-invalid @enderror"
                                    placeholder="(00) 00000-0000"
                                    value="{{ old('telefone', $representante->telefone ?? '') }}">
                            </div>
                            @error('telefone')
                                <span class="field-error-message"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        @if (isset($representante))
                            <div class="col-md-6">
                                <label class="field-label-new"><span>Status</span></label>
                                <div class="toggle-field">
                                    <label class="toggle-switch-label">
                                        <input type="checkbox" name="ativo" value="1"
                                            {{ old('ativo', $representante->ativo) ? 'checked' : '' }}>
                                        <span class="toggle-track"></span>
                                    </label>
                                    <span class="toggle-status-text" id="toggleText">
                                        {{ $representante->ativo ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </div>
                            </div>
                        @endif

                        <div class="col-12">
                            <label class="field-label-new"><span>Observações</span></label>
                            <div class="input-with-icon">
                                <i class="bi bi-chat-left-text input-icon-left" style="top: 1rem;"></i>
                                <textarea name="observacoes"
                                    class="form-input-new form-textarea-new @error('observacoes') is-invalid @enderror"
                                    rows="3"
                                    placeholder="Observações sobre o representante">{{ old('observacoes', $representante->observacoes ?? '') }}</textarea>
                            </div>
                            @error('observacoes')
                                <span class="field-error-message"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-footer">
                <a href="{{ route('representantes.index') }}" class="btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancelar
                </a>
                <button type="submit" class="btn-primary" id="submitBtn">
                    <i class="bi bi-check-circle"></i>
                    {{ isset($representante) ? 'Salvar Alterações' : 'Criar Representante' }}
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.cnpj-mask').mask('00.000.000/0000-00');
            $('.telefone-mask').mask('(00) 00000-0000');

            var emailTimer = null;
            var emailValido = true;

            $('#emailInput').on('blur', function() {
                validarEmail();
            });

            $('#emailInput').on('input', function() {
                clearTimeout(emailTimer);
                var $feedback = $('#emailFeedback');
                $feedback.html('');
                emailValido = true;
            });

            function validarEmail() {
                var email = $('#emailInput').val().trim();
                var $feedback = $('#emailFeedback');

                if (!email) {
                    $feedback.html('');
                    emailValido = true;
                    return;
                }

                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    $feedback.html('<span class="feedback-error"><i class="bi bi-exclamation-circle"></i> E-mail inválido</span>');
                    emailValido = false;
                    return;
                }

                $.ajax({
                    url: '{{ route("representantes.validar_email") }}',
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: {
                        email: email
                        @if(isset($representante))
                            , user_id: {{ $representante->user_id }}
                        @endif
                    },
                    success: function(response) {
                        if (response.disponivel) {
                            $feedback.html('<span class="feedback-success"><i class="bi bi-check-circle"></i> E-mail disponível</span>');
                            emailValido = true;
                        } else {
                            $feedback.html('<span class="feedback-error"><i class="bi bi-exclamation-circle"></i> ' + response.mensagem + '</span>');
                            emailValido = false;
                        }
                    }
                });
            }

            $('#cnpjInput').on('blur', function() {
                var cnpj = $(this).val().replace(/\D/g, '');
                var $feedback = $('#cnpjFeedback');

                if (cnpj.length === 0) {
                    $feedback.html('');
                    return;
                }

                if (cnpj.length !== 14) {
                    $feedback.html('<span class="feedback-error"><i class="bi bi-exclamation-circle"></i> CNPJ deve ter 14 dígitos</span>');
                    return;
                }

                $.ajax({
                    url: '{{ route("validar.cnpj") }}',
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: {
                        cnpj: cnpj
                        @if(isset($representante))
                            , representante_id: {{ $representante->id }}
                        @endif
                    },
                    success: function(response) {
                        if (response.disponivel) {
                            $feedback.html('<span class="feedback-success"><i class="bi bi-check-circle"></i> CNPJ disponível</span>');
                        } else {
                            $feedback.html('<span class="feedback-error"><i class="bi bi-exclamation-circle"></i> ' + response.mensagem + '</span>');
                        }
                    }
                });
            });

            $('#cnpjInput').on('paste', function(e) {
                e.preventDefault();
                var pastedData = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
                var valor = pastedData.replace(/\D/g, '').substring(0, 14);
                var $el = $(this);
                $el.unmask().val(valor).mask('00.000.000/0000-00');
            });

            $('input[name="ativo"]').on('change', function() {
                $('#toggleText').text($(this).is(':checked') ? 'Ativo' : 'Inativo');
            });

            $('#repForm').on('submit', function(e) {
                if (!emailValido) {
                    e.preventDefault();
                    toastr.error('Corrija o e-mail antes de salvar.');
                    $('#emailInput').focus();
                    return false;
                }
                var btn = $('#submitBtn');
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Salvando...');
            });

            @if (session('success'))
                toastr.success('{{ session('success') }}');
            @elseif (session('error'))
                toastr.error('{{ session('error') }}');
            @endif
        });
    </script>
@endpush

@push('styles')
    <style>
        .page-header { margin-bottom: 1.5rem; }
        .page-header-content { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.75rem; box-shadow: var(--shadow-sm); }
        .page-title-wrapper { display: flex; align-items: center; gap: 1.25rem; }
        .page-back-btn { width: 48px; height: 48px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--text-color); font-size: 1.5rem; transition: all 0.3s ease; text-decoration: none; }
        .page-back-btn:hover { background: var(--primary-color); color: white; transform: translateX(-4px); }
        .page-icon { width: 56px; height: 56px; background: var(--primary-color); color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; flex-shrink: 0; }
        .page-title { font-size: 1.5rem; font-weight: 700; color: var(--text-color); margin: 0; }
        .page-subtitle { color: var(--text-muted); font-size: 0.9rem; margin: 0; }

        .alert-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: flex-start; gap: 1rem; box-shadow: var(--shadow-sm); }
        .alert-danger { border-left: 4px solid #ef4444; background: linear-gradient(135deg, rgba(239, 68, 68, 0.05) 0%, rgba(239, 68, 68, 0.02) 100%); }
        .alert-icon { width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
        .alert-icon i { color: white; }
        .alert-content { flex: 1; }
        .alert-title { font-size: 1rem; font-weight: 700; color: #ef4444; margin: 0 0 0.75rem 0; }
        .alert-list { margin: 0; padding-left: 1.25rem; color: var(--danger-color); }
        .alert-list li { margin-bottom: 0.375rem; }
        .alert-close { background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; transition: all 0.2s ease; }
        .alert-close:hover { background: rgba(0, 0, 0, 0.05); color: var(--text-color); }

        .content-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem; overflow: hidden; }
        .card-header-section { padding: 1.5rem 1.75rem; border-bottom: 1px solid var(--border-color); }
        .card-title { font-size: 1.125rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 0.625rem; }
        .card-title i { color: var(--primary-color); font-size: 1.25rem; }
        .card-subtitle { color: var(--text-muted); font-size: 0.85rem; margin: 0.5rem 0 0 0; }
        .card-content { padding: 1.75rem; }

        .field-label-new { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9375rem; font-weight: 600; color: var(--text-color); margin-bottom: 0.5rem; }
        .field-label-new .required-star { color: #ef4444; font-size: 1.125rem; }

        .input-with-icon { position: relative; display: flex; align-items: center; }
        .input-icon-left { position: absolute; left: 1rem; color: var(--text-muted); font-size: 1rem; z-index: 1; pointer-events: none; }

        .form-input-new, .form-textarea-new {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: var(--bg-body);
            color: var(--text-color);
            font-size: 0.9rem;
            transition: all 0.2s ease;
            font-family: inherit;
        }
        .form-textarea-new { min-height: 100px; resize: vertical; }
        .form-input-new:focus, .form-textarea-new:focus { outline: none; border-color: var(--primary-color); background: var(--bg-card); box-shadow: 0 0 0 3px rgba(0, 94, 130, 0.08); }
        .form-input-new:disabled { opacity: 0.65; cursor: not-allowed; background: var(--bg-body); }
        .form-input-new.is-invalid, .form-textarea-new.is-invalid { border-color: #ef4444; }
        .form-input-new.is-invalid:focus, .form-textarea-new.is-invalid:focus { box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.08); }

        .field-error-message { display: flex; align-items: center; gap: 0.5rem; font-size: 0.8125rem; color: #ef4444; margin-top: 0.375rem; }
        .field-error-message i { font-size: 0.875rem; }

        .field-feedback { display: block; margin-top: 0.375rem; font-size: 0.8125rem; }
        .feedback-success { color: var(--success-color); display: flex; align-items: center; gap: 0.375rem; }
        .feedback-error { color: #ef4444; display: flex; align-items: center; gap: 0.375rem; }

        .toggle-field { display: flex; align-items: center; gap: 0.875rem; padding: 0.625rem 0; }
        .toggle-switch-label { position: relative; display: inline-block; width: 50px; height: 28px; cursor: pointer; }
        .toggle-switch-label input { opacity: 0; width: 0; height: 0; }
        .toggle-track { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #94a3b8; border-radius: 28px; transition: 0.3s; }
        .toggle-track::before { position: absolute; content: ''; height: 22px; width: 22px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.3s; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15); }
        .toggle-switch-label input:checked + .toggle-track { background: var(--success-color); }
        .toggle-switch-label input:checked + .toggle-track::before { transform: translateX(22px); }
        .toggle-status-text { font-weight: 600; font-size: 0.9375rem; color: var(--text-color); }

        .form-footer { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); }
        .btn-primary, .btn-secondary { padding: 0.75rem 1.5rem; border-radius: 10px; font-size: 0.9rem; font-weight: 600; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s ease; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%); color: white; box-shadow: 0 4px 14px rgba(0, 94, 130, 0.25); }
        .btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0, 94, 130, 0.35); color: white; }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .btn-secondary { background: var(--bg-body); color: var(--text-color); border: 1px solid var(--border-color); }
        .btn-secondary:hover { background: var(--border-color); color: var(--text-color); }

        [data-theme="dark"] .content-card { box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3); }

        @media (max-width: 992px) {
            .page-title { font-size: 1.25rem; }
            .card-content { padding: 1.25rem; }
            .form-footer { flex-direction: column-reverse; }
            .btn-primary, .btn-secondary { width: 100%; justify-content: center; }
        }

        @media (max-width: 576px) {
            .page-header-content { padding: 1.25rem; }
            .page-title-wrapper { gap: 1rem; }
            .page-icon { width: 48px; height: 48px; font-size: 1.5rem; }
            .page-title { font-size: 1.125rem; }
            .page-subtitle { font-size: 0.85rem; }
        }
    </style>
@endpush