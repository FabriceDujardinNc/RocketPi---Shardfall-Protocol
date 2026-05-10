<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Phase 5 — tables créées maintenant pour éviter une migration majeure plus tard.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guilds', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64)->unique();
            $table->string('tag', 8)->unique();          // [ORBT] — 3-5 chars
            $table->text('description')->nullable();
            $table->string('faction', 8)->nullable();    // Faction principale choisie
            $table->foreignId('leader_id')->constrained('users')->restrictOnDelete();
            $table->unsignedSmallInteger('max_members')->default(30);
            $table->unsignedBigInteger('guild_score')->default(0);
            $table->boolean('is_recruiting')->default(true);
            $table->string('banner_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('guild_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guild_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['leader', 'officer', 'member'])->default('member');
            $table->unsignedBigInteger('contribution_score')->default(0);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique('user_id');   // Un joueur dans une seule guilde
            $table->index('guild_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guild_members');
        Schema::dropIfExists('guilds');
    }
};
