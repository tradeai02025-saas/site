<?php

/* app/Mail/NotificacaoDocumentoAvulsoMail.php */

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificacaoDocumentoAvulsoMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $dados;

    public function __construct(array $dados)
    {
        $this->dados = $dados;
    }

    public function envelope(): Envelope
    {
        $totalDocs = count($this->dados['documentos_avulsos'] ?? []);
        $plural = $totalDocs > 1 ? 's' : '';
        
        return new Envelope(
            subject: "Novo{$plural} documento{$plural} solicitado{$plural} - PI {$this->dados['pi_numero']}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notificacao-documento-avulso',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}