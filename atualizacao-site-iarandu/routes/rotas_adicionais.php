<?php

use Illuminate\Support\Facades\Route;

/**
 * ROTAS ADICIONADAS PARA O SITE INSTITUCIONAL E PLANOS IARANDÚ
 * 
 * Inserir no início do arquivo routes/web.php:
 */

// 1. Rota principal: exibe a landing page institucional com a seção de planos
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('landing');
})->name('home');

// 2. Rota de planos direta: direciona para a landing page (seção #planos)
Route::get('/planos', function () {
    return view('landing');
})->name('planos');
