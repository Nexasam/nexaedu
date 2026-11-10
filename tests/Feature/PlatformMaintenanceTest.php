<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Platform\PlatformMaintenanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;
use Tests\TestCase;

class PlatformMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_view_the_maintenance_page(): void
    {
        $this->withoutVite();
        $admin = User::factory()->platformAdmin()->create();
        $this->mock(PlatformMaintenanceService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('status')->once()->andReturn($this->serverStatus());
        });

        $this->actingAs($admin)
            ->get(route('platform.maintenance.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Maintenance/Index')
                ->where('server.branch', 'main')
                ->where('server.commit', 'abc1234'));
    }

    public function test_non_platform_admin_cannot_access_or_run_maintenance_operations(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('platform.maintenance.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('platform.maintenance.store'), ['operation' => 'migrate'])
            ->assertForbidden();
    }

    public function test_platform_admin_can_run_an_allowlisted_maintenance_operation(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $result = [
            'operation' => 'optimize_clear',
            'success' => true,
            'output' => 'Caches cleared.',
            'ran_at' => now()->toIso8601String(),
        ];
        $this->mock(PlatformMaintenanceService::class, function (MockInterface $mock) use ($result): void {
            $mock->shouldReceive('execute')->once()->with('optimize_clear')->andReturn($result);
        });

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('platform.maintenance.store'), ['operation' => 'optimize_clear'])
            ->assertRedirect()
            ->assertSessionHas('maintenance_console', $result);
    }

    public function test_maintenance_operation_requires_recent_password_confirmation(): void
    {
        $admin = User::factory()->platformAdmin()->create();

        $this->actingAs($admin)
            ->post(route('platform.maintenance.store'), ['operation' => 'migrate'])
            ->assertRedirect(route('password.confirm'));
    }

    public function test_arbitrary_commands_are_rejected(): void
    {
        $admin = User::factory()->platformAdmin()->create();

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('platform.maintenance.store'), ['operation' => 'shell_exec'])
            ->assertSessionHasErrors('operation');
    }

    /** @return array{environment: string, laravel_version: string, php_version: string, branch: string, commit: string} */
    private function serverStatus(): array
    {
        return [
            'environment' => 'production',
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'branch' => 'main',
            'commit' => 'abc1234',
        ];
    }
}
