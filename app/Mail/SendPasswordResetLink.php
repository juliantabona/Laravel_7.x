<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendPasswordResetLink extends Mailable
{
    use Queueable, SerializesModels;
    
    public $user;
    public $password_reset_link;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct( $user, $password_reset_link )
    {
        $this->user = $user;
        $this->password_reset_link = $password_reset_link;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Reset Password')->view('emails.passwords.reset');
    }
}
