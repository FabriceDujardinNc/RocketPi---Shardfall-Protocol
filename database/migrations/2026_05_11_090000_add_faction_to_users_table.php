<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Allégeance de faction unique par joueur.
 *
 * Un joueur choisit UNE seule faction (ORBIT / FERRO / VEIL) à l'inscription
 * et ne peut pas en changer ensuite. Cette colonne participe à l'identité
 * du compte (cosmétiques, missions de faction, bonus, classements).
 *
 * Backfill : les comptes existants reçoivent une faction par défaut
 * (ORBIT pour l'admin, distribué pseudo-aléatoirement pour les autres)
 * pour ne pas casser la prod. La règle "une seule faction, immuable"
 * s'applique aux nouveaux comptes via la validation côté contrôleur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('faction', 16)->nullable()->after('account_xp')->index();
        });

        // Backfill : assigne une faction aux comptes existants pour éviter
        // qu'ils restent en état orphelin. Distribution répartie sur les 3.
        $factions = ['ORBIT', 'FERRO', 'VEIL'];
        DB::table('users')->whereNull('faction')->orderBy('id')->each(function ($user) use ($factions) {
            DB::table('users')->where('id', $user->id)->update([
                'faction' => $factions[$user->id % 3],
            ]);
        }, 500);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['faction']);
            $table->dropColumn('faction');
        });
    }
};
