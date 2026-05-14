<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Skin = variant texture/material appliqué au mesh de base d'un opérateur.
 *
 * On NE duplique PAS le mesh : Unity charge le .glb de base de l'opérateur
 * et applique la texture (slot _BaseMap principalement, plus overrides) via
 * MaterialPropertyBlock au runtime.
 *
 * Distinct de la table `cosmetics` (qui mélange skin/title/voiceline/banner/
 * border et n'a aucun champ 3D structuré). Un futur lien cosmetics ↔ operator_skins
 * peut être ajouté plus tard si on veut exposer les skins comme unlockables
 * via le système de cosmetics existant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operator_skins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 96)->unique();   // ex: vex-neon, vex-default
            $table->string('name', 128);
            $table->enum('rarity', ['common', 'rare', 'epic', 'legendary'])->default('rare');
            $table->json('palette_json')->nullable();           // [{slot:"primary",hex:"#ff00aa"}, ...]
            $table->string('texture_url')->nullable();          // .ktx2 ou .png slot _BaseMap
            $table->json('material_overrides')->nullable();      // {_MetallicGlossMap:"...", _BumpMap:"..."}
            $table->enum('generation_status', ['pending', 'queued', 'generating', 'ready', 'failed'])
                ->default('pending');
            $table->string('meshy_task_id', 64)->nullable();
            $table->string('preview_url')->nullable();           // image preview UI
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);       // skin par défaut de l'opérateur
            $table->timestamps();

            $table->index(['operator_id', 'is_active']);
            $table->index('generation_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_skins');
    }
};
