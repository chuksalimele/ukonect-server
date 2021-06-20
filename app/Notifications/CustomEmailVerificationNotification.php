<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;
use Illuminate\Auth\Notifications\VerifyEmail;


class CustomEmailVerificationNotification extends VerifyEmail
{

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
        
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {

        $verificationUrl = $this->verificationUrl($notifiable);

        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $verificationUrl);
        }

        return (new MailMessage)
            ->subject(Lang::getFromJson('Email Address Verification'))
            ->line(Lang::getFromJson('To proceed in the registration procces, please click the button below so we can verify your email address.'))
            ->action(Lang::getFromJson('Verify Email Address'), $verificationUrl)
            ->line(Lang::getFromJson('If you did not create an account, no further action is required.'));
    }

}
