<?php

/* app/Console/Commands/ReconciliarDocumentosOrfaos.php */

namespace App\Console\Commands;

use App\Models\{DocumentoEnviado, Pi, PiDocumento, Status};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconciliarDocumentosOrfaos extends Command
{
    protected $signature = 'documentos:reconciliar-orfaos
                            {--dry-run : Apenas mostra o que seria alterado sem executar}
                            {--pi= : Reconciliar apenas um PI específico por ID}';

    protected $description = 'Corrige PiDocumentos que apontam para DocumentoEnviado rejeitado quando existe um substituto válido';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $piId = $this->option('pi');

        $statusRejeitados = Status::where('tipo', 'documento')
            ->whereIn('nome', ['Reprovado', 'Aguardando Reenvio'])
            ->where('ativo', true)
            ->pluck('id')
            ->toArray();

        if (empty($statusRejeitados)) {
            $this->error('Status de rejeição não encontrados no banco.');
            return Command::FAILURE;
        }

        $query = PiDocumento::whereNotNull('documento_enviado_id')
            ->whereHas('documentoEnviado', function ($q) use ($statusRejeitados) {
                $q->whereIn('status_id', $statusRejeitados);
            })
            ->whereHas('pi', function ($q) use ($piId) {
                $q->where('concluido', false)
                    ->whereNull('deleted_at');
                if ($piId) {
                    $q->where('id', $piId);
                }
            });

        $piDocsProblematicos = $query->with([
            'pi.fornecedor',
            'tipoDocumento',
            'documentoEnviado.status',
        ])->get();

        if ($piDocsProblematicos->isEmpty()) {
            $this->info('Nenhum PiDocumento com problema encontrado.');
            return Command::SUCCESS;
        }

        $this->info("Encontrados {$piDocsProblematicos->count()} PiDocumento(s) apontando para documentos rejeitados.");
        $this->newLine();

        $corrigidos = 0;
        $semSolucao = 0;
        $pisParaAtualizar = collect();

        foreach ($piDocsProblematicos as $piDoc) {
            $pi = $piDoc->pi;
            $fornecedorId = $pi->fornecedor_id;
            $tipoDocId = $piDoc->tipo_documento_id;
            $tipoDocumento = $piDoc->tipoDocumento;
            $docAtualId = $piDoc->documento_enviado_id;
            $docAtualStatus = $piDoc->documentoEnviado?->status?->nome ?? 'N/A';
            $tipoDocNome = $tipoDocumento->nome ?? 'N/A';
            $ehCompartilhavel = $tipoDocumento && $tipoDocumento->validar;

            $docSubstituto = null;

            if ($ehCompartilhavel) {
                $docSubstituto = DocumentoEnviado::where('fornecedor_id', $fornecedorId)
                    ->where('tipo_documento_id', $tipoDocId)
                    ->where('id', '!=', $docAtualId)
                    ->whereHas('status', function ($q) {
                        $q->whereIn('nome', ['Aprovado', 'Pendente', 'Aguardando validação']);
                    })
                    ->orderBy('data_envio', 'desc')
                    ->first();
            } else {
                $docSubstituto = DocumentoEnviado::where('fornecedor_id', $fornecedorId)
                    ->where('tipo_documento_id', $tipoDocId)
                    ->where('id', '!=', $docAtualId)
                    ->where('data_envio', '>', $piDoc->documentoEnviado->data_envio ?? now()->subYears(10))
                    ->whereHas('status', function ($q) {
                        $q->whereIn('nome', ['Aprovado', 'Pendente', 'Aguardando validação']);
                    })
                    ->whereDoesntHave('piDocumentos')
                    ->orderBy('data_envio', 'desc')
                    ->first();
            }

            $tipoLabel = $ehCompartilhavel ? '[compartilhável]' : '[específico por PI]';

            if (! $docSubstituto) {
                $semSolucao++;
                $this->line("  PI #{$pi->numero_pi} | {$tipoDocNome} {$tipoLabel}: Rejeitado, sem documento substituto encontrado.");
                continue;
            }

            $novoStatus = $docSubstituto->status;

            if (! $novoStatus) {
                $semSolucao++;
                $this->line("  PI #{$pi->numero_pi} | {$tipoDocNome} {$tipoLabel}: Documento substituto sem status definido, ignorando.");
                continue;
            }

            $statusPiDoc = null;

            if ($novoStatus->nome === 'Aprovado') {
                $statusPiDoc = Status::where('tipo', 'documento')
                    ->where('nome', 'Atendido')
                    ->where('ativo', true)
                    ->first();
            }

            if (! $statusPiDoc) {
                $statusPiDoc = $novoStatus;
            }

            if ($dryRun) {
                $this->line("  PI #{$pi->numero_pi} | {$tipoDocNome} {$tipoLabel}:");
                $this->line("    Atual: DocEnviado #{$docAtualId} ({$docAtualStatus})");
                $this->line("    Novo:  DocEnviado #{$docSubstituto->id} ({$novoStatus->nome}) enviado em {$docSubstituto->data_envio->format('d/m/Y H:i')}");
                $corrigidos++;
                $pisParaAtualizar->push($pi->id);
                continue;
            }

            DB::beginTransaction();
            try {
                $piDoc->update([
                    'documento_enviado_id' => $docSubstituto->id,
                    'status_id' => $statusPiDoc->id,
                ]);

                $corrigidos++;
                $pisParaAtualizar->push($pi->id);

                $this->line("  PI #{$pi->numero_pi} | {$tipoDocNome} {$tipoLabel}: DocEnviado #{$docAtualId} -> #{$docSubstituto->id} ({$novoStatus->nome})");

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("  Erro ao corrigir PI #{$pi->numero_pi} | {$tipoDocNome}: {$e->getMessage()}");
            }
        }

        $this->newLine();

        if ($corrigidos === 0 && $semSolucao === 0) {
            $this->info('Nenhuma correção necessária.');
            return Command::SUCCESS;
        }

        if ($semSolucao > 0) {
            $this->warn("{$semSolucao} PiDocumento(s) rejeitados sem substituto — precisam de reenvio manual pelo fornecedor.");
        }

        $pisUnicos = $pisParaAtualizar->unique();

        if (! $dryRun && $pisUnicos->isNotEmpty()) {
            $this->newLine();
            $this->info("Recalculando status de {$pisUnicos->count()} PI(s)...");

            foreach ($pisUnicos as $piId) {
                $pi = Pi::find($piId);
                if ($pi) {
                    $statusAnterior = $pi->status ? $pi->status->nome : 'N/A';
                    $novoStatus = $pi->atualizarStatusAutomaticamente();
                    if ($novoStatus) {
                        $this->line("  PI #{$pi->numero_pi}: {$statusAnterior} -> {$novoStatus}");
                    } else {
                        $this->line("  PI #{$pi->numero_pi}: {$statusAnterior} (sem alteração)");
                    }
                }
            }
        }

        $this->newLine();
        $prefixo = $dryRun ? '[DRY-RUN] Seriam corrigidos' : 'Corrigidos';
        $this->info("{$prefixo}: {$corrigidos} PiDocumento(s) em {$pisUnicos->count()} PI(s).");

        return Command::SUCCESS;
    }
}