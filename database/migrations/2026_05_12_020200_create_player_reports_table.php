<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Signalements joueurs (cheat, toxicité, AFK, smurf).
 *
 * Workflow :
 *   pending → reviewed (admin a vu) → dismissed | sanctioned
 *
 * Si sanctioned, l'admin applique la sanction via `User::ban` ou autre
 * action et lie l'audit via `Transaction::reason='admin_sanction'`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reported_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('match_session_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('reason', ['cheat', 'toxic', 'afk', 'smurf', 'other']);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'reviewed', 'dismissed', 'sanctioned'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('reported_id');
            // Anti-spam : 1 report par couple reporter→reported sur la même session
            $table->unique(['reporter_id', 'reported_id', 'match_session_id'], 'reports_unique_per_session');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_reports');
    }
};
