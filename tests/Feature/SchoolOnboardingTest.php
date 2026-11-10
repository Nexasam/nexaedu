<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_submit_school_for_approval(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('school.onboarding.store'), [
                'name' => 'Corona School Ikoyi',
                'slug' => 'corona-school-ikoyi',
                'address' => 'Ikoyi, Lagos',
                'phone' => '08012345678',
                'email' => 'admin@corona.test',
            ])
            ->assertRedirect(route('school.onboarding.pending'));

        $school = School::query()->where('slug', 'corona-school-ikoyi')->firstOrFail();

        $this->assertSame('pending', $school->status);
        $this->assertSame(8, mb_strlen($school->code));
        $this->assertDatabaseHas('school_users', [
            'school_id' => $school->id,
            'user_id' => $user->id,
            'role' => 'school_super_admin',
        ]);
        $this->assertDatabaseHas('school_domains', [
            'school_id' => $school->id,
            'domain' => 'corona-school-ikoyi.'.parse_url(config('app.url'), PHP_URL_HOST),
            'is_primary' => true,
        ]);
        $this->assertSame($school->id, $user->fresh()->current_school_id);
    }

    public function test_platform_admin_can_approve_a_pending_school(): void
    {
        $platformAdmin = User::factory()->platformAdmin()->create();
        $school = School::factory()->create();

        $this
            ->actingAs($platformAdmin)
            ->post(route('platform.schools.approval.store', $school))
            ->assertRedirect();

        $school->refresh();

        $this->assertSame('approved', $school->status);
        $this->assertNotNull($school->approved_at);
        $this->assertSame($platformAdmin->id, $school->approved_by);
    }

    public function test_non_platform_admin_cannot_approve_a_school(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('platform.schools.approval.store', $school))
            ->assertForbidden();

        $this->assertSame('pending', $school->fresh()->status);
    }
}
