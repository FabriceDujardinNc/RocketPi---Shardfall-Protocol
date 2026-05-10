<?php

namespace Database\Seeders;

use App\Models\Operator;
use Illuminate\Database\Seeder;

class OperatorSeeder extends Seeder
{
    public function run(): void
    {
        $operators = [
            [
                'name'    => 'Vex',
                'codename' => 'VX-01',
                'faction' => 'ORBIT',
                'role'    => 'sniper',
                'rarity'  => 'legendary',
                'stat_hp' => 80, 'stat_damage' => 95, 'stat_mobility' => 70,
                'lore'    => 'Ancienne coordinatrice de trajectoires de la station RocketPi, Vex a développé une précision surhumaine après une exposition massive aux Shards. Elle voit les trajectoires balistiques comme des équations lumineuses.',
                'weapon_name' => 'Railgun Quantique ORBIT-7',
                'weapon_description' => 'Fusil de précision à accélération magnétique. Traverse les obstacles légers.',
                'abilities' => [
                    ['name' => 'Calcul Balistique', 'type' => 'active', 'description' => 'Marque une cible. +25% de dégâts sur le prochain tir.'],
                    ['name' => 'Protocole Ghost', 'type' => 'active', 'description' => 'Invisibilité statique de 3 secondes en mode visée.'],
                    ['name' => 'APEX ORBITAL', 'type' => 'ultimate', 'description' => 'Lance un satellite de ciblage. Tirs guidés pendant 8 secondes, portée illimitée.'],
                ],
            ],
            [
                'name'    => 'Halo',
                'codename' => 'HL-02',
                'faction' => 'ORBIT',
                'role'    => 'healer',
                'rarity'  => 'epic',
                'stat_hp' => 90, 'stat_damage' => 40, 'stat_mobility' => 65,
                'lore'    => 'Médecin de bord de la station, Halo projette des champs de guérison alimentés par les Shards. Elle refuse de porter des armes offensives.',
                'weapon_name' => 'Emetteur Thérapeutique ORBIT-3',
                'weapon_description' => 'Pistolet soigneur. Soigne les alliés, repousse les ennemis.',
                'abilities' => [
                    ['name' => 'Aura Régénératrice', 'type' => 'active', 'description' => 'Bulle de soin de 6m pendant 5 secondes.'],
                    ['name' => 'Nano-Shield', 'type' => 'active', 'description' => 'Bouclier sur un allié ciblé (150 PV, 4 secondes).'],
                    ['name' => 'RENAISSANCE', 'type' => 'ultimate', 'description' => 'Résurrection instantanée d\'un allié tombé + soin de zone.'],
                ],
            ],
            [
                'name'    => 'Drift',
                'codename' => 'DR-03',
                'faction' => 'ORBIT',
                'role'    => 'scout',
                'rarity'  => 'rare',
                'stat_hp' => 75, 'stat_damage' => 60, 'stat_mobility' => 95,
                'lore'    => 'Pilote de module orbital reconverti en éclaireur, Drift utilise ses boosters de combinaison pour des déplacements imprévisibles.',
                'weapon_name' => 'SMG Orbital Drift-X',
                'weapon_description' => 'Mitraillette légère haute cadence. Idéale pour les approches rapides.',
                'abilities' => [
                    ['name' => 'Boost Orbital', 'type' => 'active', 'description' => 'Dash en avant de 8m, traverse les ennemis.'],
                    ['name' => 'Scanner Rapide', 'type' => 'active', 'description' => 'Révèle les ennemis dans un rayon de 20m pendant 6 secondes.'],
                    ['name' => 'SLINGSHOT', 'type' => 'ultimate', 'description' => 'Sprint surboosté de 3 secondes + invincibilité + dégâts au contact.'],
                ],
            ],
            [
                'name'    => 'Crag',
                'codename' => 'CR-04',
                'faction' => 'FERRO',
                'role'    => 'tank',
                'rarity'  => 'legendary',
                'stat_hp' => 140, 'stat_damage' => 65, 'stat_mobility' => 30,
                'lore'    => 'Mineur de Shards devenu mercenaire, Crag a soudé des fragments directement dans son armure. Son bouclier de quartzite peut absorber une frappe d\'artillerie.',
                'weapon_name' => 'Bouclier Quartzite + Marteau FERRO',
                'weapon_description' => 'Frappe de marteau à courte portée. Dégâts massifs sur cibles proches.',
                'abilities' => [
                    ['name' => 'Fortification', 'type' => 'active', 'description' => 'Déploie le bouclier. Absorbe 400 dégâts, réduit mobilité de 70%.'],
                    ['name' => 'Charge Minière', 'type' => 'active', 'description' => 'Charge en ligne droite sur 6m. Stun de 2 secondes au contact.'],
                    ['name' => 'CITADELLE', 'type' => 'ultimate', 'description' => 'Crée une zone imprenable de 4m pendant 10 secondes. Les alliés à l\'intérieur sont invincibles.'],
                ],
            ],
            [
                'name'    => 'Brick',
                'codename' => 'BK-05',
                'faction' => 'FERRO',
                'role'    => 'explosives',
                'rarity'  => 'epic',
                'stat_hp' => 100, 'stat_damage' => 85, 'stat_mobility' => 45,
                'lore'    => 'Artificier militaire reconverti en milicien, Brick préfère les solutions qui font le plus de bruit possible.',
                'weapon_name' => 'Lance-grenades FERRO Mk.IV',
                'weapon_description' => 'Grenades fragmentées à tir semi-auto. Dégâts de zone.',
                'abilities' => [
                    ['name' => 'Mine de Proximité', 'type' => 'active', 'description' => 'Pose une mine invisible. Explose au passage ennemi.'],
                    ['name' => 'Roquette C4', 'type' => 'active', 'description' => 'Roquette guidée télécommandée. Détonation manuelle.'],
                    ['name' => 'ENFER DE FERRO', 'type' => 'ultimate', 'description' => 'Barrage de 12 roquettes sur zone ciblée. 8 secondes de chaos.'],
                ],
            ],
            [
                'name'    => 'Iron',
                'codename' => 'IR-06',
                'faction' => 'FERRO',
                'role'    => 'assault',
                'rarity'  => 'common',
                'stat_hp' => 110, 'stat_damage' => 70, 'stat_mobility' => 60,
                'lore'    => 'Soldat de base de la milice FERRO. Fiable, polyvalent, sans fioritures. Le cheval de bataille de toute équipe.',
                'weapon_name' => 'Fusil d\'Assaut FERRO-AR5',
                'weapon_description' => 'Fusil d\'assaut polyvalent. Précis à mi-distance, cadence élevée.',
                'abilities' => [
                    ['name' => 'Burst Ferro', 'type' => 'active', 'description' => 'Rafale de 5 balles à dégâts amplifiés.'],
                    ['name' => 'Suppression', 'type' => 'active', 'description' => 'Ralentit les ennemis touchés de 30% pendant 3 secondes.'],
                    ['name' => 'PROTOCOLE ASSAUT', 'type' => 'ultimate', 'description' => 'Double cadence de tir + réduction de recul pendant 8 secondes.'],
                ],
            ],
            [
                'name'    => 'Wraith',
                'codename' => 'WR-07',
                'faction' => 'VEIL',
                'role'    => 'infiltrator',
                'rarity'  => 'epic',
                'stat_hp' => 80, 'stat_damage' => 80, 'stat_mobility' => 85,
                'lore'    => 'Fantôme sans passé documenté. L\'exposition aux Shards de VEIL lui a accordé la capacité de se fondre dans les fréquences lumineuses. Personne ne sait à quoi ressemble vraiment Wraith.',
                'weapon_name' => 'Pistol-lame Silencieux VEIL',
                'weapon_description' => 'Pistolet silencieux avec lame intégrée. Dégâts critiques par derrière.',
                'abilities' => [
                    ['name' => 'Phase Fantôme', 'type' => 'active', 'description' => 'Invisibilité complète pendant 5 secondes. Se rompt à l\'attaque.'],
                    ['name' => 'Frappe Dorsale', 'type' => 'active', 'description' => '+150% dégâts sur la prochaine attaque par derrière.'],
                    ['name' => 'PROTOCOLE OMBRE', 'type' => 'ultimate', 'description' => 'Toute l\'équipe en invisibilité 4 secondes + dash individuel de 15m.'],
                ],
            ],
            [
                'name'    => 'Echo',
                'codename' => 'EC-08',
                'faction' => 'VEIL',
                'role'    => 'hacker',
                'rarity'  => 'rare',
                'stat_hp' => 85, 'stat_damage' => 55, 'stat_mobility' => 75,
                'lore'    => 'Ancienne ingénieure réseau reconvertie en hackeur de champ. Echo utilise les Shards comme amplificateurs de signal pour pirater les systèmes ennemis à distance.',
                'weapon_name' => 'Pistolet EMP VEIL-E2',
                'weapon_description' => 'Pistolet qui désactive l\'équipement ennemi au contact.',
                'abilities' => [
                    ['name' => 'Hack Système', 'type' => 'active', 'description' => 'Désactive les capacités actives d\'un ennemi pendant 4 secondes.'],
                    ['name' => 'Signal Brouillé', 'type' => 'active', 'description' => 'Zone de brouillage de 8m. Désactive le HUD ennemi.'],
                    ['name' => 'OVERLOAD', 'type' => 'ultimate', 'description' => 'Pirate toute l\'infrastructure ennemie. Désactive les capacités de toute l\'équipe adverse 6 secondes.'],
                ],
            ],
        ];

        foreach ($operators as $data) {
            $abilities = $data['abilities'];
            unset($data['abilities']);
            Operator::firstOrCreate(
                ['codename' => $data['codename']],
                array_merge($data, ['abilities' => $abilities])
            );
        }
    }
}
