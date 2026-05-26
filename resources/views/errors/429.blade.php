{{-- resources/views/errors/429.blade.php --}}

@extends('layouts.erro')

@section('title', 'Muitas requisições')
@section('code', '429')
@section('message', 'Muitas Requisições')
@section('subtitle', 'Você atingiu o limite de requisições')

@section('gradient-start', '#f97316')
@section('gradient-end', '#ea580c')
@section('error-color', '#ea580c')

@section('content')
    <p class="text-center text-muted mb-3">
        Você fez muitas requisições em um curto período de tempo. 
        Por favor, aguarde alguns instantes antes de tentar novamente.
    </p>

    <div class="warning-card mb-3">
        <div class="d-flex align-items-start">
            <i class="bi bi-hourglass-split"></i>
            <div>
                <strong>Limite Atingido:</strong> Para proteger o sistema, limitamos o número de requisições por período de tempo.
            </div>
        </div>
    </div>

    <div class="info-card mb-3">
        <div class="d-flex align-items-start">
            <i class="bi bi-lightbulb"></i>
            <div>
                <strong>O que fazer:</strong> Aguarde alguns minutos e tente novamente. Evite atualizar a página repetidamente.
            </div>
        </div>
    </div>
@endsection

@section('actions')
    <a href="{{ url('/') }}" class="btn btn-custom-primary">
        <i class="bi bi-house-door me-2"></i>Voltar ao Início
    </a>
    <a href="javascript:history.back()" class="btn btn-custom-secondary">
        <i class="bi bi-arrow-left me-2"></i>Voltar
    </a>
@endsection