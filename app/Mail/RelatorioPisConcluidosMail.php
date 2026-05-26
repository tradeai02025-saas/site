<?php

/* app/Mail/RelatorioPisConcluidosMail.php */

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RelatorioPisConcluidosMail extends Mailable
{
    use Queueable, SerializesModels;

    public $fornecedorNome;
    public $data;
    public $pis;
    public $total;

    public function __construct(array $dados)
    {
        $this->fornecedorNome = $dados['fornecedor_nome'];
        $this->data = $dados['data'];
        $this->pis = $dados['pis'];
        $this->total = $dados['total'];
    }

    public function build()
    {
        return $this->subject('[Relatório] PIs Concluídos em ' . $this->data . ' - ' . config('app.name'))
                    ->view('emails.relatorio-pis-concluidos');
    }
}