<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lien cosmétique ↔ palier d'affinité.
 *
 * Quand un cosmétique a `unlock_at_affinity` non null et `operator_id` défini,
 * il est attribué automatiquement au joueur quand son OperatorAffinity atteint
 * ou dépasse ce niveau (cf. AffinityService::award).
 *
 * Exemple : skin_vex_neon (operator_id=42, unlock_at_affinity=5) → débloqué
 * automatiquement au level 5 d'affinité Vex.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cosmetics', function (Blueprint $table) {
            $table->unsignedTinyInteger('unlock_at_affinity')->nullable()->after('operator_id');
            $table->index(['operator_id', 'unlock_at_affinity']);
        });
    }

    public function down(): void
    {
        Schema::table('cosmetics', function (Blueprint $table) {
            $table->dropIndex(['operator_id', 'unlock_at_affinity']);
            $table->dropColumn('unlock_at_affinity');
        });
    }
};
