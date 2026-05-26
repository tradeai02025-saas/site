{{-- resources/views/errors/401.blade.php --}}

@extends('layouts.erro')

@section('title', 'Não autenticado')
@section('code', '401')
@section('message', 'Autenticação Necessária')
@section('subtitle', 'Você precisa estar logado para acessar esta página')

@section('gradient-start', '#3b82f6')
@section('gradient-end', '#2563eb')
@section('error-color', '#2563eb')

@section('content')
    <p class="text-center text-muted mb-3">
        Esta página requer autenticação. 
        Por favor, faça login para continuar.
    </p>

    <div class="info-card mb-3">
        <div class="d-flex align-items-start">
            <i class="bi bi-info-circle"></i>
            <div>
                <strong>Login Necessário:</strong> Você precisa estar autenticado no sistema para acessar este conteúdo.
            </div>
        </div>
    </div>
@endsection

@section('actions')
    <a href="{{ route('login') }}" class="btn btn-custom-primary">
        <i class="bi bi-box-arrow-in-right me-2"></i>Fazer Login
    </a>
    <a href="{{ url('/') }}" class="btn btn-custom-secondary">
        <i class="bi bi-house-door me-2"></i>Voltar ao Início
    </a>
@endsection