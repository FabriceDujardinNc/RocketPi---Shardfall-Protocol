<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Résultats individuels d'un match (1 ligne par joueur impliqué).
 *
 * En 1v1 / 1-player PvE, un seul résultat est créé. En 5v5, jusqu'à 10 lignes
 * partagent le même `match_session_id`. La table est immuable une fois
 * `validated_at` rempli — audit légal des classements compétitifs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('kills')->default(0);
            $table->unsignedInteger('deaths')->default(0);
            $table->unsignedInteger('assists')->default(0);
            $table->boolean('won')->default(false);
            $table->boolean('is_mvp')->default(false);
            $table->integer('rank_points_delta')->default(0); // Variation MMR
            $table->unsignedInteger('rank_points_after')->default(0); // Snapshot
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->unique(['match_session_id', 'user_id']);
            $table->index(['user_id', 'won']);
            $table->index(['user_id', 'validated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_results');
    }
};
