<?php

namespace SalsabilEnnaiem\PvModule\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use SalsabilEnnaiem\PvModule\Models\Pv;

class PvValidationRequest extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Pv $pv,
        public mixed $recipient,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Validation de PV requise : {$this->pv->titre}")
            ->greeting('Bonjour '.($notifiable->name ?? ''))
            ->line("Un nouveau procès-verbal « {$this->pv->titre} » est prêt pour votre signature.")
            ->line('Ce PV a été généré le '.$this->pv->date_generation?->format('d/m/Y à H:i'))
            ->action('Voir et signer le PV', $this->pvUrl())
            ->line('Si le délai de signature n\'est pas déjà dépassé, merci de répondre dans les meilleurs délais.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'pv_validation_request',
            'pv_id' => $this->pv->id,
            'titre' => $this->pv->titre,
            'url' => $this->pvUrl(),
            'date_generation' => $this->pv->date_generation?->toIso8601String(),
        ];
    }

    protected function pvUrl(): string
    {
        return route('pv-module.show', $this->pv);
    }
}