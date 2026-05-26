{{-- resources/views/errors/403.blade.php --}}

@extends('layouts.erro')

@section('title', 'Acesso negado')
@section('code', '403')
@section('message', 'Acesso Negado')
@section('subtitle', 'Você não tem permissão para acessar esta página')

@section('gradient-start', '#f59e0b')
@section('gradient-end', '#d97706')
@section('error-color', '#d97706')

@section('content')
    <p class="text-center text-muted mb-3">
        Você não possui as permissões necessárias para acessar este recurso.
        Entre em contato com o administrador se acredita que deveria ter acesso.
    </p>

    <div class="warning-card mb-3">
        <div class="d-flex align-items-start">
            <i class="bi bi-shield-lock"></i>
            <div>
                <strong>Acesso Restrito:</strong> Esta página é restrita a usuários com permissões específicas.
            </div>
        </div>
    </div>

    <div class="info-card mb-3">
        <div class="d-flex align-items-start">
            <i class="bi bi-lightbulb"></i>
            <div>
                <strong>Precisa de acesso?</strong> Solicite as permissões necessárias ao administrador do sistema.
            </div>
        </div>
    </div>
@endsection

@section('actions')
    <a href="{{ url('/') }}" class="btn btn-custom-primary">
        <i class="bi bi-house-door me-2"></i>Voltar ao Início
    </a>
    @auth
        <a href="{{ route('perfil.edit') }}" class="btn btn-custom-secondary">
            <i class="bi bi-person-gear me-2"></i>Meu Perfil
        </a>
    @endauth
@endsection
