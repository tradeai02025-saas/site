<?php

namespace App\Providers;

use App\Models\{Mensagem, RepresentanteFornecedor, User};
use App\Services\TelegramService;
use Illuminate\Support\Facades\{Cache, View};
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TelegramService::class);
    }

    public function boot(): void
    {
        View::composer(['partials.desktop-menu', 'partials.mobile-menu'], function ($view) {
            if (auth()->check()) {
                $userId = auth()->id();

                $unreadMessagesCount = Cache::remember("unread_messages_{$userId}", 60, function () use ($userId) {
                    return Mensagem::whereHas('conversa', function ($q) use ($userId) {
                        $q->whereNull('deleted_at')
                            ->whereHas('participantes', function ($query) use ($userId) {
                                $query->where('participante_type', User::class)
                                    ->where('participante_id', $userId)
                                    ->whereNull('data_saida');
                            });
                    })
                        ->where(function ($q) use ($userId) {
                            $q->where('remetente_type', '!=', User::class)
                                ->orWhere('remetente_id', '!=', $userId);
                        })
                        ->where('lida', false)
                        ->whereNull('deleted_at')
                        ->count();
                });

                $view->with('unreadMessagesCount', $unreadMessagesCount);

                $pendingVinculosCount = 0;
                if (auth()->user()->hasAnyRole(['admin', 'gerencial'])) {
                    $pendingVinculosCount = Cache::remember('pending_vinculos_count', 300, function () {
                        return RepresentanteFornecedor::where('status_vinculo', 'pendente')
                            ->where('ativo', true)
                            ->count();
                    });
                }
                $view->with('pendingVinculosCount', $pendingVinculosCount);
            }
        });
    }
}