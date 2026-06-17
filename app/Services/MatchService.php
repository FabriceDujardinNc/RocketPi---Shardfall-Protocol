<?php

namespace App\Services;

use App\Models\MatchResult;
use App\Models\MatchSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Cycle de vie d'un match Unity WebGL (mode training simplifié).
 *
 * Flow :
 *  1. POST /api/unity/session/start  → MatchService::start  (token)
 *  2. Unity joue le match
 *  3. POST /api/unity/match/result    → MatchService::finish (validation anti-cheat basique)
 *
 * Plus de classement / ranked / currency / opérateurs possédés depuis la
 * refonte « site simplifié ». Le mode reste autoritaire pour valider que les
 * scores envoyés sont plausibles (cap par seconde) avant de stocker.
 */
class MatchService
{
    private const MIN_DURATION_SECONDS = 30;
    private const MAX_DURATION_SECONDS = 30 * 60;
    private const MAX_SCORE_PER_SECOND = 100;
    private const MAX_KILLS_PER_SECOND = 1.5;
    private const SESSION_TTL_SECONDS  = 35 * 60;

    /**
     * @return array{session: MatchSession, session_token: string}
     */
    public function start(
        User $user,
        string $mode = 'deathmatch',
        ?string $clientIp = null,
        ?string $clientFingerprint = null,
    ): array {
        // Annule les sessions zombies
        MatchSession::where('user_id', $user->id)
            ->where('status', 'started')
            ->where('started_at', '<', now()->subSeconds(self::SESSION_TTL_SECONDS))
            ->update(['status' => 'abandoned']);

        $token = hash('sha256', $user->id.'|'.Str::random(48).'|'.microtime(true));

        $session = MatchSession::create([
            'user_id'            => $user->id,
            'session_token'      => $token,
            'mode'               => $mode,
            'rank_type'          => 'casual',
            'status'             => 'started',
            'started_at'         => now(),
            'client_ip'          => $clientIp,
            'client_fingerprint' => $clientFingerprint,
        ]);

        return ['session' => $session, 'session_token' => $token];
    }

    /**
     * @param  array{score?:int, kills?:int, deaths?:int, assists?:int, won?:bool, is_mvp?:bool, duration_seconds?:int}  $payload
     */
    public function finish(string $sessionToken, array $payload): MatchResult
    {
        return DB::transaction(function () use ($sessionToken, $payload) {
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

            $score    = max(0, (int) ($payload['score']            ?? 0));
            $kills    = max(0, (int) ($payload['kills']            ?? 0));
            $deaths   = max(0, (int) ($payload['deaths']           ?? 0));
            $assists  = max(0, (int) ($payload['assists']          ?? 0));
            $won      = (bool)         ($payload['won']             ?? false);
            $isMvp    = (bool)         ($payload['is_mvp']          ?? false);
            $duration = max(0, (int) ($payload['duration_seconds'] ?? $session->started_at->diffInSeconds(now())));

            $this->validateAntiCheat($duration, $score, $kills);

            $session->update([
                'status'           => 'finished',
                'finished_at'      => now(),
                'duration_seconds' => $duration,
            ]);

            return MatchResult::create([
                'match_session_id' => $session->id,
                'user_id'          => $session->user_id,
                'score'            => $score,
                'kills'            => $kills,
                'deaths'           => $deaths,
                'assists'          => $assists,
                'won'              => $won,
                'is_mvp'           => $isMvp,
                'validated_at'     => now(),
            ]);
        });
    }

    public function abandon(string $sessionToken): void
    {
        $session = MatchSession::where('session_token', $sessionToken)->first();
        if (! $session || $session->status !== 'started') {
            return;
        }
        $session->update(['status' => 'abandoned', 'finished_at' => now()]);
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
