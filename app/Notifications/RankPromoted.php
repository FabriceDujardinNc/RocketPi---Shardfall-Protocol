<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email envoyé quand un joueur change de tier compétitif (Bronze → Argent, etc.).
 *
 * Déclenché par `MatchService::finish` quand le tier d'avant/après diffère.
 * Channel `mail` + `database` (persisté pour notif center futur).
 *
 * Tone "Shardfall Protocol" — bref, militaire, sans hyperbole gacha vulgaire.
 */
class RankPromoted extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $previousTier,
        public readonly string $newTier,
        public readonly int $rankPoints,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tierLabels = [
            'bronze'   => 'Bronze',
            'silver'   => 'Argent',
            'gold'     => 'Or',
            'platinum' => 'Platine',
            'diamond'  => 'Diamant',
            'master'   => 'Maître',
        ];

        $prev = $tierLabels[$this->previousTier] ?? $this->previousTier;
        $new  = $tierLabels[$this->newTier] ?? $this->newTier;

        return (new MailMessage)
            ->subject("Promotion : {$new} — RocketPi Shardfall")
            ->greeting("Opérateur, ta progression est confirmée.")
            ->line("Tu viens de quitter le palier **{$prev}** pour entrer au palier **{$new}**.")
            ->line("Points de classement actuels : **{$this->rankPoints}**.")
            ->action('Voir le tableau de bord', url('/play'))
            ->line('Continue ainsi — le Hall of Fame attend les plus tenaces.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'rank_promoted',
            'previous_tier'  => $this->previousTier,
            'new_tier'       => $this->newTier,
            'rank_points'    => $this->rankPoints,
        ];
    }
}
