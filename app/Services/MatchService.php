<?php

namespace App\Services;

use App\Models\MatchResult;
use App\Models\MatchSession;
use App\Models\Operator;
use App\Models\PlayerOperator;
use App\Models\User;
use App\Notifications\RankPromoted;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Cycle de vie d'un match (Phase 4 — Unity WebGL).
 *
 * Flow autoritaire :
 *  1. Client appelle `/api/unity/session/start` → MatchService::start
 *     • Vérifie limites (50/jour ranked, opérateur possédé)
 *     • Crée MatchSession + session_token SHA-256
 *     • Renvoie token + signature pour Unity
 *  2. Unity joue le match côté Photon / WebGL
 *  3. Unity appelle `/api/unity/match/result` avec session_token + payload
 *     → MatchService::finish
 *     • Vérifie session valide (token + statut started + pas trop vieille)
 *     • Validation anti-cheat (score plausible vs durée, kills < theoretical max…)
 *     • Calcule rank delta via RankingService
 *     • Crée MatchResult immuable
 *     • Met à jour user.rank_points + daily_matches_played
 *     • Hook leaderboard saisonnier
 *
 * Toutes les écritures dans une `DB::transaction` + lockForUpdate sur User.
 */
class MatchService
{
    /** Durée min/max plausible d'un match (secondes). */
    private const MIN_DURATION_SECONDS = 30;
    private const MAX_DURATION_SECONDS = 30 * 60;

    /** Score max plausible / seconde (anti-cheat). 100 pts/sec = ~180k sur 30 min. */
    private const MAX_SCORE_PER_SECOND = 100;
    /** Kills max plausibles / seconde. */
    private const MAX_KILLS_PER_SECOND = 1.5;

    /** Session token TTL : la session doit être finalisée dans ce délai. */
    private const SESSION_TTL_SECONDS = 35 * 60;

    public function __construct(
        private readonly RankingService $ranking,
    ) {}

    /**
     * Démarre une nouvelle session de match. Renvoie le token à passer à Unity.
     *
     * @return array{session: MatchSession, session_token: string}
     */
    public function start(
        User $user,
        string $mode = 'deathmatch',
        string $rankType = 'casual',
        ?int $operatorUsedId = null,
        ?string $clientIp = null,
        ?string $clientFingerprint = null,
    ): array {
        if (! in_array($mode, MatchSession::MODES, true)) {
            throw new RuntimeException("Mode invalide : {$mode}");
        }
        if (! in_array($rankType, MatchSession::RANK_TYPES, true)) {
            throw new RuntimeException("Type de classement invalide : {$rankType}");
        }

        if ($rankType === 'ranked') {
            $this->assertDailyLimit($user);
        }

        if ($operatorUsedId !== null) {
            $owned = PlayerOperator::where('user_id', $user->id)
                ->where('operator_id', $operatorUsedId)
                ->exists();
            if (! $owned) {
                throw new RuntimeException('Tu ne possèdes pas cet opérateur.');
            }
        }

        // Annule toutes les sessions zombies du user (en cas de crash navigateur)
        MatchSession::where('user_id', $user->id)
            ->where('status', 'started')
            ->where('started_at', '<', now()->subSeconds(self::SESSION_TTL_SECONDS))
            ->update(['status' => 'abandoned']);

        $token = hash('sha256', $user->id.'|'.Str::random(48).'|'.microtime(true));

        $session = MatchSession::create([
            'user_id'            => $user->id,
            'session_token'      => $token,
            'mode'               => $mode,
            'rank_type'          => $rankType,
            'operator_used_id'   => $operatorUsedId,
            'status'             => 'started',
            'started_at'         => now(),
            'client_ip'          => $clientIp,
            'client_fingerprint' => $clientFingerprint,
        ]);

        return ['session' => $session, 'session_token' => $token];
    }

