<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_logins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('login_date');
            $table->unsignedSmallInteger('streak_day');          // Jour dans le cycle mensuel (1-30)
            $table->unsignedSmallInteger('streak_count');        // Streak consécutive globale
            $table->boolean('reward_claimed')->default(false);
            $table->timestamp('claimed_at')->nullable();

            $table->unique(['user_id', 'login_date']);
            $table->index(['user_id', 'login_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_logins');
    }
};
