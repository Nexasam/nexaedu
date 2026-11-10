<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\SchoolDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolDomain>
 */
class SchoolDomainFactory extends Factory
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
            'domain' => fake()->unique()->domainName(),
            'type' => 'subdomain',
            'is_primary' => true,
            'verified_at' => null,
        ];
    }
}
