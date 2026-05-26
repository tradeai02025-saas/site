<?php

/* app/Http/Middleware/CheckTemporaryPassword.php */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckTemporaryPassword
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        if (! $request->is('perfil*') && ! $request->is('logout')) {
            $incompleteStep = $user->getIncompleteProfileStep();

            if ($incompleteStep) {
                $messages = [
                    'password' => 'Você precisa alterar sua senha temporária antes de continuar.',
                    'phone' => 'Complete seu cadastro adicionando um telefone de contato.',
                    'banking' => 'Complete seu cadastro adicionando pelo menos uma conta bancária.',
                    'representante' => 'Complete seu cadastro preenchendo os dados da sua empresa.',
                ];

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $messages[$incompleteStep['step']],
                        'redirect' => route('perfil.edit', ['aba' => $incompleteStep['tab']]),
                        'required_step' => $incompleteStep['step'],
                    ], 403);
                }

                return redirect()->route('perfil.edit', ['aba' => $incompleteStep['tab']])
                    ->with('warning', $messages[$incompleteStep['step']])
                    ->with('required_step', $incompleteStep['step'])
                    ->with('toast_persistent', true);
            }
        }

        return $next($request);
    }
}