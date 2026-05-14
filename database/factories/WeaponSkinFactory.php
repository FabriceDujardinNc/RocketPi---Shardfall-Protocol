<?php

namespace Database\Factories;

use App\Models\Weapon;
use App\Models\WeaponSkin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeaponSkin>
 */
class WeaponSkinFactory extends Factory
{
    protected $model = WeaponSkin::class;

    public function definition(): array
    {
        return [
            'weapon_id'          => Weapon::factory(),
            'name'               => fake()->unique()->words(2, true) . ' Camo',
            'rarity'             => fake()->randomElement(WeaponSkin::RARITIES),
            'texture_url'        => null,
            'material_overrides' => null,
            'generation_status'  => 'pending',
            'meshy_task_id'      => null,
            'preview_url'        => null,
            'is_active'          => true,
            'is_default'         => false,
        ];
    }

    public function ready(): static
    {
        return $this->state(fn () => [
            'generation_status' => 'ready',
            'texture_url'       => 'models/weapons/test/skins/test/texture.ktx2',
        ]);
    }
}
