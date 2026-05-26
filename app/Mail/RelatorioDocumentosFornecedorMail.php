<?php

/* app/Mail/RelatorioDocumentosFornecedorMail.php */

namespace App\Mail;

use App\Models\Fornecedor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class RelatorioDocumentosFornecedorMail extends Mailable
{
    use Queueable, SerializesModels;

    public $fornecedor;
    public $documentosVencidos;
    public $documentosVencendo;
    public $pisPendentes;
    public $hoje;
    public $temUrgencia;

    public function __construct(Fornecedor $fornecedor, array $dadosRelatorio)
    {
        $this->fornecedor = $fornecedor;
        $this->hoje = $dadosRelatorio['hoje'];
        
        $this->documentosVencidos = $dadosRelatorio['documentos_vencidos']->map(function($doc) {
            $dataValidade = Carbon::parse($doc->data_validade_documento);
            $doc->dias_vencido = $this->hoje->diffInDays($dataValidade);
            $doc->data_validade_formatada = $dataValidade->format('d/m/Y');
            return $doc;
        });

        $this->documentosVencendo = $dadosRelatorio['documentos_vencendo']->map(function($doc) {
            $dataValidade = Carbon::parse($doc->data_validade_documento);
            $diasRestantes = $this->hoje->diffInDays($dataValidade);
            
            $doc->dias_restantes = $diasRestantes;
            $doc->is_urgente = $diasRestantes <= 7;
            $doc->data_validade_formatada = $dataValidade->format('d/m/Y');
            return $doc;
        });

        $this->pisPendentes = $dadosRelatorio['pis_pendentes'];

        $this->temUrgencia = $this->documentosVencidos->isNotEmpty() || 
                             $this->documentosVencendo->where('is_urgente', true)->isNotEmpty();
    }

    public function build()
    {
        $subject = $this->temUrgencia 
            ? '⚠️ URGENTE: Documentos Requerem Atenção - ' . config('app.name')
            : 'Relatório de Documentação - ' . config('app.name');

        return $this->subject($subject)
                    ->view('emails.relatorio-documentos-fornecedor')
                    ->with([
                        'fornecedor' => $this->fornecedor,
                        'documentosVencidos' => $this->documentosVencidos,
                        'documentosVencendo' => $this->documentosVencendo,
                        'pisPendentes' => $this->pisPendentes,
                        'hoje' => $this->hoje,
                        'temUrgencia' => $this->temUrgencia,
                    ]);
    }
}