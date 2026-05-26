<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use App\Services\TelegramService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('documentos:validar --sem-email')
    ->dailyAt('00:05')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping(15)
    ->runInBackground()
    ->onSuccess(function () {
        Log::channel('telegram')->info('Schedule OK: documentos:validar --sem-email (00:05)');
    })
    ->onFailure(function () {
        Log::channel('telegram')->error('Schedule FALHOU: documentos:validar --sem-email (00:05)');
        TelegramService::alertaSeguro('documentos:validar --sem-email (00:05)');
    });

Schedule::command('documentos:validar --sem-email')
    ->dailyAt('07:00')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping(15)
    ->runInBackground()
    ->onSuccess(function () {
        Log::channel('telegram')->info('Schedule OK: documentos:validar --sem-email (07:00)');
    })
    ->onFailure(function () {
        Log::channel('telegram')->error('Schedule FALHOU: documentos:validar --sem-email (07:00)');
        TelegramService::alertaSeguro('documentos:validar --sem-email (07:00)');
    });

Schedule::command('documentos:validar')
    ->weekly()
    ->mondays()
    ->at('05:30')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping(30)
    ->runInBackground()
    ->onSuccess(function () {
        Log::channel('telegram')->info('Schedule OK: documentos:validar (semanal)');
    })
    ->onFailure(function () {
        Log::channel('telegram')->error('Schedule FALHOU: documentos:validar (semanal)');
        TelegramService::alertaSeguro('documentos:validar (semanal)');
    });

Schedule::command('documentos:notificar-validacoes')
    ->everyFiveMinutes()
    ->withoutOverlapping(5)
    ->onFailure(function () {
        Log::channel('telegram')->error('Schedule FALHOU: documentos:notificar-validacoes');
        TelegramService::alertaSeguro('documentos:notificar-validacoes');
    });

Schedule::command('documentos:notificar-avulsos')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->onFailure(function () {
        Log::channel('telegram')->error('Schedule FALHOU: documentos:notificar-avulsos');
        TelegramService::alertaSeguro('documentos:notificar-avulsos');
    });

Schedule::command('pis:reconciliar-status')
    ->dailyAt('02:00')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping(15)
    ->runInBackground()
    ->onSuccess(function () {
        Log::channel('telegram')->info('Schedule OK: pis:reconciliar-status');
    })
    ->onFailure(function () {
        Log::channel('telegram')->error('Schedule FALHOU: pis:reconciliar-status');
        TelegramService::alertaSeguro('pis:reconciliar-status');
    });

Schedule::command('pis:notificar-concluidos')
    ->dailyAt('17:50')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping(15)
    ->runInBackground()
    ->onSuccess(function () {
        Log::channel('telegram')->info('Schedule OK: pis:notificar-concluidos');
    })
    ->onFailure(function () {
        Log::channel('telegram')->error('Schedule FALHOU: pis:notificar-concluidos');
        TelegramService::alertaSeguro('pis:notificar-concluidos');
    });

Schedule::command('telegram:relatorio')
    ->dailyAt('06:05')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping(10)
    ->runInBackground()
    ->onFailure(function () {
        Log::channel('telegram')->error('Schedule FALHOU: telegram:relatorio (06:05)');
    });

Schedule::command('telegram:relatorio')
    ->dailyAt('18:20')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping(10)
    ->runInBackground()
    ->onFailure(function () {
        Log::channel('telegram')->error('Schedule FALHOU: telegram:relatorio (18:20)');
    });

Schedule::command('telegram:alertas')
    ->cron('30 0,3,6,9,12,15,18,21 * * *')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping(15)
    ->runInBackground()
    ->onSuccess(function () {
        Log::channel('telegram')->info('Schedule OK: telegram:alertas');
    })
    ->onFailure(function () {
        Log::channel('telegram')->error('Schedule FALHOU: telegram:alertas');
    });

Schedule::command('logs:limpar --dias=14')
    ->weeklyOn(0, '03:00')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping(15)
    ->runInBackground();

Schedule::command('sistema:monitorar-disco')
    ->dailyAt('08:00')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping(10)
    ->runInBackground();