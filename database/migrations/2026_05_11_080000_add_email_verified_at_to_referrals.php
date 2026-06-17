<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Délai 7j d'activité réelle parrainage.
 *
 * Avant : `validateOnEmailVerified` faisait passer le Referral en
 * status=validated, ce qui débloquait immédiatement les rewards parrain
 * sur les paliers de level. Trop laxiste — quelqu'un peut vérifier son
 * email et créer un compte jetable.
 *
 * Maintenant : la vérif email enregistre `email_verified_at` sur le
 * Referral mais ne change PAS le status. Une tâche quotidienne
 * (`referrals:promote-active`) promeut en `validated` seulement si :
 *   - email vérifié depuis ≥ N jours (setting `referrals.activity_delay_days`, défaut 7)
 *   - user.last_active_at est non null (= le filleul est revenu après l'inscription)
 *
 * Sans status=validated, `checkLevelMilestones` ne crée pas de rewards
 * parrain → les comptes éphémères ne génèrent rien.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('validated_at');
            $table->index(['status', 'email_verified_at']);
        });
    }

    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropIndex(['status', 'email_verified_at']);
            $table->dropColumn('email_verified_at');
        });
    }
};
