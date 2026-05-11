<?php

namespace App\Services;

use App\Models\DailyLogin;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Connexion quotidienne avec calendrier de récompenses mensuel.
 * Spec : paliers spéciaux jours 1, 7, 15, 30.
 *
 * - Premier appel du jour → enregistre login + calcule streak
 * - claim() → débloque les rewards si pas déjà claim aujourd'hui
 */
class DailyLoginService
{
    public function __construct(private readonly RewardService $rewards) {}

    /**
     * Récompenses par jour du cycle (1-30). Les paliers spéciaux (1, 7, 15, 30)
     * donnent des shards en plus des credits standards.
     */
    public const REWARDS_BY_DAY = [
        1  => [['type' => 'credits', 'amount' => 200], ['type' => 'shards', 'amount' => 30]],
        7  => [['type' => 'credits', 'amount' => 500], ['type' => 'shards', 'amount' => 50]],
        15 => [['type' => 'credits', 'amount' => 1000], ['type' => 'shards', 'amount' => 100]],
        30 => [['type' => 'credits', 'amount' => 2000], ['type' => 'shards', 'amount' => 300], ['type' => 'tickets_premium', 'amount' => 1]],
    ];

    public const DEFAULT_REWARD = [['type' => 'credits', 'amount' => 100]];

    public function recordToday(User $user): DailyLogin
    {
        $today = CarbonImmutable::now()->startOfDay()->toDateString();

        return DB::transaction(function () use ($user, $today) {
            // whereDate : SQLite stocke le cast `date` avec une partie heure (00:00:00),
            // donc un `where('login_date', '2026-05-11')` ne matcherait pas.
            $existing = DailyLogin::where('user_id', $user->id)
                ->whereDate('login_date', $today)
                ->first();

            if ($existing) {
                return $existing;
            }

            $previous = DailyLogin::where('user_id', $user->id)
                ->orderByDesc('login_date')
                ->first();

            $yesterday = CarbonImmutable::now()->subDay()->toDateString();
            $streakCount = ($previous && $previous->login_date->toDateString() === $yesterday)
                ? $previous->streak_count + 1
                : 1;

            $streakDay = (($streakCount - 1) % 30) + 1;

            return DailyLogin::create([
                'user_id'      => $user->id,
                'login_date'   => $today,
                'streak_day'   => $streakDay,
                'streak_count' => $streakCount,
            ]);
        });
    }

    public function todayStatus(User $user): array
    {
        $today = $this->recordToday($user);

        return [
            'streak_day'     => $today->streak_day,
            'streak_count'   => $today->streak_count,
            'reward_claimed' => $today->reward_claimed,
            'reward'         => $this->rewardForDay($today->streak_day),
        ];
    }

    public function claim(User $user, ?string $ipAddress = null): array
    {
        $today = $this->recordToday($user);

        if ($today->reward_claimed) {
            throw new RuntimeException('Récompense quotidienne déjà réclamée.');
        }

        return DB::transaction(function () use ($user, $today, $ipAddress) {
            $reward = $this->rewardForDay($today->streak_day);

            $this->rewards->apply($user, $reward, 'daily_login', $today, $ipAddress);

            $today->update(['reward_claimed' => true, 'claimed_at' => now()]);

            return [
                'streak_day'   => $today->streak_day,
                'streak_count' => $today->streak_count,
                'reward'       => $reward,
            ];
        });
    }

    public function rewardForDay(int $day): array
    {
        return self::REWARDS_BY_DAY[$day] ?? self::DEFAULT_REWARD;
    }
}
