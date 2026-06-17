<?php

namespace Database\Factories;

use App\Models\Weapon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Weapon>
 */
class WeaponFactory extends Factory
{
    protected $model = Weapon::class;

    public function definition(): array
    {
        return [
            'name'              => fake()->unique()->words(2, true),
            'category'          => fake()->randomElement(Weapon::CATEGORIES),
            'rarity'            => fake()->randomElement(Weapon::RARITIES),
            'base_model_url'    => null,
            'socket_name'       => 'Hand_R',
            'generation_status' => 'pending',
            'meshy_task_id'     => null,
            'stats'             => null,
            'is_active'         => true,
        ];
    }

    public function ready(): static
    {
        return $this->state(fn () => [
            'generation_status' => 'ready',
            'base_model_url'    => 'models/weapons/test/base.glb',
        ]);
    }
}
