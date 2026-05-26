{{-- resources/views/emails/relatorio-documentos-fornecedor.blade.php --}}

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Relatório de Documentação - {{ config('app.name') }}</title>
</head>

<body
    style="margin: 0; padding: 40px 20px; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif; line-height: 1.6; color: #1e293b; background: linear-gradient(135deg, #0a192f 0%, #003d56 50%, #005e82 100%); -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">
    <div
        style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 24px 64px rgba(0, 0, 0, 0.3);">
        
        <div
            style="background: linear-gradient(135deg, {{ $temUrgencia ? '#dc2626 0%, #ef4444 100%' : '#005e82 0%, #0088b8 100%' }}); padding: 50px 40px; text-align: center; position: relative;">
            <div
                style="width: 140px; height: 140px; margin: 0 auto 24px; background: rgba(255, 255, 255, 0.95); border-radius: 50%; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10px); box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2); padding: 20px;">
                <img src="{{ asset('img/favicons/favicon-192.png') }}" alt="{{ config('app.name') }} Logo"
                    style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            <h1
                style="color: #ffffff; font-size: 32px; font-weight: 700; margin: 0 0 12px; text-shadow: 0 2px 12px rgba(0, 0, 0, 0.2); letter-spacing: -0.5px;">
                {{ $temUrgencia ? 'Atenção Necessária' : 'Relatório de Documentação' }}
            </h1>
            <p style="color: rgba(255, 255, 255, 0.92); font-size: 16px; margin: 0; font-weight: 400;">
                {{ $fornecedor->razao_social }}</p>
            <p style="color: rgba(255, 255, 255, 0.85); font-size: 14px; margin: 8px 0 0 0; font-weight: 400;">
                {{ $hoje->format('d/m/Y') }}</p>
        </div>

        
        <div style="padding: 50px 40px; background: #ffffff;">
            <p style="font-size: 20px; font-weight: 600; color: #1e293b; margin: 0 0 24px;">Prezado Fornecedor,</p>

            @if ($temUrgencia)
                <div
                    style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, rgba(239, 68, 68, 0.04) 100%); border: 2px solid rgba(239, 68, 68, 0.2); border-left: 5px solid #ef4444; border-radius: 12px; padding: 18px 22px; margin: 0 0 30px 0;">
                    <p style="font-size: 15px; color: #1e293b; margin: 0 0 8px; font-weight: 700;">AÇÃO URGENTE
                        NECESSÁRIA</p>
                    <p style="font-size: 14px; color: #64748b; margin: 0; line-height: 1.6;">
                        Há documentos vencidos ou prestes a vencer que requerem sua atenção imediata. A não
                        regularização pode impedir sua participação em processos.
                    </p>
                </div>
            @endif

            
            <div
                style="background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 24px; margin: 0 0 30px 0;">
                <div style="margin-bottom: 12px;">
                    <span style="font-size: 14px; font-weight: 600; color: #64748b;">Documentos Vencidos:</span>
                    <span
                        style="font-size: 18px; font-weight: 700; color: #dc2626; margin-left: 8px;">{{ $documentosVencidos->count() }}</span>
                </div>
                <div style="margin-bottom: 12px;">
                    <span style="font-size: 14px; font-weight: 600; color: #64748b;">Documentos Vencendo:</span>
                    <span
                        style="font-size: 18px; font-weight: 700; color: #f59e0b; margin-left: 8px;">{{ $documentosVencendo->count() }}</span>
                </div>
                <div>
                    <span style="font-size: 14px; font-weight: 600; color: #64748b;">PIs Pendentes:</span>
                    <span
                        style="font-size: 18px; font-weight: 700; color: #0088b8; margin-left: 8px;">{{ $pisPendentes->count() }}</span>
                </div>
            </div>

            @if ($documentosVencidos->isNotEmpty())
                
                <div style="margin-bottom: 40px;">
                    <h2
                        style="font-size: 18px; font-weight: 700; color: #dc2626; margin: 0 0 20px 0; padding-bottom: 12px; border-bottom: 3px solid #dc2626;">
                        Documentos Vencidos ({{ $documentosVencidos->count() }})
                    </h2>

                    @foreach ($documentosVencidos as $doc)
                        <div
                            style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.05) 0%, rgba(239, 68, 68, 0.02) 100%); border: 2px solid rgba(239, 68, 68, 0.3); border-left: 5px solid #dc2626; border-radius: 10px; padding: 16px 20px; margin-bottom: 12px;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                                <div style="font-size: 16px; font-weight: 700; color: #1e293b;">
                                    {{ $doc->tipoDocumento->nome }}</div>
                                <div
                                    style="background: #dc2626; color: #ffffff; padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                    VENCIDO</div>
                            </div>
                            <div style="font-size: 14px; color: #64748b; line-height: 1.5;">
                                <strong style="color: #1e293b;">Venceu em:</strong>
                                {{ $doc->data_validade_formatada }}<br>
                                <strong style="color: #1e293b;">Há:</strong> {{ $doc->dias_vencido }}
                                {{ $doc->dias_vencido == 1 ? 'dia' : 'dias' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($documentosVencendo->isNotEmpty())
                
                <div style="margin-bottom: 40px;">
                    <h2
                        style="font-size: 18px; font-weight: 700; color: #f59e0b; margin: 0 0 20px 0; padding-bottom: 12px; border-bottom: 3px solid #f59e0b;">
                        Documentos Vencendo em Breve ({{ $documentosVencendo->count() }})
                    </h2>

                    @foreach ($documentosVencendo as $doc)
                        <div
                            style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, rgba(245, 158, 11, 0.02) 100%); border: 2px solid rgba(245, 158, 11, 0.3); border-left: 5px solid {{ $doc->is_urgente ? '#ef4444' : '#f59e0b' }}; border-radius: 10px; padding: 16px 20px; margin-bottom: 12px;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                                <div style="font-size: 16px; font-weight: 700; color: #1e293b;">
                                    {{ $doc->tipoDocumento->nome }}</div>
                                <div
                                    style="background: {{ $doc->is_urgente ? '#ef4444' : '#f59e0b' }}; color: #ffffff; padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                    {{ $doc->dias_restantes }} {{ $doc->dias_restantes == 1 ? 'DIA' : 'DIAS' }}</div>
                            </div>
                            <div style="font-size: 14px; color: #64748b; line-height: 1.5;">
                                <strong style="color: #1e293b;">Vence em:</strong> {{ $doc->data_validade_formatada }}
                                @if ($doc->is_urgente)
                                    <br><strong style="color: #dc2626;">RENOVAÇÃO URGENTE!</strong>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($pisPendentes->isNotEmpty())
                
                <div style="margin-bottom: 40px;">
                    <h2
                        style="font-size: 18px; font-weight: 700; color: #0088b8; margin: 0 0 20px 0; padding-bottom: 12px; border-bottom: 3px solid #0088b8;">
                        PIs com Documentos Pendentes ({{ $pisPendentes->count() }})
                    </h2>

                    @foreach ($pisPendentes as $piData)
                        <div
                            style="background: linear-gradient(135deg, rgba(0, 136, 184, 0.05) 0%, rgba(0, 136, 184, 0.02) 100%); border: 2px solid rgba(0, 136, 184, 0.3); border-left: 5px solid #0088b8; border-radius: 10px; padding: 16px 20px; margin-bottom: 12px;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                                <div style="font-size: 16px; font-weight: 700; color: #1e293b;">PI:
                                    {{ $piData['pi']->numero_pi }}</div>
                                <div
                                    style="background: #0088b8; color: #ffffff; padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                    {{ $piData['documentos_pendentes']->count() }}
                                    {{ $piData['documentos_pendentes']->count() == 1 ? 'DOC' : 'DOCS' }}</div>
                            </div>
                            <div style="font-size: 14px; color: #64748b; line-height: 1.5; margin-bottom: 10px;">
                                <strong style="color: #1e293b;">Cliente:</strong>
                                {{ $piData['pi']->cliente->razao_social }}
                            </div>
                            <div style="font-size: 14px; color: #64748b; line-height: 1.5;">
                                <strong style="color: #1e293b;">Documentos necessários:</strong>
                                <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                                    @foreach ($piData['documentos_pendentes'] as $docPendente)
                                        <li style="margin-bottom: 4px; color: #475569;">
                                            {{ $docPendente['tipo_documento']->nome }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            
            <div style="text-align: center; margin: 40px 0 30px 0;">
                @if ($documentosVencidos->isNotEmpty() || $documentosVencendo->isNotEmpty())
                    <a href="{{ route('fornecedor.docs_enviar.index') }}"
                        style="display: inline-block; background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); color: #ffffff !important; text-decoration: none; padding: 16px 40px; border-radius: 14px; font-weight: 600; font-size: 15px; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 8px 24px rgba(220, 38, 38, 0.4); margin: 8px;">Enviar
                        Documentos</a>
                @endif

                <a href="{{ route('fornecedor.documentos.index') }}"
                    style="display: inline-block; background: linear-gradient(135deg, #64748b 0%, #475569 100%); color: #ffffff !important; text-decoration: none; padding: 16px 40px; border-radius: 14px; font-weight: 600; font-size: 15px; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 8px 24px rgba(100, 116, 139, 0.4); margin: 8px;">Ver
                    Meus Documentos</a>
            </div>

            <hr
                style="border: 0; height: 1px; background: linear-gradient(90deg, transparent, #e2e8f0, transparent); margin: 30px 0;">

            <div
                style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(59, 130, 246, 0.04) 100%); border: 2px solid rgba(59, 130, 246, 0.2); border-left: 5px solid #3b82f6; border-radius: 12px; padding: 18px 22px; margin: 0;">
                <p style="font-size: 14px; color: #64748b; margin: 0; line-height: 1.6;">
                    <strong style="color: #1e293b;">Importante:</strong> Mantenha sua documentação sempre atualizada
                    para evitar interrupções nos processos de suas PIs.
                </p>
            </div>
        </div>

        
        <div style="background: #f8fafc; padding: 40px; text-align: center; border-top: 1px solid #e2e8f0;">
            <p style="font-size: 20px; font-weight: 700; color: #005e82; margin: 0 0 12px;">{{ config('app.name') }}
            </p>
            <p style="font-size: 13px; color: #64748b; margin: 8px 0;">Este é um email automático</p>
            <p style="font-size: 13px; color: #64748b; margin: 8px 0;">Por favor, não responda a este email.</p>

            <div style="margin-top: 20px;">
                <a href="{{ route('login') }}"
                    style="color: #005e82; text-decoration: none; font-size: 13px; font-weight: 500; margin: 0 12px;">Acessar
                    Sistema</a>
            </div>

            <p style="margin-top: 24px; font-size: 12px; color: #64748b; margin: 8px 0;">
                {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
            </p>
        </div>
    </div>
</body>

</html>
