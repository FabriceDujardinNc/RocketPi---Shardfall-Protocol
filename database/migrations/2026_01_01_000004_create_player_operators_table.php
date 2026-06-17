<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Collection d'un joueur — un enregistrement par opérateur obtenu.
// Les doublons incrémentent duplicate_count et génèrent des fragments.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_operators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();

            $table->unsignedSmallInteger('duplicate_count')->default(0);  // Nombre d'exemplaires supplémentaires
            $table->unsignedSmallInteger('constellation')->default(0);     // Niveau constellation (0-6) débloqué via doublons
            $table->boolean('is_favorite')->default(false);
            $table->timestamp('obtained_at')->useCurrent();
            $table->timestamps();

            // Un joueur ne peut avoir qu'une entrée par opérateur
            $table->unique(['user_id', 'operator_id']);
            $table->index(['user_id', 'obtained_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_operators');
    }
};
