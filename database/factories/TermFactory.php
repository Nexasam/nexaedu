<?php

namespace Database\Factories;

use App\Models\AcademicSession;
use App\Models\School;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Term>
 */
class TermFactory extends Factory
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
            'academic_session_id' => AcademicSession::factory(),
            'name' => fake()->unique()->randomElement(['First Term', 'Second Term', 'Third Term']).' '.fake()->unique()->numberBetween(1, 999),
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addMonths(3)->toDateString(),
            'is_current' => false,
        ];
    }
}
