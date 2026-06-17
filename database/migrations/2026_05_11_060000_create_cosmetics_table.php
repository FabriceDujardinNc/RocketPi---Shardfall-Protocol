<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogue de cosmétiques (skins, titres, voicelines, bannières de profil, bordures).
 *
 * Avant cette table, les cosmétiques étaient implicites via des currency types
 * `cosmetic_*` (cosmetic_title_apex, cosmetic_border_legend…) — RewardService
 * les créditait comme compteurs opaques sans qu'on puisse afficher icône / nom.
 *
 * Ce catalogue donne une représentation propre aux cosmétiques unlocables :
 *  - un slug stable utilisé comme « currency type » virtuel
 *  - un type fonctionnel (skin / title / voiceline / banner / border)
 *  - un opérateur lié (optionnel — un skin appartient à un opérateur, un titre non)
 *  - une rareté pour le tri / preview
 *
 * Les déblocages joueur restent stockés via player_cosmetics (pivot).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cosmetics', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 96)->unique();    // ex: title_apex_2026, skin_vex_neon
            $table->string('name', 128);
            $table->text('description')->nullable();
            $table->enum('type', ['skin', 'title', 'voiceline', 'banner', 'border']);
            $table->enum('rarity', ['common', 'rare', 'epic', 'legendary'])->default('rare');
            $table->foreignId('operator_id')->nullable()->constrained()->nullOnDelete();
            $table->string('preview_url')->nullable();   // image/audio preview
            $table->string('asset_url')->nullable();     // ressource exposée au joueur
            $table->boolean('is_active')->default(true); // catalogué mais activable
            $table->json('metadata')->nullable();         // libre : audio_duration, palette hex, etc.
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index(['operator_id']);
        });

        Schema::create('player_cosmetics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cosmetic_id')->constrained()->cascadeOnDelete();
            $table->timestamp('unlocked_at')->useCurrent();
            $table->string('source', 32)->nullable();    // gacha, battle_pass, achievement, admin_grant…
            $table->boolean('is_equipped')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'cosmetic_id']);
            $table->index(['user_id', 'is_equipped']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_cosmetics');
        Schema::dropIfExists('cosmetics');
    }
};
