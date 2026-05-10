<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Stocke le compteur pity par joueur et par bannière.
// Critique : doit être mis à jour atomiquement avec chaque tirage (DB::transaction + lockForUpdate).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pity_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('banner_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('legendary_counter')->default(0);  // Resetté à 0 quand légendaire obtenu
            $table->unsignedSmallInteger('epic_counter')->default(0);       // Resetté à 0 quand épique obtenu
            $table->unsignedBigInteger('total_pulls')->default(0);          // Total tirages sur cette bannière
            $table->timestamps();

            $table->unique(['user_id', 'banner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pity_counters');
    }
};
