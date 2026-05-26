<?php

/* app/Console/Commands/NotificarValidacoesDocumentos.php */

namespace App\Console\Commands;

use App\Mail\NotificacaoValidacaoDocumentosMail;
use App\Models\PiDocumento;
use App\Services\MailRotator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Cache, DB, Log};

class NotificarValidacoesDocumentos extends Command
{
    protected $signature = 'documentos:notificar-validacoes';

    protected $description = 'Consolida e notifica fornecedores sobre validações de documentos agrupadas por PI';

    private $logger;

    private const TEMPO_MAX_ESPERA_MINUTOS = 5;

    public function __construct()
    {
        parent::__construct();
        $this->logger = Log::channel('docs');
    }

    public function handle()
    {
        $this->info('====== NOTIFICANDO VALIDACOES DE DOCUMENTOS ======');
        $this->info('Data/Hora: '.now()->format('d/m/Y H:i:s'));

        $this->logger->info('Comando NotificarValidacoesDocumentos Iniciado', [
            'data_execucao' => now()->toDateTimeString(),
        ]);

        $emailsEnviados = 0;
        $emailsJaEnviados = 0;
        $emailsPendentes = 0;
        $errosEmail = 0;

        $chaves = $this->buscarChavesNotificacoes();

        if (empty($chaves)) {
            $this->info('Nenhuma notificacao pendente');

            return Command::SUCCESS;
        }

        $this->info('Total de PIs com notificacoes: '.count($chaves));

        $bar = $this->output->createProgressBar(count($chaves));
        $bar->start();

        foreach ($chaves as $chave) {
            $dados = Cache::get($chave);

            if (! $dados) {
                $this->logger->warning('Chave do cache retornou null', ['chave' => $chave]);
                $bar->advance();

                continue;
            }

            $piId = $dados['pi_id'];
            $piNumero = $dados['pi_numero'] ?? 'N/A';

            if (isset($dados['email_enviado']) && $dados['email_enviado'] === true) {
                $this->logger->info('Email ja enviado para este PI, pulando', [
                    'pi_id' => $piId,
                    'pi_numero' => $piNumero,
                ]);
                $emailsJaEnviados++;
                $bar->advance();

                continue;
            }

            $documentosPendentes = PiDocumento::where('pi_id', $piId)
                ->whereNotNull('documento_enviado_id')
                ->whereHas('documentoEnviado', function ($q) {
                    $q->whereHas('status', function ($sq) {
                        $sq->where('tipo', 'documento')
                            ->where(function ($statusQuery) {
                                $statusQuery->whereRaw('LOWER(nome) = ?', ['pendente'])
                                    ->orWhereRaw('LOWER(nome) LIKE ?', ['%aguardando%validação%']);
                            });
                    });
                })
                ->count();

            $primeiraAcao = ($dados['documentos_acao'] ?? [])[0] ?? null;
            $tempoEsperaMinutos = $primeiraAcao
                ? now()->diffInMinutes(\Carbon\Carbon::parse($primeiraAcao['data']))
                : 0;

            $tempoExcedido = $tempoEsperaMinutos >= self::TEMPO_MAX_ESPERA_MINUTOS;

            if ($documentosPendentes > 0 && ! $tempoExcedido) {
                $this->logger->info('Aguardando - ainda ha documentos pendentes', [
                    'pi_id' => $piId,
                    'pi_numero' => $piNumero,
                    'documentos_pendentes' => $documentosPendentes,
                    'tempo_espera_minutos' => $tempoEsperaMinutos,
                ]);
                $emailsPendentes++;
                $bar->advance();

                continue;
            }

            if ($documentosPendentes > 0) {
                $this->logger->warning('Enviando email com documentos pendentes (tempo excedido)', [
                    'pi_id' => $piId,
                    'pi_numero' => $piNumero,
                    'documentos_pendentes' => $documentosPendentes,
                    'tempo_espera_minutos' => $tempoEsperaMinutos,
                ]);
            }

            $resultado = $this->enviarEmailConsolidado($dados);

            if ($resultado) {
                $dados['email_enviado'] = true;
                $dados['data_envio'] = now()->toDateTimeString();
                Cache::put($chave, $dados, now()->addHours(24));

                $emailsEnviados++;
            } else {
                $errosEmail++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('====== NOTIFICACAO CONCLUIDA ======');
        $this->table(
            ['Metrica', 'Quantidade'],
            [
                ['PIs processados', count($chaves)],
                ['Emails enviados agora', $emailsEnviados],
                ['Emails ja enviados (skip)', $emailsJaEnviados],
                ['Aguardando validacoes', $emailsPendentes],
                ['Erros no envio', $errosEmail],
            ]
        );

        $this->logger->info('Comando NotificarValidacoesDocumentos Concluido', [
            'pis_processados' => count($chaves),
            'emails_enviados' => $emailsEnviados,
            'emails_ja_enviados' => $emailsJaEnviados,
            'emails_pendentes' => $emailsPendentes,
            'erros_email' => $errosEmail,
        ]);

        return Command::SUCCESS;
    }

    private function buscarChavesNotificacoes(): array
    {
        $prefix = 'notificacoes_validacao_pi_';
        $chaves = [];
        $cacheDriver = config('cache.default');

        if ($cacheDriver === 'database') {
            try {
                $cacheTable = config('cache.stores.database.table', 'cache');

                $results = DB::table($cacheTable)
                    ->where('key', 'like', '%'.$prefix.'%')
                    ->where('expiration', '>', now()->timestamp)
                    ->get();

                foreach ($results as $row) {
                    $laravelPrefix = config('cache.prefix');
                    $key = $row->key;

                    if ($laravelPrefix && strpos($key, $laravelPrefix) === 0) {
                        $key = substr($key, strlen($laravelPrefix) + 1);
                    }

                    if (strpos($key, $prefix) === 0) {
                        $chaves[] = $key;
                    }
                }

            } catch (\Exception $e) {
                $this->logger->error('Erro ao buscar chaves Database Cache', [
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif ($cacheDriver === 'redis') {
            try {
                $redis = Cache::getStore()->connection();
                $laravelPrefix = config('cache.prefix');
                $pattern = ($laravelPrefix ? $laravelPrefix.':' : '').$prefix.'*';

                $keys = $redis->keys($pattern);

                foreach ($keys as $key) {
                    $cleanKey = $laravelPrefix
                        ? str_replace($laravelPrefix.':', '', $key)
                        : $key;
                    $chaves[] = $cleanKey;
                }
            } catch (\Exception $e) {
                $this->logger->error('Erro ao buscar chaves Redis', [
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            $cacheDir = storage_path('framework/cache/data');

            if (is_dir($cacheDir)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($cacheDir)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $content = @file_get_contents($file->getPathname());
                        if ($content && strpos($content, $prefix) !== false) {
                            if (preg_match('/notificacoes_validacao_pi_(\d+)/', $content, $matches)) {
                                $chaves[] = $prefix.$matches[1];
                            }
                        }
                    }
                }
                $chaves = array_unique($chaves);
            }
        }

        return $chaves;
    }

    private function enviarEmailConsolidado(array $dados): bool
    {
        try {
            $emailFornecedor = $dados['fornecedor_email'];

            if (empty($emailFornecedor)) {
                $this->logger->warning('Fornecedor sem email cadastrado', [
                    'pi_id' => $dados['pi_id'],
                    'fornecedor' => $dados['fornecedor_nome'],
                ]);

                return false;
            }

            $mailable = new NotificacaoValidacaoDocumentosMail($dados);

            MailRotator::send(
                $mailable,
                $emailFornecedor
            );

            $fornecedorId = $dados['fornecedor_id'] ?? null;
            if ($fornecedorId) {
                enviar_para_representantes(new NotificacaoValidacaoDocumentosMail($dados), $fornecedorId);
            }

            $this->logger->info('Email de notificacao consolidado enviado', [
                'pi_id' => $dados['pi_id'],
                'pi_numero' => $dados['pi_numero'],
                'fornecedor' => $dados['fornecedor_nome'],
                'email' => $emailFornecedor,
                'total_acoes' => count($dados['documentos_acao']),
                'acoes_detalhadas' => collect($dados['documentos_acao'])->groupBy('acao')->map->count()->toArray(),
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erro ao enviar email de notificacao', [
                'pi_id' => $dados['pi_id'],
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}