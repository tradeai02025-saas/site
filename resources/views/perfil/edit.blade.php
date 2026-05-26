{{-- resources/views/perfil/edit.blade.php --}}

@extends('layouts.app')

@section('title', 'Meu Perfil')

@section('content')
    <div class="container-fluid">
        <div class="page-header">
            <div class="page-header-content">
                <div class="page-title-wrapper">
                    <div class="page-icon">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <div>
                        <h1 class="page-title">Meu Perfil</h1>
                        <p class="page-subtitle">Gerencie suas informações pessoais e configurações</p>
                    </div>
                </div>
            </div>
        </div>

        @if ($incompleteStep && auth()->user()->hasAnyRole(['fornecedor', 'representante']))
            <div class="onboarding-alert">
                <div class="alert-icon">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="alert-content">
                    <h4>Complete seu cadastro para acessar o sistema</h4>
                    <p>Você precisa preencher todos os campos obrigatórios antes de utilizar o sistema.</p>
                    <div class="progress-info">
                        <span class="progress-text">Progresso: <strong>{{ $profileCompletion }}%</strong></span>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: {{ $profileCompletion }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="row mb-4">
            <div class="col-12">
                <div class="profile-summary-card">
                    <div class="profile-avatar-section">
                        <div class="profile-avatar">
                            {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2, 'UTF-8')) }}
                            <span class="profile-status">
                                <i class="bi bi-check-circle-fill"></i>
                            </span>
                        </div>
                    </div>

                    <div class="profile-info">
                        <h3 class="profile-name">{{ auth()->user()->name }}</h3>
                        <p class="profile-email">{{ auth()->user()->email }}</p>
                        <span class="profile-badge">
                            {{ auth()->user()->roles->first()->name ?? 'Usuário' }}
                        </span>
                    </div>

                    <div class="profile-meta">
                        <div class="meta-item">
                            <i class="bi bi-calendar-check"></i>
                            <div>
                                <span class="meta-label">Membro desde</span>
                                <span class="meta-value">{{ auth()->user()->created_at->format('d/m/Y') }}</span>
                            </div>
                        </div>
                        <div class="meta-item">
                            <i class="bi bi-clock-history"></i>
                            <div>
                                <span class="meta-label">Última atualização</span>
                                <span class="meta-value">{{ auth()->user()->updated_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @hasrole('fornecedor')
            @if ($incompleteStep)
                <div class="wizard-container">
                    <div
                        class="wizard-step {{ $abaAtiva === 'senha' ? 'active' : '' }} {{ !auth()->user()->needsPasswordChange() ? 'completed' : '' }}">
                        <div class="step-icon">
                            @if (!auth()->user()->needsPasswordChange())
                                <i class="bi bi-check-circle-fill"></i>
                            @else
                                <i class="bi bi-1-circle"></i>
                            @endif
                        </div>
                        <div class="step-info">
                            <div class="step-title">Senha</div>
                            <div class="step-subtitle">Alterar senha temporária</div>
                        </div>
                    </div>

                    <div class="wizard-divider"></div>

                    <div
                        class="wizard-step {{ $abaAtiva === 'empresa' ? 'active' : '' }} {{ !auth()->user()->needsPhoneNumber() ? 'completed' : '' }}">
                        <div class="step-icon">
                            @if (!auth()->user()->needsPhoneNumber())
                                <i class="bi bi-check-circle-fill"></i>
                            @else
                                <i class="bi bi-2-circle"></i>
                            @endif
                        </div>
                        <div class="step-info">
                            <div class="step-title">Empresa</div>
                            <div class="step-subtitle">Telefone de contato</div>
                        </div>
                    </div>

                    <div class="wizard-divider"></div>

                    <div
                        class="wizard-step {{ $abaAtiva === 'banking' ? 'active' : '' }} {{ !auth()->user()->needsBankingInfo() ? 'completed' : '' }}">
                        <div class="step-icon">
                            @if (!auth()->user()->needsBankingInfo())
                                <i class="bi bi-check-circle-fill"></i>
                            @else
                                <i class="bi bi-3-circle"></i>
                            @endif
                        </div>
                        <div class="step-info">
                            <div class="step-title">Bancário</div>
                            <div class="step-subtitle">Dados para pagamento</div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="tabs-nav">
                <button type="button" class="tab-button {{ $abaAtiva === 'geral' ? 'active' : '' }}" data-tab="geral"
                    {{ $incompleteStep ? 'disabled' : '' }}>
                    <i class="bi bi-person-fill"></i>
                    <span>Geral</span>
                </button>
                <button type="button" class="tab-button {{ $abaAtiva === 'senha' ? 'active' : '' }}" data-tab="senha">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span>Senha</span>
                    @if (auth()->user()->needsPasswordChange())
                        <span class="badge bg-danger ms-2">Obrigatório</span>
                    @endif
                </button>
                <button type="button" class="tab-button {{ $abaAtiva === 'empresa' ? 'active' : '' }}" data-tab="empresa">
                    <i class="bi bi-building"></i>
                    <span>Empresa</span>
                    @if (auth()->user()->needsPhoneNumber())
                        <span class="badge bg-warning text-dark ms-2">Obrigatório</span>
                    @endif
                </button>
                <button type="button" class="tab-button {{ $abaAtiva === 'endereco' ? 'active' : '' }}" data-tab="endereco"
                    {{ $incompleteStep ? 'disabled' : '' }}>
                    <i class="bi bi-geo-alt-fill"></i>
                    <span>Endereço</span>
                </button>
                <button type="button" class="tab-button {{ $abaAtiva === 'representantes' ? 'active' : '' }}"
                    data-tab="representantes" {{ $incompleteStep ? 'disabled' : '' }}>
                    <i class="bi bi-telephone-fill"></i>
                    <span>Contatos e Representação</span>
                    <span class="tab-optional">Opcional</span>
                </button>
                <button type="button" class="tab-button {{ $abaAtiva === 'banking' ? 'active' : '' }}" data-tab="banking">
                    <i class="bi bi-bank"></i>
                    <span>Dados Bancários</span>
                    @if (auth()->user()->needsBankingInfo())
                        <span class="badge bg-warning text-dark ms-2">Obrigatório</span>
                    @endif
                </button>
            </div>

            <div class="tab-content-area {{ $abaAtiva === 'geral' ? 'active' : '' }}" id="tab-geral">
                <form action="{{ route('perfil.update') }}" method="POST" id="formPerfil">
                    @csrf
                    @method('PUT')

                    <div class="content-card">
                        <div class="card-header-section">
                            <h2 class="card-title">
                                <i class="bi bi-person-badge"></i>
                                Informações Pessoais
                            </h2>
                        </div>

                        <div class="card-content">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="input-label">
                                        Nome <span class="required">*</span>
                                    </label>
                                    <div class="input-wrapper">
                                        <i class="bi bi-person"></i>
                                        <input type="text" class="input-field @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name', auth()->user()->name) }}"
                                            required>
                                    </div>
                                    @error('name')
                                        <span class="input-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="input-label">
                                        E-mail <span class="required">*</span>
                                    </label>
                                    <div class="input-wrapper">
                                        <i class="bi bi-envelope"></i>
                                        <input type="email" class="input-field @error('email') is-invalid @enderror"
                                            id="email" name="email" value="{{ old('email', auth()->user()->email) }}"
                                            required>
                                    </div>
                                    @error('email')
                                        <span class="input-error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-footer">
                                <a href="{{ previous_route('dashboard') }}" class="btn-secondary">
                                    <i class="bi bi-arrow-left-circle"></i>
                                    Voltar
                                </a>
                                <button type="submit" class="btn-primary">
                                    <i class="bi bi-check-circle"></i>
                                    Salvar Alterações
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="tab-content-area {{ $abaAtiva === 'senha' ? 'active' : '' }}" id="tab-senha">
                <form action="{{ route('perfil.update_password') }}" method="POST" id="formSenha">
                    @csrf
                    @method('PUT')

                    <div class="content-card">
                        <div class="card-header-section">
                            <h2 class="card-title">
                                <i class="bi bi-shield-lock"></i>
                                Alterar Senha
                            </h2>
                            @if (auth()->user()->needsPasswordChange())
                                <div class="alert alert-warning mt-3">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <strong>Ação obrigatória:</strong> Você precisa alterar sua senha temporária antes de
                                    continuar.
                                </div>
                            @else
                                <p class="card-subtitle">Mantenha sua conta segura com uma senha forte</p>
                            @endif
                        </div>

                        <div class="card-content">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="current_password" class="input-label">
                                        Senha Atual <span class="required">*</span>
                                    </label>
                                    <div class="input-wrapper">
                                        <i class="bi bi-lock"></i>
                                        <input type="password"
                                            class="input-field @error('current_password') is-invalid @enderror"
                                            id="current_password" name="current_password" maxlength="64" required>
                                        <button class="toggle-password" type="button" data-target="current_password"
                                            title="Mostrar senha">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    @error('current_password')
                                        <span class="input-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="password" class="input-label">
                                        Nova Senha <span class="required">*</span>
                                    </label>
                                    <div class="input-wrapper">
                                        <i class="bi bi-lock-fill"></i>
                                        <input type="password" class="input-field @error('password') is-invalid @enderror"
                                            id="password" name="password" maxlength="64" required>
                                        <button class="btn-generate-password" type="button" id="generatePasswordBtn"
                                            title="Gerar senha forte">
                                            <i class="bi bi-stars"></i>
                                        </button>
                                        <button class="toggle-password" type="button" data-target="password"
                                            title="Mostrar senha">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>

                                    <div class="password-strength-wrapper" id="passwordStrengthWrapper"
                                        style="display: none;">
                                        <div class="password-strength-bar">
                                            <div class="password-strength-fill" id="passwordStrengthFill"></div>
                                        </div>
                                        <div class="password-strength-text" id="passwordStrengthText">Muito fraca</div>
                                    </div>

                                    <div class="password-requirements">
                                        <div class="requirement-item" id="req-length">
                                            <i class="bi bi-circle"></i>
                                            <span>Mínimo de 8 caracteres</span>
                                        </div>
                                        <div class="requirement-item" id="req-uppercase">
                                            <i class="bi bi-circle"></i>
                                            <span>Pelo menos uma letra maiúscula (A-Z)</span>
                                        </div>
                                        <div class="requirement-item" id="req-lowercase">
                                            <i class="bi bi-circle"></i>
                                            <span>Pelo menos uma letra minúscula (a-z)</span>
                                        </div>
                                        <div class="requirement-item" id="req-number">
                                            <i class="bi bi-circle"></i>
                                            <span>Pelo menos um número (0-9)</span>
                                        </div>
                                        <div class="requirement-item" id="req-special">
                                            <i class="bi bi-circle"></i>
                                            <span>Pelo menos um caractere especial (!@#$%^&*)</span>
                                        </div>
                                    </div>

                                    @error('password')
                                        <span class="input-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="password_confirmation" class="input-label">
                                        Confirmar Nova Senha <span class="required">*</span>
                                    </label>
                                    <div class="input-wrapper">
                                        <i class="bi bi-lock-fill"></i>
                                        <input type="password" class="input-field" id="password_confirmation"
                                            name="password_confirmation" maxlength="64" required>
                                        <button class="toggle-password" type="button" data-target="password_confirmation"
                                            title="Mostrar senha">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <span class="password-match-message" id="passwordMatchMessage"></span>
                                </div>
                            </div>

                            <div class="form-footer">
                                @if (!auth()->user()->needsPasswordChange())
                                    <a href="{{ previous_route('dashboard') }}" class="btn-secondary">
                                        <i class="bi bi-arrow-left-circle"></i>
                                        Voltar
                                    </a>
                                @endif
                                <button type="submit" class="btn-primary" id="submitPasswordBtn" disabled>
                                    <i class="bi bi-shield-check"></i>
                                    {{ auth()->user()->needsPasswordChange() ? 'Alterar Senha e Continuar' : 'Atualizar Senha' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="tab-content-area {{ $abaAtiva === 'empresa' ? 'active' : '' }}" id="tab-empresa">
                <form action="{{ route('perfil.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    @if (auth()->user()->fornecedor)
                        <div class="content-card">
                            <div class="card-header-section">
                                <h2 class="card-title">
                                    <i class="bi bi-building"></i>
                                    Informações da Empresa
                                </h2>
                                @if (auth()->user()->needsPhoneNumber())
                                    <div class="alert alert-warning mt-3">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <strong>Campo obrigatório:</strong> Cadastre um telefone de contato para continuar.
                                    </div>
                                @else
                                    <p class="card-subtitle">Dados corporativos do fornecedor</p>
                                @endif
                            </div>

                            <div class="card-content">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="field-label-new">
                                            <span>Razão Social</span>
                                            <span class="required-star">*</span>
                                        </label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-building input-icon-left"></i>
                                            <input type="text" name="razao_social"
                                                class="form-input-new @error('razao_social') is-invalid @enderror"
                                                value="{{ old('razao_social', auth()->user()->fornecedor->razao_social) }}"
                                                placeholder="Nome jurídico da empresa" required>
                                        </div>
                                        @error('razao_social')
                                            <span class="field-error-message">
                                                <i class="bi bi-exclamation-circle"></i>
                                                {{ $message }}
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="field-label-new">
                                            <span>CNPJ</span>
                                            <span class="required-star">*</span>
                                        </label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-card-text input-icon-left"></i>
                                            <input type="text" name="cnpj" id="cnpj"
                                                class="form-input-new cnpj-mask @error('cnpj') is-invalid @enderror"
                                                value="{{ old('cnpj', auth()->user()->fornecedor->cnpj ? substr(auth()->user()->fornecedor->cnpj, 0, 2) . '.' . substr(auth()->user()->fornecedor->cnpj, 2, 3) . '.' . substr(auth()->user()->fornecedor->cnpj, 5, 3) . '/' . substr(auth()->user()->fornecedor->cnpj, 8, 4) . '-' . substr(auth()->user()->fornecedor->cnpj, 12, 2) : '') }}"
                                                placeholder="00.000.000/0000-00" readonly required>
                                        </div>
                                        @error('cnpj')
                                            <span class="field-error-message">
                                                <i class="bi bi-exclamation-circle"></i>
                                                {{ $message }}
                                            </span>
                                        @enderror
                                        <span class="field-help-text">
                                            <i class="bi bi-lock"></i>
                                            O CNPJ não pode ser alterado
                                        </span>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="field-label-new">
                                            <span>Nome Fantasia</span>
                                            <span class="required-star">*</span>
                                        </label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-shop input-icon-left"></i>
                                            <input type="text" name="nome_fantasia" id="nome_fantasia"
                                                class="form-input-new @error('nome_fantasia') is-invalid @enderror"
                                                placeholder="Nome comercial da empresa"
                                                value="{{ old('nome_fantasia', auth()->user()->fornecedor->nome_fantasia ?? '') }}"
                                                required>
                                        </div>
                                        @error('nome_fantasia')
                                            <span class="field-error-message">
                                                <i class="bi bi-exclamation-circle"></i>
                                                {{ $message }}
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="field-label-new">
                                            <span>Telefone</span>
                                            @if (auth()->user()->needsPhoneNumber())
                                                <span class="required-star">*</span>
                                            @else
                                                <span class="optional-badge">Opcional</span>
                                            @endif
                                        </label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-telephone input-icon-left"></i>
                                            <input type="text" name="telefone"
                                                class="form-input-new telefone-mask @error('telefone') is-invalid @enderror"
                                                value="{{ old('telefone', auth()->user()->fornecedor->telefone) }}"
                                                placeholder="(00) 00000-0000"
                                                {{ auth()->user()->needsPhoneNumber() ? 'required' : '' }}>
                                        </div>
                                        @error('telefone')
                                            <span class="field-error-message">
                                                <i class="bi bi-exclamation-circle"></i>
                                                {{ $message }}
                                            </span>
                                        @enderror
                                        @if (auth()->user()->needsPhoneNumber())
                                            <span class="field-help-text text-warning">
                                                <i class="bi bi-exclamation-triangle"></i>
                                                Campo obrigatório para completar seu cadastro
                                            </span>
                                        @endif
                                    </div>

                                    <div class="col-md-6">
                                        <label class="field-label-new">
                                            <span>Email de Contato</span>
                                        </label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-envelope-at input-icon-left"></i>
                                            <input type="email" name="email_contato"
                                                class="form-input-new @error('email_contato') is-invalid @enderror"
                                                value="{{ old('email_contato', auth()->user()->fornecedor->email_contato) }}"
                                                placeholder="contato@empresa.com">
                                        </div>
                                        @error('email_contato')
                                            <span class="field-error-message">
                                                <i class="bi bi-exclamation-circle"></i>
                                                {{ $message }}
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="col-md-12">
                                        <label class="field-label-new">
                                            <span>Observações</span>
                                            <span class="optional-badge">Opcional</span>
                                        </label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-chat-text input-icon-left"></i>
                                            <textarea name="observacoes" class="form-textarea-new @error('observacoes') is-invalid @enderror"
                                                placeholder="Informações adicionais sobre o fornecedor">{{ old('observacoes', auth()->user()->fornecedor->observacoes) }}</textarea>
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
                                    @if (auth()->user()->needsPasswordChange())
                                        <button type="button" class="btn-secondary" onclick="switchTab('senha')">
                                            <i class="bi bi-arrow-left-circle"></i>
                                            Voltar
                                        </button>
                                    @endif
                                    <button type="submit" class="btn-primary">
                                        <i class="bi bi-check-circle"></i>
                                        {{ auth()->user()->needsPhoneNumber() ? 'Salvar e Continuar' : 'Salvar Alterações' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </form>
            </div>

            <div class="tab-content-area {{ $abaAtiva === 'endereco' ? 'active' : '' }}" id="tab-endereco">
                <form action="{{ route('perfil.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    @if (auth()->user()->fornecedor)
                        @php
                            $endereco = auth()->user()->fornecedor->enderecos->first();
                        @endphp
                        <div class="content-card">
                            <div class="card-header-section">
                                <h2 class="card-title">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    Endereço da Empresa
                                </h2>
                                <p class="card-subtitle">Localização física da sede</p>
                            </div>

                            <div class="card-content">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="field-label-new">
                                            <span>CEP</span>
                                            <span class="optional-badge">Opcional</span>
                                        </label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-mailbox input-icon-left"></i>
                                            <input type="text" name="cep" id="cep"
                                                class="form-input-new cep-mask @error('cep') is-invalid @enderror"
                                                placeholder="00000-000" value="{{ old('cep', $endereco->cep ?? '') }}">
                                            <div class="cep-loading">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Buscando...</span>
                                                </div>
                                            </div>
                                        </div>
                                        @error('cep')
                                            <span class="field-error-message">
                                                <i class="bi bi-exclamation-circle"></i>
                                                {{ $message }}
                                            </span>
                                        @enderror
                                        <span class="field-help-text">
                                            <i class="bi bi-info-circle"></i>
                                            O sistema buscará o endereço automaticamente
                                        </span>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="field-label-new">
                                            <span>Estado</span>
                                            <span class="optional-badge">Opcional</span>
                                        </label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-map input-icon-left"></i>
                                            <select name="estado" id="estado"
                                                class="form-select-new @error('estado') is-invalid @enderror">
                                                <option value="">Selecione</option>
                                                @php
                                                    $estados = [
                                                        'AC' => 'Acre',
                                                        'AL' => 'Alagoas',
                                                        'AP' => 'Amapá',
                                                        'AM' => 'Amazonas',
                                                        'BA' => 'Bahia',
                                                        'CE' => 'Ceará',
                                                        'DF' => 'Distrito Federal',
                                                        'ES' => 'Espírito Santo',
                                                        'GO' => 'Goiás',
                                                        'MA' => 'Maranhão',
                                                        'MT' => 'Mato Grosso',
                                                        'MS' => 'Mato Grosso do Sul',
                                                        'MG' => 'Minas Gerais',
                                                        'PA' => 'Pará',
                                                        'PB' => 'Paraíba',
                                                        'PR' => 'Paraná',
                                                        'PE' => 'Pernambuco',
                                                        'PI' => 'Piauí',
                                                        'RJ' => 'Rio de Janeiro',
                                                        'RN' => 'Rio Grande do Norte',
                                                        'RS' => 'Rio Grande do Sul',
                                                        'RO' => 'Rondônia',
                                                        'RR' => 'Roraima',
                                                        'SC' => 'Santa Catarina',
                                                        'SP' => 'São Paulo',
                                                        'SE' => 'Sergipe',
                                                        'TO' => 'Tocantins',
                                                    ];
                                                    $estadoAtual = old('estado', $endereco->estado ?? '');
                                                @endphp
                                                @foreach ($estados as $sigla => $nome)
                                                    <option value="{{ $sigla }}"
                                                        {{ $estadoAtual == $sigla ? 'selected' : '' }}>{{ $sigla }} -
                                                        {{ $nome }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('estado')
                                            <span class="field-error-message">
                                                <i class="bi bi-exclamation-circle"></i>
                                                {{ $message }}
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="field-label-new">
                                            <span>Cidade</span>
                                            <span class="optional-badge">Opcional</span>
                                        </label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-pin-map input-icon-left"></i>
                                            <input type="text" name="cidade" id="cidade"
                                                class="form-input-new @error('cidade') is-invalid @enderror"
                                                placeholder="Nome da cidade"
                                                value="{{ old('cidade', $endereco->cidade ?? '') }}">
                                        </div>
                                        @error('cidade')
                                            <span class="field-error-message">
                                                <i class="bi bi-exclamation-circle"></i>
                                                {{ $message }}
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="field-label-new">
                                            <span>Bairro</span>
                                            <span class="optional-badge">Opcional</span>
                                        </label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-signpost-split input-icon-left"></i>
                                            <input type="text" name="bairro" id="bairro"
                                                class="form-input-new @error('bairro') is-invalid @enderror"
                                                placeholder="Nome do bairro"
                                                value="{{ old('bairro', $endereco->bairro ?? '') }}">
                                        </div>
                                        @error('bairro')
                                            <span class="field-error-message">
                                                <i class="bi bi-exclamation-circle"></i>
                                                {{ $message }}
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="field-label-new">
                                            <span>Logradouro</span>
                                            <span class="optional-badge">Opcional</span>
                                        </label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-signpost input-icon-left"></i>
                                            <input type="text" name="logradouro" id="logradouro"
                                                class="form-input-new @error('logradouro') is-invalid @enderror"
                                                placeholder="Rua, Avenida, Travessa..."
                                                value="{{ old('logradouro', $endereco->logradouro ?? '') }}">
                                        </div>
                                        @error('logradouro')
                                            <span class="field-error-message">
                                                <i class="bi bi-exclamation-circle"></i>
                                                {{ $message }}
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="field-label-new">
                                            <span>Número</span>
                                            <span class="optional-badge">Opcional</span>
                                        </label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-hash input-icon-left"></i>
                                            <input type="text" name="numero" id="numero"
                                                class="form-input-new @error('numero') is-invalid @enderror" placeholder="Nº"
                                                value="{{ old('numero', $endereco->numero ?? '') }}">
                                        </div>
                                        @error('numero')
                                            <span class="field-error-message">
                                                <i class="bi bi-exclamation-circle"></i>
                                                {{ $message }}
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="col-md-12">
                                        <label class="field-label-new">
                                            <span>Complemento</span>
                                            <span class="optional-badge">Opcional</span>
                                        </label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-building input-icon-left"></i>
                                            <input type="text" name="complemento" id="complemento"
                                                class="form-input-new @error('complemento') is-invalid @enderror"
                                                placeholder="Apto, Sala, Bloco..."
                                                value="{{ old('complemento', $endereco->complemento ?? '') }}">
                                        </div>
                                        @error('complemento')
                                            <span class="field-error-message">
                                                <i class="bi bi-exclamation-circle"></i>
                                                {{ $message }}
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-footer">
                                    <button type="button" class="btn-secondary" onclick="switchTab('empresa')">
                                        <i class="bi bi-arrow-left-circle"></i>
                                        Voltar
                                    </button>
                                    <button type="submit" class="btn-primary">
                                        <i class="bi bi-check-circle"></i>
                                        Salvar Endereço
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </form>
            </div>

            <div class="tab-content-area {{ $abaAtiva === 'representantes' ? 'active' : '' }}" id="tab-representantes">
                <form action="{{ route('perfil.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    @if (auth()->user()->fornecedor)
                        <div class="content-card">
                            <div class="card-header-section">
                                <h2 class="card-title">
                                    <i class="bi bi-telephone-fill"></i>
                                    Contatos da Empresa
                                </h2>
                                <p class="card-subtitle">Pessoas de contato que a Trade utiliza para comunicação sobre seus PIs. Esses contatos não acessam o sistema.</p>
                            </div>

                            <div class="card-content">
                                <div class="dynamic-items-container" id="representantesContainer">
                                    @if (auth()->user()->fornecedor->representantes->count() > 0)
                                        @foreach (auth()->user()->fornecedor->representantes as $index => $rep)
                                            <div class="dynamic-item" data-index="{{ $index }}">
                                                <input type="hidden" name="representantes[{{ $index }}][id]"
                                                    value="{{ $rep->id }}">

                                                <div class="dynamic-item-header">
                                                    <h4 class="dynamic-item-title">
                                                        <i class="bi bi-person-circle"></i>
                                                        Contato #<span class="item-number">{{ $index + 1 }}</span>
                                                    </h4>
                                                    <button type="button" class="btn-remove-item"
                                                        onclick="removeRepresentante(this)">
                                                        <i class="bi bi-trash"></i>
                                                        <span>Remover</span>
                                                    </button>
                                                </div>

                                                <div class="row g-3 mb-3">
                                                    <div class="col-md-6">
                                                        <label class="field-label-new">
                                                            <span>Nome Completo</span>
                                                            <span class="optional-badge">Opcional</span>
                                                        </label>
                                                        <div class="input-with-icon">
                                                            <i class="bi bi-person input-icon-left"></i>
                                                            <input type="text"
                                                                name="representantes[{{ $index }}][nome_completo]"
                                                                class="form-input-new"
                                                                value="{{ old('representantes.' . $index . '.nome_completo', $rep->nome_completo) }}"
                                                                placeholder="Nome completo do contato">
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="field-label-new">
                                                            <span>CPF</span>
                                                            <span class="optional-badge">Opcional</span>
                                                        </label>
                                                        <div class="input-with-icon">
                                                            <i class="bi bi-card-text input-icon-left"></i>
                                                            <input type="text"
                                                                name="representantes[{{ $index }}][cpf]"
                                                                class="form-input-new cpf-mask"
                                                                value="{{ old('representantes.' . $index . '.cpf', $rep->cpf ? substr($rep->cpf, 0, 3) . '.' . substr($rep->cpf, 3, 3) . '.' . substr($rep->cpf, 6, 3) . '-' . substr($rep->cpf, 9, 2) : '') }}"
                                                                placeholder="000.000.000-00">
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="field-label-new">
                                                            <span>Telefone 1</span>
                                                            <span class="optional-badge">Opcional</span>
                                                        </label>
                                                        <div class="input-with-icon">
                                                            <i class="bi bi-telephone input-icon-left"></i>
                                                            <input type="text"
                                                                name="representantes[{{ $index }}][telefone_1]"
                                                                class="form-input-new telefone-mask"
                                                                value="{{ old('representantes.' . $index . '.telefone_1', $rep->telefone_1) }}"
                                                                placeholder="(00) 00000-0000">
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="field-label-new">
                                                            <span>Telefone 2</span>
                                                            <span class="optional-badge">Opcional</span>
                                                        </label>
                                                        <div class="input-with-icon">
                                                            <i class="bi bi-telephone input-icon-left"></i>
                                                            <input type="text"
                                                                name="representantes[{{ $index }}][telefone_2]"
                                                                class="form-input-new telefone-mask"
                                                                value="{{ old('representantes.' . $index . '.telefone_2', $rep->telefone_2) }}"
                                                                placeholder="(00) 00000-0000">
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="field-label-new">
                                                            <span>E-mail 1</span>
                                                            <span class="optional-badge">Opcional</span>
                                                        </label>
                                                        <div class="input-with-icon">
                                                            <i class="bi bi-envelope input-icon-left"></i>
                                                            <input type="email"
                                                                name="representantes[{{ $index }}][email_1]"
                                                                class="form-input-new"
                                                                value="{{ old('representantes.' . $index . '.email_1', $rep->email_1) }}"
                                                                placeholder="email@exemplo.com">
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="field-label-new">
                                                            <span>E-mail 2</span>
                                                            <span class="optional-badge">Opcional</span>
                                                        </label>
                                                        <div class="input-with-icon">
                                                            <i class="bi bi-envelope input-icon-left"></i>
                                                            <input type="email"
                                                                name="representantes[{{ $index }}][email_2]"
                                                                class="form-input-new"
                                                                value="{{ old('representantes.' . $index . '.email_2', $rep->email_2) }}"
                                                                placeholder="email2@exemplo.com">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="empty-state-dynamic" id="emptyRepresentantes">
                                            <i class="bi bi-person-plus"></i>
                                            <p>Nenhum contato adicionado. Clique em "Adicionar Contato" para
                                                começar.</p>
                                        </div>
                                    @endif
                                </div>

                                <button type="button" class="btn-add-dynamic" id="addRepresentante">
                                    <i class="bi bi-plus-circle"></i>
                                    <span>Adicionar Contato</span>
                                </button>

                                <div class="form-footer">
                                    <button type="button" class="btn-secondary" onclick="switchTab('endereco')">
                                        <i class="bi bi-arrow-left-circle"></i>
                                        Voltar
                                    </button>
                                    <button type="submit" class="btn-primary">
                                        <i class="bi bi-check-circle"></i>
                                        Salvar Contatos
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </form>

                @if(auth()->user()->fornecedor)
                    <div class="content-card mt-4">
                        <div class="card-header-section">
                            <h2 class="card-title">
                                <i class="bi bi-building-check"></i>
                                Representação Comercial
                            </h2>
                            <p class="card-subtitle">Empresa ou profissional autorizado a acessar o sistema em seu nome, visualizar seus PIs e enviar documentos</p>
                        </div>

                        <div class="card-content">
                            <div class="alert" style="background: rgba(14,165,233,.08); border: 1px solid rgba(14,165,233,.2); border-radius: 10px; padding: 1rem; margin-bottom: 1.5rem; display: flex; align-items: flex-start; gap: .75rem;">
                                <i class="bi bi-info-circle-fill" style="color: var(--info-color); font-size: 1.25rem; flex-shrink: 0; margin-top: 2px;"></i>
                                <div style="font-size: .875rem; color: var(--text-color);">
                                    <strong>Como funciona:</strong> Busque o representante cadastrado no sistema, solicite o vínculo e aguarde a aprovação de um gestor. Após aprovado, o representante poderá acessar seus PIs e enviar documentos em seu nome.
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="field-label-new">
                                    <span>Buscar Representante Comercial</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-search input-icon-left"></i>
                                    <input type="text" class="form-input-new" id="buscaRepresentanteUsuario"
                                        placeholder="Digite o nome, email ou empresa do representante comercial (mín. 3 caracteres)"
                                        autocomplete="off">
                                </div>
                                <div id="resultadosBuscaRepresentante" class="mt-2" style="display:none;"></div>
                            </div>

                            <div id="listaVinculosRepresentante">
                                @if(isset($vinculosRepresentante) && $vinculosRepresentante->count() > 0)
                                    @foreach($vinculosRepresentante as $vinculo)
                                        <div class="dynamic-item mb-3" id="vinculo-{{ $vinculo->id }}">
                                            <div class="dynamic-item-header">
                                                <h4 class="dynamic-item-title">
                                                    <i class="bi bi-person-badge"></i>
                                                    {{ $vinculo->representanteUsuario->name ?? 'Representante' }}
                                                    @if($vinculo->representanteUsuario && $vinculo->representanteUsuario->nome_empresa)
                                                        <small class="text-muted ms-2">{{ $vinculo->representanteUsuario->nome_empresa }}</small>
                                                    @endif
                                                </h4>
                                                <div class="d-flex align-items-center gap-2">
                                                    @if($vinculo->status_vinculo === 'pendente')
                                                        <span class="badge" style="background-color: var(--warning-color); color: #000;">
                                                            <i class="bi bi-clock"></i> Pendente
                                                        </span>
                                                    @else
                                                        <span class="badge" style="background-color: var(--success-color); color: #fff;">
                                                            <i class="bi bi-check-circle"></i> Aprovado
                                                        </span>
                                                    @endif
                                                    <button type="button" class="btn-remove-item" onclick="cancelarVinculoRepresentante({{ $vinculo->id }}, this)">
                                                        <i class="bi bi-x-circle"></i>
                                                        <span>Remover</span>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="row g-2 px-3 pb-3">
                                                <div class="col-md-4">
                                                    <small class="text-muted">Email:</small>
                                                    <div>{{ $vinculo->representanteUsuario->user->email ?? '-' }}</div>
                                                </div>
                                                @if($vinculo->representanteUsuario && $vinculo->representanteUsuario->cnpj)
                                                    <div class="col-md-4">
                                                        <small class="text-muted">CNPJ:</small>
                                                        <div>{{ $vinculo->representanteUsuario->cnpj }}</div>
                                                    </div>
                                                @endif
                                                <div class="col-md-4">
                                                    <small class="text-muted">Solicitado em:</small>
                                                    <div>{{ $vinculo->created_at->format('d/m/Y H:i') }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="empty-state-dynamic" id="emptyVinculosRepresentante">
                                        <i class="bi bi-link-45deg"></i>
                                        <p>Nenhum representante comercial vinculado. Use a busca acima para encontrar e solicitar vínculo.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="tab-content-area {{ $abaAtiva === 'banking' ? 'active' : '' }}" id="tab-banking">
                <form action="{{ route('perfil.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    @if (auth()->user()->fornecedor)
                        <div class="content-card">
                            <div class="card-header-section">
                                <h2 class="card-title">
                                    <i class="bi bi-bank"></i>
                                    Dados Bancários
                                </h2>
                                @if (auth()->user()->needsBankingInfo())
                                    <div class="alert alert-warning mt-3">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <strong>Ação obrigatória:</strong> Você precisa cadastrar pelo menos uma conta bancária
                                        para completar seu cadastro.
                                    </div>
                                @else
                                    <p class="card-subtitle">Contas bancárias para pagamento</p>
                                @endif
                            </div>

                            <div class="card-content">
                                <div class="dynamic-items-container" id="dadosBancariosContainer">
                                    @if (auth()->user()->fornecedor->dadosBancarios->count() > 0)
                                        @foreach (auth()->user()->fornecedor->dadosBancarios as $index => $banco)
                                            <div class="dynamic-item" data-index="{{ $index }}">
                                                <input type="hidden" name="dados_bancarios[{{ $index }}][id]"
                                                    value="{{ $banco->id }}">

                                                <div class="dynamic-item-header">
                                                    <h4 class="dynamic-item-title">
                                                        <i class="bi bi-credit-card"></i>
                                                        Conta Bancária #<span class="item-number">{{ $index + 1 }}</span>
                                                    </h4>
                                                    <button type="button" class="btn-remove-item"
                                                        onclick="removeDadosBancarios(this)">
                                                        <i class="bi bi-trash"></i>
                                                        <span>Remover</span>
                                                    </button>
                                                </div>

                                                <div class="row g-3 mb-3">
                                                    <div class="col-md-6">
                                                        <label class="field-label-new">
                                                            <span>Banco</span>
                                                            <span class="required-star">*</span>
                                                        </label>
                                                        <div class="input-with-icon">
                                                            <i class="bi bi-bank input-icon-left"></i>
                                                            <input type="text"
                                                                name="dados_bancarios[{{ $index }}][banco]"
                                                                class="form-input-new"
                                                                value="{{ old('dados_bancarios.' . $index . '.banco', $banco->banco) }}"
                                                                placeholder="Nome do banco" required>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="field-label-new">
                                                            <span>Tipo de Conta</span>
                                                            <span class="required-star">*</span>
                                                        </label>
                                                        <div class="input-with-icon">
                                                            <i class="bi bi-wallet2 input-icon-left"></i>
                                                            <select name="dados_bancarios[{{ $index }}][tipo_conta]"
                                                                class="form-select-new" required>
                                                                <option value="">Selecione</option>
                                                                <option value="corrente"
                                                                    {{ old('dados_bancarios.' . $index . '.tipo_conta', $banco->tipo_conta) == 'corrente' ? 'selected' : '' }}>
                                                                    Corrente</option>
                                                                <option value="poupanca"
                                                                    {{ old('dados_bancarios.' . $index . '.tipo_conta', $banco->tipo_conta) == 'poupanca' ? 'selected' : '' }}>
                                                                    Poupança</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="field-label-new">
                                                            <span>Agência</span>
                                                            <span class="required-star">*</span>
                                                        </label>
                                                        <div class="input-with-icon">
                                                            <i class="bi bi-building input-icon-left"></i>
                                                            <input type="text"
                                                                name="dados_bancarios[{{ $index }}][agencia]"
                                                                class="form-input-new"
                                                                value="{{ old('dados_bancarios.' . $index . '.agencia', $banco->agencia) }}"
                                                                placeholder="0000" required>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="field-label-new">
                                                            <span>Dígito Agência</span>
                                                            <span class="optional-badge">Opcional</span>
                                                        </label>
                                                        <div class="input-with-icon">
                                                            <i class="bi bi-hash input-icon-left"></i>
                                                            <input type="text"
                                                                name="dados_bancarios[{{ $index }}][digito_agencia]"
                                                                class="form-input-new"
                                                                value="{{ old('dados_bancarios.' . $index . '.digito_agencia', $banco->digito_agencia) }}"
                                                                placeholder="0" maxlength="2">
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="field-label-new">
                                                            <span>Conta</span>
                                                            <span class="required-star">*</span>
                                                        </label>
                                                        <div class="input-with-icon">
                                                            <i class="bi bi-credit-card-2-front input-icon-left"></i>
                                                            <input type="text"
                                                                name="dados_bancarios[{{ $index }}][conta]"
                                                                class="form-input-new"
                                                                value="{{ old('dados_bancarios.' . $index . '.conta', $banco->conta) }}"
                                                                placeholder="00000000" required>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="field-label-new">
                                                            <span>Dígito Conta</span>
                                                            <span class="required-star">*</span>
                                                        </label>
                                                        <div class="input-with-icon">
                                                            <i class="bi bi-hash input-icon-left"></i>
                                                            <input type="text"
                                                                name="dados_bancarios[{{ $index }}][digito_conta]"
                                                                class="form-input-new"
                                                                value="{{ old('dados_bancarios.' . $index . '.digito_conta', $banco->digito_conta) }}"
                                                                placeholder="0" maxlength="2" required>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="field-label-new">
                                                            <span>Titular da Conta</span>
                                                            <span class="required-star">*</span>
                                                        </label>
                                                        <div class="input-with-icon">
                                                            <i class="bi bi-person input-icon-left"></i>
                                                            <input type="text"
                                                                name="dados_bancarios[{{ $index }}][titular]"
                                                                class="form-input-new"
                                                                value="{{ old('dados_bancarios.' . $index . '.titular', $banco->titular) }}"
                                                                placeholder="Nome do titular" required>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="field-label-new">
                                                            <span>CPF/CNPJ do Titular</span>
                                                            <span class="required-star">*</span>
                                                        </label>
                                                        <div class="input-with-icon">
                                                            <i class="bi bi-card-text input-icon-left"></i>
                                                            <input type="text"
                                                                name="dados_bancarios[{{ $index }}][cpf_cnpj_titular]"
                                                                class="form-input-new cpf-cnpj-mask"
                                                                value="{{ old('dados_bancarios.' . $index . '.cpf_cnpj_titular', $banco->cpf_cnpj_titular) }}"
                                                                placeholder="000.000.000-00" required>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="field-label-new">
                                                            <span>Tipo de Chave PIX</span>
                                                            <span class="optional-badge">Opcional</span>
                                                        </label>
                                                        <div class="input-with-icon">
                                                            <i class="bi bi-qr-code input-icon-left"></i>
                                                            <select name="dados_bancarios[{{ $index }}][pix_tipo]"
                                                                class="form-select-new">
                                                                <option value="">Selecione</option>
                                                                <option value="cpf"
                                                                    {{ old('dados_bancarios.' . $index . '.pix_tipo', $banco->pix_tipo) == 'cpf' ? 'selected' : '' }}>
                                                                    CPF</option>
                                                                <option value="cnpj"
                                                                    {{ old('dados_bancarios.' . $index . '.pix_tipo', $banco->pix_tipo) == 'cnpj' ? 'selected' : '' }}>
                                                                    CNPJ</option>
                                                                <option value="email"
                                                                    {{ old('dados_bancarios.' . $index . '.pix_tipo', $banco->pix_tipo) == 'email' ? 'selected' : '' }}>
                                                                    E-mail</option>
                                                                <option value="telefone"
                                                                    {{ old('dados_bancarios.' . $index . '.pix_tipo', $banco->pix_tipo) == 'telefone' ? 'selected' : '' }}>
                                                                    Telefone</option>
                                                                <option value="chave_aleatoria"
                                                                    {{ old('dados_bancarios.' . $index . '.pix_tipo', $banco->pix_tipo) == 'chave_aleatoria' ? 'selected' : '' }}>
                                                                    Aleatória</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="field-label-new">
                                                            <span>Chave PIX</span>
                                                            <span class="optional-badge">Opcional</span>
                                                        </label>
                                                        <div class="input-with-icon">
                                                            <i class="bi bi-key input-icon-left"></i>
                                                            <input type="text"
                                                                name="dados_bancarios[{{ $index }}][pix_chave]"
                                                                class="form-input-new"
                                                                value="{{ old('dados_bancarios.' . $index . '.pix_chave', $banco->pix_chave) }}"
                                                                placeholder="Digite a chave PIX">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="empty-state-dynamic" id="emptyDadosBancarios">
                                            <i class="bi bi-bank"></i>
                                            <p>Nenhuma conta bancária adicionada. Clique em "Adicionar Conta" para começar.</p>
                                        </div>
                                    @endif
                                </div>

                                <button type="button" class="btn-add-dynamic" id="addDadosBancarios">
                                    <i class="bi bi-plus-circle"></i>
                                    <span>Adicionar Conta Bancária</span>
                                </button>

                                <div class="form-footer">
                                    @if (auth()->user()->needsPhoneNumber())
                                        <button type="button" class="btn-secondary" onclick="switchTab('empresa')">
                                            <i class="bi bi-arrow-left-circle"></i>
                                            Voltar
                                        </button>
                                    @else
                                        <button type="button" class="btn-secondary" onclick="switchTab('representantes')">
                                            <i class="bi bi-arrow-left-circle"></i>
                                            Voltar
                                        </button>
                                    @endif
                                    <button type="submit" class="btn-primary">
                                        <i class="bi bi-check-circle"></i>
                                        {{ auth()->user()->needsBankingInfo() ? 'Salvar e Finalizar Cadastro' : 'Salvar Dados Bancários' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </form>
            </div>
        @else
            <div class="row g-4">
                <div class="col-12">
                    <div class="content-card">
                        <div class="card-header-section">
                            <h2 class="card-title">
                                <i class="bi bi-person-badge"></i>
                                Informações Pessoais
                            </h2>
                        </div>

                        <div class="card-content">
                            <form action="{{ route('perfil.update') }}" method="POST" id="formPerfil">
                                @csrf
                                @method('PUT')

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="input-label">
                                            Nome <span class="required">*</span>
                                        </label>
                                        <div class="input-wrapper">
                                            <i class="bi bi-person"></i>
                                            <input type="text" class="input-field @error('name') is-invalid @enderror"
                                                id="name" name="name"
                                                value="{{ old('name', auth()->user()->name) }}" required>
                                        </div>
                                        @error('name')
                                            <span class="input-error">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="email" class="input-label">
                                            E-mail <span class="required">*</span>
                                        </label>
                                        <div class="input-wrapper">
                                            <i class="bi bi-envelope"></i>
                                            <input type="email" class="input-field @error('email') is-invalid @enderror"
                                                id="email" name="email"
                                                value="{{ old('email', auth()->user()->email) }}" required>
                                        </div>
                                        @error('email')
                                            <span class="input-error">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    @hasanyrole('gerencial|operacional')
                                        @if (auth()->user()->genOp)
                                            <div class="col-md-6">
                                                <label for="nome_completo" class="input-label">Nome Completo</label>
                                                <div class="input-wrapper">
                                                    <i class="bi bi-person-vcard"></i>
                                                    <input type="text"
                                                        class="input-field @error('nome_completo') is-invalid @enderror"
                                                        id="nome_completo" name="nome_completo"
                                                        value="{{ old('nome_completo', auth()->user()->genOp->nome_completo) }}">
                                                </div>
                                                @error('nome_completo')
                                                    <span class="input-error">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="col-md-6">
                                                <label for="telefone" class="input-label">Telefone</label>
                                                <div class="input-wrapper">
                                                    <i class="bi bi-telephone"></i>
                                                    <input type="text"
                                                        class="input-field telefone-mask @error('telefone') is-invalid @enderror"
                                                        id="telefone" name="telefone"
                                                        value="{{ old('telefone', auth()->user()->genOp->telefone) }}">
                                                </div>
                                                @error('telefone')
                                                    <span class="input-error">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="col-md-12">
                                                <label for="departamento" class="input-label">Departamento</label>
                                                <div class="input-wrapper">
                                                    <i class="bi bi-building"></i>
                                                    <input type="text"
                                                        class="input-field @error('departamento') is-invalid @enderror"
                                                        id="departamento" name="departamento"
                                                        value="{{ old('departamento', auth()->user()->genOp->departamento) }}">
                                                </div>
                                                @error('departamento')
                                                    <span class="input-error">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        @endif
                                    @endhasanyrole

                                    @hasrole('representante')
                                        @if(auth()->user()->representanteUsuario)
                                            <div class="col-md-6">
                                                <label for="nome_empresa" class="input-label">Nome da Empresa</label>
                                                <div class="input-wrapper">
                                                    <i class="bi bi-building"></i>
                                                    <input type="text"
                                                        class="input-field @error('nome_empresa') is-invalid @enderror"
                                                        id="nome_empresa" name="nome_empresa"
                                                        value="{{ old('nome_empresa', auth()->user()->representanteUsuario->nome_empresa) }}">
                                                </div>
                                                @error('nome_empresa')
                                                    <span class="input-error">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="col-md-6">
                                                <label for="cnpj_representante" class="input-label">CNPJ</label>
                                                <div class="input-wrapper">
                                                    <i class="bi bi-card-text"></i>
                                                    <input type="text"
                                                        class="input-field cnpj-mask @error('cnpj_representante') is-invalid @enderror"
                                                        id="cnpj_representante" name="cnpj_representante"
                                                        value="{{ old('cnpj_representante', auth()->user()->representanteUsuario->cnpj) }}"
                                                        placeholder="00.000.000/0000-00">
                                                </div>
                                                @error('cnpj_representante')
                                                    <span class="input-error">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="col-md-6">
                                                <label for="telefone_representante" class="input-label">Telefone</label>
                                                <div class="input-wrapper">
                                                    <i class="bi bi-telephone"></i>
                                                    <input type="text"
                                                        class="input-field telefone-mask @error('telefone_representante') is-invalid @enderror"
                                                        id="telefone_representante" name="telefone_representante"
                                                        value="{{ old('telefone_representante', auth()->user()->representanteUsuario->telefone) }}">
                                                </div>
                                                @error('telefone_representante')
                                                    <span class="input-error">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        @endif
                                    @endhasrole
                                </div>

                                <div class="form-footer">
                                    <a href="{{ previous_route('dashboard') }}" class="btn-secondary">
                                        <i class="bi bi-arrow-left-circle"></i>
                                        Voltar
                                    </a>
                                    <button type="submit" class="btn-primary">
                                        <i class="bi bi-check-circle"></i>
                                        Salvar Alterações
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="card-header-section">
                            <h2 class="card-title">
                                <i class="bi bi-shield-lock"></i>
                                Alterar Senha
                            </h2>
                            <p class="card-subtitle">Mantenha sua conta segura com uma senha forte</p>
                        </div>

                        <div class="card-content">
                            <form action="{{ route('perfil.update_password') }}" method="POST" id="formSenha">
                                @csrf
                                @method('PUT')

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="current_password" class="input-label">
                                            Senha Atual <span class="required">*</span>
                                        </label>
                                        <div class="input-wrapper">
                                            <i class="bi bi-lock"></i>
                                            <input type="password"
                                                class="input-field @error('current_password') is-invalid @enderror"
                                                id="current_password" name="current_password" maxlength="64" required>
                                            <button class="toggle-password" type="button" data-target="current_password"
                                                title="Mostrar senha">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        @error('current_password')
                                            <span class="input-error">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="password" class="input-label">
                                            Nova Senha <span class="required">*</span>
                                        </label>
                                        <div class="input-wrapper">
                                            <i class="bi bi-lock-fill"></i>
                                            <input type="password"
                                                class="input-field @error('password') is-invalid @enderror" id="password"
                                                name="password" maxlength="64" required>
                                            <button class="btn-generate-password" type="button" id="generatePasswordBtn"
                                                title="Gerar senha forte">
                                                <i class="bi bi-stars"></i>
                                            </button>
                                            <button class="toggle-password" type="button" data-target="password"
                                                title="Mostrar senha">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>

                                        <div class="password-strength-wrapper" id="passwordStrengthWrapper"
                                            style="display: none;">
                                            <div class="password-strength-bar">
                                                <div class="password-strength-fill" id="passwordStrengthFill"></div>
                                            </div>
                                            <div class="password-strength-text" id="passwordStrengthText">Muito fraca</div>
                                        </div>

                                        <div class="password-requirements">
                                            <div class="requirement-item" id="req-length">
                                                <i class="bi bi-circle"></i>
                                                <span>Mínimo de 8 caracteres</span>
                                            </div>
                                            <div class="requirement-item" id="req-uppercase">
                                                <i class="bi bi-circle"></i>
                                                <span>Pelo menos uma letra maiúscula (A-Z)</span>
                                            </div>
                                            <div class="requirement-item" id="req-lowercase">
                                                <i class="bi bi-circle"></i>
                                                <span>Pelo menos uma letra minúscula (a-z)</span>
                                            </div>
                                            <div class="requirement-item" id="req-number">
                                                <i class="bi bi-circle"></i>
                                                <span>Pelo menos um número (0-9)</span>
                                            </div>
                                            <div class="requirement-item" id="req-special">
                                                <i class="bi bi-circle"></i>
                                                <span>Pelo menos um caractere especial (!@#$%^&*)</span>
                                            </div>
                                        </div>

                                        @error('password')
                                            <span class="input-error">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="password_confirmation" class="input-label">
                                            Confirmar Nova Senha <span class="required">*</span>
                                        </label>
                                        <div class="input-wrapper">
                                            <i class="bi bi-lock-fill"></i>
                                            <input type="password" class="input-field" id="password_confirmation"
                                                name="password_confirmation" maxlength="64" required>
                                            <button class="toggle-password" type="button"
                                                data-target="password_confirmation" title="Mostrar senha">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <span class="password-match-message" id="passwordMatchMessage"></span>
                                    </div>
                                </div>

                                <div class="form-footer">
                                    <a href="{{ previous_route('dashboard') }}" class="btn-secondary">
                                        <i class="bi bi-arrow-left-circle"></i>
                                        Voltar
                                    </a>
                                    <button type="submit" class="btn-primary" id="submitPasswordBtn" disabled>
                                        <i class="bi bi-shield-check"></i>
                                        Atualizar Senha
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endhasrole
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        class PasswordFieldManager {
            constructor() {
                this.passwordFields = ['#current_password', '#password', '#password_confirmation'];
                this.init();
            }

            init() {
                this.bindToggleEvents();
                this.bindFocusEvents();
            }

            bindToggleEvents() {
                $('.toggle-password').on('click', (e) => {
                    const $button = $(e.currentTarget);
                    if ($button.prop('disabled')) return;
                    $button.prop('disabled', true);
                    const target = $button.data('target');
                    const $input = $(`#${target}`);
                    const newType = $input.attr('type') === 'password' ? 'text' : 'password';
                    $input.attr('type', newType);
                    const $icon = $button.find('i');
                    $button.addClass('animating');
                    if (newType === 'text') {
                        $icon.removeClass('bi-eye').addClass('bi-eye-slash');
                        $button.attr('title', 'Ocultar senha');
                    } else {
                        $icon.removeClass('bi-eye-slash').addClass('bi-eye');
                        $button.attr('title', 'Mostrar senha');
                    }
                    setTimeout(() => {
                        $button.removeClass('animating').prop('disabled', false);
                    }, 300);
                });
            }

            bindFocusEvents() {
                $(this.passwordFields.join(',')).on('blur', (e) => {
                    this.hidePasswordField($(e.currentTarget));
                });
                $(this.passwordFields.join(',')).on('focus', (e) => {
                    const currentId = $(e.currentTarget).attr('id');
                    $(this.passwordFields.join(',')).each((index, element) => {
                        if ($(element).attr('id') !== currentId) {
                            this.hidePasswordField($(element));
                        }
                    });
                });
            }

            hidePasswordField($input) {
                if ($input.attr('type') === 'text') {
                    $input.attr('type', 'password');
                    const inputId = $input.attr('id');
                    const $button = $(`.toggle-password[data-target="${inputId}"]`);
                    const $icon = $button.find('i');
                    $icon.removeClass('bi-eye-slash').addClass('bi-eye');
                    $button.attr('title', 'Mostrar senha');
                }
            }
        }

        class PasswordGenerator {
            constructor() {
                this.isGenerating = false;
                this.init();
            }

            init() {
                $('#generatePasswordBtn').on('click', () => {
                    this.generateAndFillPassword();
                });
            }

            generateSecurePassword(length = 16) {
                const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                const lowercase = 'abcdefghijklmnopqrstuvwxyz';
                const numbers = '0123456789';
                const special = '!@#$%^&*()_+-=[]{}';
                const all = uppercase + lowercase + numbers + special;
                let password = '';
                password += uppercase[Math.floor(Math.random() * uppercase.length)];
                password += lowercase[Math.floor(Math.random() * lowercase.length)];
                password += numbers[Math.floor(Math.random() * numbers.length)];
                password += special[Math.floor(Math.random() * special.length)];
                for (let i = password.length; i < length; i++) {
                    password += all[Math.floor(Math.random() * all.length)];
                }
                return password.split('').sort(() => Math.random() - 0.5).join('');
            }

            generateAndFillPassword() {
                if (this.isGenerating) return;
                this.isGenerating = true;
                const $button = $('#generatePasswordBtn');
                $button.prop('disabled', true).addClass('generating');
                const generatedPassword = this.generateSecurePassword();
                $('#password').val(generatedPassword).attr('type', 'text');
                $('#password_confirmation').val(generatedPassword).attr('type', 'password');
                const $passIcon = $(`.toggle-password[data-target="password"] i`);
                $passIcon.removeClass('bi-eye').addClass('bi-eye-slash');
                const $confIcon = $(`.toggle-password[data-target="password_confirmation"] i`);
                $confIcon.removeClass('bi-eye-slash').addClass('bi-eye');
                $('#password').trigger('input');
                $('#password_confirmation').trigger('input');
                if (typeof toastr !== 'undefined') {
                    toastr.success('Senha forte gerada com sucesso!', 'Gerador de Senha', {
                        timeOut: 4000
                    });
                }
                setTimeout(() => {
                    $button.removeClass('generating').prop('disabled', false);
                    this.isGenerating = false;
                }, 600);
            }
        }

        class PasswordStrengthChecker {
            constructor() {
                this.init();
            }

            init() {
                $('#password').on('input', () => {
                    this.updatePasswordStrength($('#password').val());
                });
            }

            checkPasswordStrength(password) {
                let strength = 0;
                const requirements = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /[0-9]/.test(password),
                    special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
                };
                $('#req-length').toggleClass('valid', requirements.length);
                $('#req-uppercase').toggleClass('valid', requirements.uppercase);
                $('#req-lowercase').toggleClass('valid', requirements.lowercase);
                $('#req-number').toggleClass('valid', requirements.number);
                $('#req-special').toggleClass('valid', requirements.special);
                if (requirements.length) strength++;
                if (requirements.uppercase) strength++;
                if (requirements.lowercase) strength++;
                if (requirements.number) strength++;
                if (requirements.special) strength++;
                return {
                    score: strength,
                    isValid: strength === 5
                };
            }

            updatePasswordStrength(password) {
                const $wrapper = $('#passwordStrengthWrapper');
                const $fill = $('#passwordStrengthFill');
                const $text = $('#passwordStrengthText');
                if (password.length === 0) {
                    $wrapper.hide();
                    return false;
                }
                $wrapper.show();
                const result = this.checkPasswordStrength(password);
                $fill.removeClass('weak fair good strong');
                $text.removeClass('weak fair good strong');
                let strengthClass = 'weak';
                let strengthText = 'Muito fraca';
                switch (result.score) {
                    case 1:
                    case 2:
                        strengthClass = 'weak';
                        strengthText = 'Muito fraca';
                        break;
                    case 3:
                        strengthClass = 'fair';
                        strengthText = 'Fraca';
                        break;
                    case 4:
                        strengthClass = 'good';
                        strengthText = 'Boa';
                        break;
                    case 5:
                        strengthClass = 'strong';
                        strengthText = 'Forte';
                        break;
                }
                $fill.addClass(strengthClass);
                $text.addClass(strengthClass).text(strengthText);
                return result.isValid;
            }

            getPasswordStrength(password) {
                return this.checkPasswordStrength(password);
            }
        }

        class PasswordMatchValidator {
            constructor() {
                this.init();
            }

            init() {
                $('#password_confirmation').on('input', () => {
                    this.checkPasswordMatch();
                });
                $('#password').on('input', () => {
                    this.checkPasswordMatch();
                });
            }

            checkPasswordMatch() {
                const password = $('#password').val();
                const confirmation = $('#password_confirmation').val();
                const $message = $('#passwordMatchMessage');
                if (confirmation.length === 0) {
                    $message.text('').removeClass('match no-match');
                    return false;
                }
                if (password === confirmation) {
                    $message.text('✓ As senhas coincidem').removeClass('no-match').addClass('match');
                    return true;
                } else {
                    $message.text('✗ As senhas não coincidem').removeClass('match').addClass('no-match');
                    return false;
                }
            }

            isValid() {
                const password = $('#password').val();
                const confirmation = $('#password_confirmation').val();
                return password === confirmation && confirmation.length > 0;
            }
        }

        class FormSubmitController {
            constructor(passwordStrengthChecker, passwordMatchValidator) {
                this.passwordStrengthChecker = passwordStrengthChecker;
                this.passwordMatchValidator = passwordMatchValidator;
                this.init();
            }

            init() {
                $('#password, #password_confirmation, #current_password').on('input', () => {
                    this.updateSubmitButton();
                });
                this.updateSubmitButton();
            }

            updateSubmitButton() {
                const password = $('#password').val();
                const confirmation = $('#password_confirmation').val();
                const currentPassword = $('#current_password').val();
                const strengthResult = this.passwordStrengthChecker.getPasswordStrength(password);
                const passwordsMatch = password === confirmation && confirmation.length > 0;
                const hasCurrentPassword = currentPassword.length > 0;
                const canSubmit = strengthResult.isValid && passwordsMatch && hasCurrentPassword;
                $('#submitPasswordBtn').prop('disabled', !canSubmit);
            }
        }

        class FormValidator {
            constructor(config = {}) {
                this.config = {
                    isFornecedor: config.isFornecedor || false,
                    passwordStrengthChecker: config.passwordStrengthChecker || null
                };
                this.init();
            }

            init() {
                this.bindFormPerfil();
                this.bindFormSenha();
                this.bindInputValidation();
            }

            bindFormPerfil() {
                $('#formPerfil').on('submit', (e) => {
                    const email = $('#email').val();
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        e.preventDefault();
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Por favor, insira um e-mail válido.', 'Erro de Validação');
                        }
                        $('#email').addClass('is-invalid');
                        return false;
                    }
                    if (this.config.isFornecedor) {
                        const cnpj = $('#cnpj').val().replace(/\D/g, '');
                        if (cnpj.length !== 14) {
                            e.preventDefault();
                            if (typeof toastr !== 'undefined') {
                                toastr.error('CNPJ deve ter 14 dígitos.', 'Erro de Validação');
                            }
                            $('#cnpj').addClass('is-invalid');
                            return false;
                        }
                    }
                });
            }

            bindFormSenha() {
                $('#formSenha').on('submit', (e) => {
                    const password = $('#password').val();
                    const passwordConfirmation = $('#password_confirmation').val();
                    if (!this.config.passwordStrengthChecker) {
                        return true;
                    }
                    const strengthResult = this.config.passwordStrengthChecker.getPasswordStrength(password);
                    if (!strengthResult.isValid) {
                        e.preventDefault();
                        if (typeof toastr !== 'undefined') {
                            toastr.error('A senha não atende todos os requisitos de segurança.',
                                'Senha Inválida');
                        }
                        return false;
                    }
                    if (password !== passwordConfirmation) {
                        e.preventDefault();
                        if (typeof toastr !== 'undefined') {
                            toastr.error('As senhas não coincidem.', 'Erro de Validação');
                        }
                        $('#password_confirmation').addClass('is-invalid');
                        return false;
                    }
                });
            }

            bindInputValidation() {
                $('.input-field').on('input', function() {
                    $(this).removeClass('is-invalid');
                });
            }
        }

        class InputMaskManager {
            constructor() {
                this.init();
            }

            init() {
                if (typeof $.fn.mask !== 'undefined') {
                    $('.telefone-mask').mask('(00) 00000-0000');
                    $('.cnpj-mask').mask('00.000.000/0000-00');
                    $('.cpf-mask').mask('000.000.000-00');
                    $('.cep-mask').mask('00000-000');

                    this.setupCPFCNPJMask();

                    $('.cnpj-mask').on('paste', function(e) {
                        e.preventDefault();
                        var pastedData = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
                        var valor = pastedData.replace(/\D/g, '').substring(0, 14);
                        $(this).unmask().val(valor).mask('00.000.000/0000-00');
                    });
                }
            }

            setupCPFCNPJMask() {
                const SPMaskBehavior = function(val) {
                    return val.replace(/\D/g, '').length <= 11 ? '000.000.000-009' : '00.000.000/0000-00';
                };

                const spOptions = {
                    onKeyPress: function(val, e, field, options) {
                        field.mask(SPMaskBehavior.apply({}, arguments), options);
                    }
                };

                $('.cpf-cnpj-mask').each(function() {
                    const $input = $(this);

                    $input.mask(SPMaskBehavior($input.val()), spOptions);

                    $input.on('paste', function(e) {
                        e.preventDefault();

                        const $el = $(this);

                        let pastedData = '';
                        if (e.originalEvent && e.originalEvent.clipboardData) {
                            pastedData = e.originalEvent.clipboardData.getData('text');
                        } else if (window.clipboardData) {
                            pastedData = window.clipboardData.getData('Text');
                        }

                        const valor = pastedData.replace(/\D/g, '');

                        if (valor.length === 0) return;

                        $el.unmask();

                        if (valor.length <= 11) {
                            $el.mask('000.000.000-009', spOptions);
                        } else {
                            $el.mask('00.000.000/0000-00', spOptions);
                        }

                        $el.val(valor).trigger('input');
                    });
                });
            }
        }

        class CEPSearchManager {
            constructor() {
                this.isSearching = false;
                this.init();
            }

            init() {
                $('#cep').on('blur', () => {
                    this.searchCEP();
                });
            }

            showLoading() {
                $('.cep-loading').show();
                $('#cep').prop('disabled', true);
                this.isSearching = true;
            }

            hideLoading() {
                $('.cep-loading').hide();
                $('#cep').prop('disabled', false);
                this.isSearching = false;
            }

            async searchCEP() {
                if (this.isSearching) return;

                const cep = $('#cep').val().replace(/\D/g, '');

                if (cep.length !== 8) {
                    return;
                }

                this.showLoading();

                toastr.info('Buscando informações do CEP...', 'Aguarde', {
                    timeOut: 2000,
                    progressBar: true
                });

                const apis = [{
                        name: 'BrasilAPI',
                        url: `https://brasilapi.com.br/api/cep/v1/${cep}`,
                        parser: (data) => ({
                            logradouro: data.street || '',
                            bairro: data.neighborhood || '',
                            cidade: data.city || '',
                            estado: data.state || ''
                        })
                    },
                    {
                        name: 'ViaCEP',
                        url: `https://viacep.com.br/ws/${cep}/json/`,
                        parser: (data) => {
                            if (data.erro) return null;
                            return {
                                logradouro: data.logradouro || '',
                                bairro: data.bairro || '',
                                cidade: data.localidade || '',
                                estado: data.uf || ''
                            };
                        }
                    },
                    {
                        name: 'Postmon',
                        url: `https://api.postmon.com.br/v1/cep/${cep}`,
                        parser: (data) => ({
                            logradouro: data.logradouro || '',
                            bairro: data.bairro || '',
                            cidade: data.cidade || '',
                            estado: data.estado || ''
                        })
                    }
                ];

                for (const api of apis) {
                    try {
                        const data = await this.fetchWithTimeout(api.url, {
                            timeout: 5000
                        });
                        const parsed = api.parser(data);

                        if (parsed && parsed.cidade) {
                            this.fillAddressFields(parsed);
                            this.hideLoading();

                            toastr.success(
                                `Endereço encontrado!`,
                                'CEP localizado', {
                                    timeOut: 3000,
                                    progressBar: true
                                }
                            );
                            return;
                        }
                    } catch (error) {
                        continue;
                    }
                }

                this.hideLoading();
                this.showErrorMessage(cep);
            }

            async fetchWithTimeout(url, options = {}) {
                const timeout = options.timeout || 5000;

                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), timeout);

                try {
                    const response = await fetch(url, {
                        ...options,
                        signal: controller.signal,
                        headers: options.headers || {}
                    });

                    clearTimeout(timeoutId);

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    return await response.json();
                } catch (error) {
                    clearTimeout(timeoutId);

                    if (error.name === 'AbortError') {
                        throw new Error('Timeout');
                    }

                    throw error;
                }
            }

            fillAddressFields(data) {
                $('#logradouro').val(data.logradouro).trigger('input');
                $('#bairro').val(data.bairro).trigger('input');
                $('#cidade').val(data.cidade).trigger('input');
                $('#estado').val(data.estado).trigger('change');

                $('#logradouro, #bairro, #cidade, #estado').each(function() {
                    $(this).addClass('field-filled');
                    setTimeout(() => $(this).removeClass('field-filled'), 1000);
                });
            }

            showErrorMessage(cep) {
                toastr.warning(
                    'Não foi possível encontrar o CEP. Preencha o endereço manualmente.',
                    'CEP não encontrado', {
                        timeOut: 5000,
                        progressBar: true
                    }
                );

                setTimeout(() => {
                    $('#logradouro').focus();
                }, 300);
            }

            formatCEP(cep) {
                return cep.replace(/(\d{5})(\d{3})/, '$1-$2');
            }
        }

        class CPFCNPJValidator {
            constructor() {
                this.init();
            }

            init() {
                $(document).on('blur', '.cpf-cnpj-mask', (e) => {
                    this.validateDocument($(e.currentTarget));
                });

                $(document).on('input', '.cpf-cnpj-mask', (e) => {
                    const $input = $(e.currentTarget);
                    if ($input.hasClass('is-invalid')) {
                        this.validateDocument($input);
                    }
                });
            }

            validateDocument($input) {
                const value = $input.val().replace(/\D/g, '');

                if (value.length === 0) {
                    this.clearValidation($input);
                    return true;
                }

                if (value.length <= 11) {
                    return this.validateCPF($input, value);
                } else {
                    return this.validateCNPJ($input, value);
                }
            }

            validateCPF($input, cpf) {
                if (cpf.length !== 11) {
                    this.setInvalid($input, 'CPF deve ter 11 dígitos');
                    return false;
                }

                if (this.isRepeatedDigits(cpf)) {
                    this.setInvalid($input, 'CPF inválido');
                    return false;
                }

                if (!this.checkCPFDigits(cpf)) {
                    this.setInvalid($input, 'CPF inválido');
                    return false;
                }

                this.setValid($input);
                return true;
            }

            validateCNPJ($input, cnpj) {
                if (cnpj.length !== 14) {
                    this.setInvalid($input, 'CNPJ deve ter 14 dígitos');
                    return false;
                }

                if (this.isRepeatedDigits(cnpj)) {
                    this.setInvalid($input, 'CNPJ inválido');
                    return false;
                }

                if (!this.checkCNPJDigits(cnpj)) {
                    this.setInvalid($input, 'CNPJ inválido');
                    return false;
                }

                this.setValid($input);
                return true;
            }

            isRepeatedDigits(value) {
                return value.split('').every(digit => digit === value[0]);
            }

            checkCPFDigits(cpf) {
                let sum = 0;
                let remainder;

                for (let i = 1; i <= 9; i++) {
                    sum += parseInt(cpf.substring(i - 1, i)) * (11 - i);
                }

                remainder = (sum * 10) % 11;
                if (remainder === 10 || remainder === 11) remainder = 0;
                if (remainder !== parseInt(cpf.substring(9, 10))) return false;

                sum = 0;
                for (let i = 1; i <= 10; i++) {
                    sum += parseInt(cpf.substring(i - 1, i)) * (12 - i);
                }

                remainder = (sum * 10) % 11;
                if (remainder === 10 || remainder === 11) remainder = 0;
                if (remainder !== parseInt(cpf.substring(10, 11))) return false;

                return true;
            }

            checkCNPJDigits(cnpj) {
                let length = cnpj.length - 2;
                let numbers = cnpj.substring(0, length);
                let digits = cnpj.substring(length);
                let sum = 0;
                let pos = length - 7;

                for (let i = length; i >= 1; i--) {
                    sum += parseInt(numbers.charAt(length - i)) * pos--;
                    if (pos < 2) pos = 9;
                }

                let result = sum % 11 < 2 ? 0 : 11 - sum % 11;
                if (result !== parseInt(digits.charAt(0))) return false;

                length = length + 1;
                numbers = cnpj.substring(0, length);
                sum = 0;
                pos = length - 7;

                for (let i = length; i >= 1; i--) {
                    sum += parseInt(numbers.charAt(length - i)) * pos--;
                    if (pos < 2) pos = 9;
                }

                result = sum % 11 < 2 ? 0 : 11 - sum % 11;
                if (result !== parseInt(digits.charAt(1))) return false;

                return true;
            }

            setInvalid($input, message) {
                $input.addClass('is-invalid');

                let $errorMsg = $input.closest('.input-with-icon').siblings('.cpf-cnpj-error-message');

                if ($errorMsg.length === 0) {
                    $errorMsg = $(
                        '<span class="field-error-message cpf-cnpj-error-message"><i class="bi bi-exclamation-circle"></i></span>'
                    );
                    $input.closest('.input-with-icon').after($errorMsg);
                }

                $errorMsg.find('span').remove();
                $errorMsg.append(`<span>${message}</span>`);
                $errorMsg.show();

                const $form = $input.closest('form');
                if ($form.length > 0) {
                    $form.find('.btn-primary, button[type="submit"]').prop('disabled', true);
                }
            }

            setValid($input) {
                $input.removeClass('is-invalid').addClass('is-valid');

                const $errorMsg = $input.closest('.input-with-icon').siblings('.cpf-cnpj-error-message');
                $errorMsg.hide();

                const $form = $input.closest('form');
                if ($form.length > 0) {
                    const hasInvalidFields = $form.find('.is-invalid').length > 0;
                    $form.find('.btn-primary, button[type="submit"]').prop('disabled', hasInvalidFields);
                }
            }

            clearValidation($input) {
                $input.removeClass('is-invalid is-valid');
                const $errorMsg = $input.closest('.input-with-icon').siblings('.cpf-cnpj-error-message');
                $errorMsg.hide();

                const $form = $input.closest('form');
                if ($form.length > 0) {
                    const hasInvalidFields = $form.find('.is-invalid').length > 0;
                    $form.find('.btn-primary, button[type="submit"]').prop('disabled', hasInvalidFields);
                }
            }

            validateAllDocuments($container) {
                let allValid = true;
                $container.find('.cpf-cnpj-mask').each((index, element) => {
                    if (!this.validateDocument($(element))) {
                        allValid = false;
                    }
                });
                return allValid;
            }
        }

        class FormBankingValidator {
            constructor(cpfCnpjValidator) {
                this.cpfCnpjValidator = cpfCnpjValidator;
                this.init();
            }

            init() {
                $('form').on('submit', (e) => {
                    const $form = $(e.currentTarget);

                    if ($form.find('#dadosBancariosContainer').length > 0) {
                        if (!this.validateForm($form)) {
                            e.preventDefault();

                            toastr.error(
                                'Por favor, corrija os erros antes de salvar.',
                                'Dados inválidos', {
                                    timeOut: 5000
                                }
                            );

                            const $firstError = $form.find('.is-invalid').first();
                            if ($firstError.length > 0) {
                                $('html, body').animate({
                                    scrollTop: $firstError.offset().top - 100
                                }, 500);
                                $firstError.focus();
                            }

                            return false;
                        }
                    }
                });
            }

            validateForm($form) {
                let isValid = true;

                $form.find('.cpf-cnpj-mask').each((index, element) => {
                    const $input = $(element);
                    const value = $input.val().replace(/\D/g, '');

                    if (value.length > 0) {
                        if (!this.cpfCnpjValidator.validateDocument($input)) {
                            isValid = false;
                        }
                    }
                });

                $form.find('.form-input-new[required], .form-select-new[required]').each((index, element) => {
                    const $input = $(element);
                    if (!$input.val() || $input.val().trim() === '') {
                        $input.addClass('is-invalid');
                        isValid = false;
                    }
                });

                return isValid;
            }
        }

        function initUserProfile(config = {}) {
            if (typeof $ === 'undefined') {
                return null;
            }
            const inputMaskManager = new InputMaskManager();
            const passwordFieldManager = new PasswordFieldManager();
            const passwordGenerator = new PasswordGenerator();
            const passwordStrengthChecker = new PasswordStrengthChecker();
            const passwordMatchValidator = new PasswordMatchValidator();
            const formSubmitController = new FormSubmitController(
                passwordStrengthChecker,
                passwordMatchValidator
            );
            const formValidator = new FormValidator({
                isFornecedor: config.isFornecedor || false,
                passwordStrengthChecker: passwordStrengthChecker
            });
            const cepSearchManager = new CEPSearchManager();
            const cpfCnpjValidator = new CPFCNPJValidator();
            const formBankingValidator = new FormBankingValidator(cpfCnpjValidator);

            return {
                inputMaskManager,
                passwordFieldManager,
                passwordGenerator,
                passwordStrengthChecker,
                passwordMatchValidator,
                formSubmitController,
                formValidator,
                cepSearchManager,
                cpfCnpjValidator,
                formBankingValidator
            };
        }

        window.switchTab = function(tabName) {
            $('.tab-button').removeClass('active');
            $(`.tab-button[data-tab="${tabName}"]`).addClass('active');
            $('.tab-content-area').removeClass('active');
            $(`#tab-${tabName}`).addClass('active');
        };

        window.removeRepresentante = function(btn) {
            const $item = $(btn).closest('.dynamic-item');
            const numero = $item.find('.item-number').text();
            const representanteId = $item.find('input[name*="[id]"]').val();

            if (!representanteId) {
                $item.fadeOut(300, function() {
                    $(this).remove();
                    checkEmptyRepresentantes();
                });
                toastr.info('Contato removido');
                return;
            }

            Swal.fire({
                title: 'Remover Contato?',
                html: `
            <div style="text-align: left;">
                <p style="margin-bottom: 1.25rem; font-size: 1.05rem; color: var(--text-color); line-height: 1.6;">
                    Deseja realmente remover o <strong>Contato #${numero}</strong>?
                </p>
                <div class="warning-box">
                    <p>
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span>Esta ação não pode ser desfeita e será aplicada imediatamente.</span>
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
                padding: '2rem',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch(`/perfil/representante/${representanteId}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Erro ao remover contato');
                            }
                            return response.json();
                        })
                        .catch(error => {
                            Swal.showValidationMessage(`Erro: ${error.message}`);
                        });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed && result.value && result.value.success) {
                    $item.fadeOut(300, function() {
                        $(this).remove();
                        checkEmptyRepresentantes();
                    });

                    Swal.fire({
                        icon: 'success',
                        title: 'Removido!',
                        text: result.value.message || 'Representante removido com sucesso!',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else if (result.isDismissed && result.dismiss === Swal.DismissReason.cancel) {
                    toastr.info('Operação cancelada', 'Cancelado');
                }
            });
        };

        window.removeDadosBancarios = function(btn) {
            const $item = $(btn).closest('.dynamic-item');
            const numero = $item.find('.item-number').text();
            const dadosBancariosId = $item.find('input[name*="[id]"]').val();

            if (!dadosBancariosId) {
                $item.fadeOut(300, function() {
                    $(this).remove();
                    checkEmptyDadosBancarios();
                });
                toastr.info('Dados bancários removidos');
                return;
            }

            Swal.fire({
                title: 'Remover Dados Bancários?',
                html: `
            <div style="text-align: left;">
                <p style="margin-bottom: 1.25rem; font-size: 1.05rem; color: var(--text-color); line-height: 1.6;">
                    Deseja realmente remover a <strong>Conta Bancária #${numero}</strong>?
                </p>
                <div class="warning-box">
                    <p>
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span>Esta ação não pode ser desfeita e será aplicada imediatamente.</span>
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
                padding: '2rem',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch(`/perfil/dados-bancarios/${dadosBancariosId}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Erro ao remover dados bancários');
                            }
                            return response.json();
                        })
                        .catch(error => {
                            Swal.showValidationMessage(`Erro: ${error.message}`);
                        });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed && result.value && result.value.success) {
                    $item.fadeOut(300, function() {
                        $(this).remove();
                        checkEmptyDadosBancarios();
                    });

                    Swal.fire({
                        icon: 'success',
                        title: 'Removido!',
                        text: result.value.message || 'Dados bancários removidos com sucesso!',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else if (result.isDismissed && result.dismiss === Swal.DismissReason.cancel) {
                    toastr.info('Operação cancelada', 'Cancelado');
                }
            });
        };

        function checkEmptyRepresentantes() {
            const $container = $('#representantesContainer');
            const totalItems = $container.find('.dynamic-item').length;

            if (totalItems === 0) {
                if ($('#emptyRepresentantes').length === 0) {
                    const emptyStateHtml = `
                <div class="empty-state-dynamic" id="emptyRepresentantes">
                    <i class="bi bi-person-plus"></i>
                    <p>Nenhum contato adicionado. Clique em "Adicionar Contato" para começar.</p>
                </div>
            `;
                    $container.html(emptyStateHtml);
                } else {
                    $('#emptyRepresentantes').fadeIn(300);
                }
            }
        }

        function checkEmptyDadosBancarios() {
            const $container = $('#dadosBancariosContainer');
            const totalItems = $container.find('.dynamic-item').length;

            if (totalItems === 0) {
                if ($('#emptyDadosBancarios').length === 0) {
                    const emptyStateHtml = `
                <div class="empty-state-dynamic" id="emptyDadosBancarios">
                    <i class="bi bi-bank"></i>
                    <p>Nenhuma conta bancária adicionada. Clique em "Adicionar Conta" para começar.</p>
                </div>
            `;
                    $container.html(emptyStateHtml);
                } else {
                    $('#emptyDadosBancarios').fadeIn(300);
                }
            }
        }

        function updateItemNumbers($container) {
            $container.find('.dynamic-item').each(function(index) {
                $(this).find('.item-number').text(index + 1);
            });
        }

        $(document).ready(function() {
            const profileConfig = {
                isFornecedor: {{ auth()->user()->hasRole('fornecedor') ? 'true' : 'false' }}
            };
            window.userProfileInstance = initUserProfile(profileConfig);

            $('.tab-button').on('click', function() {
                if ($(this).prop('disabled')) {
                    return;
                }
                const tab = $(this).data('tab');
                switchTab(tab);
            });

            const abaAtiva = '{{ $abaAtiva }}';
            if (abaAtiva) {
                switchTab(abaAtiva);
            }

            @if ($incompleteStep)
                $('.tab-button[disabled]').on('click', function(e) {
                    e.preventDefault();
                    toastr.warning('Complete os passos obrigatórios primeiro', 'Cadastro Incompleto');
                });
            @endif

            $('#cep').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $(this).blur();
                    return false;
                }
            });

            $('#cnpj').on('blur', function() {
                const cnpj = $(this).val();
                const cnpjLimpo = cnpj.replace(/\D/g, '');

                if (cnpjLimpo.length === 14) {
                    const fornecedorId = $('input[name="fornecedor_id"]').val();

                    $.ajax({
                        url: `{{ route('validar.cnpj') }}`,
                        method: 'POST',
                        data: {
                            cnpj: cnpj,
                            fornecedor_id: fornecedorId,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (!response.disponivel) {
                                toastr.error('Este CNPJ já está cadastrado no sistema.',
                                    'CNPJ Duplicado');
                                $('#cnpj').addClass('is-invalid');
                            } else {
                                $('#cnpj').removeClass('is-invalid');
                            }
                        }
                    });
                }
            });

            $('form').on('keypress', function(e) {
                if (e.which === 13 && $(e.target).is('#cep')) {
                    e.preventDefault();
                    return false;
                }
            });

            let representanteIndex =
                {{ auth()->user()->fornecedor ? auth()->user()->fornecedor->representantes->count() : 0 }};
            $('#addRepresentante').on('click', function() {
                $('#emptyRepresentantes').hide();

                const template = `<div class="dynamic-item" data-index="${representanteIndex}">
            <div class="dynamic-item-header">
                <h4 class="dynamic-item-title">
                    <i class="bi bi-person-circle"></i>
                    Contato #<span class="item-number">${representanteIndex + 1}</span>
                </h4>
                <button type="button" class="btn-remove-item" onclick="removeRepresentante(this)">
                    <i class="bi bi-trash"></i>
                    <span>Remover</span>
                </button>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="field-label-new">
                        <span>Nome Completo</span>
                        <span class="optional-badge">Opcional</span>
                    </label>
                    <div class="input-with-icon">
                        <i class="bi bi-person input-icon-left"></i>
                        <input type="text" name="representantes[${representanteIndex}][nome_completo]"
                            class="form-input-new"
                            placeholder="Nome completo do contato">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="field-label-new">
                        <span>CPF</span>
                        <span class="optional-badge">Opcional</span>
                    </label>
                    <div class="input-with-icon">
                        <i class="bi bi-card-text input-icon-left"></i>
                        <input type="text" name="representantes[${representanteIndex}][cpf]"
                            class="form-input-new cpf-mask"
                            placeholder="000.000.000-00">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="field-label-new">
                        <span>Telefone 1</span>
                        <span class="optional-badge">Opcional</span>
                    </label>
                    <div class="input-with-icon">
                        <i class="bi bi-telephone input-icon-left"></i>
                        <input type="text" name="representantes[${representanteIndex}][telefone_1]"
                            class="form-input-new telefone-mask"
                            placeholder="(00) 00000-0000">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="field-label-new">
                        <span>Telefone 2</span>
                        <span class="optional-badge">Opcional</span>
                    </label>
                    <div class="input-with-icon">
                        <i class="bi bi-telephone input-icon-left"></i>
                        <input type="text" name="representantes[${representanteIndex}][telefone_2]"
                            class="form-input-new telefone-mask"
                            placeholder="(00) 00000-0000">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="field-label-new">
                        <span>E-mail 1</span>
                        <span class="optional-badge">Opcional</span>
                    </label>
                    <div class="input-with-icon">
                        <i class="bi bi-envelope input-icon-left"></i>
                        <input type="email" name="representantes[${representanteIndex}][email_1]"
                            class="form-input-new"
                            placeholder="email@exemplo.com">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="field-label-new">
                        <span>E-mail 2</span>
                        <span class="optional-badge">Opcional</span>
                    </label>
                    <div class="input-with-icon">
                        <i class="bi bi-envelope input-icon-left"></i>
                        <input type="email" name="representantes[${representanteIndex}][email_2]"
                            class="form-input-new"
                            placeholder="email2@exemplo.com">
                    </div>
                </div>
            </div>
        </div>`;

                $('#representantesContainer').append(template);
                $('.cpf-mask').mask('000.000.000-00');
                $('.telefone-mask').mask('(00) 00000-0000');

                updateItemNumbers($('#representantesContainer'));
                representanteIndex++;
            });

            let bancoIndex =
                {{ auth()->user()->fornecedor ? auth()->user()->fornecedor->dadosBancarios->count() : 0 }};
            $('#addDadosBancarios').on('click', function() {
                $('#emptyDadosBancarios').hide();

                const template = `<div class="dynamic-item" data-index="${bancoIndex}">
                    <div class="dynamic-item-header">
                        <h4 class="dynamic-item-title">
                            <i class="bi bi-credit-card"></i>
                            Conta Bancária #<span class="item-number">${bancoIndex + 1}</span>
                        </h4>
                        <button type="button" class="btn-remove-item" onclick="removeDadosBancarios(this)">
                            <i class="bi bi-trash"></i>
                            <span>Remover</span>
                        </button>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="field-label-new">
                                <span>Banco</span>
                                <span class="required-star">*</span>
                            </label>
                            <div class="input-with-icon">
                                <i class="bi bi-bank input-icon-left"></i>
                                <input type="text" name="dados_bancarios[${bancoIndex}][banco]"
                                    class="form-input-new"
                                    placeholder="Nome do banco"
                                    required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label-new">
                                <span>Tipo de Conta</span>
                                <span class="required-star">*</span>
                            </label>
                            <div class="input-with-icon">
                                <i class="bi bi-wallet2 input-icon-left"></i>
                                <select name="dados_bancarios[${bancoIndex}][tipo_conta]" class="form-select-new" required>
                                    <option value="">Selecione</option>
                                    <option value="corrente">Corrente</option>
                                    <option value="poupanca">Poupança</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label-new">
                                <span>Agência</span>
                                <span class="required-star">*</span>
                            </label>
                            <div class="input-with-icon">
                                <i class="bi bi-building input-icon-left"></i>
                                <input type="text" name="dados_bancarios[${bancoIndex}][agencia]"
                                    class="form-input-new"
                                    placeholder="0000"
                                    required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label-new">
                                <span>Dígito Agência</span>
                                <span class="optional-badge">Opcional</span>
                            </label>
                            <div class="input-with-icon">
                                <i class="bi bi-hash input-icon-left"></i>
                                <input type="text" name="dados_bancarios[${bancoIndex}][digito_agencia]"
                                    class="form-input-new"
                                    placeholder="0"
                                    maxlength="2">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label-new">
                                <span>Conta</span>
                                <span class="required-star">*</span>
                            </label>
                            <div class="input-with-icon">
                                <i class="bi bi-credit-card-2-front input-icon-left"></i>
                                <input type="text" name="dados_bancarios[${bancoIndex}][conta]"
                                    class="form-input-new"
                                    placeholder="00000000"
                                    required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label-new">
                                <span>Dígito Conta</span>
                                <span class="required-star">*</span>
                            </label>
                            <div class="input-with-icon">
                                <i class="bi bi-hash input-icon-left"></i>
                                <input type="text" name="dados_bancarios[${bancoIndex}][digito_conta]"
                                    class="form-input-new"
                                    placeholder="0"
                                    maxlength="2"
                                    required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label-new">
                                <span>Titular da Conta</span>
                                <span class="required-star">*</span>
                            </label>
                            <div class="input-with-icon">
                                <i class="bi bi-person input-icon-left"></i>
                                <input type="text" name="dados_bancarios[${bancoIndex}][titular]"
                                    class="form-input-new"
                                    placeholder="Nome do titular"
                                    required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label-new">
                                <span>CPF/CNPJ do Titular</span>
                                <span class="required-star">*</span>
                            </label>
                            <div class="input-with-icon">
                                <i class="bi bi-card-text input-icon-left"></i>
                                <input type="text" name="dados_bancarios[${bancoIndex}][cpf_cnpj_titular]"
                                    class="form-input-new cpf-cnpj-mask"
                                    placeholder="000.000.000-00 ou 00.000.000/0000-00"
                                    required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label-new">
                                <span>Tipo de Chave PIX</span>
                                <span class="optional-badge">Opcional</span>
                            </label>
                            <div class="input-with-icon">
                                <i class="bi bi-qr-code input-icon-left"></i>
                                <select name="dados_bancarios[${bancoIndex}][pix_tipo]" class="form-select-new">
                                    <option value="">Selecione</option>
                                    <option value="cpf">CPF</option>
                                    <option value="cnpj">CNPJ</option>
                                    <option value="email">E-mail</option>
                                    <option value="telefone">Telefone</option>
                                    <option value="chave_aleatoria">Aleatória</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label-new">
                                <span>Chave PIX</span>
                                <span class="optional-badge">Opcional</span>
                            </label>
                            <div class="input-with-icon">
                                <i class="bi bi-key input-icon-left"></i>
                                <input type="text" name="dados_bancarios[${bancoIndex}][pix_chave]"
                                    class="form-input-new"
                                    placeholder="Digite a chave PIX">
                            </div>
                        </div>
                    </div>
                </div>`;

                $('#dadosBancariosContainer').append(template);

                const $newItem = $('#dadosBancariosContainer .dynamic-item').last();
                const $cpfCnpjInput = $newItem.find('.cpf-cnpj-mask');

                const SPMaskBehavior = function(val) {
                    return val.replace(/\D/g, '').length <= 11 ? '000.000.000-009' :
                        '00.000.000/0000-00';
                };

                const spOptions = {
                    onKeyPress: function(val, e, field, options) {
                        field.mask(SPMaskBehavior.apply({}, arguments), options);
                    }
                };

                $cpfCnpjInput.mask(SPMaskBehavior, spOptions);

                updateItemNumbers($('#dadosBancariosContainer'));
                bancoIndex++;
            });

            var buscaRepTimer = null;

            $('#buscaRepresentanteUsuario').on('input', function() {
                clearTimeout(buscaRepTimer);
                var termo = $(this).val().trim();
                var $resultados = $('#resultadosBuscaRepresentante');

                if (termo.length < 3) {
                    $resultados.hide().html('');
                    return;
                }

                buscaRepTimer = setTimeout(function() {
                    $.ajax({
                        url: '/perfil/buscar-representantes-usuarios',
                        method: 'GET',
                        data: { termo: termo },
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        success: function(response) {
                            if (!response.success) return;

                            if (response.resultados.length === 0) {
                                $resultados.show().html(
                                    '<div class="p-3 text-center text-muted">' +
                                    '<i class="bi bi-search"></i> Nenhum representante comercial encontrado' +
                                    '</div>'
                                );
                                return;
                            }

                            var html = '<div class="list-group">';
                            response.resultados.forEach(function(rep) {
                                html += '<div class="list-group-item d-flex justify-content-between align-items-center" ' +
                                    'style="background: var(--bg-card, #1e293b); border-color: var(--border-color, #475569); color: var(--text-primary, #f1f5f9);">' +
                                    '<div>' +
                                    '<strong>' + escapeHtmlPerfil(rep.nome) + '</strong>' +
                                    (rep.empresa ? ' <small class="text-muted">(' + escapeHtmlPerfil(rep.empresa) + ')</small>' : '') +
                                    '<br><small class="text-muted">' + escapeHtmlPerfil(rep.email) + '</small>' +
                                    '</div>' +
                                    '<button type="button" class="btn btn-sm btn-primary" onclick="solicitarVinculoRepresentante(' + rep.id + ', this)">' +
                                    '<i class="bi bi-link-45deg"></i> Vincular' +
                                    '</button>' +
                                    '</div>';
                            });
                            html += '</div>';

                            $resultados.show().html(html);
                        },
                        error: function() {
                            $resultados.show().html(
                                '<div class="p-3 text-center text-danger">' +
                                '<i class="bi bi-exclamation-triangle"></i> Erro ao buscar representantes comerciais' +
                                '</div>'
                            );
                        }
                    });
                }, 500);
            });
        });

        function solicitarVinculoRepresentante(repId, btnEl) {
            var $btn = $(btnEl);
            $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Enviando...');

            $.ajax({
                url: '/perfil/solicitar-vinculo-representante',
                method: 'POST',
                data: { representante_usuario_id: repId },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#buscaRepresentanteUsuario').val('');
                        $('#resultadosBuscaRepresentante').hide().html('');
                        $('#emptyVinculosRepresentante').remove();

                        var v = response.vinculo;
                        var html = '<div class="dynamic-item mb-3" id="vinculo-' + v.id + '">' +
                            '<div class="dynamic-item-header">' +
                            '<h4 class="dynamic-item-title">' +
                            '<i class="bi bi-person-badge"></i> ' + escapeHtmlPerfil(v.representante_nome) +
                            (v.representante_empresa ? ' <small class="text-muted ms-2">' + escapeHtmlPerfil(v.representante_empresa) + '</small>' : '') +
                            '</h4>' +
                            '<div class="d-flex align-items-center gap-2">' +
                            '<span class="badge" style="background-color: var(--warning-color); color: #000;">' +
                            '<i class="bi bi-clock"></i> Pendente</span>' +
                            '<button type="button" class="btn-remove-item" onclick="cancelarVinculoRepresentante(' + v.id + ', this)">' +
                            '<i class="bi bi-x-circle"></i> <span>Remover</span></button>' +
                            '</div></div>' +
                            '<div class="row g-2 px-3 pb-3">' +
                            '<div class="col-md-4"><small class="text-muted">Email:</small><div>' + escapeHtmlPerfil(v.representante_email) + '</div></div>' +
                            '<div class="col-md-4"><small class="text-muted">Solicitado em:</small><div>' + escapeHtmlPerfil(v.data) + '</div></div>' +
                            '</div></div>';

                        $('#listaVinculosRepresentante').append(html);
                    } else {
                        toastr.error(response.message);
                        $btn.prop('disabled', false).html('<i class="bi bi-link-45deg"></i> Vincular');
                    }
                },
                error: function(xhr) {
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Erro ao solicitar vínculo.';
                    toastr.error(msg);
                    $btn.prop('disabled', false).html('<i class="bi bi-link-45deg"></i> Vincular');
                }
            });
        }

        function cancelarVinculoRepresentante(vinculoId, btnEl) {
            Swal.fire({
                title: 'Remover vínculo?',
                text: 'O representante perderá acesso aos seus PIs e documentos.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#475569',
                confirmButtonText: 'Sim, remover',
                cancelButtonText: 'Cancelar',
                background: '#1e293b',
                color: '#f1f5f9',
            }).then(function(result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/perfil/cancelar-vinculo-representante/' + vinculoId,
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                $('#vinculo-' + vinculoId).fadeOut(300, function() {
                                    $(this).remove();
                                    if ($('#listaVinculosRepresentante .dynamic-item').length === 0) {
                                        $('#listaVinculosRepresentante').html(
                                            '<div class="empty-state-dynamic" id="emptyVinculosRepresentante">' +
                                            '<i class="bi bi-link-45deg"></i>' +
                                            '<p>Nenhum representante comercial vinculado.</p></div>'
                                        );
                                    }
                                });
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            toastr.error(xhr.responseJSON ? xhr.responseJSON.message : 'Erro ao remover vínculo.');
                        }
                    });
                }
            });
        }

        function escapeHtmlPerfil(str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    </script>
@endpush

@push('styles')
    <style>
        .page-header-content {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.75rem;
            box-shadow: var(--shadow-sm);
        }

        .page-title-wrapper {
            display: flex;
            align-items: center;
            gap: 1.25rem;
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

        .page-subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin: 0;
        }

        .onboarding-alert {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 2px solid #f59e0b;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            gap: 1.25rem;
            align-items: flex-start;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
        }

        .alert-icon {
            width: 48px;
            height: 48px;
            background: #f59e0b;
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .alert-content {
            flex: 1;
        }

        .alert-content h4 {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-on-light-bg);
            margin: 0 0 0.5rem 0;
        }

        .alert-content p {
            font-size: 0.9375rem;
            color: var(--text-on-light-bg);
            margin: 0 0 1rem 0;
        }

        .progress-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .progress-text {
            font-size: 0.875rem;
            color: #78350f;
            font-weight: 600;
        }

        .progress-bar-container {
            width: 100%;
            height: 8px;
            background: rgba(146, 64, 14, 0.2);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #f59e0b, #d97706);
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .wizard-container {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-sm);
        }

        .wizard-step {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
            position: relative;
            opacity: 0.6;
            transition: all 0.3s ease;
        }

        .wizard-step.active {
            opacity: 1;
        }

        .wizard-step.completed {
            opacity: 1;
        }

        .wizard-step .step-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: var(--bg-body);
            color: var(--text-muted);
            border: 2px solid var(--border-color);
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .wizard-step.active .step-icon {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(0, 94, 130, 0.25);
        }

        .wizard-step.completed .step-icon {
            background: #10b981;
            color: white;
            border-color: var(--success-color);
        }

        .step-info {
            display: flex;
            flex-direction: column;
        }

        .step-title {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--text-color);
        }

        .step-subtitle {
            font-size: 0.8125rem;
            color: var(--text-muted);
        }

        .wizard-divider {
            flex: 0 0 60px;
            height: 2px;
            background: var(--border-color);
            position: relative;
        }

        .wizard-step.completed+.wizard-divider {
            background: #10b981;
        }

        .tab-button[disabled] {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: all;
        }

        .tab-button[disabled]:hover {
            background: transparent;
            transform: none;
        }

        .profile-summary-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 2rem;
            align-items: center;
        }

        .profile-avatar-section {
            display: flex;
            justify-content: center;
        }

        .profile-avatar {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            border: 4px solid var(--bg-body);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .profile-status {
            position: absolute;
            bottom: 0px;
            right: 0px;
            width: 28px;
            height: 28px;
            background: var(--success-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            border: 3px solid var(--bg-card);
            font-size: 0.875rem;
        }

        .profile-info {
            flex: 1;
        }

        .profile-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-color);
            margin: 0 0 0.5rem 0;
        }

        .profile-email {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin: 0 0 0.75rem 0;
        }

        .profile-badge {
            display: inline-block;
            padding: 0.375rem 1rem;
            background: var(--primary-color);
            color: white;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .profile-meta {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding-left: 2rem;
            border-left: 1px solid var(--border-color);
        }

        .meta-item {
            display: flex;
            align-items: flex-start;
            gap: 0.875rem;
        }

        .meta-item i {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-body);
            color: var(--primary-color);
            border-radius: 10px;
            font-size: 1.125rem;
            flex-shrink: 0;
        }

        .meta-label {
            display: block;
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }

        .meta-value {
            display: block;
            font-size: 0.9rem;
            color: var(--text-color);
            font-weight: 600;
        }

        .tabs-nav {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 0.5rem;
            margin-bottom: 2rem;
            display: flex;
            gap: 0.5rem;
            box-shadow: var(--shadow-sm);
            overflow-x: auto;
        }

        .tab-button {
            flex: 1;
            min-width: 150px;
            padding: 1rem 1.5rem;
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

        .tab-button:hover:not(.active):not([disabled]) {
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
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card-header-section {
            padding: 1.5rem 1.75rem;
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
            padding: 1.75rem;
        }

        .input-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .required {
            color: #ef4444;
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

        .input-wrapper,
        .input-with-icon {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper>i,
        .input-icon-left {
            position: absolute;
            left: 1rem;
            color: var(--text-muted);
            font-size: 1rem;
            z-index: 1;
            pointer-events: none;
        }

        .input-field,
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
            min-height: 100px;
            resize: vertical;
        }

        #password {
            padding-right: 6rem;
        }

        .input-field:focus,
        .form-input-new:focus,
        .form-select-new:focus,
        .form-textarea-new:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--bg-card);
            box-shadow: 0 0 0 3px rgba(0, 94, 130, 0.08);
        }

        .input-field.is-invalid,
        .form-input-new.is-invalid,
        .form-select-new.is-invalid,
        .form-textarea-new.is-invalid {
            border-color: #ef4444;
        }

        .input-field.is-invalid:focus,
        .form-input-new.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.08);
        }

        .input-error,
        .field-error-message {
            display: block;
            color: #ef4444;
            font-size: 0.8rem;
            margin-top: 0.375rem;
        }

        .field-error-message {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
        }

        .field-error-message i {
            font-size: 0.875rem;
        }

        .field-help-text {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
            color: var(--text-muted);
            margin-top: 0.375rem;
        }

        .field-help-text i {
            font-size: 0.875rem;
            color: var(--primary-color);
        }

        .field-help-text.text-warning {
            color: var(--warning-color);
        }

        .field-help-text.text-warning i {
            color: var(--warning-color);
        }

        .toggle-password {
            position: absolute;
            right: 0.75rem;
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-password:hover {
            color: var(--primary-color);
            background: var(--bg-body);
            transform: scale(1.05);
        }

        .toggle-password:active {
            transform: scale(0.95);
        }

        .toggle-password:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .toggle-password i {
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .toggle-password.animating i {
            animation: quickFlip 0.3s ease;
        }

        @keyframes quickFlip {
            0% {
                transform: scale(1) rotateY(0deg);
            }

            50% {
                transform: scale(0.5) rotateY(90deg);
            }

            100% {
                transform: scale(1) rotateY(0deg);
            }
        }

        .btn-generate-password {
            position: absolute;
            right: 3rem;
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            z-index: 2;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-generate-password:not(:disabled):hover {
            color: var(--primary-color);
            background: var(--bg-body);
            transform: scale(1.05);
        }

        .btn-generate-password:not(:disabled):active {
            transform: scale(0.95);
        }

        .btn-generate-password:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-generate-password.generating {
            pointer-events: none;
        }

        .btn-generate-password i {
            transition: transform 0.2s ease;
        }

        .btn-generate-password.generating i {
            animation: quickSpin 0.6s ease-in-out;
        }

        @keyframes quickSpin {
            0% {
                transform: rotate(0deg) scale(1);
            }

            50% {
                transform: rotate(180deg) scale(1.2);
            }

            100% {
                transform: rotate(360deg) scale(1);
            }
        }

        .password-strength-wrapper {
            margin-top: 0.75rem;
        }

        .password-strength-bar {
            width: 100%;
            height: 6px;
            background: var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .password-strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 10px;
        }

        .password-strength-fill.weak {
            width: 25%;
            background: #ef4444;
        }

        .password-strength-fill.fair {
            width: 50%;
            background: #f59e0b;
        }

        .password-strength-fill.good {
            width: 75%;
            background: #3b82f6;
        }

        .password-strength-fill.strong {
            width: 100%;
            background: #10b981;
        }

        .password-strength-text {
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
            transition: all 0.2s ease;
        }

        .password-strength-text.weak {
            color: #ef4444;
        }

        .password-strength-text.fair {
            color: var(--warning-color);
        }

        .password-strength-text.good {
            color: #3b82f6;
        }

        .password-strength-text.strong {
            color: var(--success-color);
        }

        .password-requirements {
            margin-top: 1rem;
            padding: 1rem;
            background: var(--bg-body);
            border-radius: 10px;
            border: 1px solid var(--border-color);
            display: flex;
            justify-content: space-evenly;
        }

        .requirement-item {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            color: var(--text-muted);
            padding: 0.5rem 0.75rem;
            background: var(--bg-card);
            border-radius: 8px;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }

        .requirement-item i {
            font-size: 0.75rem;
            transition: all 0.2s ease;
        }

        .requirement-item.valid {
            color: var(--success-color);
            border-color: var(--success-color);
            background: rgba(16, 185, 129, 0.05);
        }

        .requirement-item.valid i::before {
            content: "\f26b";
            font-family: "bootstrap-icons";
            color: var(--success-color);
        }

        .password-match-message {
            display: block;
            font-size: 0.8rem;
            margin-top: 0.375rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .password-match-message.match {
            color: var(--success-color);
        }

        .password-match-message.no-match {
            color: #ef4444;
        }

        .form-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .btn-primary,
        .btn-secondary {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 94, 130, 0.25);
        }

        .btn-primary:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: var(--bg-body);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--border-color);
            transform: translateY(-1px);
        }

        .btn-secondary:active {
            transform: translateY(0);
        }

        .dynamic-items-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .dynamic-item {
            background: var(--bg-body);
            border: 2px solid var(--border-color);
            border-radius: 14px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .dynamic-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(0, 94, 130, 0.1);
        }

        .dynamic-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .dynamic-item-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-color);
            margin: 0;
        }

        .dynamic-item-title i {
            color: var(--primary-color);
            font-size: 1.5rem;
        }

        .btn-remove-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-remove-item:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }

        .btn-add-dynamic {
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.875rem 1.75rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9375rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-add-dynamic:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 94, 130, 0.3);
        }

        .empty-state-dynamic {
            text-align: center;
            padding: 2rem;
            background: var(--bg-body);
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            color: var(--text-muted);
        }

        .empty-state-dynamic i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state-dynamic p {
            margin: 0;
            font-size: 0.9375rem;
        }

        .cep-loading {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            display: none;
        }

        .cep-loading .spinner-border {
            width: 1.25rem;
            height: 1.25rem;
            border-width: 2px;
        }

        #cep:disabled {
            opacity: 0.7;
            cursor: wait;
        }

        .field-filled {
            animation: fieldFillPulse 0.6s ease;
        }

        @keyframes fieldFillPulse {
            0% {
                background-color: var(--bg-body);
            }

            50% {
                background-color: rgba(0, 94, 130, 0.1);
                transform: scale(1.02);
            }

            100% {
                background-color: var(--bg-body);
                transform: scale(1);
            }
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 10px;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .alert i {
            font-size: 1.25rem;
            flex-shrink: 0;
            margin-top: 0.125rem;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #f59e0b;
            color: var(--text-on-light-bg);
        }

        .alert-warning i {
            color: var(--warning-color);
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

        .swal-confirm-btn {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            color: white !important;
        }

        .swal-confirm-btn:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.35) !important;
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

        .warning-box {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            padding: 1.25rem;
            border-radius: 10px;
            margin: 1.25rem 0;
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
            .wizard-container {
                flex-direction: column;
                gap: 1.5rem;
            }

            .wizard-step {
                width: 100%;
            }

            .wizard-divider {
                width: 2px;
                height: 40px;
                flex: 0 0 40px;
            }

            .profile-summary-card {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                text-align: center;
            }

            .profile-avatar-section {
                justify-content: center;
            }

            .profile-meta {
                border-left: none;
                border-top: 1px solid var(--border-color);
                padding-left: 0;
                padding-top: 1.5rem;
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

            .password-requirements {
                flex-direction: column;
            }

            .dynamic-item-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .btn-remove-item {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            .onboarding-alert {
                flex-direction: column;
                padding: 1.25rem;
            }

            .wizard-container {
                padding: 1.5rem;
            }

            .wizard-step .step-icon {
                width: 40px;
                height: 40px;
                font-size: 1.25rem;
            }

            .profile-summary-card {
                padding: 1.5rem;
            }

            .profile-avatar {
                width: 80px;
                height: 80px;
                font-size: 1.75rem;
            }

            .profile-name {
                font-size: 1.25rem;
            }

            #password {
                padding-right: 3rem;
            }

            .btn-generate-password {
                position: relative;
                right: auto;
                margin-top: 0.5rem;
                width: 100%;
                justify-content: center;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.75rem;
                border: 1px solid var(--border-color);
                background: var(--bg-body);
                font-size: 0.875rem;
                font-weight: 500;
            }

            .btn-generate-password::after {
                content: 'Gerar Senha Forte';
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

            .swal2-popup {
                padding: 1.5rem !important;
                width: calc(100% - 2rem) !important;
                max-width: 500px !important;
            }

            .swal2-title {
                font-size: 1.5rem !important;
                margin-bottom: 1.25rem !important;
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
        }
    </style>
@endpush