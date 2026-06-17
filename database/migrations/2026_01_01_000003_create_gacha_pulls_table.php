<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Table d'audit légal pour tous les tirages gacha.
// Chaque ligne = 1 tirage individuel (pas un pack de 10).
// Conservée indéfiniment pour conformité réglementaire (loot box transparency).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gacha_pulls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('banner_id')->constrained()->restrictOnDelete();
            $table->foreignId('operator_id')->constrained()->restrictOnDelete();

            $table->enum('rarity', ['common', 'rare', 'epic', 'legendary']);
            $table->string('currency_used', 16);   // "shards", "tickets_premium"
            $table->unsignedSmallInteger('cost');   // coût en unités de currency_used

            // État du pity au moment du tirage (audit + debug)
            $table->unsignedSmallInteger('pity_count_before');  // compteur avant ce tirage
            $table->boolean('was_pity_hit')->default(false);    // tirage déclenché par pity
            $table->boolean('was_soft_pity')->default(false);   // dans la zone soft pity
            $table->boolean('was_rate_up')->default(false);     // était rate-up sur la bannière

            // Contexte de session (anti-triche, support client)
            $table->string('session_id', 64)->nullable();
            $table->string('ip_address', 45)->nullable();

            // Horodatage immuable (pas de updated_at)
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['banner_id', 'created_at']);
            $table->index('rarity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gacha_pulls');
    }
};
