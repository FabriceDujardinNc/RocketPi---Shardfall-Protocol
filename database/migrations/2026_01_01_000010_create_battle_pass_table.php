<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('battle_passes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64);
            $table->unsignedSmallInteger('season_number');
            $table->unsignedSmallInteger('total_tiers')->default(50);
            $table->unsignedInteger('premium_price_shards')->default(1000); // ~10€ en équivalent shards
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('battle_pass_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('battle_pass_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('tier_number');      // 1-50
            $table->unsignedInteger('xp_required');           // XP cumulé requis
            $table->json('free_reward')->nullable();           // Récompense gratuite
            $table->json('premium_reward')->nullable();        // Récompense premium
            $table->boolean('is_milestone')->default(false);  // Paliers spéciaux (5, 10, 25, 50)

            $table->unique(['battle_pass_id', 'tier_number']);
        });

        Schema::create('battle_pass_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('battle_pass_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_premium')->default(false);
            $table->unsignedInteger('xp_earned')->default(0);
            $table->unsignedSmallInteger('current_tier')->default(0);
            $table->json('claimed_tiers')->nullable();         // [1, 2, 5, …]
            $table->timestamp('purchased_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'battle_pass_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('battle_pass_progress');
        Schema::dropIfExists('battle_pass_tiers');
        Schema::dropIfExists('battle_passes');
    }
};
