<?php

/* app/Http/Controllers/AuthController.php */

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Hash, Log, Password};
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRules;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'O email é obrigatório',
            'email.email' => 'Digite um email válido',
            'password.required' => 'A senha é obrigatória',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = Auth::user();

            if ($user->hasRole('representante') && $user->representanteUsuario && ! $user->representanteUsuario->ativo) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => 'Sua conta de representante está desativada. Entre em contato com o administrador.']);
            }

            activity()
                ->causedBy($user)
                ->log('Login realizado');

            $incompleteStep = $user->getIncompleteProfileStep();

            if ($incompleteStep) {
                $messages = [
                    'password' => 'Por segurança, você precisa alterar sua senha temporária.',
                    'phone' => 'Complete seu cadastro adicionando um telefone de contato.',
                    'banking' => 'Complete seu cadastro adicionando dados bancários para receber pagamentos.',
                    'representante' => 'Complete seu cadastro preenchendo os dados da sua empresa.',
                ];

                return redirect()->route('perfil.edit', ['aba' => $incompleteStep['tab']])
                    ->with('warning', $messages[$incompleteStep['step']])
                    ->with('required_step', $incompleteStep['step']);
            }

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Bem-vindo, '.$user->name.'!');
        }

        Log::channel('security')->warning('Tentativa de login falhou', [
            'email' => $request->email,
            'ip' => $request->ip(),
        ]);

        activity()->withProperties([
            'email' => $request->email,
            'ip' => $request->ip(),
        ])->log('Tentativa de login falhou');

        try {
            $tentativasRecentes = \Spatie\Activitylog\Models\Activity::where('description', 'Tentativa de login falhou')
                ->whereJsonContains('properties->ip', $request->ip())
                ->where('created_at', '>=', now()->subMinutes(10))
                ->count();

            if ($tentativasRecentes >= 3) {
                app(\App\Services\TelegramService::class)->alertLoginSuspeito(
                    $request->email,
                    $request->ip(),
                    $tentativasRecentes
                );
            }
        } catch (\Throwable $t) {
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors([
                'email' => 'Credenciais inválidas.',
            ]);
    }

    public function logout(Request $request)
    {
        activity()
            ->causedBy(Auth::user())
            ->log('Logout realizado');

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('info', 'Você saiu do sistema com sucesso.');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'O email é obrigatório',
            'email.email' => 'Digite um email válido',
        ]);

        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                Log::channel('security')->info('Link de recuperação de senha enviado', [
                    'email' => $request->email,
                    'ip' => $request->ip(),
                ]);
            } else {
                Log::channel('security')->warning('Falha ao enviar link de recuperação', [
                    'email' => $request->email,
                    'status' => $status,
                    'ip' => $request->ip(),
                ]);
            }

            return redirect()->route('login')
                ->with('success', 'Se este email estiver cadastrado, você receberá um link de recuperação em instantes.');
        } catch (\Exception $e) {
            Log::channel('security')->error('Erro ao enviar link de recuperação de senha', [
                'email' => $request->email,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')
                ->with('success', 'Se este email estiver cadastrado, você receberá um link de recuperação em instantes.');
        }
    }

    public function showResetPassword(Request $request, $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRules::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ], [
            'password.required' => 'A senha é obrigatória',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres',
            'password.confirmed' => 'As senhas não conferem',
        ]);

        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                    ])->setRememberToken(Str::random(60));

                    $user->save();

                    event(new PasswordReset($user));
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                Log::channel('security')->info('Senha redefinida com sucesso', [
                    'email' => $request->email,
                    'ip' => $request->ip(),
                ]);

                return redirect()->route('login')
                    ->with('success', 'Senha redefinida com sucesso! Faça login com sua nova senha.');
            }

            Log::channel('security')->warning('Falha ao redefinir senha', [
                'email' => $request->email,
                'status' => $status,
                'ip' => $request->ip(),
            ]);

            return back()->withErrors(['email' => 'Não foi possível redefinir a senha. Token pode estar expirado.']);
        } catch (\Exception $e) {
            Log::channel('security')->error('Erro ao redefinir senha', [
                'email' => $request->email,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['email' => 'Erro ao redefinir a senha. Tente novamente.']);
        }
    }
}