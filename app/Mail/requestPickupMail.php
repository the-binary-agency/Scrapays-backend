<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class requestPickupMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $pickup;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $pickup)
    {
        $this->user = $user;
        $this->pickup = $pickup;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('Email.pickupRequest')->with([
            'user' => $this->user,
            'pickup' => $this->pickup
            ]);
    }
}
// MAIL_DRIVER=smtp
// MAIL_HOST=mail.scrapays.com
// MAIL_PORT=465
// MAIL_USERNAME=no-reply@scrapays.com
// MAIL_PASSWORD=scrapays2019
// MAIL_ENCRYPTION=tls
// MAIL_FROM_ADDRESS=no-reply@scrapays.com
// MAIL_FROM_NAME="${APP_NAME}"