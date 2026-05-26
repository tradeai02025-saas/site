<?php

/* app/Console/Commands/ValidarDocumentos.php */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{DocumentoEnviado, Fornecedor, Pi, Status};
use App\Mail\RelatorioDocumentosFornecedorMail;
use App\Services\MailRotator;
use Carbon\Carbon;
use Illuminate\Support\Facades\{Cache, DB, Log};

class ValidarDocumentos extends Command
{
    protected $signature = 'documentos:validar 
                            {--todos : Notificar sobre todos os documentos, mesmo sem PI ativa}
                            {--sem-email : Apenas valida documentos sem enviar notificações por email}';
    
    protected $description = 'Valida documentos e notifica fornecedores sobre vencimentos e PIs pendentes';

    private $logger;

    public function __construct()
    {
        parent::__construct();
        $this->logger = Log::channel('docs');
    }

    public function handle()
    {
        $this->info('====== INICIANDO VALIDAÇÃO DE DOCUMENTOS ======');
        $this->info('Data/Hora: ' . now()->format('d/m/Y H:i:s'));
        
        $hoje = Carbon::today();
        $duasSemanasFrente = $hoje->copy()->addWeeks(2);
        
        $notificarTodos = $this->option('todos');
        $semEmail = $this->option('sem-email');
        
        if ($semEmail) {
            $this->info('Modo: apenas validação (sem envio de email)');
        }

        if ($notificarTodos) {
            $this->warn('Modo TODOS ativado: notificará sobre documentos mesmo sem PI ativa');
        } else {
            $this->info('Modo padrão: notificará apenas sobre documentos em PIs ativas');
        }
        
        $this->logger->info('====== Comando ValidarDocumentos Iniciado ======', [
            'data_execucao' => now()->toDateTimeString(),
            'data_hoje' => $hoje->toDateString(),
            'data_limite_2semanas' => $duasSemanasFrente->toDateString(),
            'notificar_todos' => $notificarTodos,
            'sem_email' => $semEmail,
        ]);

        $statusAguardandoReenvio = Status::where('tipo', 'documento')
            ->where('nome', 'Aguardando Reenvio')
            ->where('ativo', true)
            ->first();

        try {
            $baseQuery = DocumentoEnviado::with(['fornecedor.user', 'tipoDocumento', 'piDocumentos.pi'])
                ->whereNotNull('data_validade_documento')
                ->whereHas('tipoDocumento', function($q) {
                    $q->where('validar', true)
                      ->where('validade_dias', '>', 0);
                });

            $totalDocumentos = (clone $baseQuery)->count();
            $documentosAtualizados = 0;
            $documentosValidados = 0;
            $documentosInvalidados = 0;
            $pisAfetados = collect();

            $this->info("Total de documentos a processar: {$totalDocumentos}");
            $this->newLine();

            $bar = $this->output->createProgressBar($totalDocumentos);
            $bar->start();

            $baseQuery->chunk(200, function ($lote) use (&$documentosAtualizados, &$documentosValidados, &$documentosInvalidados, &$pisAfetados, $hoje, $statusAguardandoReenvio, $bar) {
                DB::beginTransaction();
                try {
                    foreach ($lote as $documento) {
                        if (!$documento->fornecedor) {
                            $bar->advance();
                            continue;
                        }

                        $dataValidade = Carbon::parse($documento->data_validade_documento);
                        $validoAtual = $documento->valido;
                        
                        $validoNovo = $dataValidade->toDateString() >= $hoje->toDateString();

                        if ($validoAtual != $validoNovo) {
                            $dadosAtualizar = ['valido' => $validoNovo];

                            if (!$validoNovo && $statusAguardandoReenvio) {
                                $statusAtualNome = optional($documento->status)->nome;
                                if ($statusAtualNome === 'Aprovado') {
                                    $dadosAtualizar['status_id'] = $statusAguardandoReenvio->id;
                                }
                            }

                            $documento->update($dadosAtualizar);

                            if (!$validoNovo && $statusAguardandoReenvio && isset($dadosAtualizar['status_id'])) {
                                \App\Models\ValidacaoDocumento::create([
                                    'documento_enviado_id' => $documento->id,
                                    'operacional_id' => null,
                                    'status_id' => $statusAguardandoReenvio->id,
                                    'data_validacao' => now(),
                                    'observacoes' => 'Documento vencido em ' . $dataValidade->format('d/m/Y') . ' — invalidado automaticamente pelo sistema',
                                ]);

                                \App\Models\PiDocumento::where('documento_enviado_id', $documento->id)
                                    ->whereNull('deleted_at')
                                    ->update(['status_id' => $statusAguardandoReenvio->id]);

                                foreach ($documento->piDocumentos as $piDoc) {
                                    if ($piDoc->pi && !$piDoc->pi->concluido) {
                                        $fornecedor = $documento->fornecedor;
                                        if ($fornecedor && $fornecedor->user) {
                                            $chaveCache = "notificacoes_validacao_pi_{$piDoc->pi->id}";
                                            $dadosCache = Cache::get($chaveCache, [
                                                'pi_id' => $piDoc->pi->id,
                                                'pi_numero' => $piDoc->pi->numero_pi,
                                                'fornecedor_id' => $piDoc->pi->fornecedor_id,
                                                'fornecedor_email' => $fornecedor->user->email ?? null,
                                                'fornecedor_nome' => $fornecedor->razao_social,
                                                'cliente_nome' => $piDoc->pi->cliente ? $piDoc->pi->cliente->razao_social : 'N/A',
                                                'documentos_acao' => [],
                                                'email_enviado' => false,
                                            ]);

                                            $dadosCache['documentos_acao'][] = [
                                                'tipo_documento_nome' => optional($documento->tipoDocumento)->nome,
                                                'acao' => 'reprovado',
                                                'motivo' => 'Documento vencido em ' . $dataValidade->format('d/m/Y') . ' — necessário reenvio com documento atualizado.',
                                                'validador_nome' => 'Sistema (automático)',
                                                'data' => now()->toDateTimeString(),
                                            ];

                                            Cache::put($chaveCache, $dadosCache, now()->addMinutes(60));
                                        }
                                    }
                                }
                            }

                            $documentosAtualizados++;
                            
                            if ($validoNovo) {
                                $documentosValidados++;
                            } else {
                                $documentosInvalidados++;

                                foreach ($documento->piDocumentos as $piDoc) {
                                    if ($piDoc->pi && !$piDoc->pi->concluido) {
                                        $pisAfetados->push($piDoc->pi);
                                    }
                                }
                            }

                            $this->logger->info('Documento atualizado', [
                                'documento_id' => $documento->id,
                                'tipo_documento' => optional($documento->tipoDocumento)->nome,
                                'fornecedor' => optional($documento->fornecedor)->razao_social,
                                'data_validade' => $dataValidade->format('d/m/Y'),
                                'valido_antes' => $validoAtual ? 'SIM' : 'NÃO',
                                'valido_agora' => $validoNovo ? 'SIM' : 'NÃO',
                                'status_atualizado' => isset($dadosAtualizar['status_id']) ? 'Aguardando Reenvio' : 'não alterado',
                            ]);
                        }

                        $bar->advance();
                    }

                    DB::commit();
                } catch (\Exception $eLote) {
                    DB::rollBack();
                    $this->logger->error('Erro ao processar lote de documentos', [
                        'error' => $eLote->getMessage(),
                    ]);
                    foreach ($lote as $item) {
                        $bar->advance();
                    }
                }
            });

            $bar->finish();
            $this->newLine(2);

            $this->info("Documentos inválidados nesta execução: {$documentosInvalidados}");

            $pisAtualizados = 0;
            if ($pisAfetados->isNotEmpty()) {
                $pisUnicos = $pisAfetados->unique('id');

                $this->info("Reconciliando status de {$pisUnicos->count()} PI(s) afetado(s)...");

                foreach ($pisUnicos as $pi) {
                    $novoStatus = $pi->atualizarStatusAutomaticamente();
                    if ($novoStatus) {
                        $pisAtualizados++;
                        $this->line("  PI #{$pi->numero_pi}: status atualizado para {$novoStatus}");
                        $this->logger->info('PI status atualizado por vencimento de documento', [
                            'pi_id' => $pi->id,
                            'numero_pi' => $pi->numero_pi,
                            'novo_status' => $novoStatus,
                        ]);
                    }
                }

                $this->info("{$pisAtualizados} PI(s) com status corrigido.");
            }

            if (!$semEmail) {
                $this->info('====== PROCESSANDO NOTIFICAÇÕES POR FORNECEDOR ======');
                $resultadosEmail = $this->processarNotificacoesFornecedores($hoje, $duasSemanasFrente, $notificarTodos);
            }

            $this->newLine();
            $this->info('====== VALIDAÇÃO CONCLUÍDA COM SUCESSO ======');

            $tabela = [
                ['Total de documentos validáveis', $totalDocumentos],
                ['Documentos atualizados', $documentosAtualizados],
                ['Validados (válidos agora)', $documentosValidados],
                ['Invalidados (vencidos agora)', $documentosInvalidados],
                ['PIs reconciliados', $pisAfetados->unique('id')->count()],
            ];

            if (!$semEmail) {
                $tabela[] = ['Fornecedores notificados', $resultadosEmail['enviados']];
                $tabela[] = ['Fornecedores sem pendências', $resultadosEmail['sem_pendencias']];
                $tabela[] = ['Erros no envio', $resultadosEmail['erros']];
            }

            $this->table(['Métrica', 'Quantidade'], $tabela);

            $this->logger->info('====== Comando ValidarDocumentos Concluído ======', [
                'total_documentos' => $totalDocumentos,
                'documentos_atualizados' => $documentosAtualizados,
                'documentos_validados' => $documentosValidados,
                'documentos_invalidados' => $documentosInvalidados,
                'pis_reconciliados' => $pisAfetados->unique('id')->count(),
                'fornecedores_notificados' => $semEmail ? 'N/A' : ($resultadosEmail['enviados'] ?? 0),
                'erros_email' => $semEmail ? 'N/A' : ($resultadosEmail['erros'] ?? 0),
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('ERRO durante a validação: ' . $e->getMessage());
            
            $this->logger->error('====== ERRO no Comando ValidarDocumentos ======', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    private function processarNotificacoesFornecedores(Carbon $hoje, Carbon $duasSemanasFrente, bool $notificarTodos): array
    {
        $emailsEnviados = 0;
        $errosEmail = 0;
        $semPendencias = 0;

        $fornecedores = Fornecedor::with(['user', 'documentosEnviados.tipoDocumento', 'pis.piDocumentos.tipoDocumento'])
            ->whereHas('user')
            ->get();

        $totalFornecedores = $fornecedores->count();
        $this->info("Total de fornecedores a processar: {$totalFornecedores}");

        $barFornecedores = $this->output->createProgressBar($totalFornecedores);
        $barFornecedores->start();

        foreach ($fornecedores as $fornecedor) {
            $dadosRelatorio = $this->montarDadosRelatorio($fornecedor, $hoje, $duasSemanasFrente, $notificarTodos);

            if ($this->deveEnviarEmail($dadosRelatorio)) {
                $resultado = $this->enviarEmailRelatorio($fornecedor, $dadosRelatorio);
                
                if ($resultado) {
                    $emailsEnviados++;
                } else {
                    $errosEmail++;
                }
            } else {
                $semPendencias++;
            }

            $barFornecedores->advance();
        }

        $barFornecedores->finish();
        $this->newLine();

        return [
            'enviados' => $emailsEnviados,
            'sem_pendencias' => $semPendencias,
            'erros' => $errosEmail
        ];
    }

    private function montarDadosRelatorio(Fornecedor $fornecedor, Carbon $hoje, Carbon $duasSemanasFrente, bool $notificarTodos): array
    {
        if ($notificarTodos) {
            $documentosVencidos = $fornecedor->documentosEnviados()
                ->with('tipoDocumento')
                ->where('valido', false)
                ->whereHas('tipoDocumento', function($q) {
                    $q->where('validar', true)
                      ->where('validade_dias', '>', 0);
                })
                ->orderBy('data_validade_documento', 'desc')
                ->get();
        } else {
            $documentosVencidos = $fornecedor->documentosEnviados()
                ->with('tipoDocumento')
                ->where('valido', false)
                ->whereHas('tipoDocumento', function($q) {
                    $q->where('validar', true)
                      ->where('validade_dias', '>', 0);
                })
                ->whereHas('piDocumentos.pi', function($q) {
                    $q->where('concluido', false);
                })
                ->orderBy('data_validade_documento', 'desc')
                ->get();
        }

        if ($notificarTodos) {
            $documentosVencendo = $fornecedor->documentosEnviados()
                ->with('tipoDocumento')
                ->where('valido', true)
                ->whereBetween('data_validade_documento', [$hoje, $duasSemanasFrente])
                ->whereHas('tipoDocumento', function($q) {
                    $q->where('validar', true)
                      ->where('validade_dias', '>', 0);
                })
                ->orderBy('data_validade_documento', 'asc')
                ->get();
        } else {
            $documentosVencendo = $fornecedor->documentosEnviados()
                ->with('tipoDocumento')
                ->where('valido', true)
                ->whereBetween('data_validade_documento', [$hoje, $duasSemanasFrente])
                ->whereHas('tipoDocumento', function($q) {
                    $q->where('validar', true)
                      ->where('validade_dias', '>', 0);
                })
                ->whereHas('piDocumentos.pi', function($q) {
                    $q->where('concluido', false);
                })
                ->orderBy('data_validade_documento', 'asc')
                ->get();
        }

        $pisPendentes = Pi::with(['cliente', 'piDocumentos' => function($q) {
                $q->whereNull('documento_enviado_id')
                  ->with('tipoDocumento');
            }])
            ->where('fornecedor_id', $fornecedor->id)
            ->where('concluido', false) 
            ->whereHas('piDocumentos', function($q) {
                $q->whereNull('documento_enviado_id');
            })
            ->get()
            ->map(function($pi) {
                return [
                    'pi' => $pi,
                    'documentos_pendentes' => $pi->piDocumentos->map(function($piDoc) {
                        return [
                            'tipo_documento' => $piDoc->tipoDocumento,
                        ];
                    })
                ];
            });

        return [
            'documentos_vencidos' => $documentosVencidos,
            'documentos_vencendo' => $documentosVencendo,
            'pis_pendentes' => $pisPendentes,
            'hoje' => $hoje,
            'modo_todos' => $notificarTodos
        ];
    }

    private function deveEnviarEmail(array $dadosRelatorio): bool
    {
        return $dadosRelatorio['documentos_vencidos']->isNotEmpty() ||
               $dadosRelatorio['documentos_vencendo']->isNotEmpty() ||
               $dadosRelatorio['pis_pendentes']->isNotEmpty();
    }

    private function enviarEmailRelatorio(Fornecedor $fornecedor, array $dadosRelatorio): bool
    {
        try {
            if (!$fornecedor->user || empty($fornecedor->user->email)) {
                $this->logger->warning('Fornecedor sem email cadastrado', [
                    'fornecedor_id' => $fornecedor->id,
                    'razao_social' => $fornecedor->razao_social
                ]);
                return false;
            }

            $emailFornecedor = $fornecedor->user->email;

            MailRotator::send(
                new RelatorioDocumentosFornecedorMail($fornecedor, $dadosRelatorio),
                $emailFornecedor
            );

            $this->logger->info('Email de relatório enviado', [
                'fornecedor_id' => $fornecedor->id,
                'fornecedor' => $fornecedor->razao_social,
                'email' => $emailFornecedor,
                'docs_vencidos' => $dadosRelatorio['documentos_vencidos']->count(),
                'docs_vencendo' => $dadosRelatorio['documentos_vencendo']->count(),
                'pis_pendentes' => $dadosRelatorio['pis_pendentes']->count(),
                'modo_todos' => $dadosRelatorio['modo_todos']
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erro ao enviar email de relatório', [
                'fornecedor_id' => $fornecedor->id,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }
}