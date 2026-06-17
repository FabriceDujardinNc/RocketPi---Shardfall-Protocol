<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Armes = entités 3D indépendantes des opérateurs.
 *
 * Une arme n'est PAS liée à un opérateur particulier — n'importe quel
 * opérateur peut équiper n'importe quelle arme compatible (filtrage gameplay
 * géré ailleurs : Operator.role, Weapon.category…).
 *
 * Le mesh d'arme s'attache au socket nommé `socket_name` (par défaut
 * `Hand_R`) — cf. unity-client/docs/RIG_CONTRACT.md.
 *
 * weapon_skins : variant texture appliqué au mesh d'arme (même principe que
 * operator_skins, sans dupliquer le .glb).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weapons', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 96)->unique();      // ex: assault-rifle-ar15
            $table->string('name', 128);
            $table->enum('category', [
                'assault', 'sniper', 'shotgun', 'smg', 'pistol', 'launcher', 'melee',
            ]);
            $table->enum('rarity', ['common', 'rare', 'epic', 'legendary'])->default('common');
            $table->string('base_model_url')->nullable();         // .glb
            $table->string('socket_name', 32)->default('Hand_R'); // attachement par défaut
            $table->enum('generation_status', ['pending', 'queued', 'generating', 'ready', 'failed'])
                ->default('pending');
            $table->string('meshy_task_id', 64)->nullable();
            $table->json('stats')->nullable();          // {damage, fire_rate, recoil, range, magazine}
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'is_active']);
            $table->index('generation_status');
        });

        Schema::create('weapon_skins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weapon_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 96)->unique();
            $table->string('name', 128);
            $table->enum('rarity', ['common', 'rare', 'epic', 'legendary'])->default('rare');
            $table->string('texture_url')->nullable();
            $table->json('material_overrides')->nullable();
            $table->enum('generation_status', ['pending', 'queued', 'generating', 'ready', 'failed'])
                ->default('pending');
            $table->string('meshy_task_id', 64)->nullable();
            $table->string('preview_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['weapon_id', 'is_active']);
            $table->index('generation_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weapon_skins');
        Schema::dropIfExists('weapons');
    }
};
