<?php

/* app/Mail/NotificacaoConclusaoPiMail.php */

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificacaoConclusaoPiMail extends Mailable
{
    use Queueable, SerializesModels;

    public $piNumero;
    public $clienteNome;
    public $fornecedorNome;
    public $dataConclusao;
    public $totalDocumentos;
    public $urlPi;

    public function __construct(array $dados)
    {
        $this->piNumero = $dados['pi_numero'];
        $this->clienteNome = $dados['cliente_nome'];
        $this->fornecedorNome = $dados['fornecedor_nome'];
        $this->dataConclusao = $dados['data_conclusao'];
        $this->totalDocumentos = $dados['total_documentos'];
        $this->urlPi = $dados['url_pi'];
    }

    public function build()
    {
        return $this->subject('[PI ' . $this->piNumero . '] Concluído com Sucesso - ' . config('app.name'))
                    ->view('emails.notificacao-conclusao-pi');
    }
}