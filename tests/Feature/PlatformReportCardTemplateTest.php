<?php

namespace Tests\Feature;

use App\Models\ClassGroup;
use App\Models\PlatformReportCardTemplate;
use App\Models\PlatformReportCardTemplateAccess;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformReportCardTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_create_a_complete_report_card_template(): void
    {
        $admin = User::factory()->platformAdmin()->create();

        $this->actingAs($admin)
            ->post(route('platform.report-card-templates.store'), $this->templatePayload())
            ->assertRedirect();

        $template = PlatformReportCardTemplate::query()->firstOrFail();

        $this->assertSame('primary', $template->education_level);
        $this->assertSame('A', $template->grading_bands[0]['grade']);
        $this->assertSame(['Excellent', 'Good'], $template->traits[0]['rating_options']);
        $this->assertTrue($template->layout['show_attendance']);
    }

    public function test_school_selects_a_compatible_platform_template_for_a_class_group(): void
    {
        [$user, $school] = $this->schoolSuperAdmin();
        $classGroup = ClassGroup::factory()->create(['school_id' => $school->id, 'name' => 'Primary']);
        $platformTemplate = PlatformReportCardTemplate::factory()->create($this->templatePayload());
        PlatformReportCardTemplateAccess::factory()->create([
            'school_id' => $school->id,
            'platform_report_card_template_id' => $platformTemplate->id,
        ]);

        $this->actingAs($user)
            ->put(route('school.report-card-template-selection.update'), [
                'class_group_id' => $classGroup->id,
                'platform_report_card_template_id' => $platformTemplate->id,
            ])
            ->assertRedirect();

        $selection = $school->reportCardTemplateSelections()->firstOrFail();
        $materializedTemplate = $selection->reportCardTemplate;

        $this->assertSame($platformTemplate->id, $selection->platform_report_card_template_id);
        $this->assertSame('Platform Primary Report', $materializedTemplate->name);
        $this->assertSame($platformTemplate->layout, $materializedTemplate->layout);
        $this->assertCount(2, $materializedTemplate->gradingScheme->bands);
        $this->assertDatabaseHas('report_card_trait_definitions', [
            'school_id' => $school->id,
            'class_group_id' => $classGroup->id,
            'name' => 'Character',
        ]);
    }

    public function test_school_cannot_select_a_template_for_another_education_level(): void
    {
        [$user, $school] = $this->schoolSuperAdmin();
        $classGroup = ClassGroup::factory()->create(['school_id' => $school->id, 'name' => 'Secondary']);
        $platformTemplate = PlatformReportCardTemplate::factory()->create($this->templatePayload());
        PlatformReportCardTemplateAccess::factory()->create([
            'school_id' => $school->id,
            'platform_report_card_template_id' => $platformTemplate->id,
        ]);

        $this->actingAs($user)
            ->from(route('school.report-cards.index'))
            ->put(route('school.report-card-template-selection.update'), [
                'class_group_id' => $classGroup->id,
                'platform_report_card_template_id' => $platformTemplate->id,
            ])
            ->assertRedirect(route('school.report-cards.index'))
            ->assertSessionHasErrors('template');

        $this->assertDatabaseCount('school_report_card_template_selections', 0);
    }

    public function test_platform_admin_records_offline_payment_and_grants_template_access(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $school = School::factory()->approved()->create();
        $template = PlatformReportCardTemplate::factory()->create();

        $this->actingAs($admin)
            ->post(route('platform.report-card-templates.accesses.store', $template), [
                'school_id' => $school->id,
                'payment_reference' => 'BANK-TRANSFER-001',
                'amount' => 75000,
                'payment_note' => 'Confirmed by accounts.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('platform_report_card_template_accesses', [
            'school_id' => $school->id,
            'platform_report_card_template_id' => $template->id,
            'status' => 'granted',
            'payment_method' => 'offline',
            'payment_reference' => 'BANK-TRANSFER-001',
            'granted_by' => $admin->id,
        ]);
    }

    public function test_school_cannot_select_an_unpaid_template(): void
    {
        [$user, $school] = $this->schoolSuperAdmin();
        $classGroup = ClassGroup::factory()->create(['school_id' => $school->id, 'name' => 'Primary']);
        $template = PlatformReportCardTemplate::factory()->create($this->templatePayload());

        $this->actingAs($user)
            ->from(route('school.report-cards.index'))
            ->put(route('school.report-card-template-selection.update'), [
                'class_group_id' => $classGroup->id,
                'platform_report_card_template_id' => $template->id,
            ])
            ->assertSessionHasErrors('template');

        $this->assertDatabaseCount('school_report_card_template_selections', 0);
    }

    /** @return array{User, School} */
    private function schoolSuperAdmin(): array
    {
        $user = User::factory()->create();
        $school = School::factory()->approved()->create(['owner_user_id' => $user->id]);
        $school->users()->create(['user_id' => $user->id, 'role' => 'school_super_admin', 'status' => 'active']);
        $user->update(['current_school_id' => $school->id]);

        return [$user, $school];
    }

    /** @return array<string, mixed> */
    private function templatePayload(): array
    {
        return [
            'name' => 'Platform Primary Report',
            'education_level' => 'primary',
            'term_result_mode' => 'independent',
            'is_active' => true,
            'layout' => [
                'show_position' => true,
                'show_class_statistics' => true,
                'show_attendance' => true,
                'show_promotion_decision' => true,
            ],
            'grading_bands' => [
                ['minimum_score' => 70, 'maximum_score' => 100, 'grade' => 'A', 'remark' => 'Excellent'],
                ['minimum_score' => 0, 'maximum_score' => 69.99, 'grade' => 'F', 'remark' => 'Needs Improvement'],
            ],
            'traits' => [
                ['name' => 'Character', 'rating_options' => ['Excellent', 'Good']],
            ],
        ];
    }
}
