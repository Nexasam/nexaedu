<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_school_is_redirected_to_onboarding(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('school.onboarding.create'));
    }

    public function test_user_with_pending_school_is_redirected_to_pending_screen(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->create(['owner_user_id' => $user->id, 'status' => 'pending']);
        $school->users()->create([
            'user_id' => $user->id,
            'role' => 'school_super_admin',
            'status' => 'active',
        ]);
        $user->forceFill(['current_school_id' => $school->id])->save();

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('school.onboarding.pending'));
    }

    public function test_platform_admin_dashboard_redirects_to_platform_schools(): void
    {
        $platformAdmin = User::factory()->platformAdmin()->create();

        $this
            ->actingAs($platformAdmin)
            ->get(route('dashboard'))
            ->assertRedirect(route('platform.schools.index'));
    }

    public function test_approved_school_super_admin_can_view_dashboard(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->approved()->create([
            'owner_user_id' => $user->id,
            'logo_path' => 'school-logos/example.png',
        ]);
        $school->users()->create([
            'user_id' => $user->id,
            'role' => 'school_super_admin',
            'status' => 'active',
        ]);
        $user->forceFill(['current_school_id' => $school->id])->save();

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('school')
                ->where('school.id', $school->id)
                ->where('currentSchool.logo_url', '/storage/school-logos/example.png?v='.$school->updated_at->timestamp)
                ->has('setup')
            );
    }

    public function test_approved_school_user_without_super_admin_role_cannot_view_dashboard(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->approved()->create(['owner_user_id' => $user->id]);
        $school->users()->create([
            'user_id' => $user->id,
            'role' => 'teacher',
            'status' => 'active',
        ]);
        $user->forceFill(['current_school_id' => $school->id])->save();

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();
    }
}
