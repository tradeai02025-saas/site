{{-- resources/views/users/form.blade.php --}}

@extends('layouts.app')

@section('title', isset($user) ? 'Editar Usuário' : 'Novo Usuário')

@section('content')
    <link href="{{ asset('css/pages/forms.css') }}" rel="stylesheet">
    <div class="container-fluid">
        <div class="page-header">
            <div class="page-header-content">
                <div class="page-title-wrapper">
                    <a href="{{ route('users.index') }}" class="page-back-btn">
                        <i class="bi bi-arrow-left-circle"></i>
                    </a>
                    <div class="page-icon">
                        <i class="bi bi-person-fill-gear"></i>
                    </div>
                    <div>
                        <h1 class="page-title">{{ isset($user) ? 'Editar Usuário' : 'Novo Usuário' }}</h1>
                        <p class="page-subtitle">
                            {{ isset($user) ? 'Atualize as informações de ' . $user->name : 'Cadastre um novo usuário no sistema' }}
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

        <div class="tabs-nav" id="tabsNav" style="display: none;">
            <button type="button" class="tab-button active" data-tab="basico">
                <i class="bi bi-person-circle"></i>
                <span>Dados Básicos</span>
                <span class="tab-required">Obrigatório</span>
            </button>
            <button type="button" class="tab-button" data-tab="complementar">
                <i class="bi bi-file-earmark-text"></i>
                <span>Informações Complementares</span>
                <span class="tab-required">Obrigatório</span>
            </button>
        </div>

        <form action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}" method="POST"
            id="userForm">
            @csrf
            @if (isset($user))
                @method('PUT')
            @endif

            <div class="tab-content-area active" id="tab-basico">
                <div class="content-card">
                    <div class="card-header-section">
                        <h2 class="card-title">
                            <i class="bi bi-person-vcard-fill"></i>
                            Informações de Acesso
                        </h2>
                        <p class="card-subtitle">Configure as credenciais de login do usuário</p>
                    </div>

                    <div class="card-content">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="field-label-new">
                                    <span>Nome de Usuário</span>
                                    <span class="required-star">*</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-person input-icon-left"></i>
                                    <input type="text" name="name" id="name"
                                        class="form-input-new @error('name') is-invalid @enderror"
                                        placeholder="Digite o nome completo" value="{{ old('name', $user->name ?? '') }}"
                                        required autofocus>
                                </div>
                                @error('name')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="field-label-new">
                                    <span>Endereço de Email</span>
                                    <span class="required-star">*</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-envelope input-icon-left"></i>
                                    <input type="email" name="email" id="email"
                                        class="form-input-new @error('email') is-invalid @enderror"
                                        placeholder="email@exemplo.com" value="{{ old('email', $user->email ?? '') }}"
                                        required>
                                </div>
                                @error('email')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="field-label-new">
                                    <span>Senha de Acesso</span>
                                    @if (isset($user))
                                        <span class="optional-badge">Opcional</span>
                                    @else
                                        <span class="required-star">*</span>
                                    @endif
                                </label>
                                <div class="input-with-icon flex flex-wrap">
                                    <i class="bi bi-lock-fill input-icon-left"></i>
                                    <input type="password" name="password" id="password"
                                        class="form-input-new @error('password') is-invalid @enderror"
                                        placeholder="Crie uma senha segura" maxlength="64"
                                        {{ isset($user) ? '' : 'required' }}>
                                    <button type="button" class="btn-generate-password" id="generatePassword"
                                        title="Gerar senha forte" tabindex="-1">
                                        <i class="bi bi-stars"></i>
                                    </button>
                                    <button type="button" class="toggle-password" data-target="password"
                                        title="Mostrar senha" tabindex="-1">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="field-label-new">
                                    <span>Confirme a Senha</span>
                                    @if (!isset($user))
                                        <span class="required-star">*</span>
                                    @else
                                        <span class="optional-badge">Opcional</span>
                                    @endif
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-shield-check input-icon-left"></i>
                                    <input type="password" name="password_confirmation" id="password_confirmation"
                                        class="form-input-new" placeholder="Digite a senha novamente" maxlength="64"
                                        {{ isset($user) ? '' : 'required' }}>
                                    <button type="button" class="toggle-password" data-target="password_confirmation"
                                        title="Mostrar senha" tabindex="-1">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <span class="password-match-message" id="passwordMatchMessage"></span>
                            </div>

                            <div class="col-md-12">
                                <div class="password-requirements-card" id="passwordRequirements" style="display: none;">
                                    <div class="requirements-header">
                                        <i class="bi bi-shield-lock-fill"></i>
                                        <span>Requisitos de Segurança</span>
                                    </div>
                                    <div class="requirements-grid">
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header-section">
                        <h2 class="card-title">
                            <i class="bi bi-shield-check"></i>
                            Perfil de Acesso
                        </h2>
                        <p class="card-subtitle">Selecione o nível de permissão do usuário</p>
                    </div>

                    <div class="card-content">
                        <div class="roles-grid">
                            @foreach ($roles as $role)
                                @php
                                    $roleConfig = [
                                        'admin' => [
                                            'icon' => 'bi-shield-fill-check',
                                            'color' => '#dc2626',
                                            'title' => 'Administrador',
                                            'desc' => 'Acesso total ao sistema',
                                        ],
                                        'gerencial' => [
                                            'icon' => 'bi-briefcase-fill',
                                            'color' => '#005e82',
                                            'title' => 'Gerencial',
                                            'desc' => 'Gerencia PIs e equipes',
                                        ],
                                        'operacional' => [
                                            'icon' => 'bi-gear-fill',
                                            'color' => '#0284c7',
                                            'title' => 'Operacional',
                                            'desc' => 'Valida documentos',
                                        ],
                                        'fornecedor' => [
                                            'icon' => 'bi-building',
                                            'color' => '#d97706',
                                            'title' => 'Fornecedor',
                                            'desc' => 'Acesso externo limitado',
                                        ],
                                        'representante' => [
                                            'icon' => 'bi-person-badge',
                                            'color' => '#7c3aed',
                                            'title' => 'Representante',
                                            'desc' => 'Representa fornecedores',
                                        ],
                                    ];
                                    $config = $roleConfig[$role->name] ?? [
                                        'icon' => 'bi-person',
                                        'color' => '#64748b',
                                        'title' => $role->name,
                                        'desc' => '',
                                    ];
                                    $isSelected =
                                        old(
                                            'role',
                                            isset($user) && $user->roles->first() ? $user->roles->first()->name : '',
                                        ) == $role->name;
                                @endphp

                                @if ($role->name === 'admin')
                                    @hasrole('admin')
                                        <input type="radio" class="role-input" name="role"
                                            id="role_{{ $role->name }}" value="{{ $role->name }}"
                                            {{ $isSelected ? 'checked' : '' }} required>
                                        <label class="role-option" for="role_{{ $role->name }}">
                                            <div class="role-icon-circle" style="background: {{ $config['color'] }}">
                                                <i class="bi {{ $config['icon'] }}"></i>
                                            </div>
                                            <div class="role-details">
                                                <span class="role-name">{{ $config['title'] }}</span>
                                                <span class="role-description">{{ $config['desc'] }}</span>
                                            </div>
                                            <div class="role-selector">
                                                <i class="bi bi-check-circle-fill"></i>
                                            </div>
                                        </label>
                                    @endhasrole
                                @else
                                    <input type="radio" class="role-input" name="role"
                                        id="role_{{ $role->name }}" value="{{ $role->name }}"
                                        {{ $isSelected ? 'checked' : '' }} required>
                                    <label class="role-option" for="role_{{ $role->name }}">
                                        <div class="role-icon-circle" style="background: {{ $config['color'] }}">
                                            <i class="bi {{ $config['icon'] }}"></i>
                                        </div>
                                        <div class="role-details">
                                            <span class="role-name">{{ $config['title'] }}</span>
                                            <span class="role-description">{{ $config['desc'] }}</span>
                                        </div>
                                        <div class="role-selector">
                                            <i class="bi bi-check-circle-fill"></i>
                                        </div>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                        @error('role')
                            <span class="field-error-message">
                                <i class="bi bi-exclamation-circle"></i>
                                {{ $message }}
                            </span>
                        @enderror
                    </div>
                </div>

                <div id="genOpSection" style="display: none;">
                    <div class="content-card">
                        <div class="card-header-section">
                            <h2 class="card-title">
                                <i class="bi bi-person-badge-fill"></i>
                                Dados Profissionais
                            </h2>
                            <p class="card-subtitle">Informações do colaborador interno</p>
                        </div>

                        <div class="card-content">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="field-label-new">
                                        <span>Nome Completo</span>
                                        <span class="required-star">*</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="bi bi-person-vcard input-icon-left"></i>
                                        <input type="text" name="nome_completo" id="nome_completo"
                                            class="form-input-new @error('nome_completo') is-invalid @enderror"
                                            placeholder="Nome completo do colaborador"
                                            value="{{ old('nome_completo', isset($user) && $user->genOp ? $user->genOp->nome_completo : '') }}">
                                    </div>
                                    @error('nome_completo')
                                        <span class="field-error-message">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="field-label-new">
                                        <span>Telefone</span>
                                        <span class="optional-badge">Opcional</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="bi bi-telephone-fill input-icon-left"></i>
                                        <input type="text" name="telefone"
                                            class="form-input-new telefone-mask @error('telefone') is-invalid @enderror"
                                            placeholder="(00) 00000-0000"
                                            value="{{ old('telefone', isset($user) && $user->genOp ? $user->genOp->telefone : '') }}">
                                    </div>
                                    @error('telefone')
                                        <span class="field-error-message">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-12">
                                    <label class="field-label-new">
                                        <span>Departamento</span>
                                        <span class="optional-badge">Opcional</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="bi bi-diagram-3-fill input-icon-left"></i>
                                        <input type="text" name="departamento"
                                            class="form-input-new @error('departamento') is-invalid @enderror"
                                            placeholder="Ex: Operações, Logística, Comercial"
                                            value="{{ old('departamento', isset($user) && $user->genOp ? $user->genOp->departamento : '') }}">
                                    </div>
                                    @error('departamento')
                                        <span class="field-error-message">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="representanteSection" style="display: none;">
                    <div class="content-card">
                        <div class="card-header-section">
                            <h2 class="card-title">
                                <i class="bi bi-person-badge-fill"></i>
                                Dados do Representante
                            </h2>
                            <p class="card-subtitle">Informações da empresa ou pessoa que representa fornecedores</p>
                        </div>

                        <div class="card-content">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="field-label-new">
                                        <span>Nome da Empresa</span>
                                        <span class="optional-badge">Opcional</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="bi bi-building input-icon-left"></i>
                                        <input type="text" name="nome_empresa" id="nome_empresa"
                                            class="form-input-new @error('nome_empresa') is-invalid @enderror"
                                            placeholder="Nome da empresa representante (se aplicável)"
                                            value="{{ old('nome_empresa', isset($user) && $user->representanteUsuario ? $user->representanteUsuario->nome_empresa : '') }}">
                                    </div>
                                    @error('nome_empresa')
                                        <span class="field-error-message">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="field-label-new">
                                        <span>CNPJ</span>
                                        <span class="optional-badge">Opcional</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="bi bi-card-text input-icon-left"></i>
                                        <input type="text" name="representante_cnpj" id="representante_cnpj"
                                            class="form-input-new cnpj-mask @error('representante_cnpj') is-invalid @enderror"
                                            placeholder="00.000.000/0000-00"
                                            value="{{ old('representante_cnpj', isset($user) && $user->representanteUsuario && $user->representanteUsuario->cnpj ? preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $user->representanteUsuario->cnpj) : '') }}">
                                    </div>
                                    @error('representante_cnpj')
                                        <span class="field-error-message">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="field-label-new">
                                        <span>Telefone</span>
                                        <span class="optional-badge">Opcional</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="bi bi-telephone-fill input-icon-left"></i>
                                        <input type="text" name="representante_telefone"
                                            class="form-input-new telefone-mask @error('representante_telefone') is-invalid @enderror"
                                            placeholder="(00) 00000-0000"
                                            value="{{ old('representante_telefone', isset($user) && $user->representanteUsuario ? $user->representanteUsuario->telefone : '') }}">
                                    </div>
                                    @error('representante_telefone')
                                        <span class="field-error-message">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="field-label-new">
                                        <span>Observações</span>
                                        <span class="optional-badge">Opcional</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="bi bi-chat-text input-icon-left"></i>
                                        <textarea name="representante_observacoes"
                                            class="form-input-new @error('representante_observacoes') is-invalid @enderror"
                                            placeholder="Informações adicionais sobre o representante"
                                            rows="2" maxlength="1000">{{ old('representante_observacoes', isset($user) && $user->representanteUsuario ? $user->representanteUsuario->observacoes : '') }}</textarea>
                                    </div>
                                    @error('representante_observacoes')
                                        <span class="field-error-message">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="fornecedorBasicoSection" style="display: none;">
                    <div class="content-card">
                        <div class="card-header-section">
                            <h2 class="card-title">
                                <i class="bi bi-building-fill"></i>
                                Dados da Empresa
                            </h2>
                            <p class="card-subtitle">Informações corporativas do fornecedor</p>
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
                                        <input type="text" name="razao_social" id="razao_social"
                                            class="form-input-new @error('razao_social') is-invalid @enderror"
                                            placeholder="Nome jurídico da empresa"
                                            value="{{ old('razao_social', isset($user) && $user->fornecedor ? $user->fornecedor->razao_social : '') }}">
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
                                            placeholder="00.000.000/0000-00"
                                            value="{{ old('cnpj', isset($user) && $user->fornecedor && $user->fornecedor->cnpj ? substr($user->fornecedor->cnpj, 0, 2) . '.' . substr($user->fornecedor->cnpj, 2, 3) . '.' . substr($user->fornecedor->cnpj, 5, 3) . '/' . substr($user->fornecedor->cnpj, 8, 4) . '-' . substr($user->fornecedor->cnpj, 12, 2) : '') }}">
                                    </div>
                                    @error('cnpj')
                                        <span class="field-error-message">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
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
                                            placeholder="Nome comercial"
                                            value="{{ old('nome_fantasia', isset($user) && $user->fornecedor ? $user->fornecedor->nome_fantasia : '') }}">
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
                                        <span class="required-star">*</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="bi bi-telephone-fill input-icon-left"></i>
                                        <input type="text" name="fornecedor_telefone" id="fornecedor_telefone"
                                            class="form-input-new telefone-mask @error('fornecedor_telefone') is-invalid @enderror"
                                            placeholder="(00) 00000-0000"
                                            value="{{ old('fornecedor_telefone', isset($user) && $user->fornecedor ? $user->fornecedor->telefone : '') }}">
                                    </div>
                                    @error('fornecedor_telefone')
                                        <span class="field-error-message">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-footer">
                                <a href="{{ route('users.index') }}" class="btn-secondary">
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

                <div id="adminSubmitSection" style="display: none;">
                    <div class="form-footer">
                        <a href="{{ route('users.index') }}" class="btn-secondary">
                            <i class="bi bi-x-circle"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn-primary" id="submitBtnSimple">
                            <i class="bi bi-check-circle"></i>
                            <span>{{ isset($user) ? 'Atualizar Usuário' : 'Salvar Usuário' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="tab-content-area" id="tab-complementar">
                <div class="content-card">
                    <div class="card-header-section">
                        <h2 class="card-title">
                            <i class="bi bi-geo-alt-fill"></i>
                            Endereço
                        </h2>
                        <p class="card-subtitle">Localização da sede da empresa</p>
                    </div>

                    <div class="card-content">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="field-label-new">
                                    <span>CEP</span>
                                    <span class="required-star">*</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-mailbox input-icon-left"></i>
                                    <input type="text" name="cep" id="cep"
                                        class="form-input-new cep-mask @error('cep') is-invalid @enderror"
                                        placeholder="00000-000"
                                        value="{{ old('cep', isset($user) && $user->fornecedor && $user->fornecedor->enderecos->first() ? $user->fornecedor->enderecos->first()->cep : '') }}">
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
                                    <span class="required-star">*</span>
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
                                            $estadoAtual = old(
                                                'estado',
                                                isset($user) &&
                                                $user->fornecedor &&
                                                $user->fornecedor->enderecos->first()
                                                    ? $user->fornecedor->enderecos->first()->estado
                                                    : '',
                                            );
                                        @endphp
                                        @foreach ($estados as $sigla => $nome)
                                            <option value="{{ $sigla }}"
                                                {{ $estadoAtual == $sigla ? 'selected' : '' }}>
                                                {{ $sigla }} - {{ $nome }}
                                            </option>
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
                                    <span class="required-star">*</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-pin-map-fill input-icon-left"></i>
                                    <input type="text" name="cidade" id="cidade"
                                        class="form-input-new @error('cidade') is-invalid @enderror"
                                        placeholder="Nome da cidade"
                                        value="{{ old('cidade', isset($user) && $user->fornecedor && $user->fornecedor->enderecos->first() ? $user->fornecedor->enderecos->first()->cidade : '') }}">
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
                                    <span class="required-star">*</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-signpost-split-fill input-icon-left"></i>
                                    <input type="text" name="bairro" id="bairro"
                                        class="form-input-new @error('bairro') is-invalid @enderror"
                                        placeholder="Nome do bairro"
                                        value="{{ old('bairro', isset($user) && $user->fornecedor && $user->fornecedor->enderecos->first() ? $user->fornecedor->enderecos->first()->bairro : '') }}">
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
                                    <span class="required-star">*</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-signpost-fill input-icon-left"></i>
                                    <input type="text" name="logradouro" id="logradouro"
                                        class="form-input-new @error('logradouro') is-invalid @enderror"
                                        placeholder="Rua, Avenida, Travessa..."
                                        value="{{ old('logradouro', isset($user) && $user->fornecedor && $user->fornecedor->enderecos->first() ? $user->fornecedor->enderecos->first()->logradouro : '') }}">
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
                                    <span class="required-star">*</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-hash input-icon-left"></i>
                                    <input type="text" name="numero" id="numero"
                                        class="form-input-new @error('numero') is-invalid @enderror" placeholder="Nº"
                                        value="{{ old('numero', isset($user) && $user->fornecedor && $user->fornecedor->enderecos->first() ? $user->fornecedor->enderecos->first()->numero : '') }}">
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
                                        value="{{ old('complemento', isset($user) && $user->fornecedor && $user->fornecedor->enderecos->first() ? $user->fornecedor->enderecos->first()->complemento : '') }}">
                                </div>
                                @error('complemento')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header-section">
                        <h2 class="card-title">
                            <i class="bi bi-person-badge-fill"></i>
                            Representantes
                        </h2>
                        <p class="card-subtitle">Pessoas de contato do fornecedor</p>
                    </div>

                    <div class="card-content">
                        <div class="dynamic-items-container" id="representantesContainer">
                            @if (isset($user) && $user->fornecedor && $user->fornecedor->representantes->count() > 0)
                                @foreach ($user->fornecedor->representantes as $index => $rep)
                                    <div class="dynamic-item" data-index="{{ $index }}">
                                        <input type="hidden" name="representantes[{{ $index }}][id]"
                                            value="{{ $rep->id }}">

                                        <div class="dynamic-item-header">
                                            <h4 class="dynamic-item-title">
                                                <i class="bi bi-person-circle"></i>
                                                Representante #<span class="item-number">{{ $index + 1 }}</span>
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
                                                    <span class="required-star">*</span>
                                                </label>
                                                <div class="input-with-icon">
                                                    <i class="bi bi-person input-icon-left"></i>
                                                    <input type="text"
                                                        name="representantes[{{ $index }}][nome_completo]"
                                                        class="form-input-new"
                                                        value="{{ old('representantes.' . $index . '.nome_completo', $rep->nome_completo) }}"
                                                        placeholder="Nome completo do representante" required>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="field-label-new">
                                                    <span>CPF</span>
                                                    <span class="required-star">*</span>
                                                </label>
                                                <div class="input-with-icon">
                                                    <i class="bi bi-card-text input-icon-left"></i>
                                                    <input type="text" name="representantes[{{ $index }}][cpf]"
                                                        class="form-input-new cpf-mask validate-cpf"
                                                        value="{{ old('representantes.' . $index . '.cpf', $rep->cpf ? substr($rep->cpf, 0, 3) . '.' . substr($rep->cpf, 3, 3) . '.' . substr($rep->cpf, 6, 3) . '-' . substr($rep->cpf, 9, 2) : '') }}"
                                                        placeholder="000.000.000-00" required>
                                                </div>
                                                <span class="cpf-error field-error-message" style="display: none;">
                                                    <i class="bi bi-exclamation-circle"></i>
                                                    CPF inválido
                                                </span>
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
                                    <p>Nenhum representante adicionado. Clique em "Adicionar Representante" para começar.
                                    </p>
                                </div>
                            @endif
                        </div>

                        <button type="button" class="btn-add-dynamic" id="addRepresentante">
                            <i class="bi bi-plus-circle"></i>
                            <span>Adicionar Representante</span>
                        </button>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header-section">
                        <h2 class="card-title">
                            <i class="bi bi-bank"></i>
                            Dados Bancários
                        </h2>
                        <p class="card-subtitle">Contas bancárias para pagamento</p>
                    </div>

                    <div class="card-content">
                        <div class="dynamic-items-container" id="dadosBancariosContainer">
                            @if (isset($user) && $user->fornecedor && $user->fornecedor->dadosBancarios->count() > 0)
                                @foreach ($user->fornecedor->dadosBancarios as $index => $banco)
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
                            <button type="button" class="btn-secondary" id="backToBasico">
                                <i class="bi bi-arrow-left-circle"></i>
                                Voltar
                            </button>
                            <button type="submit" class="btn-primary" id="submitBtn">
                                <i class="bi bi-check-circle"></i>
                                <span>{{ isset($user) ? 'Atualizar Usuário' : 'Salvar Usuário' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="{{ asset('js/pages/form-users.js') }}"></script>
    <script>
        const routes = {
            destroyRepresentante: "{{ route('users.destroy_representante', ['user' => isset($user) ? $user->id : ':user', 'representante' => ':representante']) }}",
            destroyDadosBancarios: "{{ route('users.destroy_dados_bancarios', ['user' => isset($user) ? $user->id : ':user', 'dadosBancarios' => ':dadosBancarios']) }}"
        };

        const isEditing = {{ isset($user) ? 'true' : 'false' }};

        function validarCPF(cpf) {
            cpf = cpf.replace(/[^\d]/g, '');

            if (cpf.length !== 11) return false;

            if (/^(\d)\1{10}$/.test(cpf)) return false;

            let soma = 0;
            let resto;

            for (let i = 1; i <= 9; i++) {
                soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
            }

            resto = (soma * 10) % 11;
            if (resto === 10 || resto === 11) resto = 0;
            if (resto !== parseInt(cpf.substring(9, 10))) return false;

            soma = 0;
            for (let i = 1; i <= 10; i++) {
                soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
            }

            resto = (soma * 10) % 11;
            if (resto === 10 || resto === 11) resto = 0;
            if (resto !== parseInt(cpf.substring(10, 11))) return false;

            return true;
        }

        function validarCNPJ(cnpj) {
            cnpj = cnpj.replace(/[^\d]/g, '');

            if (cnpj.length !== 14) return false;

            if (/^(\d)\1{13}$/.test(cnpj)) return false;

            let tamanho = cnpj.length - 2;
            let numeros = cnpj.substring(0, tamanho);
            let digitos = cnpj.substring(tamanho);
            let soma = 0;
            let pos = tamanho - 7;

            for (let i = tamanho; i >= 1; i--) {
                soma += numeros.charAt(tamanho - i) * pos--;
                if (pos < 2) pos = 9;
            }

            let resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
            if (resultado != digitos.charAt(0)) return false;

            tamanho = tamanho + 1;
            numeros = cnpj.substring(0, tamanho);
            soma = 0;
            pos = tamanho - 7;

            for (let i = tamanho; i >= 1; i--) {
                soma += numeros.charAt(tamanho - i) * pos--;
                if (pos < 2) pos = 9;
            }

            resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
            if (resultado != digitos.charAt(1)) return false;

            return true;
        }

        function validateBasicoTab() {
            let isValid = true;
            let errors = [];

            const requiredFieldsBasic = [{
                    id: '#name',
                    name: 'Nome de Usuário'
                },
                {
                    id: '#email',
                    name: 'Email'
                }
            ];

            if (!isEditing) {
                requiredFieldsBasic.push({
                    id: '#password',
                    name: 'Senha'
                }, {
                    id: '#password_confirmation',
                    name: 'Confirmação de Senha'
                });
            }

            const selectedRole = $('input[name="role"]:checked').val();

            if (!selectedRole) {
                isValid = false;
                errors.push('Perfil de Acesso');
            }

            requiredFieldsBasic.forEach(field => {
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

            if (selectedRole === 'gerencial' || selectedRole === 'operacional') {
                const $nomeCompleto = $('#nome_completo');
                if (!$nomeCompleto.val() || $nomeCompleto.val().trim() === '') {
                    isValid = false;
                    $nomeCompleto.addClass('is-invalid');
                    errors.push('Nome Completo (Dados Profissionais)');
                } else {
                    $nomeCompleto.removeClass('is-invalid');
                }
            }

            if (selectedRole === 'fornecedor') {
                const fornecedorFields = [{
                        id: '#razao_social',
                        name: 'Razão Social'
                    },
                    {
                        id: '#cnpj',
                        name: 'CNPJ'
                    },
                    {
                        id: '#nome_fantasia',
                        name: 'Nome Fantasia'
                    },
                    {
                        id: '#fornecedor_telefone',
                        name: 'Telefone'
                    }
                ];

                fornecedorFields.forEach(field => {
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

                const cnpj = $('#cnpj').val();
                if (cnpj && !validarCNPJ(cnpj)) {
                    isValid = false;
                    $('#cnpj').addClass('is-invalid');
                    if (!errors.includes('CNPJ')) {
                        errors.push('CNPJ (inválido)');
                    }
                }
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

        function validateComplementarTab() {
            let isValid = true;
            let errors = [];

            const requiredFields = [{
                    id: '#cep',
                    name: 'CEP'
                },
                {
                    id: '#estado',
                    name: 'Estado'
                },
                {
                    id: '#cidade',
                    name: 'Cidade'
                },
                {
                    id: '#bairro',
                    name: 'Bairro'
                },
                {
                    id: '#logradouro',
                    name: 'Logradouro'
                },
                {
                    id: '#numero',
                    name: 'Número'
                }
            ];

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

        function switchTab(tabName) {
            const currentTab = $('.tab-content-area.active').attr('id').replace('tab-', '');

            if (currentTab === 'basico' && tabName === 'complementar') {
                if (!validateBasicoTab()) {
                    return;
                }
            }

            $('.tab-button').removeClass('active');
            $(`.tab-button[data-tab="${tabName}"]`).addClass('active');
            $('.tab-content-area').removeClass('active');
            $(`#tab-${tabName}`).addClass('active');
            $('html, body').animate({
                scrollTop: 0
            }, 300);
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

                $('#cep').on('keypress', (e) => {
                    if (e.which === 13) {
                        e.preventDefault();
                        $(e.target).blur();
                        return false;
                    }
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
        }

        $(document).ready(function() {
            let representanteIndex =
                {{ isset($user) && $user->fornecedor ? $user->fornecedor->representantes->count() : 0 }};
            let bancoIndex =
                {{ isset($user) && $user->fornecedor ? $user->fornecedor->dadosBancarios->count() : 0 }};

            const cepManager = new CEPSearchManager();

            function aplicarMascaras() {
                $('.cpf-mask').mask('000.000.000-00');
                $('.cnpj-mask').each(function() {
                    $(this).mask('00.000.000/0000-00');
                    
                    $(this).on('paste', function(e) {
                        const $el = $(this);
                        setTimeout(function() {
                            let valor = $el.val().replace(/\D/g, '');
                            if (valor.length <= 14) {
                                $el.unmask().val(valor).mask('00.000.000/0000-00');
                            }
                        }, 10);
                    });
                });
                
                $('.cep-mask').mask('00000-000');
                $('.telefone-mask').mask('(00) 00000-0000');

                var SPMaskBehavior = function(val) {
                    return val.replace(/\D/g, '').length <= 11 ? '000.000.000-009' : '00.000.000/0000-00';
                };
                var spOptions = {
                    onKeyPress: function(val, e, field, options) {
                        field.mask(SPMaskBehavior.apply({}, arguments), options);
                    }
                };

                $('.cpf-cnpj-mask').each(function() {
                    $(this).mask(SPMaskBehavior($(this).val()), spOptions);
                });

                $(document).on('focus', '.cpf-cnpj-mask', function() {
                    $(this).mask(SPMaskBehavior($(this).val()), spOptions);
                });

                $(document).on('paste', '.cpf-cnpj-mask', function(e) {
                    e.preventDefault();
                    var raw = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
                    var digits = raw.replace(/\D/g, '').substring(0, 14);
                    $(this).val(digits);
                    $(this).mask(SPMaskBehavior(digits), spOptions);
                    $(this).trigger('input');
                });
            }

            aplicarMascaras();

            $(document).on('blur', '.validate-cpf', function() {
                const $input = $(this);
                const cpf = $input.val();
                const $error = $input.closest('.col-md-6, .col-md-12').find('.cpf-error');

                if (cpf && cpf.replace(/[^\d]/g, '').length === 11) {
                    if (validarCPF(cpf)) {
                        $input.removeClass('is-invalid').addClass('is-valid');
                        $error.hide();
                    } else {
                        $input.removeClass('is-valid').addClass('is-invalid');
                        $error.show();
                    }
                } else if (cpf) {
                    $input.removeClass('is-valid').addClass('is-invalid');
                    $error.show();
                } else {
                    $input.removeClass('is-valid is-invalid');
                    $error.hide();
                }
            });

            $(document).on('input', '.validate-cpf', function() {
                const $input = $(this);
                $input.removeClass('is-valid is-invalid');
                $input.closest('.col-md-6, .col-md-12').find('.cpf-error').hide();
            });

            $(document).on('blur', '.cpf-cnpj-mask', function() {
                const $input = $(this);
                const valor = $input.val();
                const valorLimpo = valor.replace(/[^\d]/g, '');

                if (!valor) {
                    $input.removeClass('is-valid is-invalid');
                    return;
                }

                let isValid = false;

                if (valorLimpo.length === 11) {
                    isValid = validarCPF(valor);
                } else if (valorLimpo.length === 14) {
                    isValid = validarCNPJ(valor);
                }

                if (isValid) {
                    $input.removeClass('is-invalid').addClass('is-valid');
                } else {
                    $input.removeClass('is-valid').addClass('is-invalid');
                }
            });

            $(document).on('input', '.cpf-cnpj-mask', function() {
                const $input = $(this);
                $input.removeClass('is-valid is-invalid');
            });

            $('input[name="role"]').on('change', function() {
                const role = $(this).val();

                $('#genOpSection').hide();
                $('#fornecedorBasicoSection').hide();
                $('#representanteSection').hide();
                $('#adminSubmitSection').hide();
                $('#tabsNav').hide();
                $('.tab-content-area').removeClass('active');
                $('#tab-basico').addClass('active');

                if (role === 'fornecedor') {
                    $('#fornecedorBasicoSection').show();
                    $('#tabsNav').show();
                } else if (role === 'gerencial' || role === 'operacional') {
                    $('#genOpSection').show();
                    $('#adminSubmitSection').show();
                } else if (role === 'representante') {
                    $('#representanteSection').show();
                    $('#adminSubmitSection').show();
                } else {
                    $('#adminSubmitSection').show();
                }
            });

            @if (isset($user) && $user->roles->first())
                const initialRole = '{{ $user->roles->first()->name }}';
                if (initialRole) {
                    $('input[name="role"][value="' + initialRole + '"]').trigger('change');
                }
            @endif

            $('.tab-button').on('click', function() {
                const tab = $(this).data('tab');
                const currentTab = $('.tab-content-area.active').attr('id').replace('tab-', '');

                if (currentTab === 'basico' && tab === 'complementar') {
                    if (!validateBasicoTab()) {
                        return;
                    }
                }

                switchTab(tab);
            });

            $('#nextToComplementar').on('click', function() {
                if (validateBasicoTab()) {
                    switchTab('complementar');
                }
            });

            $('#backToBasico').on('click', function() {
                switchTab('basico');
            });

            $('#addRepresentante').on('click', function() {
                $('#emptyRepresentantes').hide();

                const template = `
                    <div class="dynamic-item" data-index="${representanteIndex}">
                        <div class="dynamic-item-header">
                            <h4 class="dynamic-item-title">
                                <i class="bi bi-person-circle"></i>
                                Representante #<span class="item-number">${representanteIndex + 1}</span>
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
                                    <span class="required-star">*</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-person input-icon-left"></i>
                                    <input type="text" name="representantes[${representanteIndex}][nome_completo]"
                                        class="form-input-new"
                                        placeholder="Nome completo do representante"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="field-label-new">
                                    <span>CPF</span>
                                    <span class="required-star">*</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-card-text input-icon-left"></i>
                                    <input type="text" name="representantes[${representanteIndex}][cpf]"
                                        class="form-input-new cpf-mask validate-cpf"
                                        placeholder="000.000.000-00"
                                        required>
                                </div>
                                <span class="cpf-error field-error-message" style="display: none;">
                                    <i class="bi bi-exclamation-circle"></i>
                                    CPF inválido
                                </span>
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
                    </div>
                `;

                $('#representantesContainer').append(template);
                aplicarMascaras();
                updateItemNumbers($('#representantesContainer'));
                representanteIndex++;
            });

            window.removeRepresentante = function(btn) {
                const $item = $(btn).closest('.dynamic-item');
                const numero = $item.find('.item-number').text();
                const representanteId = $item.find('input[name*="[id]"]').val();

                if (!representanteId) {
                    $item.fadeOut(300, function() {
                        $(this).remove();
                        checkEmptyRepresentantes();
                        updateItemNumbers($('#representantesContainer'));
                    });
                    toastr.info('Representante removido');
                    return;
                }

                const userId = '{{ isset($user) ? $user->id : '' }}';
                const url = routes.destroyRepresentante
                    .replace(':user', userId)
                    .replace(':representante', representanteId);

                Swal.fire({
                    title: 'Remover Representante?',
                    html: `
                        <div style="text-align: left;">
                            <p style="margin-bottom: 1.25rem; font-size: 1.05rem; color: var(--text-color); line-height: 1.6;">
                                Deseja realmente remover o <strong>Representante #${numero}</strong>?
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
                        return fetch(url, {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                                        'content'),
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Erro ao remover representante');
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
                            updateItemNumbers($('#representantesContainer'));
                        });

                        Swal.fire({
                            icon: 'success',
                            title: 'Removido!',
                            text: result.value.message || 'Representante removido com sucesso!',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                });
            };

            function checkEmptyRepresentantes() {
                const $container = $('#representantesContainer');
                const totalItems = $container.find('.dynamic-item').length;

                if (totalItems === 0) {
                    $('#emptyRepresentantes').fadeIn(300);
                } else {
                    $('#emptyRepresentantes').hide();
                }
            }

            $('#addDadosBancarios').on('click', function() {
                $('#emptyDadosBancarios').hide();

                const template = `
                    <div class="dynamic-item" data-index="${bancoIndex}">
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
                                        placeholder="000.000.000-00"
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
                    </div>
                `;

                $('#dadosBancariosContainer').append(template);
                aplicarMascaras();
                updateItemNumbers($('#dadosBancariosContainer'));
                bancoIndex++;
            });

            window.removeDadosBancarios = function(btn) {
                const $item = $(btn).closest('.dynamic-item');
                const numero = $item.find('.item-number').text();
                const dadosBancariosId = $item.find('input[name*="[id]"]').val();

                if (!dadosBancariosId) {
                    $item.fadeOut(300, function() {
                        $(this).remove();
                        checkEmptyDadosBancarios();
                        updateItemNumbers($('#dadosBancariosContainer'));
                    });
                    toastr.info('Dados bancários removidos');
                    return;
                }

                const userId = '{{ isset($user) ? $user->id : '' }}';
                const url = routes.destroyDadosBancarios
                    .replace(':user', userId)
                    .replace(':dadosBancarios', dadosBancariosId);

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
                        return fetch(url, {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                                        'content'),
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
                            updateItemNumbers($('#dadosBancariosContainer'));
                        });

                        Swal.fire({
                            icon: 'success',
                            title: 'Removido!',
                            text: result.value.message ||
                                'Dados bancários removidos com sucesso!',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                });
            };

            function checkEmptyDadosBancarios() {
                const $container = $('#dadosBancariosContainer');
                const totalItems = $container.find('.dynamic-item').length;

                if (totalItems === 0) {
                    $('#emptyDadosBancarios').fadeIn(300);
                } else {
                    $('#emptyDadosBancarios').hide();
                }
            }

            function updateItemNumbers($container) {
                $container.find('.dynamic-item').each(function(index) {
                    $(this).find('.item-number').text(index + 1);
                });
            }

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

            $('#userForm').on('submit', function(e) {
                const selectedRole = $('input[name="role"]:checked').val();

                if (selectedRole === 'fornecedor') {
                    const currentTab = $('.tab-content-area.active').attr('id').replace('tab-', '');

                    if (currentTab === 'basico') {
                        if (!validateBasicoTab()) {
                            e.preventDefault();
                            return false;
                        }
                    }

                    if (!validateComplementarTab()) {
                        e.preventDefault();
                        switchTab('complementar');
                        return false;
                    }
                } else {
                    if (!validateBasicoTab()) {
                        e.preventDefault();
                        return false;
                    }
                }

                let hasError = false;

                $('.validate-cpf').each(function() {
                    const cpf = $(this).val();
                    if (cpf && !validarCPF(cpf)) {
                        hasError = true;
                        $(this).addClass('is-invalid');
                        $(this).closest('.col-md-6, .col-md-12').find('.cpf-error').show();
                    }
                });

                $('.cpf-cnpj-mask').each(function() {
                    const valor = $(this).val();
                    const valorLimpo = valor.replace(/[^\d]/g, '');

                    if (valor && $(this).prop('required')) {
                        let isValid = false;

                        if (valorLimpo.length === 11) {
                            isValid = validarCPF(valor);
                        } else if (valorLimpo.length === 14) {
                            isValid = validarCNPJ(valor);
                        }

                        if (!isValid) {
                            hasError = true;
                            $(this).addClass('is-invalid');
                        }
                    }
                });

                if (hasError) {
                    e.preventDefault();

                    $('#submitBtn').prop('disabled', false).html(
                        '<i class="bi bi-check-circle"></i> <span>{{ isset($user) ? 'Atualizar Usuário' : 'Salvar Usuário' }}</span>'
                    );
                    $('#submitBtnSimple').prop('disabled', false).html(
                        '<i class="bi bi-check-circle"></i> <span>{{ isset($user) ? 'Atualizar Usuário' : 'Salvar Usuário' }}</span>'
                    );

                    toastr.error('Por favor, corrija os documentos inválidos antes de continuar.',
                        'Erro de Validação');

                    $('html, body').animate({
                        scrollTop: $('.is-invalid').first().offset().top - 100
                    }, 500);

                    return false;
                }

                $('#submitBtn').prop('disabled', true).html(
                    '<i class="bi bi-hourglass-split"></i> <span>Salvando...</span>');
                $('#submitBtnSimple').prop('disabled', true).html(
                    '<i class="bi bi-hourglass-split"></i> <span>Salvando...</span>');
            });

            @if (session('success'))
                toastr.success('{{ session('success') }}');
            @endif

            @if (session('error'))
                toastr.error('{{ session('error') }}');
            @endif
        });
    </script>
@endpush

@push('styles')
    <style>
        .page-header {
            margin-bottom: 1.5rem;
        }

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
            padding: 1.5rem;
            margin-bottom: 1.5rem;
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
            min-height: 100px;
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

        .form-input-new.is-valid,
        .form-select-new.is-valid,
        .form-textarea-new.is-valid {
            border-color: var(--success-color);
        }

        .form-input-new.is-valid:focus,
        .form-select-new.is-valid:focus,
        .form-textarea-new.is-valid:focus {
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.08);
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
            text-decoration: none;
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

        .dynamic-items-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
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
            margin-bottom: 1rem;
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

        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
        }

        .role-input {
            display: none;
        }

        .role-option {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem;
            background: var(--bg-body);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .role-option:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 94, 130, 0.1);
        }

        .role-input:checked+.role-option {
            border-color: var(--primary-color);
            background: rgba(0, 94, 130, 0.05);
        }

        .role-icon-circle {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .role-details {
            flex: 1;
        }

        .role-name {
            display: block;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }

        .role-description {
            display: block;
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .role-selector {
            color: var(--border-color);
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .role-input:checked+.role-option .role-selector {
            color: var(--primary-color);
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

        .swal-confirm-btn i,
        .swal-cancel-btn i {
            font-size: 1.125rem !important;
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

            .swal2-popup {
                padding: 1.25rem !important;
            }

            .swal2-title {
                font-size: 1.375rem !important;
                margin-bottom: 1rem !important;
            }

            .swal2-actions {
                flex-direction: column !important;
                width: 100% !important;
                gap: 0.75rem !important;
            }

            .swal-confirm-btn,
            .swal-cancel-btn {
                width: 100% !important;
                padding: 0.875rem 1.5rem !important;
            }
        }
    </style>
@endpush