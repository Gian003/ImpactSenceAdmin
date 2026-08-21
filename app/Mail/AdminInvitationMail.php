<?php

namespace App\Mail;

use App\Models\AdminInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly AdminInvitation $invitation) {}

    public function envelope(): Envelope
    {
        $role = $this->invitation->role === 'toc' ? 'TOC Officer' : 'Investigation Officer';
        return new Envelope(subject: "ImpactSense — You've been invited as {$role}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.admin-invitation');
    }

    public function attachments(): array
    {
        return [];
    }
}
