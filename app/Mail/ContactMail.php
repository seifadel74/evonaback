<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public ContactMessage $message;

    public function __construct(ContactMessage $message)
    {
        $this->message = $message;
    }

    public function build(): static
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('رسالة جديدة من ' . $this->message->name)
            ->markdown('emails.contact');
    }
}
