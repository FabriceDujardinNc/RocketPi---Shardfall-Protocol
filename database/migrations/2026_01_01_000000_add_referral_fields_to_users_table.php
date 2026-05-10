<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Étend la table users créée par Laravel (auth:install ou breeze/jetstream).
// On ajoute : code de parrainage unique, référent, rôle.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 16)->unique()->nullable()->after('email');
            $table->foreignId('referred_by_user_id')->nullable()->constrained('users')->nullOnDelete()->after('referral_code');
            $table->enum('role', ['user', 'admin', 'super_admin'])->default('user')->after('referred_by_user_id');
            $table->unsignedInteger('account_level')->default(1)->after('role');
            $table->unsignedBigInteger('account_xp')->default(0)->after('account_level');
            $table->string('display_name', 32)->nullable()->after('name');
            $table->string('avatar_url')->nullable()->after('display_name');
            $table->timestamp('last_active_at')->nullable()->after('email_verified_at');
            $table->boolean('is_banned')->default(false)->after('last_active_at');
            $table->text('ban_reason')->nullable()->after('is_banned');
            $table->timestamp('banned_at')->nullable()->after('ban_reason');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by_user_id']);
            $table->dropColumn([
                'referral_code', 'referred_by_user_id', 'role',
                'account_level', 'account_xp', 'display_name', 'avatar_url',
                'last_active_at', 'is_banned', 'ban_reason', 'banned_at',
            ]);
        });
    }
};
