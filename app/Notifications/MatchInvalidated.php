<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email envoyé quand un match a été rejeté par l'anti-cheat
 * (score impossible, durée invalide, kills suspects).
 *
 * Vocation : transparence — le joueur sait que sa session a été flaggée
 * et peut contacter le support. Pas de sanction directe (ban si récidive
 * détectée par les outils admin).
 */
class MatchInvalidated extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $sessionId,
        public readonly string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Session de match invalidée — RocketPi Shardfall')
            ->greeting('Opérateur, une de tes sessions a été rejetée.')
            ->line("Référence : **#{$this->sessionId}**.")
            ->line("Motif : {$this->reason}.")
            ->line("Aucun point de classement n'a été appliqué pour cette session. Si tu penses qu'il s'agit d'une erreur (lag, désync), contacte le support avec la référence.")
            ->action('Voir mes sessions', url('/play'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'match_invalidated',
            'session_id' => $this->sessionId,
            'reason'     => $this->reason,
        ];
    }
}
