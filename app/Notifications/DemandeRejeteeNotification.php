<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Demande;

class DemandeRejeteeNotification extends Notification
{
    use Queueable;

    public function __construct(protected Demande $demande) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre demande a été rejetée')
            ->greeting('Bonjour ' . $notifiable->name)
            ->line('Votre demande "' . $this->demande->motif . '" a été rejetée.')
            ->line('Montant demandé : ' . number_format($this->demande->montant_estime, 0, ',', ' ') . ' FCFA')
            ->line('Contactez le gestionnaire pour plus de détails si nécessaire.');
    }

    public function toArray(object $notifiable): array
    {
        return ['demande_id' => $this->demande->id];
    }
}
