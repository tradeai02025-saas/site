{{-- resources/views/errors/400.blade.php --}}

@extends('layouts.erro')

@section('title', 'Requisição inválida')
@section('code', '400')
@section('message', 'Requisição Inválida')
@section('subtitle', 'Não foi possível processar sua requisição')

@section('gradient-start', '#ec4899')
@section('gradient-end', '#db2777')
@section('error-color', '#db2777')

@section('content')
    <p class="text-center text-muted mb-3">
        A requisição enviada não pôde ser processada pelo servidor.
        Verifique os dados e tente novamente.
    </p>

    <div class="danger-card mb-3">
        <div class="d-flex align-items-start">
            <i class="bi bi-x-circle"></i>
            <div>
                <strong>Dados Inválidos:</strong> Os dados enviados estão em um formato incorreto ou são inválidos.
            </div>
        </div>
    </div>

    <div class="info-card mb-3">
        <div class="d-flex align-items-start">
            <i class="bi bi-lightbulb"></i>
            <div>
                <strong>O que fazer:</strong> Verifique se preencheu todos os campos corretamente e tente novamente.
            </div>
        </div>
    </div>
@endsection

@section('actions')
    <a href="javascript:history.back()" class="btn btn-custom-primary">
        <i class="bi bi-arrow-left me-2"></i>Voltar
    </a>
    <a href="{{ url('/') }}" class="btn btn-custom-secondary">
        <i class="bi bi-house-door me-2"></i>Ir ao Início
    </a>
@endsection
