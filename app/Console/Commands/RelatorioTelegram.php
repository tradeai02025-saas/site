<?php

/* app/Console/Commands/RelatorioTelegram.php */

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RelatorioTelegram extends Command
{
    protected $signature = 'telegram:relatorio';

    protected $description = 'Envia relatorio completo do sistema via Telegram';

    public function handle()
    {
        $this->info('Enviando relatório via Telegram...');

        try {
            $telegram = app(TelegramService::class);
            $telegram->setContext('telegram:relatorio');

            $controller = app(\App\Http\Controllers\TelegramWebhookController::class);
            $piChatId = $telegram->getPiChatId();

            Log::channel('telegram')->info('telegram:relatorio iniciado', [
                'destino' => $piChatId ? 'grupo_pi' : 'grupo_sistema',
                'pendentes_fila' => $telegram->pendingCount(),
            ]);

            $mensagem = $controller->buildRelatorioGeral();

            $resultado = $telegram->sendOrQueue($mensagem, $piChatId);

            if ($resultado) {
                $this->info('Relatório enviado com sucesso!');
                return Command::SUCCESS;
            }

            $this->warn('Relatório enfileirado para envio posterior (rate limit ativo).');
            Log::channel('telegram')->warning('telegram:relatorio enfileirado - rate limit ativo');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Erro ao enviar relatório: ' . $e->getMessage());
            Log::channel('telegram')->error('telegram:relatorio exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}