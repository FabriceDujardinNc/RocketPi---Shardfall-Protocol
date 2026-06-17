<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ideas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->text('body');
            $table->string('slug', 140)->unique();
            // open : proposée, en attente d'une décision admin
            // accepted : retenue, sera développée
            // rejected : refusée
            // done : implémentée et live
            $table->enum('status', ['open', 'accepted', 'rejected', 'done'])
                ->default('open')
                ->index();
            $table->unsignedInteger('votes_count')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ideas');
    }
};
