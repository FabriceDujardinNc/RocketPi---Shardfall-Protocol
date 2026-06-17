<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->string('title', 128);
            $table->text('description')->nullable();
            $table->enum('type', ['daily', 'weekly', 'event', 'story', 'challenge']);
            $table->string('objective_type', 32);      // login, pull, pvp_win, mission_complete, reach_level…
            $table->unsignedInteger('objective_target'); // Valeur cible (ex: 3 pour "gagner 3 matchs")
            $table->json('rewards');                    // [{type: shards, amount: 50}, {type: tickets, amount: 1}]
            $table->unsignedSmallInteger('xp_reward')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_active']);
        });

        Schema::create('mission_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('progress')->default(0);
            $table->boolean('completed')->default(false);
            $table->boolean('reward_claimed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'mission_id']);
            $table->index(['user_id', 'completed', 'reward_claimed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_progress');
        Schema::dropIfExists('missions');
    }
};
