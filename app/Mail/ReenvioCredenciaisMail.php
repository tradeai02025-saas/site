<?php

/* app/Mail/ReenvioCredenciaisMail.php */

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReenvioCredenciaisMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $email;
    public $senha;
    public $nome;
    public $tipoUsuario;
    public $loginUrl;

    public function __construct($user, $email, $senha, $nome, $tipoUsuario = 'fornecedor')
    {
        $this->user = $user;
        $this->email = $email;
        $this->senha = $senha;
        $this->nome = $nome;
        $this->tipoUsuario = $tipoUsuario;
        $this->loginUrl = route('login');
    }

    public function build()
    {
        return $this->subject('Novas Credenciais de Acesso - ' . config('app.name'))
                    ->view('emails.reenvio-credenciais');
    }
}