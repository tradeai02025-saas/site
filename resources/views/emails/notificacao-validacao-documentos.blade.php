{{-- resources/views/emails/notificacao-validacao-documentos.blade.php --}}

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Notificação de Validação - {{ config('app.name') }}</title>
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
    
    <span class="preheader">{{ $temReprovados ? 'Documentos reprovados' : 'Reenvio solicitado' }} - PI {{ $piNumero }}</span>
    
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 24px 64px rgba(0, 0, 0, 0.3);">
        
        <div style="background: linear-gradient(135deg, {{ $temReprovados ? '#dc2626 0%, #ef4444 100%' : '#f59e0b 0%, #f97316 100%' }}); padding: 50px 40px; text-align: center; position: relative;">
            <div style="width: 140px; height: 140px; margin: 0 auto 24px; background: rgba(255, 255, 255, 0.95); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2); padding: 20px;">
                <img src="{{asset('img/favicons/favicon-192.png')}}" alt="Logo {{ config('app.name') }}" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            <h1 class="header-title" style="color: #ffffff; font-size: 32px; font-weight: 700; margin: 0 0 12px; text-shadow: 0 2px 12px rgba(0, 0, 0, 0.2); letter-spacing: -0.5px;">
                {{ $temReprovados ? 'Documentos Reprovados' : 'Reenvio Solicitado' }}
            </h1>
            <p style="color: rgba(255, 255, 255, 0.92); font-size: 18px; margin: 0; font-weight: 600;">PI: {{ $piNumero }}</p>
            <p style="color: rgba(255, 255, 255, 0.85); font-size: 15px; margin: 8px 0 0 0; font-weight: 400;">{{ $clienteNome }}</p>
        </div>

        <div class="content-padding" style="padding: 50px 40px; background: #ffffff;">
            <p style="font-size: 20px; font-weight: 600; color: #1e293b; margin: 0 0 24px;">Prezado {{ $fornecedorNome }},</p>

            <div style="background: linear-gradient(135deg, {{ $temReprovados ? 'rgba(239, 68, 68, 0.08) 0%, rgba(239, 68, 68, 0.04) 100%' : 'rgba(245, 158, 11, 0.08) 0%, rgba(245, 158, 11, 0.04) 100%' }}); border: 2px solid {{ $temReprovados ? 'rgba(239, 68, 68, 0.2)' : 'rgba(245, 158, 11, 0.2)' }}; border-left: 5px solid {{ $temReprovados ? '#ef4444' : '#f59e0b' }}; border-radius: 12px; padding: 18px 22px; margin: 0 0 30px 0;">
                <p style="font-size: 15px; color: #1e293b; margin: 0 0 8px; font-weight: 700;">{{ $temReprovados ? 'AÇÃO NECESSÁRIA' : 'REENVIO SOLICITADO' }}</p>
                <p style="font-size: 14px; color: #64748b; margin: 0; line-height: 1.6;">
                    A equipe de validação finalizou a análise dos documentos da PI <strong>{{ $piNumero }}</strong>. 
                    {{ $temReprovados ? 'Alguns documentos foram reprovados e o' : 'Foi solicitado o' }} reenvio {{ $temReprovados ? 'é necessário' : 'de alguns documentos' }}.
                </p>
            </div>

            <div style="background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 24px; margin: 0 0 30px 0;">
                <div style="margin-bottom: 12px;">
                    <span style="font-size: 14px; font-weight: 600; color: #64748b;">Total de Ações:</span>
                    <span style="font-size: 18px; font-weight: 700; color: #0088b8; margin-left: 8px;">{{ $totalAcoes }}</span>
                </div>
                @if($documentosReprovados->isNotEmpty())
                <div style="margin-bottom: 12px;">
                    <span style="font-size: 14px; font-weight: 600; color: #64748b;">Reprovados:</span>
                    <span style="font-size: 18px; font-weight: 700; color: #dc2626; margin-left: 8px;">{{ $documentosReprovados->count() }}</span>
                </div>
                @endif
                @if($documentosReenvio->isNotEmpty())
                <div>
                    <span style="font-size: 14px; font-weight: 600; color: #64748b;">Reenvio Solicitado:</span>
                    <span style="font-size: 18px; font-weight: 700; color: #f59e0b; margin-left: 8px;">{{ $documentosReenvio->count() }}</span>
                </div>
                @endif
            </div>

            @if($documentosReprovados->isNotEmpty())
            <div style="margin-bottom: 40px;">
                <h2 style="font-size: 18px; font-weight: 700; color: #dc2626; margin: 0 0 20px 0; padding-bottom: 12px; border-bottom: 3px solid #dc2626;">
                    Documentos Reprovados ({{ $documentosReprovados->count() }})
                </h2>
                
                @foreach($documentosReprovados as $doc)
                <div class="card" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.05) 0%, rgba(239, 68, 68, 0.02) 100%); border: 2px solid rgba(239, 68, 68, 0.3); border-left: 5px solid #dc2626; border-radius: 10px; padding: 16px 20px; margin-bottom: 12px;">
                    <div style="margin-bottom: 12px;">
                        <div style="font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">{{ $doc['tipo_documento_nome'] }}</div>
                        <div style="background: #dc2626; color: #ffffff; padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block;">REPROVADO</div>
                    </div>
                    <div style="background: rgba(239, 68, 68, 0.08); padding: 12px; border-radius: 8px; margin-bottom: 10px;">
                        <div style="font-size: 13px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Motivo da Reprovação:</div>
                        <div style="font-size: 14px; color: #1e293b; line-height: 1.5;">{{ $doc['motivo'] }}</div>
                    </div>
                    <div style="font-size: 13px; color: #64748b; line-height: 1.5;">
                        <strong style="color: #1e293b;">Validado por:</strong> {{ $doc['validador_nome'] }}<br>
                        <strong style="color: #1e293b;">Data:</strong> {{ $doc['data_formatada'] }}
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            @if($documentosReenvio->isNotEmpty())
            <div style="margin-bottom: 40px;">
                <h2 style="font-size: 18px; font-weight: 700; color: #f59e0b; margin: 0 0 20px 0; padding-bottom: 12px; border-bottom: 3px solid #f59e0b;">
                    Reenvio Solicitado ({{ $documentosReenvio->count() }})
                </h2>
                
                @foreach($documentosReenvio as $doc)
                <div class="card" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, rgba(245, 158, 11, 0.02) 100%); border: 2px solid rgba(245, 158, 11, 0.3); border-left: 5px solid #f59e0b; border-radius: 10px; padding: 16px 20px; margin-bottom: 12px;">
                    <div style="margin-bottom: 12px;">
                        <div style="font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">{{ $doc['tipo_documento_nome'] }}</div>
                        <div style="background: #f59e0b; color: #ffffff; padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block;">REENVIAR</div>
                    </div>
                    <div style="background: rgba(245, 158, 11, 0.08); padding: 12px; border-radius: 8px; margin-bottom: 10px;">
                        <div style="font-size: 13px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Solicitação:</div>
                        <div style="font-size: 14px; color: #1e293b; line-height: 1.5;">{{ $doc['motivo'] }}</div>
                    </div>
                    <div style="font-size: 13px; color: #64748b; line-height: 1.5;">
                        <strong style="color: #1e293b;">Solicitado por:</strong> {{ $doc['validador_nome'] }}<br>
                        <strong style="color: #1e293b;">Data:</strong> {{ $doc['data_formatada'] }}
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <div style="text-align: center; margin: 40px 0 30px 0;">
                <a href="{{ $urlReenvio }}" class="button-cta" style="display: inline-block; background: linear-gradient(135deg, {{ $temReprovados ? '#dc2626 0%, #ef4444 100%' : '#f59e0b 0%, #f97316 100%' }}); color: #ffffff !important; text-decoration: none; padding: 16px 40px; border-radius: 14px; font-weight: 600; font-size: 15px; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 8px 24px {{ $temReprovados ? 'rgba(220, 38, 38, 0.4)' : 'rgba(245, 158, 11, 0.4)' }}; margin: 8px;">
                    Reenviar Documentos
                </a>
                
                <a href="{{ route('chat.index') }}" class="button-cta" style="display: inline-block; background: linear-gradient(135deg, #0088b8 0%, #005e82 100%); color: #ffffff !important; text-decoration: none; padding: 16px 40px; border-radius: 14px; font-weight: 600; font-size: 15px; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 8px 24px rgba(0, 136, 184, 0.4); margin: 8px;">
                    Conversar com Equipe
                </a>
            </div>

            <hr style="border: 0; height: 1px; background: linear-gradient(90deg, transparent, #e2e8f0, transparent); margin: 30px 0;">

            <div style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(59, 130, 246, 0.04) 100%); border: 2px solid rgba(59, 130, 246, 0.2); border-left: 5px solid #3b82f6; border-radius: 12px; padding: 18px 22px; margin: 0;">
                <p style="font-size: 14px; color: #64748b; margin: 0 0 8px 0; line-height: 1.6;">
                    <strong style="color: #1e293b;">Próximos Passos:</strong>
                </p>
                <p style="font-size: 14px; color: #64748b; margin: 0; line-height: 1.6;">
                    1. Acesse o sistema e revise os motivos das {{ $temReprovados ? 'reprovações' : 'solicitações' }}<br>
                    2. Corrija os documentos conforme orientações da equipe<br>
                    3. Reenvie os documentos através do sistema<br>
                    4. Em caso de dúvidas, entre em contato via chat
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
               {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
            </p>
        </div>
    </div>
</body>
</html>