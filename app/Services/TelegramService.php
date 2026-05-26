<?php

/* app/Services/TelegramService.php */

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private $client;
    private $botToken;
    private $chatId;
    private $piChatId;
    private $enabled;
    private $throttleMinutes;
    private $groupMaxPerMinute = 15;
    private $privateMaxPerMinute = 50;
    private $alertMaxPerMinute = 6;
    private $proactiveDelayMs = 2000;
    private $privateWarningThreshold = 5;
    private $groupWarningThreshold = 3;
    private ?string $currentContext = null;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->chatId = config('services.telegram.chat_id');
        $this->piChatId = config('services.telegram.pi_chat_id');
        $this->enabled = config('services.telegram.enabled', false);
        $this->throttleMinutes = config('services.telegram.throttle_minutes', 5);

        if ($this->enabled && $this->botToken) {
            $this->client = new Client([
                'base_uri' => "https://api.telegram.org/bot{$this->botToken}/",
                'timeout' => 10,
                'connect_timeout' => 5,
            ]);
        }
    }

    public function setContext(string $context): self
    {
        $this->currentContext = $context;
        return $this;
    }

    private function getContext(): string
    {
        if ($this->currentContext) {
            return $this->currentContext;
        }

        return app()->runningInConsole() ? 'console' : 'webhook';
    }

    public function getPiChatId(): ?string
    {
        return $this->piChatId;
    }

    public static function alertaSeguro(string $comando): void
    {
        try {
            app(static::class)->alertCommandFalhou($comando, 'Command retornou FAILURE');
        } catch (\Throwable $t) {
            Log::channel('telegram')->error('Falha ao enviar alerta onFailure', [
                'comando' => $comando,
                'error' => $t->getMessage(),
            ]);
        }
    }

    public function getChatRemainingQuota(string $chatId): array
    {
        $count = $this->getChatSendCount($chatId);
        $isGroup = $this->isGroupChat($chatId);
        $limit = $isGroup ? $this->groupMaxPerMinute : $this->privateMaxPerMinute;
        $remaining = max(0, $limit - $count);
        $resetIn = 60 - (int) now()->format('s');
        $threshold = $isGroup ? $this->groupWarningThreshold : $this->privateWarningThreshold;
        $nearLimit = $remaining > 0 && $remaining <= $threshold;

        Log::channel('telegram_ratelimit')->debug('QUOTA_CHECK', [
            'chat_id' => $chatId,
            'contexto' => $this->getContext(),
            'tipo' => $isGroup ? 'grupo' : 'privado',
            'count' => $count,
            'limit' => $limit,
            'remaining' => $remaining,
            'reset_in' => $resetIn,
            'near_limit' => $nearLimit,
            'exhausted' => $remaining <= 0,
        ]);

        return [
            'count' => $count,
            'limit' => $limit,
            'remaining' => $remaining,
            'reset_in' => $resetIn,
            'near_limit' => $nearLimit,
            'exhausted' => $remaining <= 0,
            'is_group' => $isGroup,
            'threshold' => $threshold,
        ];
    }

    public function savePendingAction(string $pendingKey, array $actionData): void
    {
        $key = 'tg_pending_action_' . $pendingKey;
        Cache::put($key, $actionData, 120);
    
        Log::channel('telegram_ratelimit')->info('PENDING_ACTION_SAVED', [
            'pending_key' => $pendingKey,
            'action' => $actionData['data'] ?? 'unknown',
            'contexto' => $this->getContext(),
        ]);
    }

    public function getPendingAction(string $pendingKey): ?array
    {
        $key = 'tg_pending_action_' . $pendingKey;
        $pending = Cache::get($key);
    
        if ($pending) {
            Log::channel('telegram_ratelimit')->debug('PENDING_ACTION_FOUND', [
                'pending_key' => $pendingKey,
                'action' => $pending['data'] ?? 'unknown',
                'saved_at' => $pending['saved_at'] ?? 'unknown',
            ]);
        }
    
        return $pending;
    }

    public function clearPendingAction(string $pendingKey): void
    {
        $key = 'tg_pending_action_' . $pendingKey;
        Cache::forget($key);

        Log::channel('telegram_ratelimit')->info('PENDING_ACTION_CLEARED', [
            'pending_key' => $pendingKey,
        ]);
    }

    private function isChatCircuitOpen(string $chatId): bool
    {
        if ($chatId === '') {
            return false;
        }

        $key = 'tg_cb_' . $chatId;
        $until = Cache::get($key);

        if (!$until) {
            return false;
        }

        if (now()->timestamp < $until) {
            $restante = $until - now()->timestamp;

            Log::channel('telegram_ratelimit')->warning('CIRCUIT_BREAKER_ACTIVE', [
                'chat_id' => $chatId,
                'restante_segundos' => $restante,
                'expira_em' => date('H:i:s', $until),
                'contexto' => $this->getContext(),
            ]);

            return true;
        }

        Cache::forget($key);

        Log::channel('telegram_ratelimit')->info('CIRCUIT_BREAKER_EXPIRED', [
            'chat_id' => $chatId,
            'contexto' => $this->getContext(),
        ]);

        return false;
    }

    private function openChatCircuit(string $chatId, int $seconds): void
    {
        if ($chatId === '') {
            return;
        }

        $until = now()->addSeconds($seconds)->timestamp;
        Cache::put('tg_cb_' . $chatId, $until, $seconds + 10);

        Log::channel('telegram_ratelimit')->error('CIRCUIT_BREAKER_OPENED', [
            'chat_id' => $chatId,
            'duracao_segundos' => $seconds,
            'expira_em' => now()->addSeconds($seconds)->format('H:i:s'),
            'contexto' => $this->getContext(),
            'count_minuto' => $this->getChatSendCount($chatId),
            'count_dia' => $this->getDailySendCount($chatId),
        ]);

        Log::channel('telegram')->warning("Circuit breaker ativado para chat {$chatId} por {$seconds}s", [
            'chat_id' => $chatId,
            'ate' => now()->addSeconds($seconds)->format('H:i:s'),
        ]);
    }

    private function isGroupChat(string $chatId): bool
    {
        return str_starts_with($chatId, '-');
    }

    private function getChatSendCount(string $chatId): int
    {
        $key = 'tg_chat_' . $chatId . '_' . now()->format('Y-m-d-H-i');
        return (int) Cache::get($key, 0);
    }

    private function isChatRateLimited(string $chatId): bool
    {
        $count = $this->getChatSendCount($chatId);
        $limit = $this->isGroupChat($chatId) ? $this->groupMaxPerMinute : $this->privateMaxPerMinute;

        if ($count >= $limit) {
            Log::channel('telegram_ratelimit')->warning('RATE_LIMIT_HIT', [
                'chat_id' => $chatId,
                'count' => $count,
                'limit' => $limit,
                'tipo' => $this->isGroupChat($chatId) ? 'grupo' : 'privado',
                'contexto' => $this->getContext(),
                'count_dia' => $this->getDailySendCount($chatId),
                'reset_in' => 60 - (int) now()->format('s'),
            ]);

            Log::channel('telegram')->warning("Rate limit por chat atingido", [
                'chat_id' => $chatId,
                'count' => $count,
                'limit' => $limit,
            ]);

            return true;
        }

        return false;
    }

    private function incrementChatCounter(string $chatId): void
    {
        $key = 'tg_chat_' . $chatId . '_' . now()->format('Y-m-d-H-i');
        $count = (int) Cache::get($key, 0);
        Cache::put($key, $count + 1, 120);
    }

    private function incrementGlobalDailyCounter(string $chatId): void
    {
        $key = 'tg_daily_' . $chatId . '_' . now()->format('Y-m-d');
        $count = (int) Cache::get($key, 0);
        Cache::put($key, $count + 1, 86400);
    }

    public function getDailySendCount(string $chatId): int
    {
        $key = 'tg_daily_' . $chatId . '_' . now()->format('Y-m-d');
        return (int) Cache::get($key, 0);
    }

    private function proactiveDelay(): void
    {
        if (!app()->runningInConsole()) {
            return;
        }

        $key = 'tg_last_proactive_ms';
        $lastMs = (int) Cache::get($key, 0);
        $nowMs = (int) (microtime(true) * 1000);
        $elapsed = $nowMs - $lastMs;

        if ($elapsed < $this->proactiveDelayMs && $lastMs > 0) {
            $wait = $this->proactiveDelayMs - $elapsed;

            Log::channel('telegram_ratelimit')->debug('PROACTIVE_DELAY', [
                'wait_ms' => $wait,
                'elapsed_ms' => $elapsed,
                'contexto' => $this->getContext(),
            ]);

            usleep($wait * 1000);
        }
    }

    private function markProactiveSent(): void
    {
        Cache::put('tg_last_proactive_ms', (int) (microtime(true) * 1000), 30);
    }

    private function isAlertRateLimited(): bool
    {
        $key = 'tg_alert_sends_' . now()->format('Y-m-d-H-i');
        $count = (int) Cache::get($key, 0);

        if ($count >= $this->alertMaxPerMinute) {
            Log::channel('telegram_ratelimit')->warning('ALERT_RATE_LIMIT_HIT', [
                'count' => $count,
                'limit' => $this->alertMaxPerMinute,
                'contexto' => $this->getContext(),
            ]);

            Log::channel('telegram')->warning("Rate limit de alertas atingido: {$count}/{$this->alertMaxPerMinute} alertas/min");
            return true;
        }

        return false;
    }

    private function incrementAlertCounter(): void
    {
        $key = 'tg_alert_sends_' . now()->format('Y-m-d-H-i');
        $count = (int) Cache::get($key, 0);
        Cache::put($key, $count + 1, 120);
    }

    private function apiCall(string $method, array $payload, bool $skipRateLimit = false): array
    {
        if (!$this->client) {
            return ['ok' => false, 'description' => 'Bot não configurado'];
        }

        $chatId = (string) ($payload['chat_id'] ?? '');
        $isWebhookResponse = !app()->runningInConsole();
        $contexto = $this->getContext();

        $countBefore = $chatId !== '' ? $this->getChatSendCount($chatId) : 0;
        $dailyCount = $chatId !== '' ? $this->getDailySendCount($chatId) : 0;
        $limit = 0;
        if ($chatId !== '') {
            $limit = $this->isGroupChat($chatId) ? $this->groupMaxPerMinute : $this->privateMaxPerMinute;
        }

        Log::channel('telegram_ratelimit')->info('API_CALL_START', [
            'method' => $method,
            'chat_id' => $chatId,
            'contexto' => $contexto,
            'count_minuto' => $countBefore,
            'limit_minuto' => $limit,
            'remaining' => $limit > 0 ? max(0, $limit - $countBefore) : 'n/a',
            'count_dia' => $dailyCount,
            'skip_rate_limit' => $skipRateLimit,
            'is_webhook' => $isWebhookResponse,
            'tipo_chat' => $chatId !== '' ? ($this->isGroupChat($chatId) ? 'grupo' : 'privado') : 'n/a',
        ]);

        if (!$skipRateLimit && $chatId !== '') {
            if ($this->isChatCircuitOpen($chatId)) {
                if (!$isWebhookResponse) {
                    Log::channel('telegram_ratelimit')->warning('API_CALL_BLOCKED_CIRCUIT', [
                        'method' => $method,
                        'chat_id' => $chatId,
                        'contexto' => $contexto,
                    ]);

                    return ['ok' => false, 'description' => "Circuit breaker ativo para chat {$chatId}"];
                }

                Log::channel('telegram_ratelimit')->info('API_CALL_CIRCUIT_BYPASS', [
                    'method' => $method,
                    'chat_id' => $chatId,
                    'contexto' => $contexto,
                ]);
            }

            if ($this->isChatRateLimited($chatId)) {
                Log::channel('telegram_ratelimit')->warning('API_CALL_BLOCKED_RATELIMIT', [
                    'method' => $method,
                    'chat_id' => $chatId,
                    'contexto' => $contexto,
                    'count' => $countBefore,
                    'limit' => $limit,
                    'reset_in' => 60 - (int) now()->format('s'),
                ]);

                return ['ok' => false, 'description' => 'Rate limit por chat atingido'];
            }

            $this->proactiveDelay();
        }

        try {
            $response = $this->client->post($method, [
                'json' => $payload,
            ]);

            if (!$skipRateLimit && $chatId !== '') {
                $this->incrementChatCounter($chatId);
                $this->incrementGlobalDailyCounter($chatId);
                $this->markProactiveSent();
            }

            $countAfter = $chatId !== '' ? $this->getChatSendCount($chatId) : 0;

            Log::channel('telegram_ratelimit')->info('API_CALL_SUCCESS', [
                'method' => $method,
                'chat_id' => $chatId,
                'contexto' => $contexto,
                'count_after' => $countAfter,
                'remaining_after' => $limit > 0 ? max(0, $limit - $countAfter) : 'n/a',
            ]);

            return json_decode($response->getBody(), true) ?? ['ok' => true];
        } catch (RequestException $e) {
            $response = $e->getResponse();

            if ($response && $response->getStatusCode() === 429) {
                $body = json_decode($response->getBody(), true);
                $retryAfter = $body['parameters']['retry_after'] ?? 30;

                $this->openChatCircuit($chatId, $retryAfter + 5);

                Log::channel('telegram_ratelimit')->error('API_CALL_429', [
                    'method' => $method,
                    'chat_id' => $chatId,
                    'contexto' => $contexto,
                    'retry_after' => $retryAfter,
                    'count_minuto' => $countBefore,
                    'count_dia' => $dailyCount,
                    'limit' => $limit,
                    'circuit_duracao' => $retryAfter + 5,
                ]);

                Log::channel('telegram')->warning("429 do Telegram", [
                    'method' => $method,
                    'chat_id' => $chatId,
                    'retry_after' => $retryAfter,
                    'contexto' => $contexto,
                ]);

                return ['ok' => false, 'description' => "Rate limited ({$retryAfter}s)", 'retry_after' => $retryAfter];
            }

            Log::channel('telegram_ratelimit')->error('API_CALL_ERROR', [
                'method' => $method,
                'chat_id' => $chatId,
                'contexto' => $contexto,
                'error' => $e->getMessage(),
                'status_code' => $response ? $response->getStatusCode() : 'null',
            ]);

            Log::channel('telegram')->error("Erro na API Telegram [{$method}]", [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
                'contexto' => $contexto,
            ]);

            return ['ok' => false, 'description' => $e->getMessage()];
        } catch (\Exception $e) {
            Log::channel('telegram_ratelimit')->error('API_CALL_EXCEPTION', [
                'method' => $method,
                'chat_id' => $chatId,
                'contexto' => $contexto,
                'exception' => get_class($e),
                'error' => $e->getMessage(),
            ]);

            Log::channel('telegram')->error("Erro inesperado [{$method}]", [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
                'contexto' => $contexto,
            ]);

            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    public function send(string $message, ?string $chatId = null): bool
    {
        if (!$this->enabled || !$this->client) {
            return false;
        }

        $targetChat = $chatId ?? $this->chatId;

        if (!$targetChat) {
            return false;
        }

        $chunks = $this->splitMessage($message);

        Log::channel('telegram_ratelimit')->info('SEND_START', [
            'chat_id' => $targetChat,
            'contexto' => $this->getContext(),
            'chunks' => count($chunks),
            'tamanho_bytes' => mb_strlen($message),
            'count_minuto_atual' => $this->getChatSendCount($targetChat),
            'count_dia' => $this->getDailySendCount($targetChat),
        ]);

        foreach ($chunks as $i => $chunk) {
            $result = $this->apiCall('sendMessage', [
                'chat_id' => $targetChat,
                'text' => $chunk,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            if (!($result['ok'] ?? false)) {
                Log::channel('telegram_ratelimit')->warning('SEND_CHUNK_FAILED', [
                    'chat_id' => $targetChat,
                    'contexto' => $this->getContext(),
                    'chunk_index' => $i,
                    'total_chunks' => count($chunks),
                    'reason' => $result['description'] ?? 'desconhecido',
                ]);

                Log::channel('telegram')->warning("Falha ao enviar chunk {$i}", [
                    'chat_id' => $targetChat,
                    'description' => $result['description'] ?? 'desconhecido',
                ]);
                return false;
            }
        }

        Log::channel('telegram_ratelimit')->info('SEND_COMPLETE', [
            'chat_id' => $targetChat,
            'contexto' => $this->getContext(),
            'chunks_enviados' => count($chunks),
            'count_minuto_apos' => $this->getChatSendCount($targetChat),
        ]);

        return true;
    }

    public function sendOrQueue(string $message, ?string $chatId = null): bool
    {
        $targetChat = $chatId ?? $this->chatId;

        Log::channel('telegram_ratelimit')->info('SEND_OR_QUEUE_START', [
            'chat_id' => $targetChat,
            'contexto' => $this->getContext(),
            'tamanho_bytes' => mb_strlen($message),
        ]);

        if ($this->send($message, $chatId)) {
            return true;
        }

        Log::channel('telegram_ratelimit')->warning('SEND_OR_QUEUE_FALLBACK', [
            'chat_id' => $targetChat,
            'contexto' => $this->getContext(),
        ]);

        $this->queueMessage($message, $chatId);
        return false;
    }

    public function queueMessage(string $message, ?string $chatId = null): void
    {
        try {
            $pending = $this->loadPendingMessages();

            $pending[] = [
                'message' => $message,
                'chat_id' => $chatId,
                'queued_at' => now()->toIso8601String(),
                'context' => $this->getContext(),
            ];

            if (count($pending) > 50) {
                $pending = array_slice($pending, -50);
            }

            $this->savePendingMessages($pending);

            Log::channel('telegram_ratelimit')->info('MESSAGE_QUEUED', [
                'chat_id' => $chatId ?? $this->chatId,
                'contexto' => $this->getContext(),
                'total_fila' => count($pending),
                'tamanho_msg' => mb_strlen($message),
            ]);

            Log::channel('telegram')->info('Mensagem enfileirada para retry', [
                'chat_id' => $chatId ?? $this->chatId,
                'total_pendentes' => count($pending),
            ]);
        } catch (\Throwable $t) {
            Log::channel('telegram')->error('Falha ao enfileirar mensagem', [
                'error' => $t->getMessage(),
            ]);
        }
    }

    public function processQueue(): int
    {
        try {
            $pending = $this->loadPendingMessages();
        } catch (\Throwable $t) {
            return 0;
        }

        if (empty($pending)) {
            return 0;
        }

        Log::channel('telegram_ratelimit')->info('QUEUE_PROCESS_START', [
            'total_pendentes' => count($pending),
            'contexto' => $this->getContext(),
        ]);

        $sent = 0;
        $remaining = [];
        $agora = now();

        foreach ($pending as $index => $item) {
            $queuedAt = \Carbon\Carbon::parse($item['queued_at']);

            if ($queuedAt->diffInHours($agora) >= 24) {
                Log::channel('telegram_ratelimit')->info('QUEUE_ITEM_EXPIRED', [
                    'index' => $index,
                    'chat_id' => $item['chat_id'] ?? $this->chatId,
                    'queued_at' => $item['queued_at'],
                    'original_context' => $item['context'] ?? 'unknown',
                ]);

                Log::channel('telegram')->info('Mensagem pendente descartada (>24h)', [
                    'queued_at' => $item['queued_at'],
                ]);
                continue;
            }

            $targetChat = $item['chat_id'] ?? $this->chatId;

            if ($this->isChatCircuitOpen((string) $targetChat)) {
                Log::channel('telegram_ratelimit')->warning('QUEUE_ITEM_CIRCUIT_BLOCKED', [
                    'index' => $index,
                    'chat_id' => $targetChat,
                    'queued_at' => $item['queued_at'],
                    'original_context' => $item['context'] ?? 'unknown',
                ]);

                $remaining[] = $item;
                continue;
            }

            if ($this->send($item['message'], $item['chat_id'])) {
                $sent++;

                Log::channel('telegram_ratelimit')->info('QUEUE_ITEM_SENT', [
                    'index' => $index,
                    'chat_id' => $targetChat,
                    'queued_at' => $item['queued_at'],
                    'original_context' => $item['context'] ?? 'unknown',
                ]);

                Log::channel('telegram')->info('Mensagem pendente enviada com sucesso', [
                    'queued_at' => $item['queued_at'],
                    'chat_id' => $targetChat,
                ]);
            } else {
                Log::channel('telegram_ratelimit')->warning('QUEUE_ITEM_SEND_FAILED', [
                    'index' => $index,
                    'chat_id' => $targetChat,
                    'queued_at' => $item['queued_at'],
                    'original_context' => $item['context'] ?? 'unknown',
                    'count_minuto' => $this->getChatSendCount($targetChat),
                ]);

                $remaining[] = $item;
                break;
            }
        }

        $this->savePendingMessages($remaining);

        Log::channel('telegram_ratelimit')->info('QUEUE_PROCESS_END', [
            'enviados' => $sent,
            'restantes' => count($remaining),
            'contexto' => $this->getContext(),
        ]);

        Log::channel('telegram')->info("Fila processada: {$sent} enviada(s), " . count($remaining) . " pendente(s)");

        return $sent;
    }

    public function pendingCount(): int
    {
        try {
            return count($this->loadPendingMessages());
        } catch (\Throwable $t) {
            return 0;
        }
    }

    private function getPendingFilePath(): string
    {
        return storage_path('app/telegram_pending.json');
    }

    private function loadPendingMessages(): array
    {
        $path = $this->getPendingFilePath();

        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        $messages = json_decode($content, true);

        if (!is_array($messages)) {
            if (trim($content) !== '' && trim($content) !== '[]') {
                Log::channel('telegram')->error('telegram_pending.json corrompido, conteúdo descartado', [
                    'tamanho' => strlen($content),
                    'preview' => mb_substr($content, 0, 100),
                ]);
                @rename($path, $path . '.corrupted.' . time());
            }
            return [];
        }

        return $messages;
    }

    private function savePendingMessages(array $messages): void
    {
        $path = $this->getPendingFilePath();
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    }

    public function sendWithKeyboard(string $message, array $keyboard, ?string $chatId = null): bool
    {
        if (!$this->enabled || !$this->client) {
            return false;
        }

        $targetChat = $chatId ?? $this->chatId;

        if (!$targetChat) {
            return false;
        }

        $result = $this->apiCall('sendMessage', [
            'chat_id' => $targetChat,
            'text' => $this->trimToLimit($message),
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard,
            ]),
        ]);

        return $result['ok'] ?? false;
    }

    public function editMessage(string $chatId, int $messageId, string $text, ?array $keyboard = null): bool
    {
        if (!$this->enabled || !$this->client) {
            return false;
        }

        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $this->trimToLimit($text),
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($keyboard !== null) {
            $payload['reply_markup'] = json_encode([
                'inline_keyboard' => $keyboard,
            ]);
        }

        $result = $this->apiCall('editMessageText', $payload);

        return $result['ok'] ?? false;
    }

    public function answerCallback(string $callbackId, ?string $text = null, bool $showAlert = false): void
    {
        if (!$this->enabled || !$this->client) {
            return;
        }

        $payload = ['callback_query_id' => $callbackId];
        if ($text) {
            $payload['text'] = $text;
            $payload['show_alert'] = $showAlert;
        }

        $this->apiCall('answerCallbackQuery', $payload, true);
    }

    public function setWebhook(string $url): array
    {
        return $this->apiCall('setWebhook', [
            'url' => $url,
            'allowed_updates' => ['message', 'callback_query'],
            'max_connections' => 10,
            'secret_token' => config('services.telegram.webhook_secret'),
        ], true);
    }

    public function setCommandsForGroups(): array
    {
        return $this->apiCall('setMyCommands', [
            'commands' => [
                ['command' => 'menu', 'description' => 'Abrir painel de controle'],
            ],
            'scope' => ['type' => 'all_group_chats'],
        ], true);
    }

    public function setCommandsForPrivate(): array
    {
        return $this->apiCall('setMyCommands', [
            'commands' => [
                ['command' => 'menu', 'description' => 'Abrir painel de controle'],
                ['command' => 'status', 'description' => 'Saude do sistema'],
                ['command' => 'relatorio', 'description' => 'Relatorio completo'],
                ['command' => 'ajuda', 'description' => 'Lista de comandos'],
            ],
            'scope' => ['type' => 'all_private_chats'],
        ], true);
    }

    public function setChatMenuButton(?string $chatId = null): array
    {
        $payload = ['menu_button' => ['type' => 'commands']];
        if ($chatId) {
            $payload['chat_id'] = $chatId;
        }
        return $this->apiCall('setChatMenuButton', $payload, true);
    }

    public function deleteWebhook(): array
    {
        return $this->apiCall('deleteWebhook', [], true);
    }

    public function alertError(string $titulo, string $detalhes, ?string $trace = null): void
    {
        Log::error("{$titulo}: {$detalhes}");

        if ($this->isAlertRateLimited()) {
            return;
        }

        $key = 'tg_throttle_err_' . md5($titulo);
        if ($this->shouldThrottle($key)) {
            return;
        }

        $this->incrementAlertCounter();

        $msg = "🔴 <b>Erro no sistema</b>\n\n";
        $msg .= "<b>{$this->esc($titulo)}</b>\n\n";
        $msg .= "<code>{$this->truncate($detalhes, 500)}</code>\n";

        if ($trace) {
            $msg .= "\n<blockquote expandable><b>Stack Trace</b>\n<pre>{$this->truncate($trace, 800)}</pre></blockquote>";
        }

        $msg .= "\n" . now()->format('d/m/Y H:i:s');

        $this->sendOrQueue($msg);
    }

    public function alertLoginSuspeito(string $email, string $ip, int $tentativas): void
    {
        if ($this->isAlertRateLimited()) {
            return;
        }

        $key = 'tg_throttle_login_' . md5($ip);
        if ($this->shouldThrottle($key, 10)) {
            return;
        }

        $this->incrementAlertCounter();

        $msg = "🟡 <b>Login suspeito</b>\n\n";
        $msg .= "Email: <code>{$this->esc($email)}</code>\n";
        $msg .= "IP: <code>{$this->esc($ip)}</code>\n";
        $msg .= "Tentativas: <b>{$tentativas}</b> nos últimos 10 min\n\n";
        $msg .= now()->format('d/m/Y H:i:s');

        $this->sendOrQueue($msg);
    }

    public function alertMailRotator(int $totalAgendados): void
    {
        if ($this->isAlertRateLimited()) {
            return;
        }

        $key = 'tg_throttle_mail_limit';
        if ($this->shouldThrottle($key, 60)) {
            return;
        }

        $this->incrementAlertCounter();

        $msg = "🔴 <b>Limite de e-mails atingido</b>\n\n";
        $msg .= "Todas as 10 caixas atingiram o limite de 100/dia\n";
        $msg .= "Agendados para amanhã: <b>{$totalAgendados}</b>\n\n";
        $msg .= now()->format('d/m/Y H:i:s');

        $this->sendOrQueue($msg);
    }

    public function alertCommandFalhou(string $command, string $error): void
    {
        if ($this->isAlertRateLimited()) {
            return;
        }

        $key = 'tg_throttle_cmd_' . md5($command);
        if ($this->shouldThrottle($key, 30)) {
            return;
        }

        $this->incrementAlertCounter();

        $msg = "🟡 <b>Command falhou</b>\n\n";
        $msg .= "Comando: <code>{$this->esc($command)}</code>\n";
        $msg .= "Erro: <code>{$this->truncate($error, 500)}</code>\n\n";
        $msg .= now()->format('d/m/Y H:i:s');

        $this->sendOrQueue($msg);
    }

    private function shouldThrottle(string $key, ?int $minutes = null): bool
    {
        $ttl = $minutes ?? $this->throttleMinutes;

        if (Cache::has($key)) {
            return true;
        }

        Cache::put($key, true, now()->addMinutes($ttl));
        return false;
    }

    private function splitMessage(string $message, int $maxLength = 4000): array
    {
        if (mb_strlen($message) <= $maxLength) {
            return [$message];
        }

        $chunks = [];
        $lines = explode("\n", $message);
        $current = '';

        foreach ($lines as $line) {
            if (mb_strlen($current . "\n" . $line) > $maxLength) {
                if ($current !== '') {
                    $chunks[] = $current;
                }
                $current = $line;
            } else {
                $current .= ($current !== '' ? "\n" : '') . $line;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }

    private function trimToLimit(string $text, int $max = 4000): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        return mb_substr($text, 0, $max - 30) . "\n\n<i>... truncado</i>";
    }

    private function truncate(string $text, int $max): string
    {
        $escaped = $this->esc($text);
        if (mb_strlen($escaped) <= $max) {
            return $escaped;
        }
        return mb_substr($escaped, 0, $max) . '...';
    }

    private function esc(string $text): string
    {
        return str_replace(
            ['&', '<', '>', '"'],
            ['&amp;', '&lt;', '&gt;', '&quot;'],
            $text
        );
    }
}