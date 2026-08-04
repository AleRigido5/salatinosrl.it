<?php

namespace App\Mail;

use App\Models\InvoiceSent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public InvoiceSent $invoice;
    public string $bodyText;
    protected string $pdfContent;
    protected string $pdfFilename;
    protected string $subjectOverride;

    public function __construct(InvoiceSent $invoice, string $subject, string $bodyText, string $pdfContent, string $pdfFilename)
    {
        $this->invoice = $invoice;
        $this->bodyText = $bodyText;
        $this->pdfContent = $pdfContent;
        $this->pdfFilename = $pdfFilename;
        $this->subjectOverride = $subject;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectOverride,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-sent',
            with: [
                'bodyText' => $this->bodyText,
                'invoice' => $this->invoice,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}