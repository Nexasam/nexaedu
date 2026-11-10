<?php

namespace Database\Factories;

use App\Models\ClassGroup;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolClass>
 */
class SchoolClassFactory extends Factory
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
            'class_group_id' => ClassGroup::factory(),
            'name' => 'Class '.fake()->unique()->numberBetween(1, 999),
            'sort_order' => 0,
        ];
    }
}
