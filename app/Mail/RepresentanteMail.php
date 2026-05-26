<?php

/* app/Mail/RepresentanteMail.php */

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RepresentanteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $email;
    public $senha;
    public $nome;
    public $loginUrl;

    public function __construct($user, $email, $senha, $nome)
    {
        $this->user = $user;
        $this->email = $email;
        $this->senha = $senha;
        $this->nome = $nome;
        $this->loginUrl = route('login');
    }

    public function build()
    {
        return $this->subject('Bem-vindo ao ' . config('app.name') . ' - Credenciais de Acesso')
                    ->view('emails.welcome-representante');
    }
}