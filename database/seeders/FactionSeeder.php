<?php

namespace Database\Seeders;

use App\Models\Faction;
use Illuminate\Database\Seeder;

class FactionSeeder extends Seeder
{
    public function run(): void
    {
        $factions = [
            [
                'slug'         => 'ORBIT',
                'name'         => 'ORBIT',
                'tagline'      => 'Ingénieurs orbitaux, précision et soutien',
                'lore'         => "Les survivants de la station mère. ORBIT regroupe les techniciens, médecins et navigateurs qui ont conservé une discipline militaire malgré le chaos du Shardfall. Leur doctrine : observer, calculer, frapper juste.",
                'color_hue'    => 220,
                'accent_class' => 'shard-cyan',
            ],
            [
                'slug'         => 'FERRO',
                'name'         => 'FERRO',
                'tagline'      => 'Mineurs reconvertis en milice industrielle',
                'lore'         => "Les ouvriers des plateformes industrielles. FERRO fait dans le métal et l'impact direct — tanks, démolisseurs, assauts polyvalents. Leur force vient de l'expérience du terrain et de l'usage brut des Shards.",
                'color_hue'    => 32,
                'accent_class' => 'ferro-rust',
            ],
            [
                'slug'         => 'VEIL',
                'name'         => 'VEIL',
                'tagline'      => 'Réseau clandestin, infiltrateurs et hackeurs',
                'lore'         => "Officiellement, VEIL n'existe pas. Officieusement, c'est la faction la mieux informée du conflit. Leurs Opérateurs préfèrent les Shards qui altèrent la perception et l'information — invisibilité, brouillage, intrusion.",
                'color_hue'    => 290,
                'accent_class' => 'veil-violet',
            ],
        ];

        foreach ($factions as $f) {
            Faction::firstOrCreate(['slug' => $f['slug']], $f);
        }
    }
}
