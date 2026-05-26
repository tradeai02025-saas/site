<?php

/* app/Console/Commands/NotificarDocumentosAvulsos.php */

namespace App\Console\Commands;

use App\Mail\NotificacaoDocumentoAvulsoMail;
use App\Models\{Fornecedor, Pi, PiDocumento};
use App\Services\MailRotator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Cache, DB, Log};

class NotificarDocumentosAvulsos extends Command
{
    protected $signature = 'documentos:notificar-avulsos';

    protected $description = 'Notifica fornecedores sobre novos documentos avulsos adicionados às PIs';

    private $logger;

    private const TEMPO_ESPERA_MINUTOS = 3;

    public function __construct()
    {
        parent::__construct();
        $this->logger = Log::channel('docs');
    }

    public function handle()
    {
        $this->info('====== NOTIFICANDO DOCUMENTOS AVULSOS ======');
        $this->info('Data/Hora: '.now()->format('d/m/Y H:i:s'));

        $this->logger->info('Comando NotificarDocumentosAvulsos Iniciado', [
            'data_execucao' => now()->toDateTimeString(),
        ]);

        $emailsEnviados = 0;
        $emailsJaEnviados = 0;
        $emailsPendentes = 0;
        $errosEmail = 0;

        $chaves = $this->buscarChavesNotificacoes();

        if (empty($chaves)) {
            $this->info('Nenhuma notificacao de documento avulso pendente');

            return Command::SUCCESS;
        }

        $this->info('Total de PIs com documentos avulsos: '.count($chaves));

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

            $primeiroDocumento = $dados['documentos_avulsos'][0] ?? null;
            $tempoEsperaMinutos = $primeiroDocumento
                ? now()->diffInMinutes(\Carbon\Carbon::parse($primeiroDocumento['data_adicao']))
                : 0;

            if ($tempoEsperaMinutos < self::TEMPO_ESPERA_MINUTOS) {
                $this->logger->info('Aguardando tempo de consolidacao', [
                    'pi_id' => $piId,
                    'pi_numero' => $piNumero,
                    'tempo_espera_minutos' => $tempoEsperaMinutos,
                    'tempo_necessario' => self::TEMPO_ESPERA_MINUTOS,
                ]);
                $emailsPendentes++;
                $bar->advance();

                continue;
            }

            $resultado = $this->enviarEmailNotificacao($dados);

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
                ['Aguardando consolidacao', $emailsPendentes],
                ['Erros no envio', $errosEmail],
            ]
        );

        $this->logger->info('Comando NotificarDocumentosAvulsos Concluido', [
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
        $prefix = 'notificacao_avulso_pi_';
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
                $chaves = array_unique($chaves);
            } catch (\Exception $e) {
                $this->logger->error('Erro ao buscar chaves no banco', [
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
                    if ($laravelPrefix && strpos($key, $laravelPrefix) === 0) {
                        $key = substr($key, strlen($laravelPrefix) + 1);
                    }

                    if (strpos($key, $prefix) === 0) {
                        $chaves[] = $key;
                    }
                }
                $chaves = array_unique($chaves);
            } catch (\Exception $e) {
                $this->logger->error('Erro ao buscar chaves no Redis', [
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            $pisAtivas = Pi::where('concluido', false)
                ->whereNotNull('fornecedor_id')
                ->pluck('id');

            foreach ($pisAtivas as $piId) {
                $chave = $prefix.$piId;
                if (Cache::has($chave)) {
                    $chaves[] = $chave;
                }
            }
        }

        return $chaves;
    }

    private function enviarEmailNotificacao(array $dados): bool
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

            MailRotator::send(
                new NotificacaoDocumentoAvulsoMail($dados),
                $emailFornecedor
            );

            $fornecedorId = $dados['fornecedor_id'] ?? null;
            if ($fornecedorId) {
                enviar_para_representantes(new NotificacaoDocumentoAvulsoMail($dados), $fornecedorId);
            }

            $this->logger->info('Email de documento avulso enviado', [
                'pi_id' => $dados['pi_id'],
                'pi_numero' => $dados['pi_numero'],
                'fornecedor' => $dados['fornecedor_nome'],
                'email' => $emailFornecedor,
                'total_documentos' => count($dados['documentos_avulsos']),
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erro ao enviar email de documento avulso', [
                'pi_id' => $dados['pi_id'],
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}