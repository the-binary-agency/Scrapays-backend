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