<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128);
            $table->text('lore')->nullable();
            $table->string('banner_image_url')->nullable();
            $table->enum('type', ['limited_banner', 'pvp_mode', 'pve_mode', 'story', 'collaboration']);
            $table->foreignId('banner_id')->nullable()->constrained()->nullOnDelete();  // Bannière liée
            $table->json('rewards_pool')->nullable();      // Récompenses spéciales
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
