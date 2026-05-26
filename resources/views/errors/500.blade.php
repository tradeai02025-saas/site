{{-- resources/views/errors/500.blade.php --}}

@extends('layouts.erro')

@section('title', 'Erro no servidor')
@section('code', '500')
@section('message', 'Erro Interno do Servidor')
@section('subtitle', 'Algo deu errado no nosso servidor')

@section('gradient-start', '#dc2626')
@section('gradient-end', '#ef4444')
@section('error-color', '#dc2626')

@section('content')
    <p class="text-center text-muted mb-3">
        Desculpe, ocorreu um erro inesperado no servidor. 
        Nossa equipe foi notificada e estamos trabalhando para resolver o problema.
    </p>

    <div class="danger-card mb-3">
        <div class="d-flex align-items-start">
            <i class="bi bi-exclamation-triangle"></i>
            <div>
                <strong>Erro Temporário:</strong> Este problema geralmente é temporário. Tente novamente em alguns instantes.
            </div>
        </div>
    </div>

    <div class="info-card mb-3">
        <div class="d-flex align-items-start">
            <i class="bi bi-lightbulb"></i>
            <div>
                <strong>O que fazer:</strong> Aguarde alguns minutos e tente recarregar a página. Se o problema persistir, entre em contato com o suporte.
            </div>
        </div>
    </div>
@endsection

@section('actions')
    <a href="{{ url('/') }}" class="btn btn-custom-primary">
        <i class="bi bi-house-door me-2"></i>Voltar ao Início
    </a>
    <a href="javascript:location.reload()" class="btn btn-custom-secondary">
        <i class="bi bi-arrow-clockwise me-2"></i>Tentar Novamente
    </a>
@endsection