<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2FA TOTP pour comptes admin.
 *
 * Colonnes :
 *  - two_factor_secret       : secret TOTP (chiffré via casts encrypted)
 *  - two_factor_confirmed_at : timestamp du confirm code post-setup (null = non activé)
 *  - two_factor_recovery_codes : 8 codes de secours (JSON, chiffrés)
 *
 * Imposé pour role IN (admin, super_admin) : check fait dans le middleware
 * EnsureUserIsAdmin (force redirect /2fa/setup si confirmed_at null).
 * Les joueurs réguliers peuvent activer mais ce n'est pas requis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });
    }
};
