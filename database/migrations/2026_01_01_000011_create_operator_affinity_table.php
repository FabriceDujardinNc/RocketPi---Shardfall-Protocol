<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operator_affinities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('level')->default(0);       // 0-10
            $table->unsignedInteger('xp_current')->default(0);       // XP vers le prochain niveau
            $table->json('unlocked_rewards')->nullable();             // Skins, voicelines débloqués
            $table->timestamps();

            $table->unique(['user_id', 'operator_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_affinities');
    }
};
