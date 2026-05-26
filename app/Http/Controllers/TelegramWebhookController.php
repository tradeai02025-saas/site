<?php

/* app/Http/Controllers/TelegramWebhookController.php */

namespace App\Http\Controllers;

use App\Models\{Conversa, DocumentoEnviado, Mensagem, Pi, PiDocumento, User};
use App\Services\{MailRotator, TelegramService};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Artisan, Cache, DB, Log};
use Spatie\Activitylog\Models\Activity;

class TelegramWebhookController extends Controller
{
    private TelegramService $telegram;
    private array $adminUserIds;
    private array $authorizedChatIds;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
        $this->adminUserIds = config('services.telegram.admin_user_ids', []);
        $this->authorizedChatIds = array_filter([
            config('services.telegram.chat_id', ''),
            config('services.telegram.pi_chat_id', ''),
        ]);
    }

    public function handle(Request $request, string $token)
    {
        if ($token !== config('services.telegram.webhook_secret')) {
            abort(404);
        }

        $headerSecret = $request->header('X-Telegram-Bot-Api-Secret-Token');
        if ($headerSecret && $headerSecret !== config('services.telegram.webhook_secret')) {
            abort(404);
        }

        $update = $request->all();

        if (isset($update['callback_query'])) {
            return $this->handleCallback($update['callback_query']);
        }

        if (isset($update['message']['text'])) {
            return $this->handleMessage($update['message']);
        }

        return response()->json(['ok' => true]);
    }

    private function handleMessage(array $message)
    {
        $chatId = (string) ($message['chat']['id'] ?? '');
        $userId = (string) ($message['from']['id'] ?? '');
        $text = trim($message['text'] ?? '');
        $userName = $message['from']['first_name'] ?? 'Usuario';
    
        $isGroup = in_array($chatId, $this->authorizedChatIds);
        $isAdminPrivate = !in_array($chatId, $this->authorizedChatIds) && $this->isAdmin($userId);
    
        if (!$isGroup && !$isAdminPrivate) {
            return response()->json(['ok' => true]);
        }
    
        $quota = $this->telegram->getChatRemainingQuota($chatId);
    
        if ($quota['exhausted']) {
            Log::channel('telegram_ratelimit')->warning('MESSAGE_BLOCKED_QUOTA', [
                'chat_id' => $chatId,
                'user_id' => $userId,
                'text' => mb_substr($text, 0, 30),
                'quota' => $quota,
            ]);
    
            return response()->json(['ok' => true]);
        }
    
        $replyTo = $chatId;
        $command = strtolower(explode(' ', str_replace('@', ' ', $text))[0]);
    
        try {
            switch ($command) {
                case '/menu':
                case '/start':
                case '/ajuda':
                    $this->telegram->sendWithKeyboard($this->buildMainMenuText($userName), $this->keyboardMainMenu(), $replyTo);
                    break;
                case '/status':
                    $this->telegram->sendWithKeyboard($this->buildStatus(), $this->keyboardVoltarConsultas(), $replyTo);
                    break;
                case '/relatorio':
                    $this->telegram->sendWithKeyboard($this->buildRelatorioGeral(), $this->keyboardVoltarRelatorios(), $replyTo);
                    break;
            }
        } catch (\Exception $e) {
            Log::channel('telegram')->error('Erro ao processar comando', ['command' => $command, 'error' => $e->getMessage()]);
            $this->telegram->send("<b>Erro ao processar comando.</b>\nDetalhes no log do servidor.", $replyTo);
        }

        return response()->json(['ok' => true]);
    }

    private function handleCallback(array $callback)
    {
        $chatId = (string) ($callback['message']['chat']['id'] ?? '');
        $userId = (string) ($callback['from']['id'] ?? '');
        $messageId = (int) ($callback['message']['message_id'] ?? 0);
        $data = $callback['data'] ?? '';
        $userName = $callback['from']['first_name'] ?? 'Usuario';
        $callbackId = $callback['id'] ?? '';

        $isGroup = in_array($chatId, $this->authorizedChatIds);
        $isAdminPrivate = !in_array($chatId, $this->authorizedChatIds) && $this->isAdmin($userId);

        if (!$isGroup && !$isAdminPrivate) {
            $this->telegram->answerCallback($callbackId);
            return response()->json(['ok' => true]);
        }

        $pendingKey = $isGroup ? $chatId . '_' . $userId : $chatId;

        $resumeResult = $this->checkAndResumePending($pendingKey, $chatId, $callbackId, $messageId, $userName, $userId);
        if ($resumeResult !== null) {
            return $resumeResult;
        }

        $quota = $this->telegram->getChatRemainingQuota($chatId);

        if ($quota['exhausted']) {
            $this->telegram->savePendingAction($pendingKey, [
                'data' => $data,
                'user_name' => $userName,
                'user_id' => $userId,
                'saved_at' => now()->toIso8601String(),
            ]);

            $this->telegram->answerCallback(
                $callbackId,
                "Limite de uso atingido ({$quota['count']}/{$quota['limit']}). Aguarde {$quota['reset_in']}s, vou retomar automaticamente na sua próxima interação.",
                true
            );

            Log::channel('telegram_ratelimit')->info('CALLBACK_BLOCKED_QUOTA', [
                'chat_id' => $chatId,
                'user_id' => $userId,
                'pending_key' => $pendingKey,
                'data' => $data,
                'quota' => $quota,
            ]);

            return response()->json(['ok' => true]);
        }

        if ($quota['near_limit']) {
            $this->telegram->answerCallback(
                $callbackId,
                "Uso: {$quota['count']}/{$quota['limit']} neste minuto. Reduzindo o ritmo para evitar bloqueio."           
            );
        } else {
            $this->telegram->answerCallback($callbackId);
        }

        try {
            $this->dispatchCallback($data, $chatId, $messageId, $userName, $userId);
        } catch (\Exception $e) {
            Log::channel('telegram')->error('Erro callback', ['data' => $data, 'error' => $e->getMessage()]);
            $this->telegram->editMessage($chatId, $messageId, "<b>Erro ao processar ação.</b>\nDetalhes no log do servidor.", $this->keyboardMenuPrincipal());
        }

        return response()->json(['ok' => true]);
    }

    private function checkAndResumePending(string $pendingKey, string $chatId, string $callbackId, int $messageId, string $userName, string $userId)
    {
        $pending = $this->telegram->getPendingAction($pendingKey);

        if (!$pending) {
            return null;
        }

        $quota = $this->telegram->getChatRemainingQuota($chatId);

        if ($quota['exhausted']) {
            $this->telegram->answerCallback(
                $callbackId,
                "Limite ainda ativo. Aguarde {$quota['reset_in']}s para continuar.",
                true
            );

            return response()->json(['ok' => true]);
        }

        $this->telegram->clearPendingAction($pendingKey);

        $pendingData = $pending['data'];
        $pendingUserName = $pending['user_name'] ?? $userName;
        $pendingUserId = $pending['user_id'] ?? $userId;

        $this->telegram->answerCallback($callbackId, "Limite renovado! Retomando de onde paramos...");

        Log::channel('telegram_ratelimit')->info('PENDING_ACTION_RESUMED', [
            'chat_id' => $chatId,
            'user_id' => $userId,
            'pending_key' => $pendingKey,
            'action' => $pendingData,
        ]);

        try {
            $this->dispatchCallback($pendingData, $chatId, $messageId, $pendingUserName, $pendingUserId);
        } catch (\Exception $e) {
            Log::channel('telegram')->error('Erro ao retomar ação pendente', [
                'data' => $pendingData,
                'error' => $e->getMessage(),
            ]);
            $this->telegram->editMessage($chatId, $messageId, "<b>Erro ao retomar ação.</b>\nDetalhes no log do servidor.", $this->keyboardMenuPrincipal());
        }

        return response()->json(['ok' => true]);
    }

    private function dispatchCallback(string $data, string $chatId, int $messageId, string $userName, string $userId): void
    {
        if (str_starts_with($data, 'menu_')) {
            $this->handleMenuCallback($data, $chatId, $messageId, $userName, $userId);
        } elseif (str_starts_with($data, 'cmd_')) {
            $this->handleCommandCallback($data, $chatId, $messageId);
        } elseif (str_starts_with($data, 'act_')) {
            $this->handleActionCallback($data, $chatId, $messageId, $userId, $userName);
        } elseif (str_starts_with($data, 'confirm_')) {
            $this->handleConfirmCallback($data, $chatId, $messageId, $userId, $userName);
        } elseif ($data === 'cancel_action') {
            $this->telegram->editMessage($chatId, $messageId, "<b>Ação cancelada.</b>", $this->keyboardMenuPrincipal());
        }
    }

    private function handleMenuCallback(string $data, string $chatId, int $messageId, string $userName, string $userId): void
    {
        switch ($data) {
            case 'menu_principal':
                $this->telegram->editMessage($chatId, $messageId, $this->buildMainMenuText($userName), $this->keyboardMainMenu());
                break;

            case 'menu_consultas':
                $this->telegram->editMessage($chatId, $messageId, "<b>Consultas</b>\n\nSelecione o que deseja ver:", $this->keyboardConsultas());
                break;

            case 'menu_relatorios':
                $this->telegram->editMessage($chatId, $messageId, "<b>Relatórios</b>\n\nEscolha o relatório:", $this->keyboardRelatorios());
                break;

            case 'menu_acoes':
                if (!$this->isAdmin($userId)) {
                    $this->telegram->editMessage($chatId, $messageId, "<b>Acesso restrito</b>\n\nApenas administradores autorizados.", $this->keyboardMenuPrincipal());
                    return;
                }
                $this->telegram->editMessage($chatId, $messageId, "<b>Ações do sistema</b>\n\nSelecione a ação.\n<i>Todas pedem confirmação.</i>", $this->keyboardAcoes());
                break;
        }
    }

    private function handleCommandCallback(string $data, string $chatId, int $messageId): void
    {
        $command = str_replace('cmd_', '', $data);

        $text = match ($command) {
            'status' => $this->buildStatus(),
            'emails' => $this->buildEmails(),
            'pis' => $this->buildPis(),
            'docs' => $this->buildDocs(),
            'users' => $this->buildUsers(),
            'erros' => $this->buildErros(),
            'chat' => $this->buildChat(),
            'relatorio' => $this->buildRelatorioGeral(),
            'pis_detalhado' => $this->buildPisDetalhado(),
            'docs_detalhado' => $this->buildDocsDetalhado(),
            default => 'Comando desconhecido.',
        };

        $backKeyboard = match ($command) {
            'relatorio', 'pis_detalhado', 'docs_detalhado' => $this->keyboardVoltarRelatorios(),
            default => $this->keyboardVoltarConsultas(),
        };

        if (mb_strlen($text) > 3900) {
            $this->telegram->editMessage($chatId, $messageId, "<i>Gerando...</i>", $this->keyboardMenuPrincipal());
            $this->telegram->sendWithKeyboard($text, $backKeyboard, $chatId);
        } else {
            $this->telegram->editMessage($chatId, $messageId, $text, $backKeyboard);
        }
    }

    private function handleActionCallback(string $data, string $chatId, int $messageId, string $userId, string $userName): void
    {
        if (!$this->isAdmin($userId)) {
            $this->telegram->editMessage($chatId, $messageId, "<b>Sem permissão.</b>", $this->keyboardMenuPrincipal());
            return;
        }

        $action = str_replace('act_', '', $data);
        $labels = [
            'cache_limpar' => 'Limpar todos os caches',
            'cache_rebuild' => 'Reconstruir todos os caches',
            'reconciliar' => 'Reconciliar status dos PIs',
            'validar_docs' => 'Validar documentos (sem email)',
        ];

        $label = $labels[$action] ?? $action;
        $msg = "<b>Confirmação</b>\n\nAção: <b>{$label}</b>\nSolicitante: {$userName}\n\nDeseja prosseguir?";

        $this->telegram->editMessage($chatId, $messageId, $msg, [
            [['text' => 'Sim, executar', 'callback_data' => "confirm_{$action}"], ['text' => 'Cancelar', 'callback_data' => 'cancel_action']],
        ]);
    }

    private function handleConfirmCallback(string $data, string $chatId, int $messageId, string $userId, string $userName): void
    {
        if (!$this->isAdmin($userId)) {
            $this->telegram->editMessage($chatId, $messageId, "<b>Sem permissão.</b>", $this->keyboardMenuPrincipal());
            return;
        }

        $action = str_replace('confirm_', '', $data);

        $lockKey = 'tg_action_lock_' . $action . '_' . $messageId;
        if (Cache::has($lockKey)) {
            $this->telegram->editMessage($chatId, $messageId, "<i>Ação já em execução, aguarde...</i>", []);
            return;
        }
        Cache::put($lockKey, true, now()->addMinutes(5));

        activity()->causedByAnonymous()->withProperties([
            'comando' => $action, 'telegram_user' => $userName, 'origem' => 'telegram_bot',
        ])->log("Comando via Telegram: {$action}");

        $this->telegram->editMessage($chatId, $messageId, "<i>Executando...</i>", []);

        $result = match ($action) {
            'cache_limpar' => $this->executeCacheLimpar($userName),
            'cache_rebuild' => $this->executeCacheRebuild($userName),
            'reconciliar' => $this->executeReconciliar($userName),
            'validar_docs' => $this->executeValidarDocs($userName),
            default => 'Ação desconhecida.',
        };

        $this->telegram->editMessage($chatId, $messageId, $result, $this->keyboardVoltarAcoes());
    }

    private function isAdmin(string $userId): bool
    {
        return in_array($userId, $this->adminUserIds, true);
    }

    private function buildMainMenuText(string $userName): string
    {
        $appName = config('app.name');
        return "<b>{$appName}</b>\n\nOlá, <b>{$userName}</b>.\nEscolha uma opção:";
    }

    private function keyboardMainMenu(): array
    {
        return [
            [['text' => 'Consultas', 'callback_data' => 'menu_consultas'], ['text' => 'Relatórios', 'callback_data' => 'menu_relatorios']],
            [['text' => 'Ações', 'callback_data' => 'menu_acoes']],
        ];
    }

    private function keyboardConsultas(): array
    {
        return [
            [['text' => 'Status', 'callback_data' => 'cmd_status'], ['text' => 'Emails', 'callback_data' => 'cmd_emails']],
            [['text' => 'PIs', 'callback_data' => 'cmd_pis'], ['text' => 'Documentos', 'callback_data' => 'cmd_docs']],
            [['text' => 'Usuários', 'callback_data' => 'cmd_users'], ['text' => 'Erros', 'callback_data' => 'cmd_erros']],
            [['text' => 'Chat', 'callback_data' => 'cmd_chat']],
            [['text' => 'Voltar', 'callback_data' => 'menu_principal']],
        ];
    }

    private function keyboardRelatorios(): array
    {
        return [
            [['text' => 'Visão Geral', 'callback_data' => 'cmd_relatorio']],
            [['text' => 'PIs detalhado', 'callback_data' => 'cmd_pis_detalhado'], ['text' => 'Docs detalhado', 'callback_data' => 'cmd_docs_detalhado']],
            [['text' => 'Voltar', 'callback_data' => 'menu_principal']],
        ];
    }

    private function keyboardAcoes(): array
    {
        return [
            [['text' => 'Limpar Cache', 'callback_data' => 'act_cache_limpar'], ['text' => 'Rebuild Cache', 'callback_data' => 'act_cache_rebuild']],
            [['text' => 'Reconciliar PIs', 'callback_data' => 'act_reconciliar'], ['text' => 'Validar Docs', 'callback_data' => 'act_validar_docs']],
            [['text' => 'Voltar', 'callback_data' => 'menu_principal']],
        ];
    }

    private function keyboardVoltarConsultas(): array
    {
        return [[['text' => 'Consultas', 'callback_data' => 'menu_consultas'], ['text' => 'Menu', 'callback_data' => 'menu_principal']]];
    }

    private function keyboardVoltarRelatorios(): array
    {
        return [[['text' => 'Relatórios', 'callback_data' => 'menu_relatorios'], ['text' => 'Menu', 'callback_data' => 'menu_principal']]];
    }

    private function keyboardVoltarAcoes(): array
    {
        return [[['text' => 'Ações', 'callback_data' => 'menu_acoes'], ['text' => 'Menu', 'callback_data' => 'menu_principal']]];
    }

    private function keyboardMenuPrincipal(): array
    {
        return [[['text' => 'Menu', 'callback_data' => 'menu_principal']]];
    }

    private function buildStatus(): string
    {
        $msg = "<b>Status do Sistema</b>\n" . now()->format('d/m/Y H:i:s') . "\n\n";

        $storageInfo = $this->getStorageInfo();
        $quotaBytes = 100 * 1073741824;
        $usadoBytes = $storageInfo['total_usado'];
        $livreBytes = max(0, $quotaBytes - $usadoBytes);
        $discoPercent = round(($usadoBytes / $quotaBytes) * 100, 1);
        $livrePercent = round(100 - $discoPercent, 1);

        $msg .= "<b>Disco</b> " . $this->indicador($discoPercent, 75, 90) . "\n<pre>";
        $msg .= $this->progressBar($discoPercent) . " {$discoPercent}% usado\n";
        $msg .= "Cota:  100 GB\n";
        $msg .= "Usado: " . $this->formatBytes($usadoBytes) . "\n";
        $msg .= "Livre: " . $this->formatBytes($livreBytes) . " ({$livrePercent}%)</pre>\n";

        $msg .= "<b>Uso por categoria</b>\n<pre>";
        $msg .= "Banco     " . str_pad($this->formatBytes($storageInfo['banco']), 10, ' ', STR_PAD_LEFT) . "\n";
        $msg .= "Uploads   " . str_pad($this->formatBytes($storageInfo['uploads']), 10, ' ', STR_PAD_LEFT) . "\n";
        $msg .= "Logs      " . str_pad($this->formatBytes($storageInfo['logs']), 10, ' ', STR_PAD_LEFT) . "</pre>\n";

        try {
            $totalTabelas = DB::select("SELECT COUNT(*) as total FROM information_schema.tables WHERE table_schema = DATABASE()");
            $msg .= "Tabelas: <b>{$totalTabelas[0]->total}</b>\n\n";
        } catch (\Exception $e) {}

        $expiracao = Carbon::parse('2026-09-05');
        $diasRestantes = (int) Carbon::today()->diffInDays($expiracao, false);
        if ($diasRestantes > 0) {
            $meses = intdiv($diasRestantes, 30);
            $dias = $diasRestantes % 30;
            $partes = [];
            if ($meses > 0) $partes[] = "{$meses} " . ($meses === 1 ? 'mês' : 'meses');
            if ($dias > 0) $partes[] = "{$dias} " . ($dias === 1 ? 'dia' : 'dias');
            $msg .= "<b>Hospedagem:</b> expira em {$expiracao->format('d/m/Y')} (faltam " . implode(' e ', $partes) . ")\n\n";
        } else {
            $msg .= "<b>Hospedagem: EXPIRADA desde {$expiracao->format('d/m/Y')}!</b>\n\n";
        }

        $onlineInfo = $this->getOnlineUsers();
        $msg .= "Online: <b>{$onlineInfo['total']}</b>  |  Sessões BD: <b>{$onlineInfo['total_sessoes']}</b>\n\n";

        $jobsFalhados = DB::table('failed_jobs')->count();
        if ($jobsFalhados > 0) {
            $msg .= "🔴 Jobs falhados: <b>{$jobsFalhados}</b>\n\n";
        }

        $msg .= $this->indicador($discoPercent, 75, 90) . " ";
        if ($discoPercent > 90 || $jobsFalhados > 0) {
            $msg .= "<b>Problemas detectados</b>";
        } elseif ($discoPercent > 75) {
            $msg .= "<b>Atenção necessária</b>";
        } else {
            $msg .= "<b>Operando normalmente</b>";
        }

        return $msg;
    }

    private function buildEmails(): string
    {
        $stats = MailRotator::getStats();
        $totalPercent = $stats['total_limit'] > 0 ? round(($stats['total_sent'] / $stats['total_limit']) * 100, 1) : 0;

        $msg = "<b>MailRotator</b>\n" . now()->format('d/m/Y H:i:s') . "\n\n";

        $msg .= "<b>Consumo geral</b>\n<pre>";
        $msg .= $this->progressBar($totalPercent) . " {$totalPercent}%\n";
        $msg .= "Enviados: {$stats['total_sent']} / {$stats['total_limit']}</pre>\n";

        if ($stats['scheduled'] > 0) {
            $msg .= "Agendados: <b>{$stats['scheduled']}</b>\n\n";
        }

        $msg .= "<b>Caixas</b>\n<pre>";
        foreach ($stats['boxes'] as $box => $data) {
            $bar = $this->miniBar($data['percentage']);
            $pad = str_pad($data['sent'], 3, ' ', STR_PAD_LEFT);
            $msg .= "Cx" . str_pad($box, 2, ' ') . " {$bar} {$pad}/100\n";
        }
        $msg .= "</pre>";

        return $msg;
    }

    private function buildPis(): string
    {
        $hoje = Carbon::today();
        $ontem = Carbon::yesterday();

        $totalPis = Pi::count();
        $pisAbertos = Pi::where('concluido', false)->count();
        $criadosHoje = Pi::whereDate('data_cadastro', $hoje)->count();
        $concluidosHoje = Pi::where('concluido', true)->whereDate('data_conclusao', $hoje)->count();
        $criadosOntem = Pi::whereDate('data_cadastro', $ontem)->count();
        $concluidosOntem = Pi::where('concluido', true)->whereDate('data_conclusao', $ontem)->count();
        $parados = Pi::where('concluido', false)->where('updated_at', '<', now()->subDays(5))->count();

        $msg = "<b>PIs</b>\n" . now()->format('d/m/Y H:i:s') . "\n\n";
        $msg .= "Total: <b>{$totalPis}</b>  |  Abertos: <b>{$pisAbertos}</b>\n\n";

        $msg .= "<b>Hoje vs Ontem</b>\n";
        $msg .= "  Criados: <b>{$criadosHoje}</b> " . $this->trend($criadosHoje, $criadosOntem) . "\n";
        $msg .= "  Concluídos: <b>{$concluidosHoje}</b> " . $this->trend($concluidosHoje, $concluidosOntem) . "\n\n";

        if ($parados > 0) {
            $msg .= "🔴 Parados (5+ dias): <b>{$parados}</b>\n\n";
        }

        $pisPorStatus = Pi::select('status_id', DB::raw('count(*) as total'))
            ->where('concluido', false)->with('status')->groupBy('status_id')->get();

        if ($pisPorStatus->isNotEmpty()) {
            $msg .= "<b>Abertos por status</b>\n";
            foreach ($pisPorStatus as $item) {
                $nome = $item->status ? $item->status->nome : 'Sem status';
                $msg .= "  {$nome}: <b>{$item->total}</b>\n";
            }
        }

        return $msg;
    }

    private function buildDocs(): string
    {
        $hoje = Carbon::today();
        $ontem = Carbon::yesterday();

        $totalDocs = DocumentoEnviado::count();
        $enviadosHoje = DocumentoEnviado::whereDate('data_envio', $hoje)->count();
        $enviadosOntem = DocumentoEnviado::whereDate('data_envio', $ontem)->count();
        $aprovados = DocumentoEnviado::whereHas('status', fn($q) => $q->where('nome', 'Aprovado'))->where('valido', true)->count();
        $reprovados = DocumentoEnviado::whereHas('status', fn($q) => $q->whereIn('nome', ['Reprovado', 'Aguardando Reenvio']))->count();
        $aguardando = DocumentoEnviado::whereHas('status', fn($q) => $q->whereIn('nome', ['Pendente', 'Aguardando validação']))->count();
        $vencidos = DocumentoEnviado::whereNotNull('data_validade_documento')->where('data_validade_documento', '<', $hoje)->where('valido', false)->count();
        $vencendo7d = DocumentoEnviado::where('valido', true)->whereBetween('data_validade_documento', [$hoje, $hoje->copy()->addDays(7)])->count();
        $pendentesEnvio = PiDocumento::whereNull('documento_enviado_id')->whereHas('pi', fn($q) => $q->where('concluido', false))->count();

        $msg = "<b>Documentos</b>\n" . now()->format('d/m/Y H:i:s') . "\n\n";
        $msg .= "Total: <b>{$totalDocs}</b>  |  Hoje: <b>{$enviadosHoje}</b> " . $this->trend($enviadosHoje, $enviadosOntem) . "\n\n";

        $msg .= "<b>Situação</b>\n";
        $msg .= "  Aprovados: <b>{$aprovados}</b>\n";
        $msg .= "  Reprovados: <b>{$reprovados}</b>\n";
        $msg .= "  Aguardando: <b>{$aguardando}</b>\n";
        $msg .= "  Pendentes envio: <b>{$pendentesEnvio}</b>\n\n";

        if ($vencidos > 0 || $vencendo7d > 0) {
            $msg .= "<b>Alertas</b>\n";
            if ($vencidos > 0) $msg .= "  🔴 Vencidos: <b>{$vencidos}</b>\n";
            if ($vencendo7d > 0) $msg .= "  🟡 Vencendo em 7 dias: <b>{$vencendo7d}</b>\n";
        }

        return $msg;
    }

    private function buildUsers(): string
    {
        $msg = "<b>Usuários</b>\n" . now()->format('d/m/Y H:i:s') . "\n\n";

        $onlineInfo = $this->getOnlineUsers();

        $msg .= "<b>Online ({$onlineInfo['total']})</b>\n";
        if ($onlineInfo['users']->isNotEmpty()) {
            foreach ($onlineInfo['users'] as $u) {
                $role = $u->roles->first() ? $u->roles->first()->name : '?';
                $msg .= "  {$u->name} <i>({$role})</i>\n";
            }
        } else {
            $msg .= "  <i>Nenhum usuário ativo</i>\n";
        }
        $msg .= "\n";

        $ultimosLogins = Activity::where('description', 'Login realizado')->latest()->take(8)->get();
        $msg .= "<b>Últimos logins</b>\n";
        foreach ($ultimosLogins as $login) {
            $nome = $login->causer ? $login->causer->name : 'ID:' . $login->causer_id;
            $msg .= "  {$nome} — <i>" . $login->created_at->format('d/m H:i') . "</i>\n";
        }
        $msg .= "\n";

        $totalPorRole = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join('users', 'users.id', '=', 'model_has_roles.model_id')
            ->whereNull('users.deleted_at')
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->select('roles.name', DB::raw('count(*) as total'))
            ->groupBy('roles.name')->get();

        $msg .= "<b>Por perfil</b>\n";
        foreach ($totalPorRole as $role) {
            $msg .= "  {$role->name}: <b>{$role->total}</b>\n";
        }

        return $msg;
    }

    private function buildChat(): string
    {
        $msg = "<b>Chat</b>\n" . now()->format('d/m/Y H:i:s') . "\n\n";

        $abertas = Conversa::where('status_conversa', 'aberta')->count();
        $escaladas = Conversa::where('status_conversa', 'escalada')->count();
        $resolvidas = Conversa::where('status_conversa', 'resolvida')->count();
        $total = Conversa::count();

        $msg .= "<b>Conversas</b>\n";
        $msg .= "  Total: <b>{$total}</b>  |  Abertas: <b>{$abertas}</b>\n";
        if ($escaladas > 0) $msg .= "  🟡 Escaladas: <b>{$escaladas}</b>\n";
        $msg .= "  Resolvidas: <b>{$resolvidas}</b>\n\n";

        $conversasComNaoLidas = DB::table('mensagens')
            ->where('lida', false)
            ->whereNull('deleted_at')
            ->distinct('conversa_id')
            ->count('conversa_id');

        $msg .= "<b>Pendências</b>\n";
        $msg .= "  Conversas com mensagens não lidas: <b>{$conversasComNaoLidas}</b>\n";

        if ($conversasComNaoLidas > 0) {
            $topConversasList = DB::table('mensagens')
                ->where('lida', false)
                ->whereNull('deleted_at')
                ->select('conversa_id', DB::raw('count(*) as total'))
                ->groupBy('conversa_id')
                ->orderByDesc('total')
                ->take(5)
                ->get();

            $conversaIds = $topConversasList->pluck('conversa_id')->toArray();
            $conversasMap = Conversa::whereIn('id', $conversaIds)->get()->keyBy('id');

            $msg .= "\n<b>Top pendências</b>\n";
            foreach ($topConversasList as $item) {
                $conversa = $conversasMap->get($item->conversa_id);
                $assunto = $conversa ? mb_substr($conversa->assunto, 0, 30) : 'ID:' . $item->conversa_id;
                $msg .= "  {$assunto}: <b>{$item->total}</b> msg\n";
            }
        }

        $msgHoje = Mensagem::whereDate('created_at', Carbon::today())->count();
        $msgOntem = Mensagem::whereDate('created_at', Carbon::yesterday())->count();
        $msg .= "\nMensagens hoje: <b>{$msgHoje}</b> " . $this->trend($msgHoje, $msgOntem);

        return $msg;
    }

    private function buildErros(): string
    {
        $msg = "<b>Erros do sistema</b>\n" . now()->format('d/m/Y H:i:s') . "\n\n";

        $canais = ['laravel', 'docs', 'pis', 'chat', 'emails', 'security'];
        $todosErros = [];
        $totalGeral = 0;

        foreach ($canais as $canal) {
            $logFile = storage_path("logs/{$canal}/{$canal}-" . date('Y-m-d') . ".log");
            $lines = $this->readLastLines($logFile, 500);

            $errorCount = 0;
            foreach ($lines as $line) {
                if (preg_match('/\.(ERROR|CRITICAL|EMERGENCY):/', $line)) {
                    $errorCount++;
                    if (count($todosErros) < 5) {
                        if (preg_match('/\[(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})\].*\.(ERROR|CRITICAL|EMERGENCY):\s(.+?)(\s\{|$)/', $line, $m)) {
                            $todosErros[] = ['canal' => $canal, 'hora' => $m[1], 'nivel' => $m[2], 'msg' => mb_substr($m[3], 0, 100)];
                        }
                    }
                }
            }

            if ($errorCount > 0) {
                $totalGeral += $errorCount;
                $msg .= "  " . $this->indicador($errorCount, 3, 10) . " <b>{$canal}</b>: {$errorCount}\n";
            }
        }

        if ($totalGeral === 0) {
            $msg .= "Nenhum erro hoje.";
            return $msg;
        }

        $msg .= "\nTotal: <b>{$totalGeral}</b>\n";
        $msg .= "\n<b>Últimos:</b>\n";
        foreach ($todosErros as $erro) {
            $hora = Carbon::parse($erro['hora'])->format('H:i');
            $msg .= "\n<b>{$hora}</b> [{$erro['canal']}]\n<code>{$erro['msg']}</code>\n";
        }

        return $msg;
    }

    public function buildRelatorioGeral(): string
    {
        $hoje = Carbon::today();
        $ontem = Carbon::yesterday();

        $pisAbertos = Pi::where('concluido', false)->count();
        $criadosHoje = Pi::whereDate('data_cadastro', $hoje)->count();
        $concluidosHoje = Pi::where('concluido', true)->whereDate('data_conclusao', $hoje)->count();
        $criadosOntem = Pi::whereDate('data_cadastro', $ontem)->count();
        $concluidosOntem = Pi::where('concluido', true)->whereDate('data_conclusao', $ontem)->count();
        $parados = Pi::where('concluido', false)->where('updated_at', '<', now()->subDays(5))->count();

        $enviadosHoje = DocumentoEnviado::whereDate('data_envio', $hoje)->count();
        $enviadosOntem = DocumentoEnviado::whereDate('data_envio', $ontem)->count();
        $vencidos = DocumentoEnviado::whereNotNull('data_validade_documento')->where('data_validade_documento', '<', $hoje)->where('valido', false)->count();
        $vencendo7d = DocumentoEnviado::where('valido', true)->whereBetween('data_validade_documento', [$hoje, $hoje->copy()->addDays(7)])->count();

        $stats = MailRotator::getStats();

        $onlineInfo = $this->getOnlineUsers();
        $loginsHoje = Activity::where('description', 'Login realizado')->whereDate('created_at', $hoje)->count();

        $loginsFalhados = 0;
        $logFile = storage_path('logs/security/security-' . date('Y-m-d') . '.log');
        foreach ($this->readLastLines($logFile, 500) as $line) {
            if (str_contains($line, 'Tentativa de login falhou')) $loginsFalhados++;
        }

        $conversasAbertas = Conversa::where('status_conversa', 'aberta')->count();
        $escaladas = Conversa::where('status_conversa', 'escalada')->count();
        $conversasComNaoLidas = DB::table('mensagens')
            ->where('lida', false)->whereNull('deleted_at')
            ->distinct('conversa_id')->count('conversa_id');

        $storageInfo = $this->getStorageInfo();
        $usadoPercent = round(($storageInfo['total_usado'] / (100 * 1073741824)) * 100, 1);

        $totalErros = 0;
        foreach (['laravel', 'docs', 'pis', 'chat', 'emails', 'security'] as $canal) {
            $lf = storage_path("logs/{$canal}/{$canal}-" . date('Y-m-d') . ".log");
            foreach ($this->readLastLines($lf, 500) as $ln) {
                if (preg_match('/\.(ERROR|CRITICAL|EMERGENCY):/', $ln)) $totalErros++;
            }
        }

        $jobsFalhados = DB::table('failed_jobs')->count();

        $msg = "<b>Relatório</b>  " . now()->format('d/m H:i') . "\n\n";

        $msg .= "<b>PIs</b>  Abertos: <b>{$pisAbertos}</b>";
        if ($parados > 0) $msg .= "  |  🔴 Parados: <b>{$parados}</b>";
        $msg .= "\n  Criados: {$criadosHoje} " . $this->trend($criadosHoje, $criadosOntem) . "  |  Concluídos: {$concluidosHoje} " . $this->trend($concluidosHoje, $concluidosOntem) . "\n";

        $pisPorStatus = Pi::select('status_id', DB::raw('count(*) as total'))
            ->where('concluido', false)->with('status')->groupBy('status_id')->get();
        if ($pisPorStatus->isNotEmpty()) {
            $parts = [];
            foreach ($pisPorStatus as $item) {
                $nome = $item->status ? $item->status->nome : '?';
                $parts[] = "{$nome}: {$item->total}";
            }
            $msg .= "<blockquote expandable>" . implode("\n", $parts) . "</blockquote>";
        }

        $msg .= "\n<b>Docs</b>  Enviados: <b>{$enviadosHoje}</b> " . $this->trend($enviadosHoje, $enviadosOntem);
        if ($vencidos > 0) $msg .= "  |  🔴 {$vencidos} vencidos";
        if ($vencendo7d > 0) $msg .= "  |  🟡 {$vencendo7d} vencendo";
        $msg .= "\n";

        $msg .= "<b>Emails</b>  {$stats['total_sent']}/{$stats['total_limit']}";
        if ($stats['scheduled'] > 0) $msg .= "  |  Agendados: {$stats['scheduled']}";
        $msg .= "\n";

        $msg .= "<b>Chat</b>  Abertas: <b>{$conversasAbertas}</b>";
        if ($escaladas > 0) $msg .= "  |  Escaladas: {$escaladas}";
        if ($conversasComNaoLidas > 0) $msg .= "  |  Pendências: {$conversasComNaoLidas}";
        $msg .= "\n";

        $failIcon = $this->indicador($loginsFalhados, 1, 5);
        $msg .= "<b>Usuários</b>  Online: <b>{$onlineInfo['total']}</b>  |  Logins: {$loginsHoje}  |  {$failIcon} Falhos: {$loginsFalhados}\n";

        $msg .= "<b>Disco</b>  " . $this->indicador($usadoPercent, 75, 90) . " {$usadoPercent}% usado  |  BD: " . $this->formatBytes($storageInfo['banco']) . "\n";

        $expiracao = Carbon::parse('2026-09-05');
        $diasRestantes = (int) Carbon::today()->diffInDays($expiracao, false);
        if ($diasRestantes > 0) {
            $meses = intdiv($diasRestantes, 30);
            $dias = $diasRestantes % 30;
            $partes = [];
            if ($meses > 0) $partes[] = "{$meses} " . ($meses === 1 ? 'mês' : 'meses');
            if ($dias > 0) $partes[] = "{$dias} " . ($dias === 1 ? 'dia' : 'dias');
            $msg .= "<b>Hospedagem</b>  Expira: {$expiracao->format('d/m/Y')} (faltam " . implode(' e ', $partes) . ")\n";
        } else {
            $msg .= "<b>Hospedagem</b>  <b>EXPIRADA desde {$expiracao->format('d/m/Y')}!</b>\n";
        }

        $erroIcon = $totalErros > 0 ? $this->indicador($totalErros, 3, 10) : '';
        $msg .= "<b>Erros</b>  {$erroIcon} {$totalErros} hoje";
        if ($jobsFalhados > 0) $msg .= "  |  Jobs: {$jobsFalhados}";

        return $msg;
    }

    private function buildPisDetalhado(): string
    {
        $hoje = Carbon::today();
        $ontem = Carbon::yesterday();
        $semanaPassada = now()->subWeek();

        $totalPis = Pi::count();
        $pisAbertos = Pi::where('concluido', false)->count();
        $pisConcluídos = Pi::where('concluido', true)->count();
        $criadosHoje = Pi::whereDate('data_cadastro', $hoje)->count();
        $concluidosHoje = Pi::where('concluido', true)->whereDate('data_conclusao', $hoje)->count();
        $criadosOntem = Pi::whereDate('data_cadastro', $ontem)->count();
        $concluidosOntem = Pi::where('concluido', true)->whereDate('data_conclusao', $ontem)->count();
        $criadosSemana = Pi::where('data_cadastro', '>=', $semanaPassada)->count();
        $concluidosSemana = Pi::where('concluido', true)->where('data_conclusao', '>=', $semanaPassada)->count();
        $parados5d = Pi::where('concluido', false)->where('updated_at', '<', now()->subDays(5))->count();
        $parados10d = Pi::where('concluido', false)->where('updated_at', '<', now()->subDays(10))->count();
        $parados15d = Pi::where('concluido', false)->where('updated_at', '<', now()->subDays(15))->count();

        $msg = "<b>PIs — Detalhado</b>\n" . now()->format('d/m/Y H:i:s') . "\n\n";

        $msg .= "<b>Números gerais</b>\n<pre>";
        $msg .= "Total      " . str_pad($totalPis, 6, ' ', STR_PAD_LEFT) . "\n";
        $msg .= "Abertos    " . str_pad($pisAbertos, 6, ' ', STR_PAD_LEFT) . "\n";
        $msg .= "Concluídos " . str_pad($pisConcluídos, 6, ' ', STR_PAD_LEFT) . "</pre>\n";

        $msg .= "<b>Hoje vs ontem</b>\n";
        $msg .= "  Criados:    <b>{$criadosHoje}</b> " . $this->trend($criadosHoje, $criadosOntem) . " <i>(ontem: {$criadosOntem})</i>\n";
        $msg .= "  Concluídos: <b>{$concluidosHoje}</b> " . $this->trend($concluidosHoje, $concluidosOntem) . " <i>(ontem: {$concluidosOntem})</i>\n\n";

        $msg .= "<b>Últimos 7 dias</b>\n";
        $msg .= "  Criados: <b>{$criadosSemana}</b>  |  Concluídos: <b>{$concluidosSemana}</b>\n\n";

        if ($parados5d > 0) {
            $msg .= "<b>Gargalos — PIs parados</b>\n";
            $msg .= "  🟡 5+ dias: <b>{$parados5d}</b>\n";
            if ($parados10d > 0) $msg .= "  🟠 10+ dias: <b>{$parados10d}</b>\n";
            if ($parados15d > 0) $msg .= "  🔴 15+ dias: <b>{$parados15d}</b>\n";

            $topParados = Pi::where('concluido', false)
                ->where('updated_at', '<', now()->subDays(5))
                ->orderBy('updated_at', 'asc')
                ->take(5)
                ->with(['fornecedor', 'status'])
                ->get();

            if ($topParados->isNotEmpty()) {
                $msg .= "\n<blockquote expandable><b>Mais antigos:</b>\n";
                foreach ($topParados as $pi) {
                    $dias = (int) now()->diffInDays($pi->updated_at);
                    $forn = $pi->fornecedor ? mb_substr($pi->fornecedor->razao_social, 0, 20) : '?';
                    $status = $pi->status ? $pi->status->nome : '?';
                    $msg .= "PI {$pi->numero_pi} — {$dias}d parado\n  {$forn} | {$status}\n";
                }
                $msg .= "</blockquote>\n";
            }
        }

        $pisPorStatus = Pi::select('status_id', DB::raw('count(*) as total'))
            ->where('concluido', false)->with('status')->groupBy('status_id')->get();

        if ($pisPorStatus->isNotEmpty()) {
            $msg .= "\n<b>Abertos por status</b>\n";
            foreach ($pisPorStatus as $item) {
                $nome = $item->status ? $item->status->nome : 'Sem status';
                $pct = $pisAbertos > 0 ? round(($item->total / $pisAbertos) * 100) : 0;
                $msg .= "  {$nome}: <b>{$item->total}</b> ({$pct}%)\n";
            }
        }

        return $msg;
    }

    private function buildDocsDetalhado(): string
    {
        $hoje = Carbon::today();
        $ontem = Carbon::yesterday();

        $totalDocs = DocumentoEnviado::count();
        $enviadosHoje = DocumentoEnviado::whereDate('data_envio', $hoje)->count();
        $enviadosOntem = DocumentoEnviado::whereDate('data_envio', $ontem)->count();
        $enviadosSemana = DocumentoEnviado::where('data_envio', '>=', now()->subWeek())->count();
        $aprovados = DocumentoEnviado::whereHas('status', fn($q) => $q->where('nome', 'Aprovado'))->where('valido', true)->count();
        $reprovados = DocumentoEnviado::whereHas('status', fn($q) => $q->whereIn('nome', ['Reprovado', 'Aguardando Reenvio']))->count();
        $aguardando = DocumentoEnviado::whereHas('status', fn($q) => $q->whereIn('nome', ['Pendente', 'Aguardando validação']))->count();
        $pendentesEnvio = PiDocumento::whereNull('documento_enviado_id')->whereHas('pi', fn($q) => $q->where('concluido', false))->count();
        $vencidos = DocumentoEnviado::whereNotNull('data_validade_documento')->where('data_validade_documento', '<', $hoje)->where('valido', false)->count();

        $validityRow = DocumentoEnviado::where('valido', true)
            ->whereNotNull('data_validade_documento')
            ->where('data_validade_documento', '>=', $hoje)
            ->where('data_validade_documento', '<=', $hoje->copy()->addDays(30))
            ->selectRaw('
                COUNT(*) as total_30d,
                SUM(CASE WHEN data_validade_documento <= ? THEN 1 ELSE 0 END) as total_7d,
                SUM(CASE WHEN data_validade_documento <= ? THEN 1 ELSE 0 END) as total_14d
            ', [$hoje->copy()->addDays(7), $hoje->copy()->addDays(14)])
            ->first();

        $vencendo7d  = (int) ($validityRow->total_7d ?? 0);
        $vencendo14d = (int) ($validityRow->total_14d ?? 0);
        $vencendo30d = (int) ($validityRow->total_30d ?? 0);

        $msg = "<b>Documentos — Detalhado</b>\n" . now()->format('d/m/Y H:i:s') . "\n\n";

        $msg .= "<b>Visão geral</b>\n<pre>";
        $msg .= "Total       " . str_pad($totalDocs, 6, ' ', STR_PAD_LEFT) . "\n";
        $msg .= "Aprovados   " . str_pad($aprovados, 6, ' ', STR_PAD_LEFT) . "\n";
        $msg .= "Reprovados  " . str_pad($reprovados, 6, ' ', STR_PAD_LEFT) . "\n";
        $msg .= "Aguardando  " . str_pad($aguardando, 6, ' ', STR_PAD_LEFT) . "\n";
        $msg .= "Pend. envio " . str_pad($pendentesEnvio, 6, ' ', STR_PAD_LEFT) . "</pre>\n";

        $msg .= "<b>Atividade</b>\n";
        $msg .= "  Hoje: <b>{$enviadosHoje}</b> " . $this->trend($enviadosHoje, $enviadosOntem) . " <i>(ontem: {$enviadosOntem})</i>\n";
        $msg .= "  Semana: <b>{$enviadosSemana}</b>\n\n";

        if ($vencidos > 0 || $vencendo30d > 0) {
            $msg .= "<b>Validade</b>\n";
            if ($vencidos > 0) $msg .= "  🔴 Vencidos: <b>{$vencidos}</b>\n";
            if ($vencendo7d > 0) $msg .= "  🟠 Vencendo em 7 dias: <b>{$vencendo7d}</b>\n";
            if ($vencendo14d > $vencendo7d) $msg .= "  🟡 Vencendo em 14 dias: <b>{$vencendo14d}</b>\n";
            if ($vencendo30d > $vencendo14d) $msg .= "  Vencendo em 30 dias: <b>{$vencendo30d}</b>\n";
        }

        return $msg;
    }

    private function executeCacheLimpar(string $userName): string
    {
        $cmds = ['route:clear', 'config:clear', 'view:clear', 'cache:clear', 'optimize:clear'];
        foreach ($cmds as $cmd) Artisan::call($cmd);
        $msg = "<b>Caches limpos</b>\n\nExecutado por: {$userName}\n\n";
        foreach ($cmds as $cmd) $msg .= "  ✓ <code>{$cmd}</code>\n";
        return $msg;
    }

    private function executeCacheRebuild(string $userName): string
    {
        $clear = ['route:clear', 'config:clear', 'view:clear', 'cache:clear', 'optimize:clear'];
        $cache = ['route:cache', 'config:cache', 'view:cache'];
        foreach ($clear as $cmd) Artisan::call($cmd);
        foreach ($cache as $cmd) Artisan::call($cmd);
        return "<b>Caches reconstruídos</b>\n\nExecutado por: {$userName}";
    }

    private function executeReconciliar(string $userName): string
    {
        Artisan::call('pis:reconciliar-status');
        $output = Artisan::output();
        $alterados = 0;
        if (preg_match('/(\d+) PI\(s\) atualizados/', $output, $m)) $alterados = (int) $m[1];
        return "<b>Reconciliação concluída</b>\n\nExecutado por: {$userName}\nPIs atualizados: <b>{$alterados}</b>";
    }

    private function executeValidarDocs(string $userName): string
    {
        Artisan::call('documentos:validar', ['--sem-email' => true]);
        $output = Artisan::output();
        return "<b>Validação de documentos concluída</b>\n\nExecutado por: {$userName}\n\n<blockquote expandable>Saída:\n<pre>" . mb_substr($output, 0, 800) . "</pre></blockquote>";
    }

    private function readLastLines(string $path, int $limit = 500): array
    {
        if (!file_exists($path)) {
            return [];
        }
        try {
            $file = new \SplFileObject($path, 'r');
            $file->seek(PHP_INT_MAX);
            $total = $file->key();
            $start = max(0, $total - $limit);
            $file->seek($start);
            $lines = [];
            while (!$file->eof()) {
                $line = rtrim($file->fgets());
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
            return $lines;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getStorageInfo(): array
    {
        $info = ['banco' => 0, 'uploads' => 0, 'logs' => 0, 'total_usado' => 0];

        try {
            $dbSize = DB::select("SELECT SUM(data_length + index_length) as size_bytes FROM information_schema.tables WHERE table_schema = DATABASE()");
            $info['banco'] = (int) ($dbSize[0]->size_bytes ?? 0);
        } catch (\Exception $e) {}

        try {
            $uploadsPath = storage_path('app/private');
            if (is_dir($uploadsPath)) {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($uploadsPath, \RecursiveDirectoryIterator::SKIP_DOTS));
                foreach ($iterator as $file) {
                    if ($file->isFile()) $info['uploads'] += $file->getSize();
                }
            }
        } catch (\Exception $e) {}

        try {
            foreach (['laravel', 'emails', 'security', 'docs', 'pis', 'chat', 'telegram'] as $dir) {
                $path = storage_path("logs/{$dir}");
                if (is_dir($path)) {
                    foreach (glob("{$path}/*.log") as $file) $info['logs'] += filesize($file);
                }
            }
            $singleLog = storage_path('logs/laravel.log');
            if (file_exists($singleLog)) $info['logs'] += filesize($singleLog);
        } catch (\Exception $e) {}

        $info['total_usado'] = $info['banco'] + $info['uploads'] + $info['logs'];
        return $info;
    }

    private function getOnlineUsers(): array
    {
        $lifetime = (int) config('session.lifetime', 120);
        $cutoff = now()->subMinutes($lifetime)->timestamp;
        $result = ['total' => 0, 'users' => collect(), 'total_sessoes' => 0];

        try {
            $result['total_sessoes'] = DB::table('sessions')->count();

            $fromSessions = DB::table('sessions')
                ->where('last_activity', '>=', $cutoff)
                ->whereNotNull('user_id')->where('user_id', '>', 0)
                ->pluck('user_id')->unique();

            $fromActivity = Activity::where('description', 'Login realizado')
                ->where('created_at', '>=', now()->subMinutes($lifetime))
                ->whereNotNull('causer_id')
                ->pluck('causer_id')->unique();

            $onlineIds = $fromSessions->merge($fromActivity)->unique()->values();

            if ($onlineIds->isNotEmpty()) {
                $result['users'] = User::whereIn('id', $onlineIds)->whereNull('deleted_at')->with('roles')->get();
                $result['total'] = $result['users']->count();
            }
        } catch (\Exception $e) {}

        return $result;
    }

    private function trend(int $hoje, int $ontem): string
    {
        if ($hoje > $ontem) return '↑';
        if ($hoje < $ontem) return '↓';
        return '→';
    }

    private function indicador(float $valor, float $atencao, float $critico): string
    {
        if ($valor >= $critico) return '🔴';
        if ($valor >= $atencao) return '🟡';
        return '';
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }

    private function progressBar(float $percent): string
    {
        $filled = (int) round(($percent / 100) * 15);
        return str_repeat('▓', $filled) . str_repeat('░', 15 - $filled);
    }

    private function miniBar(float $percent): string
    {
        $filled = (int) round(($percent / 100) * 8);
        return str_repeat('█', $filled) . str_repeat('░', 8 - $filled);
    }
}