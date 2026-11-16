<?php

namespace Database\Factories;

use App\Models\ImpersonationAudit;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImpersonationAudit>
 */
class ImpersonationAuditFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'impersonator_user_id' => User::factory()->platformAdmin(),
            'target_user_id' => User::factory(),
            'school_id' => School::factory(),
            'impersonator_name' => fake()->name(),
            'impersonator_email' => fake()->safeEmail(),
            'target_name' => fake()->name(),
            'target_email' => fake()->safeEmail(),
            'school_name' => fake()->company().' School',
            'status' => 'active',
            'started_at' => now(),
            'start_ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
