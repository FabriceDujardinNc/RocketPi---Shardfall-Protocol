<?php

namespace Database\Factories;

use App\Models\Operator;
use App\Models\PlayerLoadout;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlayerLoadout>
 */
class PlayerLoadoutFactory extends Factory
{
    protected $model = PlayerLoadout::class;

    public function definition(): array
    {
        return [
            'user_id'           => User::factory(),
            'operator_id'       => Operator::query()->inRandomOrder()->value('id'),
            'operator_skin_id'  => null,
            'weapon_id'         => null,
            'weapon_skin_id'    => null,
            'head_accessory_id' => null,
            'face_accessory_id' => null,
            'back_accessory_id' => null,
        ];
    }
}
