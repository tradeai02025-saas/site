{{-- resources/views/emails/reset-password.blade.php --}}

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Recuperação de Senha - {{ config('app.name') }}</title>
</head>

<body
    style="margin: 0; padding: 40px 20px; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif; line-height: 1.6; color: #1e293b; background: linear-gradient(135deg, #0a192f 0%, #003d56 50%, #005e82 100%); -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">
    <div
        style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 24px 64px rgba(0, 0, 0, 0.3);">
        <div
            style="background: linear-gradient(135deg, #005e82 0%, #0088b8 100%); padding: 50px 40px; text-align: center; position: relative;">
            <div
                style="width: 140px; height: 140px; margin: 0 auto 24px; background: rgba(255, 255, 255, 0.95); border-radius: 50%; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10px); box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2); padding: 20px;">
                <img src="{{ asset('img/favicons/favicon-192.png') }}" alt="{{ config('app.name') }} Logo"
                    style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            <h1
                style="color: #ffffff; font-size: 32px; font-weight: 700; margin: 0 0 12px; text-shadow: 0 2px 12px rgba(0, 0, 0, 0.2); letter-spacing: -0.5px;">
                Recuperação de Senha</h1>
            <p style="color: rgba(255, 255, 255, 0.92); font-size: 16px; margin: 0; font-weight: 400;">Solicitação de
                redefinição de senha recebida</p>
        </div>

        <div style="padding: 50px 40px; background: #ffffff;">
            <p style="font-size: 20px; font-weight: 600; color: #1e293b; margin: 0 0 24px;">Olá,
                <strong>{{ $user->name }}</strong>!
            </p>

            <p style="font-size: 16px; color: #475569; line-height: 1.8; margin: 0 0 20px;">
                Recebemos uma solicitação para redefinir a senha da sua conta no
                <strong>{{ config('app.name') }}</strong>.
            </p>

            <p style="font-size: 16px; color: #475569; line-height: 1.8; margin: 0 0 20px;">
                Para criar uma nova senha com segurança, clique no botão abaixo:
            </p>

            <div style="text-align: center; margin: 40px 0;">
                <a href="{{ $url }}"
                    style="display: inline-block; background: linear-gradient(135deg, #005e82 0%, #0088b8 100%); color: #ffffff !important; text-decoration: none; padding: 18px 48px; border-radius: 14px; font-weight: 600; font-size: 16px; text-transform: uppercase; letter-spacing: 1.2px; box-shadow: 0 8px 24px rgba(0, 94, 130, 0.4); transition: all 0.3s ease;">Redefinir
                    Minha Senha</a>
            </div>

            <hr
                style="border: 0; height: 1px; background: linear-gradient(90deg, transparent, #e2e8f0, transparent); margin: 30px 0;">

            <p style="font-size: 13px; color: #64748b; text-align: center; margin: 0 0 12px;">
                Se o botão acima não funcionar, copie e cole o link abaixo no seu navegador:
            </p>
            <div
                style="background: #f8fafc; border: 2px solid #e2e8f0; padding: 16px; border-radius: 12px; font-size: 13px; color: #475569; word-break: break-all; text-align: center; font-family: 'Courier New', monospace;">
                {{ $url }}</div>

            <hr
                style="border: 0; height: 1px; background: linear-gradient(90deg, transparent, #e2e8f0, transparent); margin: 30px 0;">

            <div
                style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, rgba(239, 68, 68, 0.04) 100%); border: 2px solid rgba(239, 68, 68, 0.2); border-left: 5px solid #ef4444; border-radius: 12px; padding: 18px 22px; margin: 30px 0;">
                <p style="font-size: 14px; color: #64748b; margin: 0; line-height: 1.6;">
                    <strong style="color: #be0a13;">Não reconhece esta solicitação?</strong><br>
                    Se você não solicitou a recuperação de senha, alguém pode estar tentando acessar sua conta.
                    Neste caso, recomendamos que você entre em contato conosco imediatamente e considere alterar
                    sua senha assim que possível.
                </p>
            </div>

            <div
                style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(59, 130, 246, 0.04) 100%); border: 2px solid rgba(59, 130, 246, 0.2); border-left: 5px solid #3b82f6; border-radius: 12px; padding: 20px 24px; margin: 30px 0;">
                <div
                    style="font-weight: 600; font-size: 15px; color: #1e293b; margin: 0 0 8px; display: flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"
                        style="width: 20px; height: 20px; fill: #3b82f6;">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                        <path
                            d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z" />
                    </svg>
                    Informações Importantes
                </div>
                <p style="font-size: 14px; color: #64748b; margin: 0; line-height: 1.6;">
                    • Este link é válido por <strong>60 minutos</strong> e só pode ser usado uma vez.<br>
                    • Sua senha atual permanece ativa até que você complete o processo de redefinição.
                </p>
            </div>
        </div>

        <div style="background: #f8fafc; padding: 40px; text-align: center; border-top: 1px solid #e2e8f0;">
            <p style="font-size: 20px; font-weight: 700; color: #005e82; margin: 0 0 12px;">{{ config('app.name') }}</p>
            <p style="font-size: 13px; color: #64748b; margin: 8px 0;">Este é um email automático gerado pelo sistema.
            </p>
            <p style="font-size: 13px; color: #64748b; margin: 8px 0;">Por favor, não responda a este email.</p>

            <div style="margin-top: 20px;">
                <a href="{{ route('login') }}"
                    style="color: #005e82; text-decoration: none; font-size: 13px; font-weight: 500; margin: 0 12px;">Acessar
                    Sistema</a>
            </div>

            <p style="margin-top: 24px; font-size: 12px; color: #64748b; margin: 8px 0;">
                © {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
            </p>
        </div>
    </div>
</body>

</html>