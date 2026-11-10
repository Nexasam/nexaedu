<?php

namespace Database\Factories;

use App\Models\PlatformReportCardTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformReportCardTemplate>
 */
class PlatformReportCardTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $educationLevel = fake()->randomElement(['creche', 'nursery', 'primary', 'secondary']);

        return [
            'name' => fake()->words(3, true),
            'education_level' => $educationLevel,
            'education_levels' => [$educationLevel],
            'term_result_mode' => 'independent',
            'grading_bands' => [
                ['minimum_score' => 70, 'maximum_score' => 100, 'grade' => 'A', 'remark' => 'Excellent'],
                ['minimum_score' => 0, 'maximum_score' => 69.99, 'grade' => 'F', 'remark' => 'Needs Improvement'],
            ],
            'traits' => [
                ['name' => 'Character', 'rating_options' => ['Excellent', 'Good', 'Needs Improvement']],
            ],
            'is_active' => true,
        ];
    }
}
