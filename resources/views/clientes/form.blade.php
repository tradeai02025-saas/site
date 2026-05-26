{{-- resources/views/clientes/form.blade.php --}}
@extends('layouts.app')

@section('title', isset($cliente) ? 'Editar Cliente' : 'Novo Cliente')

@section('content')
    <div class="container-fluid">
        <div class="page-header">
            <div class="page-header-content">
                <div class="page-title-wrapper">
                    <a href="{{ route('clientes.index') }}" class="page-back-btn">
                        <i class="bi bi-arrow-left-circle"></i>
                    </a>
                    <div class="page-icon">
                        <i class="bi bi-briefcase-fill"></i>
                    </div>
                    <div>
                        <h1 class="page-title">{{ isset($cliente) ? 'Editar Cliente' : 'Novo Cliente' }}</h1>
                        <p class="page-subtitle">
                            {{ isset($cliente) ? 'Atualize as informações de ' . $cliente->razao_social : 'Cadastre um novo cliente no sistema' }}
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
                <i class="bi bi-briefcase-fill"></i>
                <span>Dados da Empresa</span>
            </button>
            <button type="button" class="tab-button" data-tab="endereco">
                <i class="bi bi-geo-alt-fill"></i>
                <span>Endereço</span>
                <span class="tab-optional">Opcional</span>
            </button>
            <button type="button" class="tab-button" data-tab="documentos">
                <i class="bi bi-file-earmark-text-fill"></i>
                <span>Documentos Requeridos</span>
                <span class="tab-required">Obrigatório</span>
            </button>
            <button type="button" class="tab-button" data-tab="operacionais">
                <i class="bi bi-people-fill"></i>
                <span>Responsável</span>
                <span class="tab-required">Obrigatório</span>
            </button>
        </div>

        <form action="{{ isset($cliente) ? route('clientes.update', $cliente) : route('clientes.store') }}" method="POST"
            id="clienteForm">
            @csrf
            @if (isset($cliente))
                @method('PUT')
            @endif

            <div class="tab-content-area active" id="tab-empresa">
                <div class="content-card">
                    <div class="card-header-section">
                        <h2 class="card-title">
                            <i class="bi bi-building"></i>
                            Informações Corporativas
                        </h2>
                        <p class="card-subtitle">Dados legais e identificação da empresa</p>
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
                                        value="{{ old('razao_social', $cliente->razao_social ?? '') }}" required autofocus>
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
                                        placeholder="00.000.000/0000-00" value="{{ old('cnpj', $cliente->cnpj ?? '') }}"
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
                                    <span class="optional-badge">Opcional</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-shop input-icon-left"></i>
                                    <input type="text" name="nome_fantasia"
                                        class="form-input-new @error('nome_fantasia') is-invalid @enderror"
                                        placeholder="Nome comercial"
                                        value="{{ old('nome_fantasia', $cliente->nome_fantasia ?? '') }}">
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
                                    <span>Nome do Cliente</span>
                                    <span class="optional-badge">Opcional</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-person-badge input-icon-left"></i>
                                    <input type="text" name="nome_cliente"
                                        class="form-input-new @error('nome_cliente') is-invalid @enderror"
                                        placeholder="Nome do responsável"
                                        value="{{ old('nome_cliente', $cliente->nome_cliente ?? '') }}">
                                </div>
                                @error('nome_cliente')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="field-label-new">
                                    <span>Inscrição Estadual</span>
                                    <span class="optional-badge">Opcional</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-file-earmark-text input-icon-left"></i>
                                    <input type="text" name="inscricao_estadual"
                                        class="form-input-new @error('inscricao_estadual') is-invalid @enderror"
                                        placeholder="Número da IE"
                                        value="{{ old('inscricao_estadual', $cliente->inscricao_estadual ?? '') }}">
                                </div>
                                @error('inscricao_estadual')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="field-label-new">
                                    <span>Inscrição Municipal</span>
                                    <span class="optional-badge">Opcional</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-file-earmark-text input-icon-left"></i>
                                    <input type="text" name="inscricao_municipal"
                                        class="form-input-new @error('inscricao_municipal') is-invalid @enderror"
                                        placeholder="Número da IM"
                                        value="{{ old('inscricao_municipal', $cliente->inscricao_municipal ?? '') }}">
                                </div>
                                @error('inscricao_municipal')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <label class="field-label-new">
                                    <span>Praça</span>
                                    <span class="optional-badge">Opcional</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-geo-alt input-icon-left"></i>
                                    <input type="text" name="praca"
                                        class="form-input-new @error('praca') is-invalid @enderror"
                                        placeholder="Região de atuação"
                                        value="{{ old('praca', $cliente->praca ?? '') }}">
                                </div>
                                @error('praca')
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
                                        placeholder="Informações adicionais sobre o cliente">{{ old('observacoes', $cliente->observacoes ?? '') }}</textarea>
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

                <div class="content-card">
                    <div class="card-header-section">
                        <h2 class="card-title">
                            <i class="bi bi-telephone-fill"></i>
                            Dados de Contato
                        </h2>
                        <p class="card-subtitle">Informações para comunicação</p>
                    </div>

                    <div class="card-content">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="field-label-new">
                                    <span>Telefone</span>
                                    <span class="optional-badge">Opcional</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-telephone input-icon-left"></i>
                                    <input type="text" name="telefone"
                                        class="form-input-new telefone-mask @error('telefone') is-invalid @enderror"
                                        placeholder="(00) 00000-0000"
                                        value="{{ old('telefone', $cliente->telefone ?? '') }}">
                                </div>
                                @error('telefone')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="field-label-new">
                                    <span>Email de Contato</span>
                                    <span class="optional-badge">Opcional</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-envelope-at input-icon-left"></i>
                                    <input type="email" name="email_contato"
                                        class="form-input-new @error('email_contato') is-invalid @enderror"
                                        placeholder="contato@empresa.com"
                                        value="{{ old('email_contato', $cliente->email_contato ?? '') }}">
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
                                    <span>Website</span>
                                    <span class="optional-badge">Opcional</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-globe input-icon-left"></i>
                                    <input type="url" name="site"
                                        class="form-input-new @error('site') is-invalid @enderror"
                                        placeholder="https://www.empresa.com"
                                        value="{{ old('site', $cliente->site ?? '') }}">
                                </div>
                                @error('site')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-footer">
                            <a href="{{ route('clientes.index') }}" class="btn-secondary">
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
                        <p class="card-subtitle">Localização física da sede (opcional)</p>
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
                                        placeholder="00000-000"
                                        value="{{ old('cep', isset($cliente) && $cliente->enderecos->first() ? $cliente->enderecos->first()->cep : '') }}">
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
                                            $estadoAtual = old(
                                                'estado',
                                                isset($cliente) && $cliente->enderecos->first()
                                                    ? $cliente->enderecos->first()->estado
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
                                    <span class="optional-badge">Opcional</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-pin-map input-icon-left"></i>
                                    <input type="text" name="cidade" id="cidade"
                                        class="form-input-new @error('cidade') is-invalid @enderror"
                                        placeholder="Nome da cidade"
                                        value="{{ old('cidade', isset($cliente) && $cliente->enderecos->first() ? $cliente->enderecos->first()->cidade : '') }}">
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
                                        value="{{ old('bairro', isset($cliente) && $cliente->enderecos->first() ? $cliente->enderecos->first()->bairro : '') }}">
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
                                        value="{{ old('logradouro', isset($cliente) && $cliente->enderecos->first() ? $cliente->enderecos->first()->logradouro : '') }}">
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
                                        value="{{ old('numero', isset($cliente) && $cliente->enderecos->first() ? $cliente->enderecos->first()->numero : '') }}">
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
                                        value="{{ old('complemento', isset($cliente) && $cliente->enderecos->first() ? $cliente->enderecos->first()->complemento : '') }}">
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
                            <button type="button" class="btn-primary" onclick="switchTab('documentos')">
                                <span>Continuar</span>
                                <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-content-area" id="tab-documentos">
                <div class="content-card">
                    <div class="card-header-section">
                        <h2 class="card-title">
                            <i class="bi bi-file-earmark-text-fill"></i>
                            Documentos Requeridos para PIs
                        </h2>
                        <p class="card-subtitle">Selecione quais documentos são obrigatórios para os Processos de
                            Importação deste cliente</p>
                    </div>

                    <div class="card-content">
                        <div class="config-veiculo-section mb-4">
                            <div class="config-veiculo-option">
                                <div class="config-veiculo-header">
                                    <div class="config-veiculo-icon">
                                        <i class="bi bi-layers"></i>
                                    </div>
                                    <div class="config-veiculo-info">
                                        <span class="config-veiculo-label">Exigir Documentos por Tipo de Veículo</span>
                                        <span class="config-veiculo-desc">Quando ativado, os documentos serão definidos com
                                            base no tipo de veículo selecionado ao criar a PI (configurados em Tipos de
                                            Documentos), e não pelos documentos abaixo.</span>
                                    </div>
                                    <div class="toggle-switch-veiculo">
                                        <input type="hidden" name="exige_documento_por_veiculo" value="0">
                                        <input type="checkbox" name="exige_documento_por_veiculo"
                                            id="exige_documento_por_veiculo" class="toggle-input-veiculo" value="1"
                                            {{ old('exige_documento_por_veiculo', $cliente->exige_documento_por_veiculo ?? false) ? 'checked' : '' }}>
                                        <label for="exige_documento_por_veiculo" class="toggle-label-veiculo">
                                            <span class="toggle-slider-veiculo"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="documentosTradicionaisSection">
                            <div class="info-box-important" id="infoDocumentosObrigatorios">
                                <i class="bi bi-exclamation-circle-fill"></i>
                                <div>
                                    <strong>Campo Obrigatório:</strong> É necessário adicionar pelo menos um tipo de
                                    documento
                                    para este cliente.
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <div class="add-item-section">
                                        <label class="field-label-new">
                                            <span>Selecionar Tipo de Documento</span>
                                        </label>
                                        <div class="input-with-action">
                                            <div class="input-with-icon">
                                                <i class="bi bi-file-earmark-plus input-icon-left"></i>
                                                <select id="tipoDocumentoSelect" class="form-select-new">
                                                    <option value="">Escolha um tipo de documento para adicionar
                                                    </option>
                                                    @foreach ($tiposDocumentos as $tipoDoc)
                                                        <option value="{{ $tipoDoc->id }}"
                                                            data-nome="{{ $tipoDoc->nome }}"
                                                            data-descricao="{{ $tipoDoc->descricao }}"
                                                            data-validade="{{ $tipoDoc->validade_dias }}"
                                                            data-validar="{{ $tipoDoc->validar ? 'Sim' : 'Não' }}">
                                                            {{ $tipoDoc->nome }}
                                                            @if ($tipoDoc->validar)
                                                                @if ($tipoDoc->validade_dias === 0)
                                                                    (Reutilizável sempre)
                                                                @else
                                                                    (Reutilizável por {{ $tipoDoc->validade_dias }} dias)
                                                                @endif
                                                            @else
                                                                (Uso único por PI)
                                                            @endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <button type="button" class="btn-add-inline" id="adicionarTipoDocumento">
                                                <i class="bi bi-plus-circle"></i>
                                                <span>Adicionar</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="dynamic-items-container" id="tiposDocumentosContainer">
                                @if (isset($cliente) && $cliente->clienteDocumentosRequeridos->count() > 0)
                                    @foreach ($cliente->clienteDocumentosRequeridos as $docRequerido)
                                        <div class="dynamic-item" data-id="{{ $docRequerido->tipo_documento_id }}">
                                            <input type="hidden" name="tipos_documentos[]"
                                                value="{{ $docRequerido->tipo_documento_id }}"
                                                data-documento-requerido-id="{{ $docRequerido->id }}">

                                            <div class="dynamic-item-header">
                                                <h4 class="dynamic-item-title">
                                                    <i class="bi bi-file-earmark-text"></i>
                                                    {{ $docRequerido->tipoDocumento->nome }}
                                                </h4>
                                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                    <label class="opcional-toggle" title="Marcar como opcional">
                                                        <input type="checkbox" name="opcionais[]"
                                                            value="{{ $docRequerido->tipo_documento_id }}"
                                                            {{ $docRequerido->opcional ? 'checked' : '' }}>
                                                        <span class="opcional-toggle-label">Opcional</span>
                                                    </label>
                                                    <button type="button" class="btn-remove-item"
                                                        onclick="removeTipoDocumento(this)">
                                                        <i class="bi bi-trash"></i>
                                                        <span>Remover</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="dynamic-item-content">
                                                @if ($docRequerido->tipoDocumento->descricao)
                                                    <p class="item-description">
                                                        <i class="bi bi-info-circle"></i>
                                                        {{ $docRequerido->tipoDocumento->descricao }}
                                                    </p>
                                                @endif
                                                <div class="item-details">
                                                    @if ($docRequerido->tipoDocumento->validar)
                                                        @if ($docRequerido->tipoDocumento->validade_dias === 0)
                                                            <div class="detail-badge detail-badge-success">
                                                                <i class="bi bi-infinity"></i>
                                                                <span>Documento Permanente - Reutilizável em todas as PIs
                                                                    sem
                                                                    expiração</span>
                                                            </div>
                                                        @else
                                                            <div class="detail-badge detail-badge-info">
                                                                <i class="bi bi-calendar-check"></i>
                                                                <span>Válido por
                                                                    {{ $docRequerido->tipoDocumento->validade_dias }} dias
                                                                    -
                                                                    Reutilizável até expirar</span>
                                                            </div>
                                                        @endif
                                                        <div class="detail-badge detail-badge-primary">
                                                            <i class="bi bi-shield-check"></i>
                                                            <span>Sistema controla validade e notifica vencimentos</span>
                                                        </div>
                                                    @else
                                                        <div class="detail-badge detail-badge-warning">
                                                            <i class="bi bi-file-earmark"></i>
                                                            <span>Documento de Uso Único - Cada PI requer novo
                                                                documento</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="empty-state-dynamic" id="emptyTiposDocumentos">
                                        <i class="bi bi-file-earmark-text"></i>
                                        <p>Nenhum documento selecionado</p>
                                        <small>Adicione pelo menos um tipo de documento que será obrigatório para os
                                            Processos
                                            de Importação deste cliente</small>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="modo-veiculo-ativo-info" id="modoVeiculoAtivoInfo" style="display: none;">
                            <div class="info-box-success">
                                <i class="bi bi-check-circle-fill"></i>
                                <div>
                                    <strong>Modo por Tipo de Veículo Ativo</strong>
                                    <p class="mb-0">Os documentos serão determinados automaticamente com base no tipo de
                                        veículo selecionado ao criar cada PI. Configure os documentos por tipo de veículo na
                                        tela de <a href="{{ route('tipo_documentos.index') }}" target="_blank"
                                            class="alert-link">Tipos de Documentos</a>.</p>
                                </div>
                            </div>
                        </div>

                        <div class="form-footer">
                            <button type="button" class="btn-secondary" onclick="switchTab('endereco')">
                                <i class="bi bi-arrow-left-circle"></i>
                                Voltar
                            </button>
                            <button type="button" class="btn-primary" onclick="switchTab('operacionais')">
                                <span>Continuar</span>
                                <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-content-area" id="tab-operacionais">
                <div class="content-card">
                    <div class="card-header-section">
                        <h2 class="card-title">
                            <i class="bi bi-people-fill"></i>
                            Operacional Responsável
                        </h2>
                        <p class="card-subtitle">Defina o operacional responsável por gerenciar os Processos de Importação deste cliente</p>
                    </div>

                    <div class="card-content">
                        <div class="info-box-important">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <div>
                                <strong>Campo Obrigatório:</strong> É necessário selecionar um operacional
                                responsável por este cliente.
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="field-label-new">
                                    <span>Operacional Responsável</span>
                                    <span class="required-star">*</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="bi bi-person-badge input-icon-left"></i>
                                    @php
                                        $operacionalPrincipalAtual = null;
                                        if (isset($cliente) && isset($operacionaisVinculados)) {
                                            $opPrincipal = $operacionaisVinculados->first(function ($op) {
                                                return $op->pivot->principal;
                                            });
                                            if ($opPrincipal) {
                                                $operacionalPrincipalAtual = $opPrincipal->id;
                                            } elseif ($operacionaisVinculados->isNotEmpty()) {
                                                $operacionalPrincipalAtual = $operacionaisVinculados->first()->id;
                                            }
                                        }
                                    @endphp
                                    <select name="operacional_principal" id="operacionalSelect" class="form-select-new" required>
                                        <option value="">Selecione o operacional responsável</option>
                                        @foreach ($operacionais as $operacional)
                                            <option value="{{ $operacional->id }}"
                                                {{ old('operacional_principal', $operacionalPrincipalAtual) == $operacional->id ? 'selected' : '' }}>
                                                {{ $operacional->nome_completo }} - {{ $operacional->user->email }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('operacional_principal')
                                    <span class="field-error-message">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                                <span class="field-help-text">
                                    <i class="bi bi-info-circle"></i>
                                    Este operacional será o responsável principal pelas PIs deste cliente. Na edição de uma PI específica, o gerencial pode reatribuir para qualquer operacional do sistema.
                                </span>
                            </div>
                        </div>

                        <div class="form-footer">
                            <button type="button" class="btn-secondary" onclick="switchTab('documentos')">
                                <i class="bi bi-arrow-left-circle"></i>
                                Voltar
                            </button>
                            <button type="submit" class="btn-primary" id="submitBtn">
                                <i class="bi bi-check-circle"></i>
                                <span>{{ isset($cliente) ? 'Atualizar Cliente' : 'Salvar Cliente' }}</span>
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
    <script>
        $(document).ready(function() {
            const clienteId = '{{ isset($cliente) ? $cliente->id : '' }}';

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
                    $('.cnpj-mask').mask('00.000.000/0000-00');
                    $('.cep-mask').mask('00000-000');
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

                    this.checkDuplicateCNPJ(cnpj, $input);
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

                checkDuplicateCNPJ(cnpj, $input) {
                    $.ajax({
                        url: '{{ route("validar.cnpj") }}',
                        method: 'POST',
                        data: {
                            cnpj: cnpj,
                            cliente_id: clienteId,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: (response) => {
                            if (!response.disponivel) {
                                this.setInvalid($input, 'Este CNPJ já está cadastrado no sistema');
                                toastr.error('Este CNPJ já está cadastrado no sistema',
                                    'CNPJ Duplicado');
                            } else {
                                this.setValid($input);
                            }
                        },
                        error: () => {
                            this.setValid($input);
                        }
                    });
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
            }

            function toggleModoVeiculo() {
                const isChecked = $('#exige_documento_por_veiculo').is(':checked');

                if (isChecked) {
                    $('#veiculoInfoAlert').slideDown(300);
                    $('#documentosTradicionaisSection').slideUp(300);
                    $('#modoVeiculoAtivoInfo').slideDown(300);
                } else {
                    $('#veiculoInfoAlert').slideUp(300);
                    $('#documentosTradicionaisSection').slideDown(300);
                    $('#modoVeiculoAtivoInfo').slideUp(300);
                }
            }

            toggleModoVeiculo();

            $('#exige_documento_por_veiculo').on('change', function() {
                toggleModoVeiculo();
            });

            function validateTab(tabName) {
                let isValid = true;
                let errors = [];
                const $tab = $(`#tab-${tabName}`);

                if (tabName === 'empresa') {
                    const $cnpjInput = $('#cnpj');
                    if ($cnpjInput.hasClass('is-invalid') && $cnpjInput.val().trim() !== '') {
                        isValid = false;
                        const errorMsg = $cnpjInput.siblings('.cnpj-error-message').find('span').text();
                        errors.push(`CNPJ: ${errorMsg}`);
                    }
                }

                if (tabName === 'documentos') {
                    const modoVeiculoAtivo = $('#exige_documento_por_veiculo').is(':checked');

                    if (!modoVeiculoAtivo) {
                        const totalDocs = $('#tiposDocumentosContainer .dynamic-item').length;
                        if (totalDocs === 0) {
                            isValid = false;
                            errors.push('Você precisa adicionar pelo menos um tipo de documento');
                        }
                    }
                }

                if (tabName === 'operacionais') {
                    const operacionalSelecionado = $('#operacionalSelect').val();
                    if (!operacionalSelecionado) {
                        isValid = false;
                        errors.push('Você precisa selecionar um operacional responsável');
                    }
                }

                $tab.find('input[required], textarea[required]').each(function() {
                    const $field = $(this);
                    const value = $field.val();
                    const fieldName = $field.closest('.col-md-6, .col-md-12, .col-12').find(
                        '.field-label-new span:first').text();

                    if (!value || value.trim() === '') {
                        isValid = false;
                        $field.addClass('is-invalid');
                        if (fieldName && !errors.includes(fieldName)) {
                            errors.push(fieldName);
                        }
                    } else {
                        $field.removeClass('is-invalid');
                    }
                });

                if (!isValid) {
                    Swal.fire({
                        icon: 'warning',
                        title: tabName === 'documentos' || tabName === 'operacionais' ?
                            'Campos obrigatórios' : 'Campos incompletos',
                        html: `
                    <div style="text-align: left;">
                        <p style="margin-bottom: 1rem; color: var(--text-color);">
                            ${tabName === 'documentos' || tabName === 'operacionais' ? 'Atenção aos seguintes pontos:' : 'Por favor, preencha corretamente os seguintes campos:'}
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

                    if (currentTab === 'empresa') {
                        if (!validateTab(currentTab)) {
                            return;
                        }
                    }

                    if (currentTab === 'documentos' && tabName === 'operacionais') {
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
                switchTab(tab, false);
            });

            $('#adicionarTipoDocumento').click(function() {
                const select = $('#tipoDocumentoSelect');
                const selectedId = select.val();
                const selectedOption = select.find('option:selected');

                if (!selectedId) {
                    toastr.warning('Selecione um tipo de documento', 'Atenção');
                    return;
                }

                if ($(`#tiposDocumentosContainer .dynamic-item[data-id="${selectedId}"]`).length > 0) {
                    toastr.info('Este tipo de documento já foi adicionado', 'Informação');
                    return;
                }

                const nome = selectedOption.data('nome');
                const descricao = selectedOption.data('descricao') || '';
                const validade = selectedOption.data('validade');
                const validar = selectedOption.data('validar');

                $('#emptyTiposDocumentos').remove();

                let detailsHtml = '';
                if (validar === 'Sim') {
                    if (validade === 0) {
                        detailsHtml = `
                    <div class="detail-badge detail-badge-success">
                        <i class="bi bi-infinity"></i>
                        <span>Documento Permanente - Reutilizável em todas as PIs sem expiração</span>
                    </div>
                    <div class="detail-badge detail-badge-primary">
                        <i class="bi bi-shield-check"></i>
                        <span>Sistema controla validade e notifica vencimentos</span>
                    </div>
                `;
                    } else {
                        detailsHtml = `
                    <div class="detail-badge detail-badge-info">
                        <i class="bi bi-calendar-check"></i>
                        <span>Válido por ${validade} dias - Reutilizável até expirar</span>
                    </div>
                    <div class="detail-badge detail-badge-primary">
                        <i class="bi bi-shield-check"></i>
                        <span>Sistema controla validade e notifica vencimentos</span>
                    </div>
                `;
                    }
                } else {
                    detailsHtml = `
                <div class="detail-badge detail-badge-warning">
                    <i class="bi bi-file-earmark"></i>
                    <span>Documento de Uso Único - Cada PI requer novo documento</span>
                </div>
            `;
                }

                const itemHtml = `
            <div class="dynamic-item" data-id="${selectedId}">
                <input type="hidden" name="tipos_documentos[]" value="${selectedId}">

                <div class="dynamic-item-header">
                    <h4 class="dynamic-item-title">
                        <i class="bi bi-file-earmark-text"></i>
                        ${escapeHtml(nome)}
                    </h4>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <label class="opcional-toggle" title="Marcar como opcional">
                            <input type="checkbox" name="opcionais[]" value="${selectedId}">
                            <span class="opcional-toggle-label">Opcional</span>
                        </label>
                        <button type="button" class="btn-remove-item" onclick="removeTipoDocumento(this)">
                            <i class="bi bi-trash"></i>
                            <span>Remover</span>
                        </button>
                    </div>
                </div>

                <div class="dynamic-item-content">
                    ${descricao ? `
                                <p class="item-description">
                                    <i class="bi bi-info-circle"></i>
                                    ${escapeHtml(descricao)}
                                </p>
                            ` : ''}
                    <div class="item-details">
                        ${detailsHtml}
                    </div>
                </div>
            </div>
        `;

                $('#tiposDocumentosContainer').append(itemHtml);
                select.val('');
                toastr.success('Tipo de documento adicionado', 'Sucesso');
            });

            window.removeTipoDocumento = function(btn) {
                const $item = $(btn).closest('.dynamic-item');
                const documentoId = $item.data('id');
                const nome = $item.find('.dynamic-item-title').text().trim();

                if (clienteId) {
                    const modoVeiculoAtivo = $('#exige_documento_por_veiculo').is(':checked');
                    const totalDocs = $('#tiposDocumentosContainer .dynamic-item').length;

                    if (!modoVeiculoAtivo && totalDocs <= 1) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Não é possível remover',
                            html: `
                        <div style="text-align: left;">
                            <p style="margin-bottom: 1.25rem; font-size: 1.05rem; color: var(--text-color); line-height: 1.6;">
                                O cliente deve ter pelo menos <strong>um tipo de documento obrigatório</strong>.
                            </p>
                            <div class="warning-box">
                                <p>
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    <span>Adicione outro documento antes de remover este, ou ative o modo "Exigir Documentos por Tipo de Veículo".</span>
                                </p>
                            </div>
                        </div>
                    `,
                            confirmButtonText: 'Entendi',
                            customClass: {
                                confirmButton: 'swal-confirm-btn'
                            },
                            buttonsStyling: false,
                            width: '600px',
                            padding: '2rem'
                        });
                        return;
                    }

                    const documentoRequeridoId = $item.find('input[name="tipos_documentos[]"]').data(
                        'documento-requerido-id');

                    if (documentoRequeridoId) {
                        Swal.fire({
                            title: 'Remover Documento?',
                            html: `
                        <div style="text-align: left;">
                            <p style="margin-bottom: 1.25rem; font-size: 1.05rem; color: var(--text-color); line-height: 1.6;">
                                Deseja realmente remover <strong>${escapeHtml(nome)}</strong>?
                            </p>
                            <div class="warning-box">
                                <p>
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    <span>Esta ação não pode ser desfeita.</span>
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
                                $.ajax({
                                    url: `{{ route('clientes.index') }}/${clienteId}/documento/${documentoRequeridoId}`,
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                                            'content')
                                    },
                                    success: function(response) {
                                        if (response.success) {
                                            $item.fadeOut(300, function() {
                                                $(this).remove();
                                                checkEmptyTiposDocumentos();
                                            });

                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Removido!',
                                                text: response.message,
                                                timer: 2000,
                                                showConfirmButton: false
                                            });
                                        } else {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Erro!',
                                                text: response.message,
                                                confirmButtonText: 'Entendi',
                                                customClass: {
                                                    confirmButton: 'swal-confirm-btn'
                                                },
                                                buttonsStyling: false
                                            });
                                        }
                                    },
                                    error: function(xhr) {
                                        let errorMessage = 'Erro ao remover documento';
                                        if (xhr.responseJSON && xhr.responseJSON.message) {
                                            errorMessage = xhr.responseJSON.message;
                                        }

                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Erro!',
                                            text: errorMessage,
                                            confirmButtonText: 'Entendi',
                                            customClass: {
                                                confirmButton: 'swal-confirm-btn'
                                            },
                                            buttonsStyling: false
                                        });
                                    }
                                });
                            }
                        });
                        return;
                    }
                }

                Swal.fire({
                    title: 'Remover Documento?',
                    html: `
                <div style="text-align: left;">
                    <p style="margin-bottom: 1.25rem; font-size: 1.05rem; color: var(--text-color); line-height: 1.6;">
                        Deseja realmente remover <strong>${escapeHtml(nome)}</strong>?
                    </p>
                    <div class="warning-box">
                        <p>
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <span>Esta ação não pode ser desfeita.</span>
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
                        $item.fadeOut(300, function() {
                            $(this).remove();
                            checkEmptyTiposDocumentos();
                        });

                        Swal.fire({
                            icon: 'success',
                            title: 'Removido!',
                            text: 'Tipo de documento removido com sucesso!',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                });
            };

            function checkEmptyTiposDocumentos() {
                const $container = $('#tiposDocumentosContainer');
                const totalItems = $container.find('.dynamic-item').length;

                if (totalItems === 0) {
                    $container.html(`
                <div class="empty-state-dynamic" id="emptyTiposDocumentos">
                    <i class="bi bi-file-earmark-text"></i>
                    <p>Nenhum documento selecionado</p>
                    <small>Adicione pelo menos um tipo de documento que será obrigatório para os Processos de Importação deste cliente</small>
                </div>
            `);
                }
            }

            $('#clienteForm').on('submit', function(e) {
                const currentTab = $('.tab-content-area.active').attr('id').replace('tab-', '');

                if (currentTab !== 'operacionais') {
                    e.preventDefault();
                    switchTab('operacionais', true);
                    toastr.warning('Você precisa estar na última aba para salvar o formulário', 'Atenção');
                    return false;
                }

                if (!validateTab('documentos') || !validateTab('operacionais')) {
                    e.preventDefault();
                    return false;
                }

                const $cnpjInput = $('#cnpj');
                if ($cnpjInput.hasClass('is-invalid')) {
                    e.preventDefault();

                    Swal.fire({
                        icon: 'error',
                        title: 'CNPJ Inválido',
                        html: `
                    <div style="text-align: left;">
                        <p style="margin-bottom: 1rem; color: var(--text-color); line-height: 1.6;">
                            O CNPJ informado apresenta problemas e não pode ser salvo:
                        </p>
                        <div class="warning-box">
                            <p>
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <span>${$cnpjInput.siblings('.cnpj-error-message').find('span').text()}</span>
                            </p>
                        </div>
                        <p style="margin-top: 1rem; color: var(--text-color);">
                            Por favor, corrija o CNPJ antes de continuar.
                        </p>
                    </div>
                `,
                        confirmButtonText: 'Corrigir CNPJ',
                        customClass: {
                            confirmButton: 'swal-confirm-btn'
                        },
                        buttonsStyling: false,
                        width: '600px',
                        padding: '2rem'
                    }).then(() => {
                        switchTab('empresa', true);
                        setTimeout(() => {
                            $cnpjInput.focus();
                            $('html, body').animate({
                                scrollTop: $cnpjInput.offset().top - 100
                            }, 300);
                        }, 400);
                    });

                    return false;
                }

                $('#submitBtn').prop('disabled', true).html(
                    '<i class="bi bi-hourglass-split"></i> <span>Salvando...</span>');
            });

            const inputMaskManager = new InputMaskManager();
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

        .info-box-important {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(251, 191, 36, 0.05));
            border-left: 4px solid #f59e0b;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .info-box-important i {
            color: var(--warning-color);
            font-size: 1.25rem;
            margin-top: 0.125rem;
            flex-shrink: 0;
        }

        .info-box-important strong {
            color: #b45309;
        }

        /* Info box success para modo veículo ativo */
        .info-box-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(52, 211, 153, 0.05));
            border-left: 4px solid #10b981;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .info-box-success i {
            color: var(--success-color);
            font-size: 1.25rem;
            margin-top: 0.125rem;
            flex-shrink: 0;
        }

        .info-box-success strong {
            color: #065f46;
            display: block;
            margin-bottom: 0.5rem;
        }

        .info-box-success p {
            color: #065f46;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .info-box-success .alert-link {
            color: #047857;
            font-weight: 600;
            text-decoration: underline;
        }

        .add-item-section {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.03) 0%, rgba(0, 94, 130, 0.01) 100%);
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
        }

        .input-with-action {
            display: flex;
            gap: 0.75rem;
            align-items: stretch;
        }

        .input-with-action .input-with-icon {
            flex: 1;
        }

        .btn-add-inline {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .btn-add-inline:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 94, 130, 0.3);
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
            margin-bottom: 1rem;
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

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
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

        .dynamic-item-content {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .item-description {
            margin: 0;
            padding: 0.75rem 1rem;
            background: rgba(0, 94, 130, 0.05);
            border-left: 3px solid var(--primary-color);
            border-radius: 8px;
            color: var(--text-color);
            font-size: 0.875rem;
            display: flex;
            align-items: flex-start;
            gap: 0.625rem;
        }

        .item-description i {
            color: var(--primary-color);
            font-size: 1rem;
            margin-top: 0.125rem;
        }

        .item-details {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .detail-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.8125rem;
            font-weight: 500;
        }

        .detail-badge i {
            font-size: 0.875rem;
        }

        .detail-badge-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(96, 165, 250, 0.1));
            color: #1e40af;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .detail-badge-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(52, 211, 153, 0.1));
            color: #065f46;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .detail-badge-warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(251, 191, 36, 0.1));
            color: var(--text-on-light-bg);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .detail-badge-secondary {
            background: linear-gradient(135deg, rgba(100, 116, 139, 0.1), rgba(148, 163, 184, 0.1));
            color: #334155;
            border: 1px solid rgba(100, 116, 139, 0.2);
        }

        .detail-badge-primary {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.1), rgba(0, 94, 130, 0.05));
            color: #005e82;
            border: 1px solid rgba(0, 94, 130, 0.2);
        }

        .checkbox-principal-new {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(251, 191, 36, 0.1));
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .checkbox-principal-new:hover {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(251, 191, 36, 0.15));
            transform: translateY(-1px);
        }

        .checkbox-principal-new input[type="radio"] {
            margin: 0;
            cursor: pointer;
            width: 18px;
            height: 18px;
            accent-color: var(--warning-color);
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-on-light-bg);
        }

        .checkbox-label i {
            font-size: 0.875rem;
        }

        .checkbox-principal-new input[type="radio"]:checked+.checkbox-label {
            color: #b45309;
        }

        .empty-state-dynamic {
            text-align: center;
            padding: 3rem 2rem;
            background: var(--bg-body);
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            color: var(--text-muted);
        }

        .empty-state-dynamic i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state-dynamic p {
            margin: 0 0 0.5rem 0;
            font-weight: 600;
            font-size: 1rem;
            color: var(--text-color);
        }

        .empty-state-dynamic small {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        /* Seção de configuração por tipo de veículo */
        .config-veiculo-section {
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--border-color);
        }

        .config-veiculo-option {
            background: var(--bg-body);
            border: 2px solid var(--border-color);
            border-radius: 14px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .config-veiculo-option:hover {
            border-color: var(--primary-color);
        }

        .config-veiculo-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .config-veiculo-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .config-veiculo-icon i {
            font-size: 1.5rem;
            color: white;
        }

        .config-veiculo-info {
            flex: 1;
        }

        .config-veiculo-label {
            display: block;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.375rem;
        }

        .config-veiculo-desc {
            display: block;
            font-size: 0.875rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .toggle-switch-veiculo {
            position: relative;
            flex-shrink: 0;
        }

        .toggle-input-veiculo {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }

        .toggle-label-veiculo {
            display: block;
            width: 56px;
            height: 30px;
            background: #e2e8f0;
            border-radius: 30px;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .toggle-slider-veiculo {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .toggle-input-veiculo:checked+.toggle-label-veiculo {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
        }

        .toggle-input-veiculo:checked+.toggle-label-veiculo .toggle-slider-veiculo {
            transform: translateX(26px);
        }

        .config-veiculo-alert {
            display: none;
            margin-top: 1.25rem;
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.05), rgba(0, 94, 130, 0.02));
            border-left: 4px solid var(--primary-color);
            border-radius: 10px;
        }

        .config-veiculo-alert i {
            color: var(--primary-color);
            font-size: 1.25rem;
            margin-right: 0.75rem;
            flex-shrink: 0;
        }

        .config-veiculo-alert strong {
            color: var(--primary-color);
            display: block;
            margin-bottom: 0.5rem;
        }

        .config-veiculo-alert ul {
            margin: 0;
            padding-left: 1.25rem;
            color: var(--text-color);
            font-size: 0.875rem;
            line-height: 1.7;
        }

        .config-veiculo-alert ul li {
            margin-bottom: 0.25rem;
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
            padding: 1rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.625rem;
            transition: all 0.3s ease;
            border: none;
            min-width: 140px;
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

            .header-actions {
                width: 100%;
                justify-content: space-between;
            }

            .btn-remove-item {
                flex: 1;
                justify-content: center;
            }

            .input-with-action {
                flex-direction: column;
            }

            .btn-add-inline {
                width: 100%;
                justify-content: center;
            }

            .config-veiculo-header {
                flex-direction: column;
            }

            .toggle-switch-veiculo {
                align-self: flex-start;
                margin-top: 0.5rem;
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
                padding: 1.25rem;
            }

            .swal2-title {
                font-size: 1.375rem;
                margin-bottom: 1rem;
            }

            .swal2-actions {
                flex-direction: column;
                width: 100%;
                gap: 0.75rem;
            }

            .swal-confirm-btn,
            .swal-cancel-btn {
                width: 100%;
                padding: 0.875rem 1.5rem;
            }
        }

        .opcional-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            cursor: pointer;
            user-select: none;
        }

        .opcional-toggle input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--primary-color);
            cursor: pointer;
        }

        .opcional-toggle-label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-muted);
            transition: color 0.2s;
        }

        .opcional-toggle input[type="checkbox"]:checked + .opcional-toggle-label {
            color: var(--primary-color);
        }
    </style>
@endpush