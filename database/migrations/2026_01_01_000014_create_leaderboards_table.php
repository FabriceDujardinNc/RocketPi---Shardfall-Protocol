<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Leaderboards : Redis Sorted Sets pour les classements actifs (temps réel),
// MySQL pour les snapshots historiques post-reset.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaderboard_seasons', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64);              // "Saison 1 — Éclat Primordial"
            $table->enum('type', ['weekly', 'monthly', 'seasonal', 'annual', 'collection', 'faction']);
            $table->string('faction', 8)->nullable(); // Pour type=faction : ORBIT|FERRO|VEIL
            $table->unsignedSmallInteger('season_number');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_active')->default(false);
            $table->boolean('rewards_distributed')->default(false);
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });

        Schema::create('leaderboard_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('leaderboard_seasons')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('score')->default(0);
            $table->unsignedInteger('rank')->nullable();           // Calculé au snapshot
            $table->unsignedInteger('games_played')->default(0);
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('daily_score_earned')->default(0); // Réinitialisé chaque jour pour le plafond
            $table->date('daily_score_reset_date')->nullable();
            $table->timestamps();

            $table->unique(['season_id', 'user_id']);
            $table->index(['season_id', 'score']);
            $table->index(['season_id', 'rank']);
        });

        Schema::create('leaderboard_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('leaderboard_seasons')->cascadeOnDelete();
            $table->string('tier', 32);               // 'top_1', 'top_10', 'top_100', 'top_1pct', 'top_10pct', 'top_50pct'
            $table->string('rank_min', 16);
            $table->string('rank_max', 16);
            $table->json('rewards');                  // [{type: shards, amount: 500}, {type: title, value: 'Apex Commandant'}…]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard_rewards');
        Schema::dropIfExists('leaderboard_entries');
        Schema::dropIfExists('leaderboard_seasons');
    }
};
