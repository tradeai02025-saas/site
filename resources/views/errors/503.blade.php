{{-- resources/views/errors/503.blade.php --}}

@extends('layouts.erro')

@section('title', 'Serviço indisponível')
@section('code', '503')
@section('message', 'Serviço Indisponível')
@section('subtitle', 'O sistema está temporariamente fora do ar')

@section('gradient-start', '#6366f1')
@section('gradient-end', '#4f46e5')
@section('error-color', '#4f46e5')

@section('content')
    <p class="text-center text-muted mb-3">
        O sistema está temporariamente indisponível para manutenção. 
        Voltaremos em breve. Agradecemos sua compreensão.
    </p>

    <div class="info-card mb-3">
        <div class="d-flex align-items-start">
            <i class="bi bi-tools"></i>
            <div>
                <strong>Manutenção em Andamento:</strong> Estamos realizando melhorias no sistema para oferecer uma melhor experiência.
            </div>
        </div>
    </div>

    <div class="warning-card mb-3">
        <div class="d-flex align-items-start">
            <i class="bi bi-clock"></i>
            <div>
                <strong>Tempo Estimado:</strong> O sistema deve voltar ao normal em breve. Tente novamente mais tarde.
            </div>
        </div>
    </div>
@endsection

@section('actions')
    <a href="javascript:location.reload()" class="btn btn-custom-primary">
        <i class="bi bi-arrow-clockwise me-2"></i>Tentar Novamente
    </a>
@endsection