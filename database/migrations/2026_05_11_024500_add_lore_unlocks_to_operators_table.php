<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stocke le lore débloqué par palier d'affinité directement sur l'opérateur.
 *
 * Format JSON :
 *   [
 *     { "level": 0,  "title": "Présentation",          "snippet": "..." },
 *     { "level": 2,  "title": "Origines",              "snippet": "..." },
 *     { "level": 5,  "title": "L'incident Shardfall",  "snippet": "..." },
 *     { "level": 8,  "title": "Vie privée",            "snippet": "..." },
 *     { "level": 10, "title": "Confidence ultime",     "snippet": "..." }
 *   ]
 *
 * Avant cette migration, OperatorController::lorePart() générait ces fragments
 * à la volée en se basant sur le codename, ce qui rendait le contenu impossible
 * à éditer depuis l'admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->json('lore_unlocks')->nullable()->after('lore');
        });
    }

    public function down(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->dropColumn('lore_unlocks');
        });
    }
};
