<?php

namespace Database\Seeders;

use App\Models\PlatformReportCardTemplate;
use Illuminate\Database\Seeder;

class PlatformReportCardTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PlatformReportCardTemplate::query()->updateOrCreate(
            ['name' => 'SAFSIMS-style Secondary Report'],
            [
                'education_level' => 'secondary',
                'term_result_mode' => 'cumulative',
                'is_active' => true,
                'layout' => [
                    'show_position' => true,
                    'show_class_statistics' => true,
                    'show_attendance' => true,
                    'show_promotion_decision' => true,
                ],
                'grading_bands' => [
                    ['minimum_score' => 90, 'maximum_score' => 100, 'grade' => 'A1', 'remark' => 'Distinction'],
                    ['minimum_score' => 80, 'maximum_score' => 89.99, 'grade' => 'B2', 'remark' => 'Excellent'],
                    ['minimum_score' => 70, 'maximum_score' => 79.99, 'grade' => 'B3', 'remark' => 'Very Good'],
                    ['minimum_score' => 65, 'maximum_score' => 69.99, 'grade' => 'C4', 'remark' => 'Good'],
                    ['minimum_score' => 60, 'maximum_score' => 64.99, 'grade' => 'C5', 'remark' => 'Credit'],
                    ['minimum_score' => 50, 'maximum_score' => 59.99, 'grade' => 'C6', 'remark' => 'Credit'],
                    ['minimum_score' => 45, 'maximum_score' => 49.99, 'grade' => 'D7', 'remark' => 'Pass'],
                    ['minimum_score' => 40, 'maximum_score' => 44.99, 'grade' => 'E8', 'remark' => 'Pass'],
                    ['minimum_score' => 0, 'maximum_score' => 39.99, 'grade' => 'F9', 'remark' => 'Fail'],
                ],
                'traits' => collect([
                    'Mental Alertness', 'Physical Development', 'Relationship with Teachers',
                    'Relationship with Classmates', 'General Habit and Attitude', 'Punctuality',
                    'Neatness', 'Leadership',
                ])->map(fn (string $name): array => [
                    'name' => $name,
                    'rating_options' => ['A - Distinction', 'B - Excellent', 'C - Very Good', 'D - Good', 'E - Needs Improvement'],
                ])->all(),
            ],
        );
    }
}
