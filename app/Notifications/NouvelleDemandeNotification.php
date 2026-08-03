<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Demande;

class NouvelleDemandeNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected Demande $demande)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouvelle demande de dépense')
            ->greeting('Bonjour ' . $notifiable->name)
            ->line($this->demande->user->name . ' a soumis une nouvelle demande.')
            ->line('Motif : ' . $this->demande->motif)
            ->line('Montant estimé : ' . number_format($this->demande->montant_estime, 0, ',', ' ') . ' FCFA')
            ->action('Voir la demande', url('/demandes/' . $this->demande->id))
            ->line('Merci de traiter cette demande dans les meilleurs délais.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'demande_id' => $this->demande->id,
            'motif' => $this->demande->motif,
        ];
    }
}
