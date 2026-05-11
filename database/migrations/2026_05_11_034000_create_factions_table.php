<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faction = "ORBIT", "FERRO", "VEIL" — référencées par leur slug uppercase dans
 * operators.faction (enum) et banners.featured_operator/rate_up_operators.
 *
 * On AJOUTE cette table sans toucher aux enums existants pour éviter une
 * migration destructive sur des colonnes contraintes. Le lien se fait via le slug.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factions', function (Blueprint $table) {
            $table->string('slug', 16)->primary();   // ORBIT, FERRO, VEIL
            $table->string('name', 64);
            $table->text('tagline')->nullable();
            $table->text('lore')->nullable();
            $table->unsignedSmallInteger('color_hue')->default(220); // hue OKLCH (0-360)
            $table->string('accent_class', 32)->nullable();          // ex: shard-cyan, ferro-rust
            $table->string('banner_image_url')->nullable();
            $table->string('icon_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factions');
    }
};
