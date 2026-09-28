<?php

declare(strict_types=1);

namespace App\Mail;

use App\Services\Website\VerificationCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly string $purpose,
        string $mailLocale = 'ka',
    ) {
        $this->locale($mailLocale === 'en' ? 'en' : 'ka');
    }

    public function envelope(): Envelope
    {
        $reset = $this->purpose === VerificationCodeService::PURPOSE_RESET;
        $subject = $this->locale === 'en'
            ? ($reset ? 'Your BiteClub password reset code' : 'Your BiteClub verification code')
            : ($reset ? 'BiteClub — პაროლის აღდგენის კოდი' : 'BiteClub — ელფოსტის დადასტურების კოდი');

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.verification-code', with: [
            'code' => $this->code,
            'isReset' => $this->purpose === VerificationCodeService::PURPOSE_RESET,
            'isEnglish' => $this->locale === 'en',
        ]);
    }
}
