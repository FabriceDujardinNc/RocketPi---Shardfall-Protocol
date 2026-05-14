<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accessoires = équipements 3D additionnels attachés au rig de l'opérateur :
 * casque, masque, sac, holster, lunettes…
 *
 * `slot` détermine la zone fonctionnelle (head, face, back, hands, legs).
 * `socket_name` détermine le point d'attache concret sur le squelette (cf.
 * unity-client/docs/RIG_CONTRACT.md).
 *
 * Un même accessoire peut être compatible avec plusieurs opérateurs (pivot
 * operator_accessories). is_default sur le pivot = équipé par défaut quand
 * l'opérateur est invoqué (avant tout choix joueur).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accessories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 96)->unique();
            $table->string('name', 128);
            $table->enum('slot', ['head', 'face', 'back', 'hands', 'legs']);
            $table->enum('rarity', ['common', 'rare', 'epic', 'legendary'])->default('common');
            $table->string('base_model_url')->nullable();         // .glb
            $table->string('socket_name', 32);                    // ex: Head_Top, Face_Front, Back_Center
            $table->enum('generation_status', ['pending', 'queued', 'generating', 'ready', 'failed'])
                ->default('pending');
            $table->string('meshy_task_id', 64)->nullable();
            $table->string('preview_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['slot', 'is_active']);
            $table->index('generation_status');
        });

        Schema::create('operator_accessories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('accessory_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['operator_id', 'accessory_id']);
            $table->index(['operator_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_accessories');
        Schema::dropIfExists('accessories');
    }
};
