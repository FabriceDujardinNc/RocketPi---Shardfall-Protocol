<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operators', function (Blueprint $table) {
            $table->id();
            $table->string('name', 32)->unique();
            $table->string('codename', 16)->unique();                   // ex: VX-01
            $table->enum('faction', ['ORBIT', 'FERRO', 'VEIL']);
            $table->enum('role', ['sniper', 'healer', 'scout', 'tank', 'explosives', 'assault', 'infiltrator', 'hacker']);
            $table->enum('rarity', ['common', 'rare', 'epic', 'legendary']);
            $table->text('lore')->nullable();
            $table->string('portrait_url')->nullable();

            // Stats de base (valeurs PvP/PvE)
            $table->unsignedSmallInteger('stat_hp')->default(100);
            $table->unsignedSmallInteger('stat_damage')->default(50);
            $table->unsignedSmallInteger('stat_mobility')->default(50);

            $table->string('weapon_name', 64)->nullable();
            $table->text('weapon_description')->nullable();
            $table->json('abilities')->nullable();   // [{name, description, type: active|passive|ultimate}]

            $table->boolean('is_available')->default(true);    // false = retiré des bannières
            $table->boolean('is_rate_up')->default(false);     // rate-up actif
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['faction', 'rarity']);
            $table->index('is_available');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operators');
    }
};
