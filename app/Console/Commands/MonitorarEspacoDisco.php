<?php

/* app/Console/Commands/MonitorarEspacoDisco.php */

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Cache, File};
use Carbon\Carbon;

class MonitorarEspacoDisco extends Command
{
    protected $signature = 'sistema:monitorar-disco';

    protected $description = 'Monitora uso de disco e alerta via Telegram quando atingir limites';

    private array $limites = [95, 85, 70];

    private int $quotaBytes = 107374182400;

    private string $dataExpiracao = '2026-09-05';

    public function handle()
    {
        $usadoBytes = $this->calcularUsoSite();

        if ($usadoBytes === false) {
            $this->error('Não foi possível obter informações do disco.');
            return Command::FAILURE;
        }

        $livreBytes = max(0, $this->quotaBytes - $usadoBytes);
        $percentualUsado = round(($usadoBytes / $this->quotaBytes) * 100, 1);

        $this->info("Disco: {$this->formatBytes($usadoBytes)} / {$this->formatBytes($this->quotaBytes)} ({$percentualUsado}%)");
        $this->info("Livre: {$this->formatBytes($livreBytes)}");

        $storagePath = storage_path();
        $storageSize = $this->calcularTamanho($storagePath);
        $logsSize = $this->calcularTamanho(storage_path('logs'));
        $appSize = $this->calcularTamanho(storage_path('app'));

        foreach ($this->limites as $limite) {
            if ($percentualUsado >= $limite) {
                $cacheKey = "disk_alert_{$limite}";

                if (!Cache::has($cacheKey)) {
                    $this->enviarAlerta($percentualUsado, $livreBytes, $usadoBytes, $logsSize, $appSize, $storageSize, $limite);
                    Cache::put($cacheKey, true, now()->addHours($this->getIntervaloHoras($limite)));
                }

                break;
            }
        }

        return Command::SUCCESS;
    }

    private function calcularUsoSite(): int|false
    {
        $size = $this->calcularTamanho(base_path());

        return $size > 0 ? $size : false;
    }

    private function enviarAlerta(float $percentual, int $livre, int $usado, int $logsSize, int $appSize, int $storageSize, int $limite): void
    {
        $emoji = match(true) {
            $percentual >= 95 => '🔴',
            $percentual >= 85 => '🟠',
            default => '🟡',
        };

        $expiracao = Carbon::parse($this->dataExpiracao);
        $hoje = Carbon::today();
        $diasRestantes = $hoje->diffInDays($expiracao, false);

        $msg = "{$emoji} <b>Alerta de Disco - {$percentual}% usado</b>\n\n";
        $msg .= "<b>Limite atingido:</b> {$limite}%\n\n";
        $msg .= "<b>Quota do plano:</b> {$this->formatBytes($this->quotaBytes)}\n";
        $msg .= "<b>Usado:</b> {$this->formatBytes($usado)}\n";
        $msg .= "<b>Livre:</b> {$this->formatBytes($livre)}\n\n";
        $msg .= "<b>Detalhamento storage/:</b>\n";
        $msg .= "  storage/ total: {$this->formatBytes($storageSize)}\n";
        $msg .= "  storage/logs/: {$this->formatBytes($logsSize)}\n";
        $msg .= "  storage/app/: {$this->formatBytes($appSize)}\n\n";

        $msg .= "<b>Hospedagem Hostinger:</b>\n";
        $msg .= "Expira em: {$expiracao->format('d/m/Y')}";
        if ($diasRestantes > 0) {
            $meses = intdiv($diasRestantes, 30);
            $dias = $diasRestantes % 30;
            $partes = [];
            if ($meses > 0) $partes[] = "{$meses} " . ($meses === 1 ? 'mês' : 'meses');
            if ($dias > 0) $partes[] = "{$dias} " . ($dias === 1 ? 'dia' : 'dias');
            $msg .= " (faltam " . implode(' e ', $partes) . ")\n\n";
        } else {
            $msg .= " (<b>EXPIRADA!</b>)\n\n";
        }

        if ($percentual >= 95) {
            $msg .= "<b>AÇÃO URGENTE NECESSÁRIA!</b>\n";
            $msg .= "Execute: <code>php artisan logs:limpar</code>\n";
        } elseif ($percentual >= 85) {
            $msg .= "Considere limpar logs antigos e arquivos temporários.\n";
        }

        $msg .= "\n" . now()->format('d/m/Y H:i:s');

        try {
            app(TelegramService::class)->send($msg);
            $this->info("Alerta enviado via Telegram (limite {$limite}%)");
        } catch (\Throwable $t) {
            $this->error("Erro ao enviar alerta: " . $t->getMessage());
        }
    }

    private function getIntervaloHoras(int $limite): int
    {
        return match(true) {
            $limite >= 95 => 2,
            $limite >= 85 => 6,
            default => 24,
        };
    }

    private function calcularTamanho(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        $total = 0;

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $total += $file->getSize();
                }
            }
        } catch (\Throwable $t) {
        }

        return $total;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}