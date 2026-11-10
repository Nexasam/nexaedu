<?php

namespace Database\Factories;

use App\Models\ClassGroup;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassGroup>
 */
class ClassGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => fake()->unique()->randomElement(['Creche', 'Nursery', 'Primary', 'Secondary']).' '.fake()->unique()->numberBetween(1, 999),
            'sort_order' => 0,
        ];
    }
}
