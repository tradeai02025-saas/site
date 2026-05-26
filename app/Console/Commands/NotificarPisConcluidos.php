<?php

/* app/Console/Commands/NotificarPisConcluidos.php */

namespace App\Console\Commands;

use App\Mail\RelatorioPisConcluidosMail;
use App\Models\Pi;
use App\Services\MailRotator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NotificarPisConcluidos extends Command
{
    protected $signature = 'pis:notificar-concluidos';

    protected $description = 'Envia relatório diário de PIs concluídos agrupados por fornecedor';

    public function handle()
    {
        $this->info('====== NOTIFICANDO PIs CONCLUIDOS ======');
        $this->info('Data/Hora: '.now()->format('d/m/Y H:i:s'));

        $hoje = now()->toDateString();

        $pisConcluidos = Pi::where('concluido', true)
            ->whereDate('data_conclusao', $hoje)
            ->whereNotNull('fornecedor_id')
            ->with(['cliente:id,razao_social', 'fornecedor.user:id,email'])
            ->get();

        if ($pisConcluidos->isEmpty()) {
            $this->info('Nenhum PI concluído hoje.');

            return Command::SUCCESS;
        }

        $agrupados = $pisConcluidos->groupBy('fornecedor_id');

        $emailsEnviados = 0;
        $erros = 0;

        foreach ($agrupados as $fornecedorId => $pis) {
            $fornecedor = $pis->first()->fornecedor;

            if (! $fornecedor || ! $fornecedor->user || ! $fornecedor->user->email) {
                $this->warn("Fornecedor #{$fornecedorId} sem email, pulando.");

                continue;
            }

            try {
                $dados = [
                    'fornecedor_nome' => $fornecedor->razao_social,
                    'data' => now()->format('d/m/Y'),
                    'pis' => $pis->map(function ($pi) {
                        return [
                            'numero_pi' => $pi->numero_pi,
                            'cliente' => $pi->cliente ? $pi->cliente->razao_social : 'N/A',
                            'data_conclusao' => $pi->data_conclusao ? $pi->data_conclusao->format('d/m/Y H:i') : now()->format('d/m/Y H:i'),
                        ];
                    })->toArray(),
                    'total' => $pis->count(),
                ];

                MailRotator::send(
                    new RelatorioPisConcluidosMail($dados),
                    $fornecedor->user->email
                );

                enviar_para_representantes(new RelatorioPisConcluidosMail($dados), $fornecedorId);

                $emailsEnviados++;
                $this->info("Email enviado para {$fornecedor->razao_social} ({$pis->count()} PIs)");
            } catch (\Exception $e) {
                $erros++;
                $this->error("Erro ao enviar para {$fornecedor->razao_social}: {$e->getMessage()}");
            }
        }

        $this->info("====== CONCLUIDO: {$emailsEnviados} emails enviados, {$erros} erros ======");

        return Command::SUCCESS;
    }
}