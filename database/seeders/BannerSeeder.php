<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        // Bannière permanente — toujours active, pas de date de fin
        Banner::firstOrCreate(
            ['name' => 'Signal Shard Standard'],
            [
                'tag'              => 'RECRUTEMENT PERMANENT',
                'subtitle'         => 'Tous les opérateurs disponibles',
                'type'             => 'permanent',
                'rate_legendary'   => 0.0200,
                'rate_epic'        => 0.0800,
                'rate_rare'        => 0.3000,
                'rate_common'      => 0.6000,
                'pity_legendary'   => 80,
                'soft_pity_start'  => 60,
                'pity_epic'        => 10,
                'is_active'        => true,
            ]
        );

        // Bannière événementielle exemple — Vex rate-up
        Banner::firstOrCreate(
            ['name' => 'Opération Hexfall'],
            [
                'tag'                => 'SIGNAL SHARD · ÉVÉNEMENT',
                'subtitle'           => 'VEX RATE-UP ×3',
                'type'               => 'event',
                'featured_operator'  => 'VX-01',
                'rate_up_operators'  => ['VX-01'],
                'rate_legendary'     => 0.0200,
                'rate_epic'          => 0.0800,
                'rate_rare'          => 0.3000,
                'rate_common'        => 0.6000,
                'pity_legendary'     => 80,
                'soft_pity_start'    => 60,
                'pity_epic'          => 10,
                'starts_at'          => now(),
                'ends_at'            => now()->addDays(21),
                'is_active'          => true,
            ]
        );
    }
}
