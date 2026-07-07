<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $resetUrl,
    ) {}

    public function build(): self
    {
        return $this->subject('ImpactSense — Reset Your Password')
            ->view('emails.reset-password');
    }
}
