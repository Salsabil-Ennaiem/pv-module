<?php

namespace SalsabilEnnaiem\PvModule\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use SalsabilEnnaiem\PvModule\Models\Pv;

class PvValidated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Pv $pv,
        public mixed $actor,
        public bool $isValidation,
        public ?string $commentaire,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->actor->name ?? $this->actor->email ?? 'Un utilisateur';

        return (new MailMessage)
            ->subject(($this->isValidation ? 'PV validé' : 'PV rejeté')." : {$this->pv->titre}")
            ->greeting('Bonjour '.($notifiable->name ?? ''))
            ->line("{$name} a ".($this->isValidation ? 'validé et signé' : 'rejeté')." le procès-verbal « {$this->pv->titre} ».")
            ->when($this->commentaire, fn (MailMessage $message) => $message
                ->line('Commentaire :')
                ->line($this->commentaire))
            ->action('Voir le PV', $this->pvUrl());
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->isValidation ? 'pv_validated' : 'pv_rejected',
            'pv_id' => $this->pv->id,
            'titre' => $this->pv->titre,
            'actor_id' => $this->actor->getKey(),
            'actor_name' => $this->actor->name ?? null,
            'commentaire' => $this->commentaire,
            'url' => $this->pvUrl(),
        ];
    }

    protected function pvUrl(): string
    {
        return route(config('pv-module.routes.name_prefix', 'pv-module.').'show', $this->pv);
    }
}