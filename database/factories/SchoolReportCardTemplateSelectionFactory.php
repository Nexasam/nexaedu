<?php

namespace Database\Factories;

use App\Models\ClassGroup;
use App\Models\PlatformReportCardTemplate;
use App\Models\ReportCardTemplate;
use App\Models\School;
use App\Models\SchoolReportCardTemplateSelection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolReportCardTemplateSelection>
 */
class SchoolReportCardTemplateSelectionFactory extends Factory
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
            'class_group_id' => ClassGroup::factory(),
            'platform_report_card_template_id' => PlatformReportCardTemplate::factory(),
            'report_card_template_id' => ReportCardTemplate::factory(),
        ];
    }
}
