{{-- resources/views/errors/419.blade.php --}}

@extends('layouts.erro')

@section('title', 'Sessão expirada')
@section('code', '419')
@section('message', 'Sessão Expirada')
@section('subtitle', 'Sua sessão expirou por inatividade')

@section('gradient-start', '#8b5cf6')
@section('gradient-end', '#7c3aed')
@section('error-color', '#7c3aed')

@section('content')
    <p class="text-center text-muted mb-3">
        Sua sessão expirou por motivos de segurança. 
        Por favor, recarregue a página ou faça login novamente.
    </p>

    <div class="warning-card mb-3">
        <div class="d-flex align-items-start">
            <i class="bi bi-clock-history"></i>
            <div>
                <strong>Sessão Inativa:</strong> Por segurança, sua sessão foi encerrada após um período de inatividade.
            </div>
        </div>
    </div>

    <div class="info-card mb-3">
        <div class="d-flex align-items-start">
            <i class="bi bi-lightbulb"></i>
            <div>
                <strong>Dica:</strong> Recarregue a página atual ou faça login novamente para continuar.
            </div>
        </div>
    </div>
@endsection

@section('actions')
    <a href="javascript:location.reload()" class="btn btn-custom-primary">
        <i class="bi bi-arrow-clockwise me-2"></i>Recarregar Página
    </a>
    @guest
        <a href="{{ route('login') }}" class="btn btn-custom-secondary">
            <i class="bi bi-box-arrow-in-right me-2"></i>Fazer Login
        </a>
    @else
        <a href="{{ url('/') }}" class="btn btn-custom-secondary">
            <i class="bi bi-house-door me-2"></i>Voltar ao Início
        </a>
    @endguest
@endsection