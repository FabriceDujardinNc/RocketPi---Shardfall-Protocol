<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email envoyé J-3 avant la fin d'une saison classée (hebdo / mensuelle / saison).
 *
 * Déclenché par une commande scheduled (`Schedule::command('seasons:notify-ending')`)
 * — voir routes/console.php. Évite de spammer : la commande ne notifie que les
 * joueurs ayant participé activement (score > 0).
 */
class SeasonEndingSoon extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $seasonName,
        public readonly string $endsAt,
        public readonly int $currentRank,
        public readonly int $currentScore,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Saison se termine bientôt : {$this->seasonName}")
            ->greeting('Opérateur, dernière ligne droite.')
            ->line("La saison **{$this->seasonName}** se termine le **{$this->endsAt}**.")
            ->line("Ton rang actuel : **#{$this->currentRank}** avec **{$this->currentScore} pts**.")
            ->line('Les paliers de récompense sont calculés à la clôture — gagner encore quelques points peut changer ton tier.')
            ->action('Jouer maintenant', url('/play'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'season_ending_soon',
            'season_name'  => $this->seasonName,
            'ends_at'      => $this->endsAt,
            'current_rank' => $this->currentRank,
            'current_score' => $this->currentScore,
        ];
    }
}
