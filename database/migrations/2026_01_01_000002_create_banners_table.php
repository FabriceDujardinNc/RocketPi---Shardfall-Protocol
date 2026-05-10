<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64);
            $table->string('tag', 64)->nullable();           // "SIGNAL SHARD · ÉVÉNEMENT"
            $table->string('subtitle', 128)->nullable();     // "VEX RATE-UP ×3"
            $table->enum('type', ['permanent', 'event', 'faction', 'collab']);
            $table->string('featured_operator')->nullable(); // codename de l'opérateur rate-up principal
            $table->json('rate_up_operators')->nullable();   // ["VX-01", "CR-02"]
            $table->string('banner_image_url')->nullable();

            // Taux gacha — peuvent surcharger les taux globaux
            $table->decimal('rate_legendary', 5, 4)->default(0.0200); // 2%
            $table->decimal('rate_epic', 5, 4)->default(0.0800);      // 8%
            $table->decimal('rate_rare', 5, 4)->default(0.3000);      // 30%
            $table->decimal('rate_common', 5, 4)->default(0.6000);    // 60%

            // Pity system
            $table->unsignedSmallInteger('pity_legendary')->default(80);
            $table->unsignedSmallInteger('soft_pity_start')->default(60);
            $table->unsignedSmallInteger('pity_epic')->default(10);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'type']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
