<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Portefeuille de monnaie par joueur. Une ligne par type de monnaie.
// Types : shards (premium), credits (gratuit), tickets_standard, tickets_premium, fragments_{operator_id}
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);                         // shards, credits, tickets_premium, fragments_VX-01…
            $table->unsignedBigInteger('balance')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'type']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
