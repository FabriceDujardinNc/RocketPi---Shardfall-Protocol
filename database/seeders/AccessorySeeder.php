<?php

namespace Database\Seeders;

use App\Models\Accessory;
use App\Models\Operator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catalogue initial d'accessoires : 1 par opérateur, marqué is_default=true
 * sur le pivot operator_accessories (équipé sans action joueur).
 *
 * Aucune génération 3D ici : l'admin déclenche Meshy depuis /admin/accessories
 * quand il veut dépenser des crédits (text-to-3d ≈ 20cr/préview).
 *
 * Total = 8 accessoires → ≈ 160 crédits max si tout généré.
 */
class AccessorySeeder extends Seeder
{
    public function run(): void
    {
        $operators = Operator::query()->get()->keyBy('codename');

        $accessories = [
            ['op' => 'VX-01', 'name' => 'Visière ORBIT Précision',  'slot' => 'face',  'socket' => 'Face_Front', 'rarity' => 'epic'],
            ['op' => 'HL-02', 'name' => 'Med-Pack ORBIT-3 Carrier', 'slot' => 'back',  'socket' => 'Back_Center','rarity' => 'rare'],
            ['op' => 'DR-03', 'name' => 'Casque ORBIT Lite',        'slot' => 'head',  'socket' => 'Head_Top',  'rarity' => 'common'],
            ['op' => 'CR-04', 'name' => 'Bouclier Quartzite Plié',  'slot' => 'back',  'socket' => 'Back_Center','rarity' => 'legendary'],
            ['op' => 'BK-05', 'name' => 'Harnais Roquettes FERRO',  'slot' => 'back',  'socket' => 'Back_Center','rarity' => 'epic'],
            ['op' => 'IR-06', 'name' => 'Casque FERRO Standard',    'slot' => 'head',  'socket' => 'Head_Top',  'rarity' => 'common'],
            ['op' => 'WR-07', 'name' => 'Capuche VEIL Adaptive',    'slot' => 'head',  'socket' => 'Head_Top',  'rarity' => 'epic'],
            ['op' => 'EC-08', 'name' => 'Deck VEIL bras-monté',     'slot' => 'hands', 'socket' => 'Hand_R',    'rarity' => 'rare'],
        ];

        foreach ($accessories as $row) {
            $operator = $operators[$row['op']] ?? null;
            if (! $operator) continue;

            $accessory = Accessory::firstOrCreate(
                ['name' => $row['name']],
                [
                    'slot'              => $row['slot'],
                    'rarity'            => $row['rarity'],
                    'socket_name'       => $row['socket'],
                    'generation_status' => 'pending',
                    'is_active'         => true,
                ]
            );

            // Attache l'opérateur en pivot, is_default=true.
            // syncWithoutDetaching + maj manuelle car wasRecentlyCreated ne le couvre pas.
            DB::table('operator_accessories')->updateOrInsert(
                ['operator_id' => $operator->id, 'accessory_id' => $accessory->id],
                ['is_default'  => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
