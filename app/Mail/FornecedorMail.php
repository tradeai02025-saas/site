<?php

/* app/Mail/FornecedorMail.php */

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FornecedorMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $email;
    public $senha;
    public $razaoSocial;
    public $loginUrl;

    public function __construct($user, $email, $senha, $razaoSocial)
    {
        $this->user = $user;
        $this->email = $email;
        $this->senha = $senha;
        $this->razaoSocial = $razaoSocial;
        $this->loginUrl = route('login');
    }

    public function build()
    {
        return $this->subject('Bem-vindo ao ' . config('app.name') . ' - Credenciais de Acesso')
                    ->view('emails.welcome-fornecedor');
    }
}