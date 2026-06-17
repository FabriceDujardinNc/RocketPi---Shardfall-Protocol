<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute base_preview_url sur operators — image PNG miniature renvoyée par
 * Meshy à la fin de la génération du mesh de base (champ `thumbnail_url`).
 *
 * Sert à afficher une vignette dans l'admin et la galerie joueur sans avoir
 * à charger le .glb complet. Indépendant de `portrait_url` (qui reste pour
 * un éventuel portrait illustré 2D non lié à la 3D).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->string('base_preview_url')->nullable()->after('base_meshy_task_id');
        });
    }

    public function down(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->dropColumn('base_preview_url');
        });
    }
};
