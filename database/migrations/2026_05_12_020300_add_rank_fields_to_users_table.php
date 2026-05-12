<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Champs classement compétitif sur users (Phase 4).
 *
 * - `rank_points` : MMR brut (entier, peut être négatif transitoirement
 *   après une cascade de défaites mais clipped à 0 en sortie d'API).
 * - `daily_matches_played` + `daily_matches_reset_at` : limite 50/jour
 *   anti-burnout + anti-farm, reset minuit UTC.
 *
 * Le tier (Bronze 1 → Master) est dérivé de rank_points dans `RankingService`,
 * pas stocké pour éviter les désync.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('rank_points')->default(0)->after('account_xp');
            $table->unsignedTinyInteger('daily_matches_played')->default(0)->after('rank_points');
            $table->date('daily_matches_reset_at')->nullable()->after('daily_matches_played');
            $table->index('rank_points');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['rank_points']);
            $table->dropColumn(['rank_points', 'daily_matches_played', 'daily_matches_reset_at']);
        });
    }
};
