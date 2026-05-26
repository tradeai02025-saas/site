<?php

/* app/Console/Commands/LimparLogsAntigos.php */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class LimparLogsAntigos extends Command
{
    protected $signature = 'logs:limpar
                            {--dias=14 : Remover logs mais antigos que N dias}
                            {--dry-run : Mostra o que seria deletado sem executar}';

    protected $description = 'Remove arquivos de log antigos para liberar espaço em disco';

    public function handle()
    {
        $dias = (int) $this->option('dias');
        $isDryRun = $this->option('dry-run');
        $limite = Carbon::now()->subDays($dias);

        $this->info("Limpando logs com mais de {$dias} dias...");

        if ($isDryRun) {
            $this->warn('MODO DRY RUN - Nada sera deletado');
        }

        $logPath = storage_path('logs');
        $totalArquivos = 0;
        $totalBytes = 0;

        $diretorios = File::directories($logPath);
        $diretorios[] = $logPath;

        foreach ($diretorios as $diretorio) {
            $arquivos = File::glob($diretorio . '/*.log');

            foreach ($arquivos as $arquivo) {
                $modificado = Carbon::createFromTimestamp(File::lastModified($arquivo));

                if ($modificado->lt($limite)) {
                    $tamanho = File::size($arquivo);
                    $nomeRelativo = str_replace(storage_path('logs') . DIRECTORY_SEPARATOR, '', $arquivo);

                    if ($isDryRun) {
                        $this->line("  [DRY] {$nomeRelativo} ({$this->formatBytes($tamanho)}) - {$modificado->format('d/m/Y')}");
                    } else {
                        File::delete($arquivo);
                    }

                    $totalArquivos++;
                    $totalBytes += $tamanho;
                }
            }
        }

        $this->newLine();

        if ($totalArquivos === 0) {
            $this->info('Nenhum log antigo encontrado.');
            return Command::SUCCESS;
        }

        $acao = $isDryRun ? 'Encontrados' : 'Removidos';
        $this->info("{$acao}: {$totalArquivos} arquivo(s) - {$this->formatBytes($totalBytes)} liberados");

        return Command::SUCCESS;
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
