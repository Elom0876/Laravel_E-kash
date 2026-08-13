<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ReinitialisationMotDePasseNotification extends Notification
{
    use Queueable;

    public function __construct(protected string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim(config('app.frontend_url'), '/') . '/definir-mot-de-passe?token=' . $this->token . '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Définissez votre mot de passe — E-kash')
            ->greeting('Bonjour ' . $notifiable->name)
            ->line('Cliquez sur le bouton ci-dessous pour définir votre mot de passe.')
            ->action('Définir mon mot de passe', $url)
            ->line('Ce lien expire dans 60 minutes.');
    }
}
