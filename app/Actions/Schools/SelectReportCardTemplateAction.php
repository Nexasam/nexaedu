<?php

namespace App\Actions\Schools;

use App\Models\ClassGroup;
use App\Models\PlatformReportCardTemplate;
use App\Models\School;
use App\Models\SchoolReportCardTemplateSelection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SelectReportCardTemplateAction
{
    public function handle(School $school, ClassGroup $classGroup, PlatformReportCardTemplate $platformTemplate): SchoolReportCardTemplateSelection
    {
        if ($classGroup->school_id !== $school->id || ! $platformTemplate->is_active) {
            throw ValidationException::withMessages(['template' => 'The selected report-card template is not available.']);
        }

        $hasAccess = $school->platformReportCardTemplateAccesses()
            ->where('platform_report_card_template_id', $platformTemplate->id)
            ->where('status', 'granted')
            ->whereNull('revoked_at')
            ->exists();

        if (! $hasAccess) {
            throw ValidationException::withMessages(['template' => 'This report-card template has not been granted to your school.']);
        }

        $educationLevels = $platformTemplate->education_levels ?: [$platformTemplate->education_level];

        if (! in_array($this->educationLevelFor($classGroup), $educationLevels, true)) {
            throw ValidationException::withMessages(['template' => 'Select a template configured for this class group level.']);
        }

        return DB::transaction(function () use ($classGroup, $platformTemplate, $school): SchoolReportCardTemplateSelection {
            $selection = SchoolReportCardTemplateSelection::query()
                ->whereBelongsTo($school)
                ->whereBelongsTo($classGroup)
                ->first();
            $materializedTemplate = $selection?->reportCardTemplate;
            $gradingScheme = $materializedTemplate?->gradingScheme
                ?? $school->gradingSchemes()->create([
                    'class_group_id' => $classGroup->id,
                    'name' => $platformTemplate->name.' grading',
                    'is_default' => true,
                    'is_active' => true,
                ]);

            $gradingScheme->update([
                'name' => $platformTemplate->name.' grading',
                'class_group_id' => $classGroup->id,
                'is_active' => true,
            ]);
            $gradingScheme->bands()->delete();

            foreach ($platformTemplate->grading_bands as $index => $band) {
                $gradingScheme->bands()->create([
                    'minimum_score' => $band['minimum_score'],
                    'maximum_score' => $band['maximum_score'],
                    'grade' => $band['grade'],
                    'label' => $band['label'] ?? null,
                    'remark' => $band['remark'] ?? null,
                    'sort_order' => $index + 1,
                ]);
            }

            if ($materializedTemplate) {
                $materializedTemplate->update([
                    'grading_scheme_id' => $gradingScheme->id,
                    'name' => $platformTemplate->name,
                    'layout' => $platformTemplate->layout,
                    'is_active' => true,
                ]);
            } else {
                $materializedTemplate = $school->reportCardTemplates()->create([
                    'class_group_id' => $classGroup->id,
                    'grading_scheme_id' => $gradingScheme->id,
                    'name' => $platformTemplate->name,
                    'term_result_mode' => $platformTemplate->term_result_mode,
                    'layout' => $platformTemplate->layout,
                    'is_active' => true,
                ]);
            }

            $traitNames = collect($platformTemplate->traits)->pluck('name');

            foreach ($platformTemplate->traits as $index => $trait) {
                $school->reportCardTraitDefinitions()->updateOrCreate(
                    ['class_group_id' => $classGroup->id, 'name' => $trait['name']],
                    [
                        'rating_scale' => 'options',
                        'rating_options' => $trait['rating_options'],
                        'sort_order' => $index + 1,
                        'is_active' => true,
                    ],
                );
            }

            $school->reportCardTraitDefinitions()
                ->where('class_group_id', $classGroup->id)
                ->whereNotIn('name', $traitNames)
                ->whereDoesntHave('ratings')
                ->delete();

            return SchoolReportCardTemplateSelection::query()->updateOrCreate(
                ['school_id' => $school->id, 'class_group_id' => $classGroup->id],
                [
                    'platform_report_card_template_id' => $platformTemplate->id,
                    'report_card_template_id' => $materializedTemplate->id,
                ],
            );
        });
    }

    public function educationLevelFor(ClassGroup $classGroup): string
    {
        $name = strtolower($classGroup->name);

        return match (true) {
            str_contains($name, 'creche'), str_contains($name, 'pre-school'), str_contains($name, 'preschool') => 'creche',
            str_contains($name, 'nursery'), str_contains($name, 'kindergarten') => 'nursery',
            str_contains($name, 'secondary'), str_contains($name, 'jss'), str_contains($name, 'sss') => 'secondary',
            default => 'primary',
        };
    }
}
