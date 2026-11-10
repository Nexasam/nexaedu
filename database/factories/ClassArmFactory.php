<?php

namespace Database\Factories;

use App\Models\ClassArm;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassArm>
 */
class ClassArmFactory extends Factory
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
            'school_class_id' => SchoolClass::factory(),
            'name' => 'Arm '.fake()->unique()->numberBetween(1, 999),
            'label' => fake()->randomLetter(),
        ];
    }
}
