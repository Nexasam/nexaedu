<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('school_report_card_template_selections');

        Schema::create('school_report_card_template_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id');
            $table->foreignId('class_group_id');
            $table->foreignId('platform_report_card_template_id');
            $table->foreignId('report_card_template_id');
            $table->timestamps();

            $table->foreign('school_id', 'srcts_school_fk')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('class_group_id', 'srcts_group_fk')->references('id')->on('class_groups')->cascadeOnDelete();
            $table->foreign('platform_report_card_template_id', 'srcts_platform_fk')->references('id')->on('platform_report_card_templates')->restrictOnDelete();
            $table->foreign('report_card_template_id', 'srcts_template_fk')->references('id')->on('report_card_templates')->restrictOnDelete();
            $table->unique(['school_id', 'class_group_id'], 'srcts_school_group_unique');
            $table->index('platform_report_card_template_id', 'srcts_platform_template_idx');
        });

        DB::table('report_card_templates')
            ->orderBy('id')
            ->get()
            ->each(function (object $template): void {
                if (! $template->class_group_id) {
                    return;
                }

                $group = DB::table('class_groups')->find($template->class_group_id);

                if (! $group) {
                    return;
                }

                $name = strtolower($group->name);
                $educationLevel = match (true) {
                    str_contains($name, 'creche'), str_contains($name, 'pre-school'), str_contains($name, 'preschool') => 'creche',
                    str_contains($name, 'nursery'), str_contains($name, 'kindergarten') => 'nursery',
                    str_contains($name, 'secondary'), str_contains($name, 'jss'), str_contains($name, 'sss') => 'secondary',
                    default => 'primary',
                };
                $gradingBands = DB::table('grading_bands')
                    ->where('grading_scheme_id', $template->grading_scheme_id)
                    ->orderBy('sort_order')
                    ->get(['minimum_score', 'maximum_score', 'grade', 'label', 'remark'])
                    ->map(fn (object $band): array => (array) $band)
                    ->all();
                $traits = DB::table('report_card_trait_definitions')
                    ->where('school_id', $template->school_id)
                    ->where('class_group_id', $template->class_group_id)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get(['name', 'rating_options'])
                    ->map(fn (object $trait): array => [
                        'name' => $trait->name,
                        'rating_options' => json_decode($trait->rating_options ?: '[]', true),
                    ])
                    ->all();

                if ($gradingBands === []) {
                    return;
                }

                if ($traits === []) {
                    $traits = [['name' => 'Character', 'rating_options' => ['Excellent', 'Very Good', 'Good', 'Fair', 'Needs Improvement']]];
                }

                $platformTemplateId = DB::table('platform_report_card_templates')->insertGetId([
                    'name' => $template->name,
                    'education_level' => $educationLevel,
                    'term_result_mode' => $template->term_result_mode,
                    'layout' => $template->layout ?: json_encode([
                        'show_position' => true,
                        'show_class_statistics' => true,
                        'show_attendance' => true,
                        'show_promotion_decision' => true,
                    ]),
                    'grading_bands' => json_encode($gradingBands),
                    'traits' => json_encode($traits),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('school_report_card_template_selections')->updateOrInsert(
                    ['school_id' => $template->school_id, 'class_group_id' => $template->class_group_id],
                    [
                        'platform_report_card_template_id' => $platformTemplateId,
                        'report_card_template_id' => $template->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_report_card_template_selections');
    }
};
