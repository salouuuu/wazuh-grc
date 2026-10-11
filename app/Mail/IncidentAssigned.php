<?php

namespace App\Mail;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IncidentAssigned extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Incident $incident,
        public User $responsable
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouvel incident GRC à traiter - #' . $this->incident->id
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.incident-assigned',
        );
    }
}
