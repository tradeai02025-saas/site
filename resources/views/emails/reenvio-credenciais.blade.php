{{-- resources/views/emails/reenvio-credenciais.blade.php --}}

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Novas Credenciais - {{ config('app.name') }}</title>
</head>

<body
    style="margin: 0; padding: 40px 20px; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif; line-height: 1.6; color: #1e293b; background: linear-gradient(135deg, #0a192f 0%, #003d56 50%, #005e82 100%); -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">
    <div
        style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 24px 64px rgba(0, 0, 0, 0.3);">
        <div
            style="background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%); padding: 50px 40px; text-align: center; position: relative;">
            <div
                style="width: 140px; height: 140px; margin: 0 auto 24px; background: rgba(255, 255, 255, 0.95); border-radius: 50%; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10px); box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2); padding: 20px;">
                <img src="{{ asset('img/favicons/favicon-192.png') }}" alt="{{ config('app.name') }} Logo"
                    style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            <h1
                style="color: #ffffff; font-size: 32px; font-weight: 700; margin: 0 0 12px; text-shadow: 0 2px 12px rgba(0, 0, 0, 0.2); letter-spacing: -0.5px;">
                Novas Credenciais de Acesso</h1>
            <p style="color: rgba(255, 255, 255, 0.92); font-size: 16px; margin: 0; font-weight: 400;">Suas credenciais foram atualizadas</p>
        </div>

        <div style="padding: 50px 40px; background: #ffffff;">
            <p style="font-size: 20px; font-weight: 600; color: #1e293b; margin: 0 0 24px;">Olá,
                <strong>{{ $nome }}</strong>!</p>

            <p style="font-size: 16px; color: #475569; line-height: 1.8; margin: 0 0 30px;">
                Suas credenciais de acesso ao <strong>{{ config('app.name') }}</strong> foram atualizadas pela equipe administrativa.
                Utilize as novas credenciais abaixo para acessar o sistema:
            </p>

            <div
                style="background: linear-gradient(135deg, rgba(2, 132, 199, 0.08) 0%, rgba(14, 165, 233, 0.04) 100%); border: 2px solid rgba(2, 132, 199, 0.2); border-left: 5px solid #0284c7; border-radius: 12px; padding: 24px; margin: 30px 0;">
                <div style="margin-bottom: 18px;">
                    <div
                        style="font-size: 13px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
                        Email de Acesso</div>
                    <div
                        style="font-size: 17px; font-weight: 700; color: #1e293b; font-family: 'Courier New', monospace; background: #ffffff; padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        {{ $email }}</div>
                </div>
                <div>
                    <div
                        style="font-size: 13px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
                        Nova Senha Temporária</div>
                    <div
                        style="font-size: 17px; font-weight: 700; color: #1e293b; font-family: 'Courier New', monospace; background: #ffffff; padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        {{ $senha }}</div>
                </div>
            </div>

            <div style="text-align: center; margin: 40px 0;">
                <a href="{{ $loginUrl }}"
                    style="display: inline-block; background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%); color: #ffffff !important; text-decoration: none; padding: 18px 48px; border-radius: 14px; font-weight: 600; font-size: 16px; text-transform: uppercase; letter-spacing: 1.2px; box-shadow: 0 8px 24px rgba(2, 132, 199, 0.4); transition: all 0.3s ease;">Acessar
                    o Sistema</a>
            </div>

            <hr
                style="border: 0; height: 1px; background: linear-gradient(90deg, transparent, #e2e8f0, transparent); margin: 30px 0;">

            <div
                style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.08) 0%, rgba(245, 158, 11, 0.04) 100%); border: 2px solid rgba(245, 158, 11, 0.2); border-left: 5px solid #f59e0b; border-radius: 12px; padding: 18px 22px; margin: 30px 0;">
                <p style="font-size: 14px; color: #64748b; margin: 0; line-height: 1.6;">
                    <strong style="color: #d97706;">Importante:</strong> Por questões de segurança, recomendamos que
                    você altere sua senha imediatamente após o próximo acesso ao sistema.
                </p>
            </div>

            <div
                style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, rgba(239, 68, 68, 0.04) 100%); border: 2px solid rgba(239, 68, 68, 0.2); border-left: 5px solid #ef4444; border-radius: 12px; padding: 18px 22px; margin: 30px 0;">
                <p style="font-size: 14px; color: #64748b; margin: 0; line-height: 1.6;">
                    <strong style="color: #dc2626;">Segurança:</strong> Não compartilhe suas credenciais com terceiros.
                    Nossa equipe nunca solicitará sua senha. Se você não solicitou essa alteração, entre em contato conosco imediatamente.
                </p>
            </div>

            @if($tipoUsuario === 'representante')
            <div
                style="background: linear-gradient(135deg, rgba(14, 165, 233, 0.08) 0%, rgba(14, 165, 233, 0.04) 100%); border: 2px solid rgba(14, 165, 233, 0.2); border-left: 5px solid #0ea5e9; border-radius: 12px; padding: 18px 22px; margin: 30px 0;">
                <p style="font-size: 14px; color: #64748b; margin: 0; line-height: 1.6;">
                    <strong style="color: #0284c7;">Lembrete:</strong> Seus vínculos com fornecedores permanecem inalterados. Após fazer login com as novas credenciais, você terá acesso normalmente aos fornecedores vinculados.
                </p>
            </div>
            @endif
        </div>

        <div style="background: #f8fafc; padding: 40px; text-align: center; border-top: 1px solid #e2e8f0;">
            <p style="font-size: 20px; font-weight: 700; color: #0284c7; margin: 0 0 12px;">{{ config('app.name') }}</p>
            <p style="font-size: 13px; color: #64748b; margin: 8px 0;">Este é um email automático gerado pelo sistema.
            </p>
            <p style="font-size: 13px; color: #64748b; margin: 8px 0;">Por favor, não responda a este email.</p>

            <div style="margin-top: 20px;">
                <a href="{{ $loginUrl }}"
                    style="color: #0284c7; text-decoration: none; font-size: 13px; font-weight: 500; margin: 0 12px;">Acessar
                    Sistema</a>
            </div>

            <p style="margin-top: 24px; font-size: 12px; color: #64748b; margin: 8px 0;">
                &copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
            </p>
        </div>
    </div>
</body>

</html>