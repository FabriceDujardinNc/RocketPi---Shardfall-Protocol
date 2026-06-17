<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sort les récompenses de DailyLoginService::REWARDS_BY_DAY (constante PHP) vers
 * une table éditable. day_number est unique 1-30 ; les jours absents tombent
 * sur DailyLoginService::DEFAULT_REWARD (100 credits) côté service.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_login_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('day_number')->unique(); // 1-30 typique, max 365
            $table->json('rewards');                              // [{type,amount},...]
            $table->boolean('is_milestone')->default(false);      // affichage spécial côté joueur
            $table->string('label', 64)->nullable();              // libellé éditorial optionnel
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_login_rewards');
    }
};
