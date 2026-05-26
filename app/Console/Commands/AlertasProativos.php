<?php

/* app/Console/Commands/AlertasProativos.php */

namespace App\Console\Commands;

use App\Models\{Conversa, DocumentoEnviado, Pi};
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Cache, Log};

class AlertasProativos extends Command
{
    protected $signature = 'telegram:alertas';

    protected $description = 'Verifica condicoes criticas e envia alertas proativos via Telegram';

    public function handle()
    {
        $telegram = app(TelegramService::class);
        $telegram->setContext('telegram:alertas');

        $piChatId = $telegram->getPiChatId();

        Log::channel('telegram')->info('telegram:alertas iniciado', [
            'pendentes_fila' => $telegram->pendingCount(),
        ]);

        $pendentes = $telegram->processQueue();
        if ($pendentes > 0) {
            $this->info("Fila: {$pendentes} mensagem(s) pendente(s) enviada(s).");
        }

        $alertasSistema = [];
        $alertasPi = [];

        $this->info('Verificando condições...');

        $parados = Pi::where('concluido', false)
            ->where('updated_at', '<', now()->subDays(7))
            ->count();

        if ($parados > 0) {
            $key = 'tg_alerta_pis_parados';
            if (!Cache::has($key)) {
                $top = Pi::where('concluido', false)
                    ->where('updated_at', '<', now()->subDays(7))
                    ->orderBy('updated_at', 'asc')
                    ->take(5)
                    ->with(['fornecedor', 'status'])
                    ->get();

                $msg = "🔴 <b>PIs parados</b>\n\n";
                $msg .= "<b>{$parados} PI(s)</b> sem movimentação há 7+ dias\n\n";

                foreach ($top as $pi) {
                    $dias = (int) now()->diffInDays($pi->updated_at);
                    $forn = $pi->fornecedor ? mb_substr($pi->fornecedor->razao_social, 0, 25) : '?';
                    $status = $pi->status ? $pi->status->nome : '?';
                    $msg .= "<b>PI {$pi->numero_pi}</b> — {$dias} dias\n";
                    $msg .= "  {$forn} | {$status}\n\n";
                }

                if ($parados > 5) {
                    $msg .= "<i>... e mais " . ($parados - 5) . " PI(s)</i>\n";
                }

                $msg .= now()->format('d/m/Y H:i:s');
                $alertasPi[] = $msg;

                Cache::put($key, true, now()->addHours(12));
                $this->info("  PIs parados: {$parados} (alerta enviado)");
            } else {
                $this->info("  PIs parados: {$parados} (já alertado nas últimas 12h)");
            }
        } else {
            $this->info("  PIs parados: nenhum");
        }

        $hoje = Carbon::today();
        $vencendo = DocumentoEnviado::where('valido', true)
            ->whereBetween('data_validade_documento', [$hoje, $hoje->copy()->addDays(7)])
            ->count();

        if ($vencendo > 0) {
            $key = 'tg_alerta_docs_vencendo';
            if (!Cache::has($key)) {
                $docs = DocumentoEnviado::where('valido', true)
                    ->whereBetween('data_validade_documento', [$hoje, $hoje->copy()->addDays(7)])
                    ->with(['tipoDocumento', 'fornecedor'])
                    ->orderBy('data_validade_documento', 'asc')
                    ->take(8)
                    ->get();

                $msg = "🟡 <b>Documentos vencendo</b>\n\n";
                $msg .= "<b>{$vencendo} documento(s)</b> vencem nos próximos 7 dias\n\n";

                foreach ($docs as $doc) {
                    $dias = (int) now()->diffInDays($doc->data_validade_documento);
                    $tipo = $doc->tipoDocumento ? mb_substr($doc->tipoDocumento->nome, 0, 20) : '?';
                    $forn = $doc->fornecedor ? mb_substr($doc->fornecedor->razao_social, 0, 20) : '?';
                    $vence = Carbon::parse($doc->data_validade_documento)->format('d/m');
                    $msg .= "<b>{$tipo}</b> — vence {$vence} ({$dias}d)\n";
                    $msg .= "  {$forn}\n\n";
                }

                if ($vencendo > 8) {
                    $msg .= "<i>... e mais " . ($vencendo - 8) . " documento(s)</i>\n";
                }

                $msg .= now()->format('d/m/Y H:i:s');
                $alertasPi[] = $msg;

                Cache::put($key, true, now()->addHours(12));
                $this->info("  Docs vencendo: {$vencendo} (alerta enviado)");
            } else {
                $this->info("  Docs vencendo: {$vencendo} (já alertado nas últimas 12h)");
            }
        } else {
            $this->info("  Docs vencendo: nenhum");
        }

        $loginsFalhados = 0;
        $logFile = storage_path('logs/security/security-' . date('Y-m-d') . '.log');
        foreach ($this->readLastLines($logFile, 500) as $line) {
            if (str_contains($line, 'Tentativa de login falhou')) {
                $loginsFalhados++;
            }
        }

        if ($loginsFalhados >= 10) {
            $key = 'tg_alerta_logins_' . date('Y-m-d');
            if (!Cache::has($key)) {
                $msg = "🔴 <b>Logins falhados</b>\n\n";
                $msg .= "<b>{$loginsFalhados} tentativas</b> de login falharam hoje\n\n";
                $msg .= now()->format('d/m/Y H:i:s');
                $alertasSistema[] = $msg;

                Cache::put($key, true, now()->addHours(6));
                $this->info("  Logins falhados: {$loginsFalhados} (alerta enviado)");
            } else {
                $this->info("  Logins falhados: {$loginsFalhados} (já alertado nas últimas 6h)");
            }
        } else {
            $this->info("  Logins falhados: {$loginsFalhados} (abaixo do limiar)");
        }

        $escaladas = Conversa::where('status_conversa', 'escalada')
            ->where('updated_at', '<', now()->subHours(4))
            ->count();

        if ($escaladas > 0) {
            $key = 'tg_alerta_escaladas';
            if (!Cache::has($key)) {
                $msg = "🟡 <b>Conversas escaladas</b>\n\n";
                $msg .= "<b>{$escaladas} conversa(s)</b> escaladas há mais de 4h sem atendimento\n\n";
                $msg .= now()->format('d/m/Y H:i:s');
                $alertasSistema[] = $msg;

                Cache::put($key, true, now()->addHours(4));
                $this->info("  Escaladas: {$escaladas} (alerta enviado)");
            } else {
                $this->info("  Escaladas: {$escaladas} (já alertado nas últimas 4h)");
            }
        } else {
            $this->info("  Escaladas: nenhuma");
        }

        $totalAlertas = count($alertasSistema) + count($alertasPi);

        if ($totalAlertas === 0) {
            $this->info('Nenhum alerta a enviar.');
            Log::channel('telegram')->info('telegram:alertas concluido sem alertas');
            return Command::SUCCESS;
        }

        $this->info("Enviando {$totalAlertas} alerta(s)...");

        Log::channel('telegram')->info("telegram:alertas enviando", [
            'sistema' => count($alertasSistema),
            'pi' => count($alertasPi),
        ]);

        $enviados = 0;

        foreach ($alertasSistema as $alerta) {
            if ($telegram->send($alerta)) {
                $enviados++;
            } else {
                $telegram->queueMessage($alerta);
                $this->warn('Alerta sistema enfileirado.');
            }
        }

        foreach ($alertasPi as $alerta) {
            if ($telegram->send($alerta, $piChatId)) {
                $enviados++;
            } else {
                $telegram->queueMessage($alerta, $piChatId);
                $this->warn('Alerta PI enfileirado.');
            }
        }

        $this->info("Concluído. {$enviados}/{$totalAlertas} alerta(s) enviado(s).");
        Log::channel('telegram')->info("telegram:alertas concluido: {$enviados}/{$totalAlertas} enviado(s)");
        return Command::SUCCESS;
    }

    private function readLastLines(string $path, int $limit = 500): array
    {
        if (!file_exists($path)) {
            return [];
        }

        try {
            $fileSize = filesize($path);
            if ($fileSize === 0) {
                return [];
            }

            $maxBytes = 256 * 1024;
            $offset = max(0, $fileSize - $maxBytes);

            $handle = @fopen($path, 'r');
            if ($handle === false) {
                return [];
            }

            if ($offset > 0) {
                fseek($handle, $offset);
                fgets($handle);
            }

            $lines = [];
            while (($line = fgets($handle)) !== false) {
                $line = rtrim($line);
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
            fclose($handle);

            if (count($lines) > $limit) {
                $lines = array_slice($lines, -$limit);
            }

            return $lines;
        } catch (\Throwable $e) {
            return [];
        }
    }
}