<?php

/* app/Console/Commands/ReconciliarStatusPis.php */

namespace App\Console\Commands;

use App\Models\Pi;
use Illuminate\Console\Command;

class ReconciliarStatusPis extends Command
{
    protected $signature = 'pis:reconciliar-status
                            {--dry-run : Apenas mostra o que seria alterado}
                            {--pi= : Reconciliar apenas um PI específico por ID}';

    protected $description = 'Reconcilia os status dos PIs baseado no estado real dos documentos';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $piId = $this->option('pi');

        $query = Pi::where('concluido', false)->whereNull('deleted_at');

        if ($piId) {
            $query->where('id', $piId);
        }

        $pis = $query->with(['status'])->get();

        $this->info("Verificando {$pis->count()} PI(s)...");

        $alterados = 0;

        foreach ($pis as $pi) {
            $statusAnterior = $pi->status ? $pi->status->nome : 'N/A';

            if ($dryRun) {
                $totalDocs = $pi->piDocumentos()->count();
                $docsEnviados = $pi->piDocumentos()->whereNotNull('documento_enviado_id')->count();
                $todosAprovados = $pi->todosDocumentosAprovados();
                $flag = $todosAprovados ? ' [TODOS APROVADOS]' : '';
                $this->line("  PI #{$pi->numero_pi}: Status={$statusAnterior} | Docs: {$docsEnviados}/{$totalDocs} enviados{$flag}");
            } else {
                $novoStatus = $pi->atualizarStatusAutomaticamente();
                if ($novoStatus) {
                    $alterados++;
                    $this->line("  PI #{$pi->numero_pi}: {$statusAnterior} -> {$novoStatus}");
                }
            }
        }

        if (! $dryRun) {
            $this->info("{$alterados} PI(s) atualizados.");
        }

        return Command::SUCCESS;
    }
}