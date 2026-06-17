<?php

namespace Database\Seeders;

use App\Models\Cosmetic;
use App\Models\Operator;
use Illuminate\Database\Seeder;

/**
 * Catalogue de démarrage cosmétiques.
 *
 * Skins liés à un opérateur + unlock_at_affinity → débloqués automatiquement
 * via AffinityService quand le joueur atteint le palier.
 *
 * Titres / bordures non liés à un opérateur (slug = currency type virtuel
 * utilisé par RewardService et LeaderboardRewardSeeder).
 */
class CosmeticSeeder extends Seeder
{
    public function run(): void
    {
        $globals = [
            ['slug' => 'title_apex',                  'name' => 'Apex',                    'type' => 'title',  'rarity' => 'legendary'],
            ['slug' => 'title_apex_2026',             'name' => 'Apex 2026',               'type' => 'title',  'rarity' => 'legendary'],
            ['slug' => 'title_primordial_champion',   'name' => 'Champion Primordial',     'type' => 'title',  'rarity' => 'legendary'],
            ['slug' => 'title_master_collector',      'name' => 'Maître Collectionneur',   'type' => 'title',  'rarity' => 'epic'],
            ['slug' => 'title_collector',             'name' => 'Collectionneur',          'type' => 'title',  'rarity' => 'rare'],
            ['slug' => 'border_legend',               'name' => 'Bordure Légende',         'type' => 'border', 'rarity' => 'legendary'],
            ['slug' => 'border_apex',                 'name' => 'Bordure Apex',            'type' => 'border', 'rarity' => 'epic'],
            ['slug' => 'faction_banner',              'name' => 'Bannière de Faction',     'type' => 'banner', 'rarity' => 'rare'],
        ];
        foreach ($globals as $row) {
            Cosmetic::updateOrCreate(['slug' => $row['slug']], $row);
        }

        // Skins par opérateur (3 paliers : 2 / 5 / 10)
        Operator::query()->each(function (Operator $op) {
            $base = strtolower(str_replace('-', '_', $op->codename));
            $skins = [
                [
                    'slug'   => "skin_{$base}_field",
                    'name'   => "{$op->name} — Terrain",
                    'type'   => 'skin',
                    'rarity' => 'rare',
                    'operator_id' => $op->id,
                    'unlock_at_affinity' => 2,
                    'description' => 'Tenue de service standard, distribuée aux affiliés.',
                ],
                [
                    'slug'   => "skin_{$base}_command",
                    'name'   => "{$op->name} — Commandement",
                    'type'   => 'skin',
                    'rarity' => 'epic',
                    'operator_id' => $op->id,
                    'unlock_at_affinity' => 5,
                    'description' => "Uniforme de gala pour les opérateurs qui ont prouvé leur loyauté.",
                ],
                [
                    'slug'   => "skin_{$base}_apex",
                    'name'   => "{$op->name} — Apex",
                    'type'   => 'skin',
                    'rarity' => 'legendary',
                    'operator_id' => $op->id,
                    'unlock_at_affinity' => 10,
                    'description' => "Tenue exclusive du cercle Apex. Reflet du Shard pur dans l'armure.",
                ],
                [
                    'slug'   => "voice_{$base}_confidence",
                    'name'   => "{$op->name} — Confidence",
                    'type'   => 'voiceline',
                    'rarity' => 'epic',
                    'operator_id' => $op->id,
                    'unlock_at_affinity' => 8,
                    'description' => 'Ligne vocale confidentielle réservée aux opérateurs proches.',
                ],
            ];
            foreach ($skins as $s) {
                Cosmetic::updateOrCreate(['slug' => $s['slug']], $s);
            }
        });
    }
}
