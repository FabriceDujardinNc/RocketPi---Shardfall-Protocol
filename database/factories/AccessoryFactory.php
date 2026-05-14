<?php

namespace Database\Factories;

use App\Models\Accessory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Accessory>
 */
class AccessoryFactory extends Factory
{
    protected $model = Accessory::class;

    private const SLOT_TO_SOCKET = [
        'head'  => 'Head_Top',
        'face'  => 'Face_Front',
        'back'  => 'Back_Center',
        'hands' => 'Hand_R',
        'legs'  => 'Hip_R',
    ];

    public function definition(): array
    {
        $slot = fake()->randomElement(Accessory::SLOTS);

        return [
            'name'              => fake()->unique()->words(2, true) . ' Gear',
            'slot'              => $slot,
            'rarity'            => fake()->randomElement(Accessory::RARITIES),
            'base_model_url'    => null,
            'socket_name'       => self::SLOT_TO_SOCKET[$slot],
            'generation_status' => 'pending',
            'meshy_task_id'     => null,
            'preview_url'       => null,
            'is_active'         => true,
        ];
    }

    public function slot(string $slot): static
    {
        return $this->state(fn () => [
            'slot'        => $slot,
            'socket_name' => self::SLOT_TO_SOCKET[$slot] ?? 'Head_Top',
        ]);
    }

    public function ready(): static
    {
        return $this->state(fn () => [
            'generation_status' => 'ready',
            'base_model_url'    => 'models/accessories/test/base.glb',
        ]);
    }
}
