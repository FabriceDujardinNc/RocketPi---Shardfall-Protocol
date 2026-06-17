<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Key/value bag pour les flags globaux du jeu (maintenance, kill-switches gacha/shop,
 * messages d'annonce, etc.). Évite un sur-design type table par feature.
 *
 * Le model `Setting` cache les lectures pour éviter un hit DB par requête HTTP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->text('value')->nullable();    // JSON-encoded selon le type
            $table->string('type', 16)->default('string'); // bool|string|int|json
            $table->string('label', 128)->nullable();      // label éditorial admin
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
