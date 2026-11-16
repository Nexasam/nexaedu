<?php

namespace Tests\Feature;

use App\Models\ImpersonationAudit;
use App\Models\School;
use App\Models\User;
use App\Services\Platform\UserImpersonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PlatformUserImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_view_school_users_and_support_history(): void
    {
        $this->withoutVite();
        $admin = User::factory()->platformAdmin()->create();
        [$schoolAdmin, $school] = $this->schoolUser('school_super_admin');
        ImpersonationAudit::factory()->create([
            'impersonator_user_id' => $admin->id,
            'target_user_id' => $schoolAdmin->id,
            'school_id' => $school->id,
            'target_name' => $schoolAdmin->name,
            'school_name' => $school->name,
        ]);

        $this->actingAs($admin)
            ->get(route('platform.users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Users/Index')
                ->has('users', 1)
                ->where('users.0.name', $schoolAdmin->name)
                ->where('users.0.role', 'School Super Admin')
                ->where('users.0.is_school_owner', true)
                ->where('users.0.can_impersonate', true)
                ->has('recentSessions', 1));
    }

    public function test_non_platform_admin_cannot_view_users_or_start_support_session(): void
    {
        [$user] = $this->schoolUser();
        [$target] = $this->schoolUser();

        $this->actingAs($user)->get(route('platform.users.index'))->assertForbidden();
        $this->actingAs($user)
            ->post(route('platform.impersonations.store', $target), ['password' => 'password'])
            ->assertForbidden();
    }

    public function test_platform_admin_can_start_and_end_an_audited_support_session(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        [$target, $school] = $this->schoolUser();

        $this->actingAs($admin)
            ->post(route('platform.impersonations.store', $target), ['password' => 'password'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas(UserImpersonationService::SESSION_IMPERSONATOR_ID, $admin->id);

        $this->assertAuthenticatedAs($target);
        $audit = ImpersonationAudit::query()->firstOrFail();
        $this->assertSame('active', $audit->status);
        $this->assertSame($school->id, $audit->school_id);
        $this->assertSame($admin->id, $audit->impersonator_user_id);
        $this->assertSame($target->id, $audit->target_user_id);

        $this->delete(route('platform.impersonations.destroy'))
            ->assertRedirect(route('platform.users.index'))
            ->assertSessionMissing(UserImpersonationService::SESSION_IMPERSONATOR_ID);

        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseHas('impersonation_audits', ['id' => $audit->id, 'status' => 'ended']);
        $this->assertNotNull($audit->fresh()->ended_at);
    }

    public function test_starting_support_session_requires_the_platform_admin_password(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        [$target] = $this->schoolUser();

        $this->actingAs($admin)
            ->post(route('platform.impersonations.store', $target), ['password' => 'incorrect-password'])
            ->assertSessionHasErrors('password');

        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseCount('impersonation_audits', 0);
    }

    public function test_platform_admin_accounts_cannot_be_impersonated(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $otherAdmin = User::factory()->platformAdmin()->create();

        $this->actingAs($admin)
            ->post(route('platform.impersonations.store', $otherAdmin), ['password' => 'password'])
            ->assertUnprocessable();

        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseCount('impersonation_audits', 0);
    }

    public function test_logging_out_during_support_closes_the_audit(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        [$target] = $this->schoolUser();

        $this->actingAs($admin)
            ->post(route('platform.impersonations.store', $target), ['password' => 'password']);

        $audit = ImpersonationAudit::query()->firstOrFail();

        $this->post(route('logout'))->assertRedirect('/');

        $this->assertGuest();
        $this->assertDatabaseHas('impersonation_audits', ['id' => $audit->id, 'status' => 'logged_out']);
    }

    /** @return array{User, School} */
    private function schoolUser(string $role = 'teacher'): array
    {
        $user = User::factory()->create();
        $school = School::factory()->approved()->create([
            'owner_user_id' => $role === 'school_super_admin' ? $user->id : User::factory(),
        ]);
        $school->users()->create([
            'user_id' => $user->id,
            'role' => $role,
            'status' => 'active',
        ]);
        $user->update(['current_school_id' => $school->id]);

        return [$user->fresh(), $school];
    }
}
