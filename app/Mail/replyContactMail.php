<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class replyContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reply;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($reply)
    {
        $this->reply = $reply;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('hello@scrapays.com')
            ->subject('Contact Message Response')
            ->replyTo($this->reply->msg_email)
            ->markdown('Email.replyContactMessage');
    }
}
