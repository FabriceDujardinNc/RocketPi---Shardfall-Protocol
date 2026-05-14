<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Étend operators avec les colonnes nécessaires au pipeline de génération
 * 3D modulaire (Meshy.ai → retargeting → Unity).
 *
 *  - base_model_url        : chemin Storage::disk('public') vers le .glb du mesh de base
 *  - base_rig_version      : version du contrat rig (cf. unity-client/docs/RIG_CONTRACT.md)
 *  - base_generation_status: pending|queued|generating|ready|failed
 *  - base_meshy_task_id    : id de la tâche Meshy.ai en cours / dernière, pour polling
 *
 * Les skins/armes/accessoires sont dans des tables dédiées (cf. migrations
 * suivantes). Seul le mesh de base est porté par operators.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->string('base_model_url')->nullable()->after('portrait_url');
            $table->string('base_rig_version', 32)->default('humanoid-v1')->after('base_model_url');
            $table->enum('base_generation_status', ['pending', 'queued', 'generating', 'ready', 'failed'])
                ->default('pending')
                ->after('base_rig_version');
            $table->string('base_meshy_task_id', 64)->nullable()->after('base_generation_status');

            $table->index('base_generation_status');
        });
    }

    public function down(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->dropIndex(['base_generation_status']);
            $table->dropColumn([
                'base_model_url',
                'base_rig_version',
                'base_generation_status',
                'base_meshy_task_id',
            ]);
        });
    }
};
