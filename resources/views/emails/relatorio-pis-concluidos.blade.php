{{-- resources/views/emails/relatorio-pis-concluidos.blade.php --}}

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>PIs Concluídos - {{ config('app.name') }}</title>
    <style>
        @media only screen and (max-width: 600px) {
            .container { width: 100% !important; max-width: 100% !important; }
            .header-title { font-size: 24px !important; }
            .content-padding { padding: 30px 20px !important; }
            .card { padding: 12px 16px !important; }
        }
        .preheader { display: none; font-size: 1px; color: #ffffff; line-height: 1px; max-height: 0; max-width: 0; opacity: 0; overflow: hidden; }
    </style>
</head>
<body style="margin: 0; padding: 40px 20px; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif; line-height: 1.6; color: #1e293b; background: linear-gradient(135deg, #0a192f 0%, #003d56 50%, #005e82 100%); -webkit-font-smoothing: antialiased;">

    <span class="preheader">{{ $total }} PI(s) concluído(s) em {{ $data }}</span>

    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 24px 64px rgba(0, 0, 0, 0.3);">

        <div style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); padding: 50px 40px; text-align: center;">
            <div style="width: 140px; height: 140px; margin: 0 auto 24px; background: rgba(255, 255, 255, 0.95); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2); padding: 20px;">
                <img src="{{asset('img/favicons/favicon-192.png')}}" alt="Logo {{ config('app.name') }}" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            <h1 class="header-title" style="color: #ffffff; font-size: 28px; font-weight: 800; margin: 0 0 8px 0;">PIs Concluídos</h1>
            <p style="color: rgba(255, 255, 255, 0.9); font-size: 16px; margin: 0;">Relatório do dia {{ $data }}</p>
        </div>

        <div class="content-padding" style="padding: 40px;">
            <p style="font-size: 16px; color: #475569; margin: 0 0 24px 0;">
                Olá, <strong style="color: #1e293b;">{{ $fornecedorNome }}</strong>!
            </p>

            <p style="font-size: 16px; color: #475569; margin: 0 0 24px 0;">
                {{ $total }} {{ $total === 1 ? 'Pedido de Inserção foi concluído' : 'Pedidos de Inserção foram concluídos' }} hoje:
            </p>

            @foreach ($pis as $pi)
                <div class="card" style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px 20px; margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 700; color: #1e293b; font-size: 15px;">PI {{ $pi['numero_pi'] }}</div>
                            <div style="font-size: 13px; color: #64748b; margin-top: 4px;">{{ $pi['cliente'] }}</div>
                        </div>
                        <div style="background: #059669; color: #ffffff; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                            Concluído
                        </div>
                    </div>
                </div>
            @endforeach

            <div style="margin-top: 32px; text-align: center;">
                <a href="{{ route('pis.index') }}" style="display: inline-block; padding: 14px 40px; background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: #ffffff; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 15px; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.4);">
                    Ver Meus PIs
                </a>
            </div>
        </div>

        <div style="padding: 24px 40px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center;">
            <p style="font-size: 13px; color: #94a3b8; margin: 0;">
                Este email foi enviado automaticamente pelo sistema {{ config('app.name') }}.
            </p>
        </div>
    </div>
</body>
</html>