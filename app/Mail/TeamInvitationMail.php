<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TeamInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $email;
    public $password;
    public $role;
    public $loginUrl;

    public function __construct($name, $email, $password, $role, $loginUrl)
    {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->role = $role;
        $this->loginUrl = $loginUrl;
    }

    public function build()
    {
        return $this->subject('Invitation à rejoindre l\'équipe FlashManager')
                    ->view('emails.invitation');
    }
}
