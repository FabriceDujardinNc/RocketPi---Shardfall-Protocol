<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referee_id')->constrained('users')->cascadeOnDelete();

            $table->enum('status', ['pending', 'validated', 'rewarded', 'flagged'])->default('pending');
            // pending   : filleul inscrit mais email non vérifié ou < 7 jours activité
            // validated : conditions remplies, prêt pour récompenses
            // rewarded  : récompenses initiales distribuées
            // flagged   : suspect (multi-compte, IP suspecte) — bloqué admin

            // Données anti-abus
            $table->string('referee_ip', 45)->nullable();
            $table->string('referee_fingerprint', 64)->nullable();  // Device fingerprint
            $table->boolean('same_ip_as_referrer')->default(false);

            $table->timestamp('validated_at')->nullable();  // Quand les conditions ont été remplies
            $table->timestamp('flagged_at')->nullable();
            $table->text('flag_reason')->nullable();
            $table->timestamps();

            $table->unique('referee_id');   // Un filleul ne peut avoir qu'un parrain
            $table->index('referrer_id');
            $table->index('status');
        });

        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_id')->constrained()->cascadeOnDelete();
            $table->foreignId('beneficiary_id')->constrained('users')->cascadeOnDelete(); // parrain ou filleul
            $table->enum('trigger', [
                'referee_email_verified',    // Filleul vérifie son email
                'referee_level_5',           // Filleul atteint niveau 5
                'referee_level_15',          // Filleul atteint niveau 15
                'referee_level_30',          // Filleul atteint niveau 30
                'referee_first_purchase',    // Premier achat du filleul
            ]);
            $table->string('reward_type', 32);        // shards, tickets_premium, operator_epic_choice…
            $table->unsignedInteger('reward_amount')->nullable();
            $table->boolean('claimed')->default(false);
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->unique(['referral_id', 'trigger']);
            $table->index(['beneficiary_id', 'claimed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_rewards');
        Schema::dropIfExists('referrals');
    }
};
