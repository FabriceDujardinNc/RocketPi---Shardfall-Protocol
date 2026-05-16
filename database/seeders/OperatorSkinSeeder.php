<?php

namespace Database\Seeders;

use App\Models\Operator;
use App\Models\OperatorSkin;
use Illuminate\Database\Seeder;

/**
 * Catalogue initial de skins par opérateur :
 *  - 1 skin "Default" par opérateur (palette officielle de la faction)
 *  - 2 skins en plus pour les légendaires (1 alternative + 1 prestige)
 *
 * Aucune 3D n'est lancée ici. Statut = pending → l'admin déclenche
 * Meshy depuis /admin/skins ou /admin/operators/{slug}/assets quand
 * il décide de dépenser des crédits.
 *
 * Total skins = 8 + 2 = 10. Coût max Meshy (retexture 20cr) ≈ 200cr.
 */
class OperatorSkinSeeder extends Seeder
{
    public function run(): void
    {
        $byCodename = Operator::query()->get()->keyBy('codename');

        $skins = [
            // ──────── ORBIT ────────
            ['op' => 'VX-01', 'name' => 'Vex Default',  'rarity' => 'rare',      'is_default' => true,
                'palette' => [['slot' => 'primary', 'hex' => '#22d3ee'], ['slot' => 'accent', 'hex' => '#0f172a']]],
            // Légendaire = +2
            ['op' => 'VX-01', 'name' => 'Vex Eclipse',  'rarity' => 'epic',      'is_default' => false,
                'palette' => [['slot' => 'primary', 'hex' => '#1e293b'], ['slot' => 'accent', 'hex' => '#a855f7']]],
            ['op' => 'VX-01', 'name' => 'Vex Solar',    'rarity' => 'legendary', 'is_default' => false,
                'palette' => [['slot' => 'primary', 'hex' => '#fde68a'], ['slot' => 'accent', 'hex' => '#f59e0b']]],

            ['op' => 'HL-02', 'name' => 'Halo Default', 'rarity' => 'rare',      'is_default' => true,
                'palette' => [['slot' => 'primary', 'hex' => '#67e8f9'], ['slot' => 'accent', 'hex' => '#bbf7d0']]],

            ['op' => 'DR-03', 'name' => 'Drift Default','rarity' => 'common',    'is_default' => true,
                'palette' => [['slot' => 'primary', 'hex' => '#0ea5e9'], ['slot' => 'accent', 'hex' => '#e2e8f0']]],

            // ──────── FERRO ────────
            ['op' => 'CR-04', 'name' => 'Crag Default', 'rarity' => 'rare',      'is_default' => true,
                'palette' => [['slot' => 'primary', 'hex' => '#9a3412'], ['slot' => 'accent', 'hex' => '#1f2937']]],
            // Légendaire = +2
            ['op' => 'CR-04', 'name' => 'Crag Forge',   'rarity' => 'epic',      'is_default' => false,
                'palette' => [['slot' => 'primary', 'hex' => '#dc2626'], ['slot' => 'accent', 'hex' => '#0a0a0a']]],
            ['op' => 'CR-04', 'name' => 'Crag Obsidian','rarity' => 'legendary', 'is_default' => false,
                'palette' => [['slot' => 'primary', 'hex' => '#0a0a0a'], ['slot' => 'accent', 'hex' => '#facc15']]],

            ['op' => 'BK-05', 'name' => 'Brick Default','rarity' => 'common',    'is_default' => true,
                'palette' => [['slot' => 'primary', 'hex' => '#78350f'], ['slot' => 'accent', 'hex' => '#fbbf24']]],

            ['op' => 'IR-06', 'name' => 'Iron Default', 'rarity' => 'common',    'is_default' => true,
                'palette' => [['slot' => 'primary', 'hex' => '#52525b'], ['slot' => 'accent', 'hex' => '#d4d4d8']]],

            // ──────── VEIL ────────
            ['op' => 'WR-07', 'name' => 'Wraith Default','rarity' => 'rare',     'is_default' => true,
                'palette' => [['slot' => 'primary', 'hex' => '#1e1b4b'], ['slot' => 'accent', 'hex' => '#7c3aed']]],

            ['op' => 'EC-08', 'name' => 'Echo Default', 'rarity' => 'common',    'is_default' => true,
                'palette' => [['slot' => 'primary', 'hex' => '#312e81'], ['slot' => 'accent', 'hex' => '#22d3ee']]],
        ];

        foreach ($skins as $row) {
            if (! isset($byCodename[$row['op']])) {
                continue;
            }
            OperatorSkin::firstOrCreate(
                ['operator_id' => $byCodename[$row['op']]->id, 'name' => $row['name']],
                [
                    'rarity'            => $row['rarity'],
                    'palette_json'      => $row['palette'],
                    'generation_status' => 'pending',
                    'is_active'         => true,
                    'is_default'        => $row['is_default'],
                ]
            );
        }
    }
}
