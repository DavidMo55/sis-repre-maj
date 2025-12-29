<?php

namespace App\Mail;

use App\Models\PasswordTicket; 
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordRecoveryRequested extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * El objeto Ticket que se está solicitando.
     */
    public $ticket;

    /**
     * Crear una nueva instancia de mensaje.
     */
    public function __construct(PasswordTicket $ticket)
    {
        $this->ticket = $ticket;
    }

    /**
     * Obtener el sobre del mensaje.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ' Nuevo Ticket: Solicitud de Recuperación de Contraseña (' . $this->ticket->email_provided . ')',
        );
    }

    /**
     * Obtener la definición del contenido del mensaje.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.password-recovery-request',
            with: [
                'username' => $this->ticket->username_provided,
                'email' => $this->ticket->email_provided,
                'ticketId' => $this->ticket->id,
            ],
        );
    }
}