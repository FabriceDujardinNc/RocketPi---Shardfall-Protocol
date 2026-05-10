<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Historique immuable de tous les mouvements de monnaie.
// Jamais de UPDATE/DELETE — append-only pour audit complet.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('currency_type', 32);
            $table->bigInteger('amount');              // Positif = crédit, négatif = débit
            $table->unsignedBigInteger('balance_after');
            $table->string('reason', 64);              // gacha_pull, daily_login, mission_reward, admin_grant, stripe_purchase…
            $table->unsignedBigInteger('reference_id')->nullable();   // ID de la ligne source (gacha_pull.id, mission.id…)
            $table->string('reference_type', 64)->nullable();          // App\Models\GachaPull, App\Models\Mission…
            $table->string('description')->nullable();                  // Message humain optionnel
            $table->string('ip_address', 45)->nullable();

            // Immuable — pas de updated_at
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'currency_type', 'created_at']);
            $table->index('reason');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
