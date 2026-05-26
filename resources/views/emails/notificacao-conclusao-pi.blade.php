{{-- resources/views/emails/notificacao-conclusao-pi.blade.php --}}

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>PI Concluído - {{ config('app.name') }}</title>
    <style>
        @media only screen and (max-width: 600px) {
            .container { width: 100% !important; max-width: 100% !important; }
            .header-title { font-size: 24px !important; }
            .button-cta { padding: 14px 32px !important; font-size: 14px !important; }
            .content-padding { padding: 30px 20px !important; }
            .card { padding: 12px 16px !important; }
        }
        .preheader { display: none; font-size: 1px; color: #ffffff; line-height: 1px; max-height: 0; max-width: 0; opacity: 0; overflow: hidden; }
    </style>
</head>
<body style="margin: 0; padding: 40px 20px; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif; line-height: 1.6; color: #1e293b; background: linear-gradient(135deg, #0a192f 0%, #003d56 50%, #005e82 100%); -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">

    <span class="preheader">PI {{ $piNumero }} foi concluído com sucesso!</span>

    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 24px 64px rgba(0, 0, 0, 0.3);">

        <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 50px 40px; text-align: center; position: relative;">
            <div style="width: 140px; height: 140px; margin: 0 auto 24px; background: rgba(255, 255, 255, 0.95); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2); padding: 20px;">
                <img src="{{asset('img/favicons/favicon-192.png')}}" alt="Logo {{ config('app.name') }}" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            <h1 class="header-title" style="color: #ffffff; font-size: 32px; font-weight: 700; margin: 0 0 12px; text-shadow: 0 2px 12px rgba(0, 0, 0, 0.2); letter-spacing: -0.5px;">
                PI Concluído!
            </h1>
            <p style="color: rgba(255, 255, 255, 0.92); font-size: 18px; margin: 0; font-weight: 600;">PI: {{ $piNumero }}</p>
            <p style="color: rgba(255, 255, 255, 0.85); font-size: 15px; margin: 8px 0 0 0; font-weight: 400;">{{ $clienteNome }}</p>
        </div>

        <div class="content-padding" style="padding: 50px 40px; background: #ffffff;">
            <p style="font-size: 20px; font-weight: 600; color: #1e293b; margin: 0 0 24px;">Prezado {{ $fornecedorNome }},</p>

            <div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(16, 185, 129, 0.04) 100%); border: 2px solid rgba(16, 185, 129, 0.2); border-left: 5px solid #10b981; border-radius: 12px; padding: 18px 22px; margin: 0 0 30px 0;">
                <p style="font-size: 15px; color: #1e293b; margin: 0 0 8px; font-weight: 700;">PROCESSO FINALIZADO</p>
                <p style="font-size: 14px; color: #64748b; margin: 0; line-height: 1.6;">
                    O Pedido de Inserção <strong>{{ $piNumero }}</strong> do cliente <strong>{{ $clienteNome }}</strong> foi concluído com sucesso. Todos os documentos foram aprovados pela equipe de validação.
                </p>
            </div>

            <div style="background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 24px; margin: 0 0 30px 0;">
                <div style="margin-bottom: 12px;">
                    <span style="font-size: 14px; font-weight: 600; color: #64748b;">Número do PI:</span>
                    <span style="font-size: 18px; font-weight: 700; color: #0088b8; margin-left: 8px;">{{ $piNumero }}</span>
                </div>
                <div style="margin-bottom: 12px;">
                    <span style="font-size: 14px; font-weight: 600; color: #64748b;">Cliente:</span>
                    <span style="font-size: 16px; font-weight: 600; color: #1e293b; margin-left: 8px;">{{ $clienteNome }}</span>
                </div>
                <div style="margin-bottom: 12px;">
                    <span style="font-size: 14px; font-weight: 600; color: #64748b;">Data de Conclusão:</span>
                    <span style="font-size: 16px; font-weight: 600; color: #1e293b; margin-left: 8px;">{{ $dataConclusao }}</span>
                </div>
                <div>
                    <span style="font-size: 14px; font-weight: 600; color: #64748b;">Documentos Aprovados:</span>
                    <span style="font-size: 18px; font-weight: 700; color: #10b981; margin-left: 8px;">{{ $totalDocumentos }}</span>
                </div>
            </div>

            <div style="text-align: center; margin: 40px 0 30px 0;">
                <a href="{{ $urlPi }}" class="button-cta" style="display: inline-block; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff !important; text-decoration: none; padding: 16px 40px; border-radius: 14px; font-weight: 600; font-size: 15px; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4); margin: 8px;">
                    Ver Detalhes do PI
                </a>
            </div>

            <hr style="border: 0; height: 1px; background: linear-gradient(90deg, transparent, #e2e8f0, transparent); margin: 30px 0;">

            <div style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(59, 130, 246, 0.04) 100%); border: 2px solid rgba(59, 130, 246, 0.2); border-left: 5px solid #3b82f6; border-radius: 12px; padding: 18px 22px; margin: 0;">
                <p style="font-size: 14px; color: #64748b; margin: 0; line-height: 1.6;">
                    Este PI foi concluído e não requer mais nenhuma ação. Caso tenha dúvidas, entre em contato pelo chat do sistema.
                </p>
            </div>
        </div>

        <div style="background: #f8fafc; padding: 40px; text-align: center; border-top: 1px solid #e2e8f0;">
            <p style="font-size: 20px; font-weight: 700; color: #005e82; margin: 0 0 12px;">{{ config('app.name') }}</p>
            <p style="font-size: 13px; color: #64748b; margin: 8px 0;">Este é um email automático</p>
            <p style="font-size: 13px; color: #64748b; margin: 8px 0;">Por favor, não responda a este email.</p>

            <div style="margin-top: 20px;">
                <a href="{{ route('login') }}" style="color: #005e82; text-decoration: none; font-size: 13px; font-weight: 500; margin: 0 12px;">Acessar Sistema</a>
                <span style="color: #cbd5e0; margin: 0 8px;">•</span>
                <a href="{{ route('chat.index') }}" style="color: #005e82; text-decoration: none; font-size: 13px; font-weight: 500; margin: 0 12px;">Chat de Suporte</a>
            </div>

            <p style="margin-top: 24px; font-size: 12px; color: #64748b; margin: 8px 0;">
                © {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
            </p>
        </div>
    </div>
</body>
</html>