    /**
     * Finalise un match avec validation autoritaire. Atomique sur User + Session.
     *
     * @param  array{score?:int, kills?:int, deaths?:int, assists?:int, won?:bool, is_mvp?:bool, duration_seconds?:int}  $payload
     */
    public function finish(string $sessionToken, array $payload, ?LeaderboardService $leaderboard = null): MatchResult
    {
        return DB::transaction(function () use ($sessionToken, $payload, $leaderboard) {
            $session = MatchSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();
            if (! $session) {
                throw new RuntimeException('Session inconnue ou expirée.');
            }
            if ($session->status !== 'started') {
                throw new RuntimeException("Session déjà clôturée (statut : {$session->status}).");
            }
            if ($session->started_at->diffInSeconds(now()) > self::SESSION_TTL_SECONDS) {
                $session->update(['status' => 'abandoned']);
                throw new RuntimeException('Session expirée.');
            }

            $user = User::where('id', $session->user_id)->lockForUpdate()->first();
            if (! $user) {
                throw new RuntimeException('Joueur introuvable.');
            }

            $score    = max(0, (int) ($payload['score']            ?? 0));
            $kills    = max(0, (int) ($payload['kills']            ?? 0));
            $deaths   = max(0, (int) ($payload['deaths']           ?? 0));
            $assists  = max(0, (int) ($payload['assists']          ?? 0));
            $won      = (bool)         ($payload['won']             ?? false);
            $isMvp    = (bool)         ($payload['is_mvp']          ?? false);
            $duration = max(0, (int) ($payload['duration_seconds'] ?? $session->started_at->diffInSeconds(now())));

            $this->validateAntiCheat($duration, $score, $kills);

            $rankDelta = 0;
            $rankAfter = $user->rank_points;
            $tierBefore = $this->ranking->tierFor($user->rank_points);
            if ($session->rank_type === 'ranked') {
                $rankDelta = $this->ranking->pointsDelta($won, $isMvp, $user->rank_points);
                $rankAfter = $this->ranking->applyDelta($user, $rankDelta);
                $this->bumpDailyCounter($user);
            }
            $tierAfter = $this->ranking->tierFor($rankAfter);

            // Notification de promotion si le tier change vers le haut.
            // (Pas de notif sur démotion — moins motivant, et trop fréquent en
            // haut de classement où une mauvaise série fait yo-yo.)
            if ($tierBefore !== $tierAfter && $this->isPromotion($tierBefore, $tierAfter)) {
                $user->notify(new RankPromoted($tierBefore, $tierAfter, $rankAfter));
            }

            $session->update([
                'status'           => 'finished',
                'finished_at'      => now(),
                'duration_seconds' => $duration,
                'result_signature' => hash_hmac('sha256', json_encode([
                    'session_token' => $sessionToken, 'score' => $score, 'kills' => $kills,
                    'deaths' => $deaths, 'assists' => $assists, 'won' => $won, 'is_mvp' => $isMvp,
                ], JSON_THROW_ON_ERROR), config('app.key')),
            ]);

            $result = MatchResult::create([
                'match_session_id'   => $session->id,
                'user_id'            => $user->id,
                'score'              => $score,
                'kills'              => $kills,
                'deaths'             => $deaths,
                'assists'            => $assists,
                'won'                => $won,
                'is_mvp'             => $isMvp,
                'rank_points_delta'  => $rankDelta,
                'rank_points_after'  => $rankAfter,
                'validated_at'       => now(),
            ]);

            // Hook leaderboard saisonnier : alimente la saison compétitive
            // active si rank_type=ranked. Casual ne contribue pas.
            if ($leaderboard !== null && $session->rank_type === 'ranked' && $score > 0) {
                $activeSeasons = $leaderboard->activeSeasons()->where('type', 'seasonal');
                foreach ($activeSeasons as $s) {
                    $leaderboard->addPoints($user, $s, $score);
                }
            }

            return $result;
        });
    }

    /**
     * Annule explicitement une session (joueur quitte). Compte comme défaite
     * en ranked pour éviter le rage-quit gratuit.
     */
    public function abandon(string $sessionToken): void
    {
        $session = MatchSession::where('session_token', $sessionToken)->first();
        if (! $session || $session->status !== 'started') {
            return;
        }

        $session->update([
            'status'      => 'abandoned',
            'finished_at' => now(),
        ]);

        if ($session->rank_type === 'ranked') {
            DB::transaction(function () use ($session) {
                $user = User::where('id', $session->user_id)->lockForUpdate()->first();
                if (! $user) return;
                $delta = $this->ranking->pointsDelta(false, false, $user->rank_points);
                $this->ranking->applyDelta($user, $delta);
                $this->bumpDailyCounter($user);
                MatchResult::create([
                    'match_session_id' => $session->id,
                    'user_id'          => $user->id,
                    'won'              => false,
                    'rank_points_delta' => $delta,
                    'rank_points_after' => $user->fresh()->rank_points,
                    'validated_at'     => now(),
                ]);
            });
        }
    }

    private function assertDailyLimit(User $user): void
    {
        $today = CarbonImmutable::now('UTC')->toDateString();
        $reset = $user->daily_matches_reset_at?->toDateString();
        if ($reset !== $today) {
            $user->forceFill([
                'daily_matches_played'   => 0,
                'daily_matches_reset_at' => $today,
            ])->save();
        }
        if ($user->daily_matches_played >= RankingService::DAILY_MATCH_LIMIT) {
            throw new RuntimeException(
                'Limite quotidienne atteinte ('.RankingService::DAILY_MATCH_LIMIT.' matchs classés/jour).'
            );
        }
    }

    private function bumpDailyCounter(User $user): void
    {
        $today = CarbonImmutable::now('UTC')->toDateString();
        $reset = $user->daily_matches_reset_at?->toDateString();
        if ($reset !== $today) {
            $user->forceFill([
                'daily_matches_played'   => 1,
                'daily_matches_reset_at' => $today,
            ])->save();
        } else {
            $user->forceFill([
                'daily_matches_played' => $user->daily_matches_played + 1,
            ])->save();
        }
    }

    /**
     * Compare l'ordre des tiers : true si $after > $before dans la hiérarchie.
     */
    private function isPromotion(string $before, string $after): bool
    {
        $order = ['bronze' => 0, 'silver' => 1, 'gold' => 2, 'platinum' => 3, 'diamond' => 4, 'master' => 5];
        return ($order[$after] ?? 0) > ($order[$before] ?? 0);
    }

    private function validateAntiCheat(int $duration, int $score, int $kills): void
    {
        if ($duration < self::MIN_DURATION_SECONDS) {
            throw new RuntimeException("Durée match trop courte ({$duration}s).");
        }
        if ($duration > self::MAX_DURATION_SECONDS) {
            throw new RuntimeException("Durée match trop longue ({$duration}s).");
        }

        $maxScore = $duration * self::MAX_SCORE_PER_SECOND;
        if ($score > $maxScore) {
            throw new RuntimeException("Score impossible ({$score} > {$maxScore} max plausible).");
        }

        $maxKills = (int) floor($duration * self::MAX_KILLS_PER_SECOND);
        if ($kills > $maxKills) {
            throw new RuntimeException("Kills impossibles ({$kills} > {$maxKills} max plausibles).");
        }
    }
}
