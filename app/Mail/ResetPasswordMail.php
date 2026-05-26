<?php

/* app/Mail/ResetPasswordMail.php */

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $url;
    public $token;

    public function __construct($user, $token)
    {
        $this->user = $user;
        $this->token = $token;
        $this->url = url(route('password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ], false));
    }

    public function build()
    {
        return $this->subject('Recuperação de Senha - ' . config('app.name'))
                    ->view('emails.reset-password');
    }
}