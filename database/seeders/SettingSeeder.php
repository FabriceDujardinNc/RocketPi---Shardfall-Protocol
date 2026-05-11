<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    private const DEFAULTS = [
        ['key' => 'maintenance_mode',   'type' => 'bool',   'value' => '0', 'label' => 'Mode maintenance global'],
        ['key' => 'gacha_enabled',      'type' => 'bool',   'value' => '1', 'label' => 'Activer le gacha'],
        ['key' => 'shop_enabled',       'type' => 'bool',   'value' => '1', 'label' => 'Activer la boutique'],
        ['key' => 'leaderboards_enabled', 'type' => 'bool', 'value' => '1', 'label' => 'Activer les classements'],
        ['key' => 'maintenance_message', 'type' => 'string', 'value' => 'RocketPi est en maintenance — retour estimé : ~30 min.', 'label' => "Message de maintenance affiché aux joueurs"],
        ['key' => 'announcement',        'type' => 'string', 'value' => '', 'label' => "Bannière d'annonce globale (vide = masquée)"],
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as $row) {
            Setting::firstOrCreate(
                ['key' => $row['key']],
                ['type' => $row['type'], 'value' => $row['value'], 'label' => $row['label']],
            );
        }
    }
}
