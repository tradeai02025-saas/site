{{-- resources/views/errors/404.blade.php --}}

@extends('layouts.erro')

@section('title', 'Página não encontrada')
@section('code', '404')
@section('message', 'Página Não Encontrada')
@section('subtitle', 'Ops! A página que você procura não existe')

@section('gradient-start', '#005e82')
@section('gradient-end', '#0088b8')
@section('error-color', '#005e82')

@section('content')
    <p class="text-center text-muted mb-3">
        A página que você está tentando acessar não foi encontrada.
        Ela pode ter sido movida, excluída ou o link pode estar incorreto.
    </p>

    <div class="info-card mb-3">
        <div class="d-flex align-items-start">
            <i class="bi bi-lightbulb"></i>
            <div>
                <strong>Dica:</strong> Verifique se o endereço está correto ou clique no botão abaixo para voltar ao início.
            </div>
        </div>
    </div>
@endsection

@section('actions')
    <a href="{{ url('/') }}" class="btn btn-custom-primary">
        <i class="bi bi-house-door me-2"></i>Voltar ao Início
    </a>
@endsection
