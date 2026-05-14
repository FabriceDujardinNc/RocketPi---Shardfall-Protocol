<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Loadout = configuration équipement active d'un joueur pour un opérateur :
 * skin + arme + skin d'arme + accessoires (head/face/back).
 *
 * Une seule ligne par couple (user, operator) — le joueur change son loadout
 * en updatant cette ligne. Pour l'historique on s'appuie sur les timestamps
 * + journal applicatif (pas de versioning loadout en BDD).
 *
 * Les FKs des slots cosmétiques sont nullable → un loadout peut être partiel
 * (l'opérateur tombe sur ses defaults : operator_skins.is_default,
 * operator_accessories.is_default).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_loadouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();

            $table->foreignId('operator_skin_id')->nullable()->constrained('operator_skins')->nullOnDelete();
            $table->foreignId('weapon_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('weapon_skin_id')->nullable()->constrained('weapon_skins')->nullOnDelete();

            $table->foreignId('head_accessory_id')->nullable()->constrained('accessories')->nullOnDelete();
            $table->foreignId('face_accessory_id')->nullable()->constrained('accessories')->nullOnDelete();
            $table->foreignId('back_accessory_id')->nullable()->constrained('accessories')->nullOnDelete();

            $table->timestamps();

            $table->unique(['user_id', 'operator_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_loadouts');
    }
};
