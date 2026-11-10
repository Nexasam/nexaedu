<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Actions\Schools\SelectReportCardTemplateAction;
use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\PlatformReportCardTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReportCardTemplateSelectionController extends Controller
{
    public function __construct(private SelectReportCardTemplateAction $selectReportCardTemplate) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_group_id' => ['required', 'integer'],
            'platform_report_card_template_id' => ['required', 'integer'],
        ]);
        $school = $request->user()->currentSchool;
        abort_if($school === null, 403);
        $classGroup = ClassGroup::query()->whereBelongsTo($school)->findOrFail($validated['class_group_id']);
        $template = PlatformReportCardTemplate::query()->findOrFail($validated['platform_report_card_template_id']);

        $this->selectReportCardTemplate->handle($school, $classGroup, $template);

        return back()->with('status', "{$template->name} selected for {$classGroup->name}.");
    }
}
