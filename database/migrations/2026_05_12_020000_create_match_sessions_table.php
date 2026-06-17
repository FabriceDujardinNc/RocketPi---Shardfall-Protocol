<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sessions de match (Phase 4 — Unity WebGL + Photon).
 *
 * Une session est créée à `/api/unity/session/start`. Elle porte un
 * `session_token` SHA-256 unique passé à Unity, qui sera renvoyé avec
 * le résultat à `/api/unity/match/result`. Permet validation autoritaire :
 * - le serveur connaît `started_at` (durée plausible ?)
 * - le serveur connaît `operator_used_id` (compatible avec la collection ?)
 * - le serveur calcule lui-même les points / classement / récompenses
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_token', 64)->unique(); // SHA-256 hex
            $table->enum('mode', ['deathmatch', 'pve', 'custom', 'training'])->default('deathmatch');
            $table->enum('rank_type', ['ranked', 'casual'])->default('casual');
            $table->foreignId('operator_used_id')->nullable()->constrained('operators')->nullOnDelete();
            $table->enum('status', ['started', 'finished', 'abandoned', 'invalidated'])->default('started');
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('client_ip', 45)->nullable();
            $table->string('client_fingerprint', 128)->nullable();
            // Signature HMAC du payload final (anti-tampering)
            $table->string('result_signature', 128)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'rank_type', 'started_at']);
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_sessions');
    }
};
