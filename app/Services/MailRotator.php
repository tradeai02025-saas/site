<?php

/* app/Services/MailRotator.php */

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\{Config, Log, Mail};

class MailRotator
{
    private static $controlFile = 'mail_limits.json';
    private static $maxPerBox = 100;
    private static $totalBoxes = 10;
    private static $adminEmail = 'tradeai02025@gmail.com';

    public static function send($mailable, $to, $data = [])
    {
        $lockPath = storage_path('app/' . self::$controlFile . '.lock');
        $lockHandle = fopen($lockPath, 'c');
        $box = null;
        $control = null;
        $agendadosParaProcessar = [];

        try {
            if ($lockHandle) {
                flock($lockHandle, LOCK_EX);
            }

            $control = self::loadControlRaw();
            $today = now()->toDateString();

            if ($control['date'] !== $today) {
                $agendadosParaProcessar = $control['scheduled'] ?? [];
                Log::channel('emails')->info("Novo dia - Resetando contadores. Agendados pendentes: " . count($agendadosParaProcessar));

                $control = self::buildFreshControl();
                self::saveControlRaw($control);
            }

            $box = self::getAvailableBox($control);

            if ($box === null) {
                $control['scheduled'][] = [
                    'mailable_class' => is_object($mailable) ? get_class($mailable) : $mailable,
                    'mailable_data' => is_object($mailable) ? self::extractMailableData($mailable) : [],
                    'to' => $to,
                    'data' => $data,
                    'scheduled_at' => now()->toDateTimeString()
                ];
                self::saveControlRaw($control);

                if ($lockHandle) {
                    flock($lockHandle, LOCK_UN);
                    fclose($lockHandle);
                    $lockHandle = null;
                }

                self::notifyAdmin(count($control['scheduled']));
                Log::channel('emails')->warning("LIMITE ATINGIDO - Email agendado para amanha. Total agendados: " . count($control['scheduled']), ['to' => $to]);

                self::processarAgendados($agendadosParaProcessar);
                return false;
            }

            $control['boxes'][$box]['sent']++;
            $control['boxes'][$box]['last_used'] = now()->toDateTimeString();
            self::saveControlRaw($control);

            if ($lockHandle) {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
                $lockHandle = null;
            }

            self::configureMailer($box);

            if (is_string($mailable)) {
                Mail::send($mailable, $data, function($message) use ($to) {
                    $message->to($to);
                });
            } else {
                Mail::to($to)->send($mailable);
            }

            Log::channel('emails')->info("Email enviado - Caixa {$box} ({$control['boxes'][$box]['sent']}/100)", ['to' => $to]);

            self::processarAgendados($agendadosParaProcessar);
            return true;

        } catch (Exception $e) {
            if ($lockHandle) {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
                $lockHandle = null;
            }

            Log::channel('emails')->error("Erro ao enviar: " . $e->getMessage(), ['to' => $to]);

            $result = self::retryWithNextBox($mailable, $to, $data, $control ?? self::loadControlSafe(), $box ?? 1);

            self::processarAgendados($agendadosParaProcessar);
            return $result;
        }
    }

    private static function processarAgendados(array $agendados)
    {
        if (empty($agendados)) {
            return;
        }

        Log::channel('emails')->info("Processando " . count($agendados) . " emails agendados do dia anterior");

        foreach ($agendados as $email) {
            try {
                $mailable = self::reconstructMailable($email);
                self::send($mailable, $email['to'], $email['data']);
            } catch (Exception $e) {
                Log::channel('emails')->error("Erro ao processar email agendado: " . $e->getMessage());
            }
        }
    }

