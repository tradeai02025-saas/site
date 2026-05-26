<?php

/* app/Console/Commands/ConfigurarTelegramBot.php */

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class ConfigurarTelegramBot extends Command
{
    protected $signature = 'telegram:configurar
                            {--remove : Remove o webhook}
                            {--test : Envia mensagem de teste}';

    protected $description = 'Configura o webhook, menu e botoes do bot do Telegram';

    public function handle()
    {
        $telegram = app(TelegramService::class);

        if (!config('services.telegram.bot_token')) {
            $this->error('TELEGRAM_BOT_TOKEN nao configurado no .env');
            return Command::FAILURE;
        }

        if (!config('services.telegram.chat_id')) {
            $this->error('TELEGRAM_CHAT_ID nao configurado no .env');
            $this->info('Dica: adicione o bot ao grupo, envie uma mensagem, e acesse:');
            $this->info('https://api.telegram.org/bot' . config('services.telegram.bot_token') . '/getUpdates');
            $this->info('Procure o campo "chat":{"id": ...} na resposta.');
            return Command::FAILURE;
        }

        if ($this->option('remove')) {
            $result = $telegram->deleteWebhook();
            if ($result['ok'] ?? false) {
                $this->info('Webhook removido com sucesso!');
            } else {
                $this->error('Erro ao remover webhook: ' . ($result['description'] ?? 'desconhecido'));
            }
            return Command::SUCCESS;
        }

        if ($this->option('test')) {
            $appName = config('app.name');

            $msg = "🏢 <b>{$appName}</b>\n";
            $msg .= "━━━━━━━━━━━━━━━━━━━━\n\n";
            $msg .= "✅ <b>Conexao estabelecida com sucesso!</b>\n\n";
            $msg .= "🌐 URL: <code>" . config('app.url') . "</code>\n";
            $msg .= "🕐 " . now()->format('d/m/Y H:i:s');

            $resultado = $telegram->sendWithKeyboard($msg, [
                [
                    ['text' => '📊 Consultas', 'callback_data' => 'menu_consultas'],
                    ['text' => '⚙️ Acoes', 'callback_data' => 'menu_acoes'],
                ],
                [
                    ['text' => '📋 Relatorio Completo', 'callback_data' => 'cmd_relatorio'],
                ],
            ]);

            if ($resultado) {
                $this->info('Mensagem de teste enviada com sucesso!');
            } else {
                $this->error('Falha ao enviar mensagem de teste. Verifique os logs em storage/logs/telegram/');
            }
            return Command::SUCCESS;
        }

        $this->info('Configurando bot do Telegram...');
        $this->info('(pausas entre chamadas para evitar rate limit)');
        $this->newLine();

        $secret = config('services.telegram.webhook_secret');
        if (!$secret) {
            $this->error('TELEGRAM_WEBHOOK_SECRET nao configurado no .env');
            return Command::FAILURE;
        }

        $webhookUrl = config('app.url') . '/api/telegram/webhook/' . $secret;

        $result = $telegram->setWebhook($webhookUrl);
        if ($result['ok'] ?? false) {
            $this->info('  ✓ Webhook configurado');
        } else {
            $this->error('  ✗ Webhook: ' . ($result['description'] ?? 'desconhecido'));
            return Command::FAILURE;
        }

        sleep(2);

        $result = $telegram->setCommandsForGroups();
        if ($result['ok'] ?? false) {
            $this->info('  ✓ Comandos de grupo (apenas /menu)');
        } else {
            $this->error('  ✗ Comandos grupo: ' . ($result['description'] ?? 'desconhecido'));
        }

        sleep(2);

        $result = $telegram->setCommandsForPrivate();
        if ($result['ok'] ?? false) {
            $this->info('  ✓ Comandos privados');
        } else {
            $this->error('  ✗ Comandos privados: ' . ($result['description'] ?? 'desconhecido'));
        }

        sleep(2);

        $result = $telegram->setChatMenuButton();
        if ($result['ok'] ?? false) {
            $this->info('  ✓ Botao de menu (default)');
        } else {
            $this->error('  ✗ Botao menu: ' . ($result['description'] ?? 'desconhecido'));
        }

        $this->newLine();
        $this->info('Configuracao concluida!');
        $this->newLine();

        $this->table(['Item', 'Valor'], [
            ['Bot Token', '***' . substr(config('services.telegram.bot_token'), -6)],
            ['Chat ID', config('services.telegram.chat_id')],
            ['Webhook', $webhookUrl],
            ['Admin IDs', implode(', ', config('services.telegram.admin_user_ids', []))],
            ['Menu Grupo', '/menu (abre painel com botoes)'],
            ['Menu Privado', '/menu, /status, /relatorio, /ajuda'],
        ]);

        $this->newLine();
        $this->info('No grupo: botao Menu → /menu → painel interativo');
        $this->info('No privado (admin): todos os comandos disponiveis');

        return Command::SUCCESS;
    }
}