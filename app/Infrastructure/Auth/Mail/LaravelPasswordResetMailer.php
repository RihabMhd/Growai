<?php

namespace Infrastructure\Auth\Mail;

use App\Mail\ResetPasswordMail;
use Application\Auth\Contracts\PasswordResetMailerInterface;
use Illuminate\Support\Facades\Mail;

class LaravelPasswordResetMailer implements PasswordResetMailerInterface
{
    public function send(string $email, string $plainToken): void
    {
        Mail::to($email)->send(new ResetPasswordMail($plainToken));
    }
}
