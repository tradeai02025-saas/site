<?php

/* app/Mail/NotificacaoValidacaoDocumentosMail.php */

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use App\Models\Pi;

class NotificacaoValidacaoDocumentosMail extends Mailable
{
    use Queueable, SerializesModels;

    public $piNumero;
    public $clienteNome;
    public $fornecedorNome;
    public $documentosReprovados;
    public $documentosReenvio;
    public $totalAcoes;
    public $temReprovados;
    public $urlReenvio;

    public function __construct(array $dados)
    {
        $this->piNumero = $dados['pi_numero'];
        $this->clienteNome = $dados['cliente_nome'];
        $this->fornecedorNome = $dados['fornecedor_nome'];

        $acoes = collect($dados['documentos_acao']);
        
        $this->documentosReprovados = $acoes->filter(function($doc) {
            return $doc['acao'] === 'reprovado';
        })->map(function($doc) {
            $doc['data_formatada'] = Carbon::parse($doc['data'])->format('d/m/Y H:i');
            return $doc;
        });

        $this->documentosReenvio = $acoes->filter(function($doc) {
            return $doc['acao'] === 'reenvio_solicitado';
        })->map(function($doc) {
            $doc['data_formatada'] = Carbon::parse($doc['data'])->format('d/m/Y H:i');
            return $doc;
        });

        $this->totalAcoes = $acoes->count();
        $this->temReprovados = $this->documentosReprovados->isNotEmpty();
        
        $pi = Pi::where('numero_pi', $this->piNumero)->first();
        $this->urlReenvio = $pi 
            ? route('pis.enviar_documentos', ['pi' => $pi->id]) 
            : route('pis.index');
    }

    public function build()
    {
        $subject = $this->temReprovados 
            ? '[PI ' . $this->piNumero . '] Documentos Reprovados - ' . config('app.name')
            : '[PI ' . $this->piNumero . '] Reenvio de Documentos Solicitado - ' . config('app.name');

        return $this->subject($subject)
                    ->view('emails.notificacao-validacao-documentos');
    }
}