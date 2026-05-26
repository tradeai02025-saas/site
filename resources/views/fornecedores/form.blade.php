{{-- resources/views/fornecedores/form.blade.php --}}

@extends('layouts.app')

@section('title', isset($fornecedor) ? 'Editar Fornecedor' : 'Novo Fornecedor')

@section('content')
    <div class="container-fluid">
        <div class="page-header">
            <div class="page-header-content">
                <div class="page-title-wrapper">
                    <a href="{{ route('fornecedores.index') }}" class="page-back-btn">
                        <i class="bi bi-arrow-left-circle"></i>
                    </a>
                    <div class="page-icon">
                        <i class="bi bi-building-fill"></i>
                    </div>
                    <div>
                        <h1 class="page-title">{{ isset($fornecedor) ? 'Editar Fornecedor' : 'Novo Fornecedor' }}</h1>
                        <p class="page-subtitle">
                            {{ isset($fornecedor) ? 'Atualize as informações de ' . $fornecedor->razao_social : 'Cadastre um novo fornecedor no sistema' }}
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
            <button type="button" class="tab-button active" data-tab="empresa">
                <i class="bi bi-building-fill"></i>
                <span>Dados da Empresa</span>
            </button>
            <button type="button" class="tab-button" data-tab="endereco">
                <i class="bi bi-geo-alt-fill"></i>
                <span>Endereço</span>
            </button>
            @if (isset($fornecedor))
                <button type="button" class="tab-button" data-tab="representantes">
                    <i class="bi bi-telephone-fill"></i>
                    <span>Contatos</span>
                    <span class="tab-optional">Opcional</span>
                </button>
                <button type="button" class="tab-button" data-tab="bancario">
                    <i class="bi bi-bank"></i>
                    <span>Dados Bancários</span>
                    <span class="tab-optional">Opcional</span>
                </button>
            @endif
        </div>

        <form action="{{ isset($fornecedor) ? route('fornecedores.update', $fornecedor) : route('fornecedores.store') }}"
            method="POST" id="fornecedorForm" novalidate>
            @csrf
            @if (isset($fornecedor))
                @method('PUT')
            @endif

            <div class="tab-content-area active" id="tab-empresa">
                <div class="content-card">
                    <div class="card-header-section">
                        <h2 class="card-title">
                            <i class="bi bi-building"></i>
                            Informações Básicas
                        </h2>
                        <p class="card-subtitle">Dados essenciais para cadastro do fornecedor</p>
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
                                        value="{{ old('razao_social', $fornecedor->razao_social ?? '') }}" required
                                        autofocus>
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
                                        value="{{ old('cnpj', isset($fornecedor) && $fornecedor->cnpj ? substr($fornecedor->cnpj, 0, 2) . '.' . substr($fornecedor->cnpj, 2, 3) . '.' . substr($fornecedor->cnpj, 5, 3) . '/' . substr($fornecedor->cnpj, 8, 4) . '-' . substr($fornecedor->cnpj, 12, 2) : '') }}"
                                        required>
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
                                        placeholder="Nome comercial da empresa"
                                        value="{{ old('nome_fantasia', $fornecedor->nome_fantasia ?? '') }}" required>
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
                                    <span>Email de Contato</span>
                                    <span class="required-star">*</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-envelope-at input-icon-left"></i>
                                    <input type="email" name="email_contato"
                                        class="form-input-new @error('email_contato') is-invalid @enderror"
                                        placeholder="contato@empresa.com"
                                        value="{{ old('email_contato', $fornecedor->email_contato ?? '') }}" required>
                                </div>
                                @if (!isset($fornecedor))
                                    <span class="field-help-text">
                                        <i class="bi bi-info-circle"></i>
                                        Este email será usado para criar o login do fornecedor
                                    </span>
                                @endif
                                @error('email_contato')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                @if (isset($fornecedor))
                    <div class="content-card">
                        <div class="card-header-section">
                            <h2 class="card-title">
                                <i class="bi bi-info-circle"></i>
                                Informações Complementares
                            </h2>
                            <p class="card-subtitle">Dados adicionais da empresa</p>
                        </div>

                        <div class="card-content">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="field-label-new">
                                        <span>Nome Fantasia</span>
                                        <span class="required-star">*</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="bi bi-shop input-icon-left"></i>
                                        <input type="text" name="nome_fantasia" id="nome_fantasia"
                                            class="form-input-new @error('nome_fantasia') is-invalid @enderror"
                                            placeholder="Nome comercial"
                                            value="{{ old('nome_fantasia', $fornecedor->nome_fantasia ?? '') }}" required>
                                    </div>
                                    @error('nome_fantasia')
                                        <span class="field-error-message">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="field-label-new">
                                        <span>Nome do Veículo</span>
                                        <span class="required-star">*</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="bi bi-collection-play input-icon-left"></i>
                                        <input type="text" name="nome_veiculo" id=""
                                            class="form-input-new @error('nome_veiculo') is-invalid @enderror"
                                            placeholder="Nome do veículo/produto"
                                            value="{{ old('nome_veiculo', $fornecedor->nome_veiculo ?? '') }}" required>
                                    </div>
                                    @error('nome_veiculo')
                                        <span class="field-error-message">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-6 d-none">
                                    <label class="field-label-new">
                                        <span>Praça</span>
                                        <span class="optional-badge">Opcional</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="bi bi-geo-alt input-icon-left"></i>
                                        <input type="text" name="praca"
                                            class="form-input-new @error('praca') is-invalid @enderror"
                                            placeholder="Região de atuação"
                                            value="{{ old('praca', $fornecedor->praca ?? '') }}">
                                    </div>
                                    @error('praca')
                                        <span class="field-error-message">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-6 d-none">
                                    <label class="field-label-new">
                                        <span>Website</span>
                                        <span class="optional-badge">Opcional</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="bi bi-globe input-icon-left"></i>
                                        <input type="url" name="site"
                                            class="form-input-new @error('site') is-invalid @enderror"
                                            placeholder="https://www.empresa.com"
                                            value="{{ old('site', $fornecedor->site ?? '') }}">
                                    </div>
                                    @error('site')
                                        <span class="field-error-message">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="field-label-new">
                                        <span>Telefone</span>
                                        <span class="required-star">*</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="bi bi-telephone input-icon-left"></i>
                                        <input type="text" name="telefone" id="telefone"
                                            class="form-input-new telefone-mask @error('telefone') is-invalid @enderror"
                                            placeholder="(00) 00000-0000"
                                            value="{{ old('telefone', $fornecedor->telefone ?? '') }}" required>
                                    </div>
                                    @error('telefone')
                                        <span class="field-error-message">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-md-6 d-none">
                                    <label class="field-label-new">
                                        <span>Fax</span>
                                        <span class="optional-badge">Opcional</span>
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="bi bi-printer input-icon-left"></i>
                                        <input type="text" name="fax"
                                            class="form-input-new telefone-mask @error('fax') is-invalid @enderror"
                                            placeholder="(00) 0000-0000"
                                            value="{{ old('fax', $fornecedor->fax ?? '') }}">
                                    </div>
                                    @error('fax')
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
                                            placeholder="Informações adicionais sobre o fornecedor">{{ old('observacoes', $fornecedor->observacoes ?? '') }}</textarea>
                                    </div>
                                    @error('observacoes')
                                        <span class="field-error-message">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="content-card">
                    <div class="card-content">
                        <div class="form-footer">
                            <a href="{{ route('fornecedores.index') }}" class="btn-secondary">
                                <i class="bi bi-x-circle"></i>
                                Cancelar
                            </a>
                            <button type="button" class="btn-primary" onclick="switchTab('endereco')">
                                <span>Continuar</span>
                                <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-content-area" id="tab-endereco">
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
                                    <span class="required-star">*</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-mailbox input-icon-left"></i>
                                    <input type="text" name="cep" id="cep"
                                        class="form-input-new cep-mask @error('cep') is-invalid @enderror"
                                        placeholder="00000-000"
                                        value="{{ old('cep', isset($fornecedor) && $fornecedor->enderecos->first() ? $fornecedor->enderecos->first()->cep : '') }}"
                                        required>
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
                                        class="form-select-new @error('estado') is-invalid @enderror" required>
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
                                                isset($fornecedor) && $fornecedor->enderecos->first()
                                                    ? $fornecedor->enderecos->first()->estado
                                                    : '',
                                            );
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
                                    <span class="required-star">*</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-pin-map input-icon-left"></i>
                                    <input type="text" name="cidade" id="cidade"
                                        class="form-input-new @error('cidade') is-invalid @enderror"
                                        placeholder="Nome da cidade"
                                        value="{{ old('cidade', isset($fornecedor) && $fornecedor->enderecos->first() ? $fornecedor->enderecos->first()->cidade : '') }}"
                                        required>
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
                                    <i class="bi bi-signpost-split input-icon-left"></i>
                                    <input type="text" name="bairro" id="bairro"
                                        class="form-input-new @error('bairro') is-invalid @enderror"
                                        placeholder="Nome do bairro"
                                        value="{{ old('bairro', isset($fornecedor) && $fornecedor->enderecos->first() ? $fornecedor->enderecos->first()->bairro : '') }}"
                                        required>
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
                                    <i class="bi bi-signpost input-icon-left"></i>
                                    <input type="text" name="logradouro" id="logradouro"
                                        class="form-input-new @error('logradouro') is-invalid @enderror"
                                        placeholder="Rua, Avenida, Travessa..."
                                        value="{{ old('logradouro', isset($fornecedor) && $fornecedor->enderecos->first() ? $fornecedor->enderecos->first()->logradouro : '') }}"
                                        required>
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
                                        value="{{ old('numero', isset($fornecedor) && $fornecedor->enderecos->first() ? $fornecedor->enderecos->first()->numero : '') }}"
                                        required>
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
                                        value="{{ old('complemento', isset($fornecedor) && $fornecedor->enderecos->first() ? $fornecedor->enderecos->first()->complemento : '') }}">
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
                            @if (!isset($fornecedor))
                                <button type="submit" class="btn-primary" id="submitBtn">
                                    <i class="bi bi-check-circle"></i>
                                    <span>Salvar Fornecedor</span>
                                </button>
                            @else
                                <button type="button" class="btn-primary" onclick="switchTab('representantes')">
                                    <span>Continuar</span>
                                    <i class="bi bi-arrow-right"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if (isset($fornecedor))
                <div class="tab-content-area" id="tab-representantes">
                    <div class="content-card">
                        <div class="card-header-section">
                            <h2 class="card-title">
                                <i class="bi bi-telephone-fill"></i>
                                Contatos da Empresa
                            </h2>
                            <p class="card-subtitle">Pessoas de contato para comunicação sobre PIs. Não acessam o sistema.</p>
                        </div>

                        <div class="card-content">
                            <div class="dynamic-items-container" id="representantesContainer">
                                @if ($fornecedor->representantes->count() > 0)
                                    @foreach ($fornecedor->representantes as $index => $rep)
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
                                                        <span class="required-star">*</span>
                                                    </label>
                                                    <div class="input-with-icon">
                                                        <i class="bi bi-person input-icon-left"></i>
                                                        <input type="text"
                                                            name="representantes[{{ $index }}][nome_completo]"
                                                            class="form-input-new"
                                                            value="{{ old('representantes.' . $index . '.nome_completo', $rep->nome_completo) }}"
                                                            placeholder="Nome completo do contato" required>
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
                                            começar.
                                        </p>
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
                                <button type="button" class="btn-primary" onclick="switchTab('bancario')">
                                    <span>Continuar</span>
                                    <i class="bi bi-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-content-area" id="tab-bancario">
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
                                @if ($fornecedor->dadosBancarios->count() > 0)
                                    @foreach ($fornecedor->dadosBancarios as $index => $banco)
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
                                <button type="button" class="btn-secondary" onclick="switchTab('representantes')">
                                    <i class="bi bi-arrow-left-circle"></i>
                                    Voltar
                                </button>
                                <button type="submit" class="btn-primary" id="submitBtn">
                                    <i class="bi bi-check-circle"></i>
                                    <span>{{ isset($fornecedor) ? 'Atualizar Fornecedor' : 'Salvar Fornecedor' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        const routes = {
            destroyRepresentante: "{{ route('fornecedores.destroy_representante', ['fornecedor' => isset($fornecedor) ? $fornecedor->id : ':fornecedor', 'representante' => ':representante']) }}",
            destroyDadosBancarios: "{{ route('fornecedores.destroy_dados_bancarios', ['fornecedor' => isset($fornecedor) ? $fornecedor->id : ':fornecedor', 'dadosBancarios' => ':dadosBancarios']) }}"
        };

        $(document).ready(function() {
            let representanteIndex = {{ isset($fornecedor) ? $fornecedor->representantes->count() : 0 }};
            let bancoIndex = {{ isset($fornecedor) ? $fornecedor->dadosBancarios->count() : 0 }};

            class InputMaskManager {
                constructor() {
                    this.init();
                }

                init() {
                    if (typeof $.fn.mask !== 'undefined') {
                        this.aplicarMascaras();
                    }
                }

                aplicarMascaras() {
                    $('.telefone-mask').mask('(00) 00000-0000');
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
                    $('.cpf-mask').mask('000.000.000-00');
                    $('.cep-mask').mask('00000-000');

                    $('.cpf-cnpj-mask').each(function() {
                        const $input = $(this);

                        let valorInicial = $input.val().replace(/\D/g, '');
                        if (valorInicial.length > 11) {
                            $input.mask('00.000.000/0000-00');
                        } else {
                            $input.mask('000.000.000-00');
                        }

                        $input.on('input', function() {
                            let val = $(this).val().replace(/\D/g, '');
                            if (val.length <= 11) {
                                $(this).mask('000.000.000-00');
                            } else {
                                $(this).mask('00.000.000/0000-00');
                            }
                        });

                        $input.on('paste', function(e) {
                            e.preventDefault();

                            const $el = $(this);

                            let pastedData = '';
                            if (e.originalEvent && e.originalEvent.clipboardData) {
                                pastedData = e.originalEvent.clipboardData.getData('text');
                            } else if (window.clipboardData) {
                                pastedData = window.clipboardData.getData('Text');
                            }

                            let valor = pastedData.replace(/\D/g, '');

                            if (valor.length === 0) return;

                            $el.unmask();

                            let valorFormatado;
                            if (valor.length <= 11) {
                                valorFormatado = valor.replace(
                                    /^(\d{0,3})(\d{0,3})(\d{0,3})(\d{0,2}).*/,
                                    function(_, p1, p2, p3, p4) {
                                        let r = p1;
                                        if (p2) r += '.' + p2;
                                        if (p3) r += '.' + p3;
                                        if (p4) r += '-' + p4;
                                        return r;
                                    });
                                $el.mask('000.000.000-00');
                            } else {
                                valorFormatado = valor.replace(
                                    /^(\d{0,2})(\d{0,3})(\d{0,3})(\d{0,4})(\d{0,2}).*/,
                                    function(_, p1, p2, p3, p4, p5) {
                                        let r = p1;
                                        if (p2) r += '.' + p2;
                                        if (p3) r += '.' + p3;
                                        if (p4) r += '/' + p4;
                                        if (p5) r += '-' + p5;
                                        return r;
                                    });
                                $el.mask('00.000.000/0000-00');
                            }

                            $el.val(valorFormatado);
                        });
                    });
                }
            }

            class CPFValidator {
                constructor() {
                    this.init();
                }

                init() {
                    $(document).on('blur', '.cpf-mask', (e) => {
                        this.validateCPF($(e.currentTarget));
                    });

                    $(document).on('input', '.cpf-mask', (e) => {
                        const $input = $(e.currentTarget);
                        if ($input.hasClass('is-invalid')) {
                            this.validateCPF($input);
                        }
                    });
                }

                validateCPF($input) {
                    const cpf = $input.val().replace(/\D/g, '');

                    if (cpf.length === 0) {
                        this.clearValidation($input);
                        return true;
                    }

                    if (cpf.length !== 11) {
                        this.setInvalid($input, 'CPF deve ter 11 dígitos');
                        return false;
                    }

                    if (this.isRepeatedDigits(cpf)) {
                        this.setInvalid($input, 'CPF inválido');
                        return false;
                    }

                    if (!this.checkDigits(cpf)) {
                        this.setInvalid($input, 'CPF inválido');
                        return false;
                    }

                    this.setValid($input);
                    return true;
                }

                isRepeatedDigits(cpf) {
                    const repeatedPatterns = [
                        '00000000000', '11111111111', '22222222222', '33333333333',
                        '44444444444', '55555555555', '66666666666', '77777777777',
                        '88888888888', '99999999999'
                    ];
                    return repeatedPatterns.includes(cpf);
                }

                checkDigits(cpf) {
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

                setInvalid($input, message) {
                    $input.addClass('is-invalid');

                    let $errorMsg = $input.closest('.input-with-icon').siblings('.cpf-error-message');

                    if ($errorMsg.length === 0) {
                        $errorMsg = $(
                            '<span class="field-error-message cpf-error-message"><i class="bi bi-exclamation-circle"></i></span>'
                        );
                        $input.closest('.input-with-icon').after($errorMsg);
                    }

                    $errorMsg.find('span').remove();
                    $errorMsg.append(`<span>${message}</span>`);
                    $errorMsg.show();
                }

                setValid($input) {
                    $input.removeClass('is-invalid').addClass('is-valid');
                    const $errorMsg = $input.closest('.input-with-icon').siblings('.cpf-error-message');
                    $errorMsg.hide();
                }

                clearValidation($input) {
                    $input.removeClass('is-invalid is-valid');
                    const $errorMsg = $input.closest('.input-with-icon').siblings('.cpf-error-message');
                    $errorMsg.hide();
                }
            }

            class CNPJValidator {
                constructor() {
                    this.init();
                }

                init() {
                    $('#cnpj').on('blur', () => {
                        this.validateCNPJ($('#cnpj'));
                    });

                    $('#cnpj').on('input', () => {
                        const $input = $('#cnpj');
                        if ($input.hasClass('is-invalid')) {
                            this.validateCNPJ($input);
                        }
                    });
                }

                validateCNPJ($input) {
                    const cnpj = $input.val().replace(/\D/g, '');

                    if (cnpj.length === 0) {
                        this.clearValidation($input);
                        return true;
                    }

                    if (cnpj.length !== 14) {
                        this.setInvalid($input, 'CNPJ deve ter 14 dígitos');
                        return false;
                    }

                    if (this.isRepeatedDigits(cnpj)) {
                        this.setInvalid($input, 'CNPJ inválido');
                        return false;
                    }

                    if (!this.checkDigits(cnpj)) {
                        this.setInvalid($input, 'CNPJ inválido');
                        return false;
                    }

                    this.setValid($input);
                    return true;
                }

                isRepeatedDigits(cnpj) {
                    const repeatedPatterns = [
                        '00000000000000', '11111111111111', '22222222222222', '33333333333333',
                        '44444444444444', '55555555555555', '66666666666666', '77777777777777',
                        '88888888888888', '99999999999999'
                    ];
                    return repeatedPatterns.includes(cnpj);
                }

                checkDigits(cnpj) {
                    let size = cnpj.length - 2;
                    let numbers = cnpj.substring(0, size);
                    let digits = cnpj.substring(size);
                    let sum = 0;
                    let pos = size - 7;

                    for (let i = size; i >= 1; i--) {
                        sum += numbers.charAt(size - i) * pos--;
                        if (pos < 2) pos = 9;
                    }

                    let result = sum % 11 < 2 ? 0 : 11 - sum % 11;
                    if (result != digits.charAt(0)) return false;

                    size = size + 1;
                    numbers = cnpj.substring(0, size);
                    sum = 0;
                    pos = size - 7;

                    for (let i = size; i >= 1; i--) {
                        sum += numbers.charAt(size - i) * pos--;
                        if (pos < 2) pos = 9;
                    }

                    result = sum % 11 < 2 ? 0 : 11 - sum % 11;
                    if (result != digits.charAt(1)) return false;

                    return true;
                }

                setInvalid($input, message) {
                    $input.addClass('is-invalid');

                    let $errorMsg = $input.closest('.input-with-icon').siblings('.cnpj-error-message');

                    if ($errorMsg.length === 0) {
                        $errorMsg = $(
                            '<span class="field-error-message cnpj-error-message"><i class="bi bi-exclamation-circle"></i></span>'
                        );
                        $input.closest('.input-with-icon').after($errorMsg);
                    }

                    $errorMsg.find('span').remove();
                    $errorMsg.append(`<span>${message}</span>`);
                    $errorMsg.show();
                }

                setValid($input) {
                    $input.removeClass('is-invalid').addClass('is-valid');
                    const $errorMsg = $input.closest('.input-with-icon').siblings('.cnpj-error-message');
                    $errorMsg.hide();
                }

                clearValidation($input) {
                    $input.removeClass('is-invalid is-valid');
                    const $errorMsg = $input.closest('.input-with-icon').siblings('.cnpj-error-message');
                    $errorMsg.hide();
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

                    $('#cep').on('keypress', function(e) {
                        if (e.which === 13) {
                            e.preventDefault();
                            $(this).blur();
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

                formatCEP(cep) {
                    return cep.replace(/(\d{5})(\d{3})/, '$1-$2');
                }
            }

            function validateTab(tabName) {
                let isValid = true;
                let errors = [];
                const $tab = $(`#tab-${tabName}`);

                $tab.find('input[required], select[required], textarea[required]').each(function() {
                    const $field = $(this);
                    const value = $field.val();
                    const fieldName = $field.closest('.col-md-4, .col-md-6, .col-md-12').find(
                        '.field-label-new span:first').text();

                    if (!value || value.trim() === '') {
                        isValid = false;
                        $field.addClass('is-invalid');
                        errors.push(fieldName);
                    } else {
                        $field.removeClass('is-invalid');
                    }
                });

                if (!isValid) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Campos obrigatórios',
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

                    const $firstInvalid = $tab.find('.is-invalid:first');
                    if ($firstInvalid.length) {
                        $('html, body').animate({
                            scrollTop: $firstInvalid.offset().top - 100
                        }, 300);
                        setTimeout(() => $firstInvalid.focus(), 400);
                    }
                }

                return isValid;
            }

            window.switchTab = function(tabName, skipValidation = false) {
                if (!skipValidation) {
                    const currentTab = $('.tab-content-area.active').attr('id').replace('tab-', '');

                    if (currentTab === 'empresa' || currentTab === 'endereco') {
                        if (!validateTab(currentTab)) {
                            return;
                        }
                    }
                }

                $('.tab-button').removeClass('active');
                $(`.tab-button[data-tab="${tabName}"]`).addClass('active');
                $('.tab-content-area').removeClass('active');
                $(`#tab-${tabName}`).addClass('active');
                $('html, body').animate({
                    scrollTop: 0
                }, 300);
            };

            $('.tab-button').on('click', function() {
                const tab = $(this).data('tab');
                switchTab(tab);
            });

            @if (isset($fornecedor))
                $('#addRepresentante').on('click', function() {
                    $('#emptyRepresentantes').hide();

                    const template = `
                <div class="dynamic-item" data-index="${representanteIndex}">
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
                                <span class="required-star">*</span>
                            </label>
                            <div class="input-with-icon">
                                <i class="bi bi-person input-icon-left"></i>
                                <input type="text" name="representantes[${representanteIndex}][nome_completo]"
                                    class="form-input-new"
                                    placeholder="Nome completo do contato"
                                    required>
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
                </div>
            `;

                    $('#representantesContainer').append(template);
                    inputMaskManager.aplicarMascaras();
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
                        toastr.info('Contato removido');
                        return;
                    }

                    const fornecedorId = '{{ $fornecedor->id }}';
                    const url = routes.destroyRepresentante
                        .replace(':fornecedor', fornecedorId)
                        .replace(':representante', representanteId);

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
                                updateItemNumbers($('#representantesContainer'));
                            });

                            Swal.fire({
                                icon: 'success',
                                title: 'Removido!',
                                text: result.value.message ||
                                    'Contato removido com sucesso!',
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
                    inputMaskManager.aplicarMascaras();
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
                        toastr.info('Dados bancários removidos (não estavam salvos)');
                        return;
                    }

                    const fornecedorId = '{{ $fornecedor->id }}';
                    const url = routes.destroyDadosBancarios
                        .replace(':fornecedor', fornecedorId)
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
            @endif

            function updateItemNumbers($container) {
                $container.find('.dynamic-item').each(function(index) {
                    $(this).find('.item-number').text(index + 1);
                });
            }

            $('#fornecedorForm').on('submit', function(e) {
                const tabsParaValidar = ['empresa', 'endereco'];
                for (const tab of tabsParaValidar) {
                    if ($(`#tab-${tab}`).length && !validateTab(tab)) {
                        e.preventDefault();
                        switchTab(tab, true);
                        return;
                    }
                }
                $('#submitBtn').prop('disabled', true).html(
                    '<i class="bi bi-hourglass-split"></i> <span>Salvando...</span>');
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

            const inputMaskManager = new InputMaskManager();
            const cpfValidator = new CPFValidator();
            const cnpjValidator = new CNPJValidator();
            const cepSearchManager = new CEPSearchManager();
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