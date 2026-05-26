<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'check.temporary.password' => \App\Http\Middleware\CheckTemporaryPassword::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\TrackNavigation::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (HttpException $e, $request) {
            if ($e->getStatusCode() === 400) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $e->getMessage() ?: 'Requisição inválida.'], 400);
                }
                return response()->view('errors.400', [], 400);
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, $request) {
            try {
                $user = auth()->user();
                $userName = $user ? $user->name : 'Anonimo';
                $userId = $user ? $user->id : 'N/A';
                $role = $user && $user->roles->first() ? $user->roles->first()->name : 'N/A';

                \Illuminate\Support\Facades\Log::channel('security')->warning('HTTP 403 - Acesso Negado', [
                    'usuario' => $userName,
                    'user_id' => $userId,
                    'role' => $role,
                    'url' => $request->fullUrl(),
                    'metodo' => $request->method(),
                    'ip' => $request->ip(),
                    'motivo' => $e->getMessage() ?: 'Sem mensagem',
                ]);
            } catch (\Throwable $t) {
            }

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage() ?: 'Acesso negado.'], 403);
            }
            return response()->view('errors.403', [], 403);
        });

        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Recurso não encontrado.'], 404);
            }
            return response()->view('errors.404', [], 404);
        });

        $exceptions->render(function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Registro não encontrado.'], 404);
            }
            return response()->view('errors.404', [], 404);
        });

        $exceptions->render(function (TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Sessão expirada. Recarregue a página.'], 419);
            }
            return response()->view('errors.419', [], 419);
        });

        $exceptions->render(function (HttpException $e, $request) {
            if ($e->getStatusCode() === 429) {
                if ($request->is('login') && $request->isMethod('post')) {
                    $retryAfter = (int) ($e->getHeaders()['Retry-After'] ?? 60);

                    return redirect()->route('login')
                        ->withInput($request->only('email'))
                        ->with('throttle_seconds', $retryAfter);
                }

                try {
                    $user = auth()->user();
                    $userName = $user ? $user->name : 'Anonimo';
                    $userId = $user ? $user->id : 'N/A';

                    \Illuminate\Support\Facades\Log::channel('security')->warning('HTTP 429 - Rate Limit Atingido', [
                        'usuario' => $userName,
                        'user_id' => $userId,
                        'url' => $request->fullUrl(),
                        'metodo' => $request->method(),
                        'ip' => $request->ip(),
                    ]);
                } catch (\Throwable $t) {
                }

                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Muitas requisições. Aguarde um momento.'], 429);
                }
                return response()->view('errors.429', [], 429);
            }
        });

        $exceptions->render(function (HttpException $e, $request) {
            if ($e->getStatusCode() === 500) {
                try {
                    $detalhes = "URL: {$request->fullUrl()}\n"
                        . "Metodo: {$request->method()}\n"
                        . "IP: {$request->ip()}\n"
                        . "Erro: " . ($e->getMessage() ?: 'Sem mensagem');

                    app(\App\Services\TelegramService::class)->alertError(
                        'HTTP 500 - Erro Interno',
                        $detalhes,
                        $e->getTraceAsString()
                    );
                } catch (\Throwable $t) {
                }

                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Erro interno do servidor.'], 500);
                }
                return response()->view('errors.500', [], 500);
            }
        });

        $exceptions->render(function (HttpException $e, $request) {
            if ($e->getStatusCode() === 503) {
                try {
                    app(\App\Services\TelegramService::class)->alertError(
                        'HTTP 503 - Sistema em Manutencao',
                        "URL: {$request->fullUrl()}\n"
                        . "IP: {$request->ip()}\n"
                        . "O sistema esta retornando 503 para requisicoes."
                    );
                } catch (\Throwable $t) {
                }

                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Sistema em manutenção.'], 503);
                }
                return response()->view('errors.503', [], 503);
            }
        });

        $exceptions->render(function (\Throwable $e, $request) {
            if (!$e instanceof HttpException && !$e instanceof AuthenticationException && !$e instanceof TokenMismatchException && !$e instanceof ModelNotFoundException && !$e instanceof ValidationException) {
                try {
                    $user = auth()->user();
                    $userName = $user ? $user->name : 'Anonimo';
                    $userId = $user ? $user->id : 'N/A';

                    $detalhes = "Usuario: {$userName} (ID: {$userId})\n"
                        . "URL: {$request->fullUrl()}\n"
                        . "Metodo: {$request->method()}\n"
                        . "IP: {$request->ip()}\n"
                        . "Erro: {$e->getMessage()}";

                    app(\App\Services\TelegramService::class)->alertError(
                        get_class($e) . ' - Excecao nao tratada',
                        $detalhes,
                        $e->getFile() . ':' . $e->getLine()
                    );
                } catch (\Throwable $t) {
                }
            }
        });
    })->create();