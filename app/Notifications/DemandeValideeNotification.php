<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Demande;

class DemandeValideeNotification extends Notification
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
            ->subject('Votre demande a été validée')
            ->greeting('Bonjour ' . $notifiable->name)
            ->line('Votre demande "' . $this->demande->motif . '" a été validée.')
            ->line('Montant : ' . number_format($this->demande->montant_estime, 0, ',', ' ') . ' FCFA')
            ->line('Vous pouvez maintenant retirer les fonds et procéder à la dépense.')
            ->line('Pensez à soumettre votre preuve d\'achat une fois la dépense effectuée.');
    }

    public function toArray(object $notifiable): array
    {
        return ['demande_id' => $this->demande->id];
    }
}
