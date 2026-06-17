<?php

namespace Database\Factories;

use App\Models\Operator;
use App\Models\OperatorSkin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OperatorSkin>
 */
class OperatorSkinFactory extends Factory
{
    protected $model = OperatorSkin::class;

    public function definition(): array
    {
        return [
            'operator_id'        => Operator::query()->inRandomOrder()->value('id'),
            'name'               => fake()->unique()->words(2, true) . ' Skin',
            'rarity'             => fake()->randomElement(OperatorSkin::RARITIES),
            'palette_json'       => null,
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
            'texture_url'       => 'models/operators/test/skins/test/texture.ktx2',
        ]);
    }
}