    private static function loadControlRaw()
    {
        $path = storage_path('app/' . self::$controlFile);

        if (!file_exists($path)) {
            $control = self::buildFreshControl();
            self::saveControlRaw($control);
            return $control;
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (!$data || !isset($data['boxes'])) {
            $control = self::buildFreshControl();
            self::saveControlRaw($control);
            return $control;
        }

        return $data;
    }

    private static function loadControlSafe()
    {
        try {
            return self::loadControlRaw();
        } catch (Exception $e) {
            return self::buildFreshControl();
        }
    }

    private static function buildFreshControl()
    {
        $control = [
            'date' => now()->toDateString(),
            'boxes' => [],
            'scheduled' => []
        ];

        for ($i = 1; $i <= self::$totalBoxes; $i++) {
            $control['boxes'][$i] = [
                'sent' => 0,
                'limit' => self::$maxPerBox,
                'last_used' => null
            ];
        }

        return $control;
    }

    private static function saveControlRaw($control)
    {
        $path = storage_path('app/' . self::$controlFile);
        file_put_contents($path, json_encode($control, JSON_PRETTY_PRINT));
    }

    private static function getAvailableBox($control)
    {
        for ($i = 1; $i <= self::$totalBoxes; $i++) {
            if ($control['boxes'][$i]['sent'] < $control['boxes'][$i]['limit']) {
                return $i;
            }
        }
        return null;
    }

    private static function configureMailer($box)
    {
        $boxConfig = config("mail.boxes.{$box}");

        if (!$boxConfig) {
            $suffix = $box === 1 ? '' : "_{$box}";
            $boxConfig = [
                'host' => env("MAIL_HOST{$suffix}"),
                'port' => env("MAIL_PORT{$suffix}"),
                'encryption' => env("MAIL_ENCRYPTION{$suffix}"),
                'username' => env("MAIL_USERNAME{$suffix}"),
                'password' => env("MAIL_PASSWORD{$suffix}"),
                'from_address' => env("MAIL_FROM_ADDRESS{$suffix}"),
                'from_name' => env("MAIL_FROM_NAME{$suffix}", env('APP_NAME')),
            ];
        }

        if (empty($boxConfig['host']) || empty($boxConfig['port']) || empty($boxConfig['username']) || empty($boxConfig['password'])) {
            throw new \Exception("Configuração de email incompleta para a caixa {$box}.");
        }

        Config::set('mail.mailers.smtp', [
            'transport' => 'smtp',
            'host' => $boxConfig['host'],
            'port' => (int)$boxConfig['port'],
            'encryption' => $boxConfig['encryption'],
            'username' => $boxConfig['username'],
            'password' => $boxConfig['password'],
            'timeout' => 30,
        ]);

        Config::set('mail.from', [
            'address' => $boxConfig['from_address'],
            'name' => $boxConfig['from_name'] ?? env('APP_NAME'),
        ]);
    }

    private static function retryWithNextBox($mailable, $to, $data, $control, $currentBox)
    {
        if (! isset($control['boxes'][$currentBox]['errors'])) {
            $control['boxes'][$currentBox]['errors'] = 0;
        }
        $control['boxes'][$currentBox]['errors']++;

        if ($control['boxes'][$currentBox]['errors'] >= 3) {
            $control['boxes'][$currentBox]['sent'] = self::$maxPerBox;
        }

        self::saveControlRaw($control);

        Log::channel('emails')->warning("Caixa {$currentBox} com erro - Tentando proxima");

        $nextBox = self::getAvailableBox($control);
        if ($nextBox === null) {
            $control['scheduled'][] = [
                'mailable_class' => is_object($mailable) ? get_class($mailable) : $mailable,
                'mailable_data' => is_object($mailable) ? self::extractMailableData($mailable) : [],
                'to' => $to,
                'data' => $data,
                'scheduled_at' => now()->toDateTimeString()
            ];
            self::saveControlRaw($control);
            self::notifyAdmin(count($control['scheduled']));
            Log::channel('emails')->warning("LIMITE ATINGIDO - Email agendado para amanha. Total agendados: " . count($control['scheduled']), ['to' => $to]);
            return false;
        }

        try {
            self::configureMailer($nextBox);

            if (is_string($mailable)) {
                Mail::send($mailable, $data, function($message) use ($to) {
                    $message->to($to);
                });
            } else {
                Mail::to($to)->send($mailable);
            }

            $control['boxes'][$nextBox]['sent']++;
            self::saveControlRaw($control);
            return true;

        } catch (Exception $e) {
            Log::channel('emails')->error("Falha total: " . $e->getMessage());
            return false;
        }
    }

    private static function notifyAdmin($totalScheduled)
    {
        try {
            self::configureMailer(1);

            $message = "LIMITE DE EMAILS ATINGIDO\n\n";
            $message .= "Todas as 10 caixas atingiram 100 emails/24h\n";
            $message .= "Emails agendados para amanha: {$totalScheduled}\n\n";
            $message .= "Os emails serao enviados automaticamente as 00:00";

            Mail::raw($message, function($mail) {
                $mail->to(self::$adminEmail)->subject('Limite de Emails - ' . now()->format('d/m/Y H:i'));
            });

            Log::channel('emails')->info("Admin notificado sobre limite excedido");

        } catch (Exception $e) {
            Log::channel('emails')->error("Erro ao notificar admin: " . $e->getMessage());
        }

        try {
            app(\App\Services\TelegramService::class)->alertMailRotator($totalScheduled);
        } catch (\Throwable $t) {
        }
    }

    private static function extractMailableData($mailable): array
    {
        $data = [];
        $reflection = new \ReflectionObject($mailable);
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $value = $prop->getValue($mailable);
            if (is_scalar($value) || is_array($value) || is_null($value)) {
                $data[$prop->getName()] = $value;
            } elseif ($value instanceof \Illuminate\Database\Eloquent\Model) {
                $data[$prop->getName()] = ['__model' => get_class($value), '__id' => $value->getKey()];
            }
        }
        return $data;
    }

    private static function reconstructMailable(array $email)
    {
        $class = $email['mailable_class'] ?? $email['mailable'] ?? null;
        $mailableData = $email['mailable_data'] ?? [];

        if (!$class || !class_exists($class)) {
            throw new Exception("Classe de email não encontrada: {$class}");
        }

        $args = [];
        foreach ($mailableData as $key => $value) {
            if (is_array($value) && isset($value['__model'], $value['__id'])) {
                $modelClass = $value['__model'];
                if (class_exists($modelClass)) {
                    $args[$key] = $modelClass::find($value['__id']);
                }
            } else {
                $args[$key] = $value;
            }
        }

        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return new $class;
        }

        $params = [];
        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $args)) {
                $params[] = $args[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $params[] = $param->getDefaultValue();
            } else {
                $params[] = null;
            }
        }

        return $reflection->newInstanceArgs($params);
    }

    public static function getStats()
    {
        $control = self::loadControlRaw();

        $today = now()->toDateString();
        if ($control['date'] !== $today) {
            $control = self::buildFreshControl();
            self::saveControlRaw($control);
        }

        $stats = [
            'date' => $control['date'],
            'total_sent' => 0,
            'total_limit' => self::$totalBoxes * self::$maxPerBox,
            'scheduled' => count($control['scheduled']),
            'boxes' => []
        ];

        foreach ($control['boxes'] as $box => $data) {
            $stats['total_sent'] += $data['sent'];
            $stats['boxes'][$box] = [
                'sent' => $data['sent'],
                'remaining' => $data['limit'] - $data['sent'],
                'percentage' => round(($data['sent'] / $data['limit']) * 100, 1)
            ];
        }

        return $stats;
    }
}