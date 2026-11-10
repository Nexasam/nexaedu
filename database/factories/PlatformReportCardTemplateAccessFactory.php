<?php

namespace Database\Factories;

use App\Models\PlatformReportCardTemplate;
use App\Models\PlatformReportCardTemplateAccess;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformReportCardTemplateAccess>
 */
class PlatformReportCardTemplateAccessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory()->approved(),
            'platform_report_card_template_id' => PlatformReportCardTemplate::factory(),
            'status' => 'granted',
            'payment_method' => 'offline',
            'payment_reference' => fake()->bothify('PAY-########'),
            'amount' => fake()->randomFloat(2, 10000, 250000),
            'currency' => 'NGN',
            'payment_note' => 'Offline payment confirmed.',
            'granted_at' => now(),
            'revoked_at' => null,
        ];
    }
}
