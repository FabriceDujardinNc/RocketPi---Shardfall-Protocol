<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bascule les URLs publiques / admin de `id` vers `slug` pour le SEO et la lisibilité.
 *
 * Models qui gagnent une colonne `slug` :
 *  - operators       (déjà `codename` unique mais on veut un slug formaté minuscule)
 *  - banners
 *  - missions
 *  - battle_passes
 *  - events
 *  - leaderboard_seasons
 *
 * Pas concernés :
 *  - factions          : slug est déjà le PK (ORBIT/FERRO/VEIL)
 *  - achievements      : `key` joue déjà le rôle de slug
 *  - daily_login_rewards : `day_number` est l'identifiant naturel
 *  - users             : déjà `slug` (depuis migration profil)
 *
 * Le slug est null-able initialement pour ne pas casser les rows existantes ;
 * un hook `booted::saving` sur chaque model (trait HasAutoSlug) le remplit
 * automatiquement à partir du name/title. Un backfill suit la migration.
 */
return new class extends Migration
{
    private const TABLES_FROM_NAME = [
        'banners',
        'missions',
        'battle_passes',
        'events',
        'leaderboard_seasons',
    ];

    public function up(): void
    {
        foreach (self::TABLES_FROM_NAME as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->string('slug', 96)->nullable()->after('id');
                $t->unique('slug', "{$table}_slug_unique");
            });
        }

        // Operators : déjà codename unique, on ajoute slug séparé pour matcher le pattern
        Schema::table('operators', function (Blueprint $t) {
            $t->string('slug', 96)->nullable()->after('id');
            $t->unique('slug', 'operators_slug_unique');
        });
    }

    public function down(): void
    {
        foreach (self::TABLES_FROM_NAME as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropUnique("{$table}_slug_unique");
                $t->dropColumn('slug');
            });
        }
        Schema::table('operators', function (Blueprint $t) {
            $t->dropUnique('operators_slug_unique');
            $t->dropColumn('slug');
        });
    }
};
