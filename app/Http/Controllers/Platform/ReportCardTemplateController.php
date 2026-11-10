<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformReportCardTemplate;
use App\Models\PlatformReportCardTemplateAccess;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ReportCardTemplateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Platform/ReportCardTemplates/Index', [
            'templates' => PlatformReportCardTemplate::query()
                ->withCount(['accesses' => fn ($query) => $query->where('status', 'granted')->whereNull('revoked_at')])
                ->orderBy('name')
                ->get(),
            'schools' => School::query()->where('status', 'approved')->orderBy('name')->get(['id', 'name', 'code']),
            'accesses' => PlatformReportCardTemplateAccess::query()
                ->where('status', 'granted')
                ->whereNull('revoked_at')
                ->with(['school:id,name,code', 'platformTemplate:id,name'])
                ->latest('granted_at')
                ->get(),
            'educationLevels' => [
                ['id' => 'creche', 'name' => 'Creche'],
                ['id' => 'nursery', 'name' => 'Nursery'],
                ['id' => 'primary', 'name' => 'Primary'],
                ['id' => 'secondary', 'name' => 'Secondary'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $template = PlatformReportCardTemplate::query()->create($this->validatedTemplateData($request));

        return back()->with('status', "{$template->name} report-card template created.");
    }

    public function update(Request $request, PlatformReportCardTemplate $platformReportCardTemplate): RedirectResponse
    {
        $platformReportCardTemplate->update($this->validatedTemplateData($request));

        return back()->with('status', "{$platformReportCardTemplate->name} report-card template updated. Schools can reselect it to apply the changes.");
    }

    public function destroy(PlatformReportCardTemplate $platformReportCardTemplate): RedirectResponse
    {
        if ($platformReportCardTemplate->selections()->exists() || $platformReportCardTemplate->accesses()->exists()) {
            throw ValidationException::withMessages([
                'template' => 'This template has school access or selections. Deactivate it instead of deleting it.',
            ]);
        }

        $platformReportCardTemplate->delete();

        return back()->with('status', 'Report-card template deleted.');
    }

    /** @return array<string, mixed> */
    private function validatedTemplateData(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'education_levels' => ['required', 'array', 'min:1', 'max:4'],
            'education_levels.*' => ['required', 'distinct', 'in:creche,nursery,primary,secondary'],
            'term_result_mode' => ['required', 'in:independent,cumulative'],
            'is_active' => ['required', 'boolean'],
            'layout' => ['required', 'array'],
            'layout.show_position' => ['required', 'boolean'],
            'layout.show_class_statistics' => ['required', 'boolean'],
            'layout.show_attendance' => ['required', 'boolean'],
            'layout.show_promotion_decision' => ['required', 'boolean'],
            'grading_bands' => ['required', 'array', 'min:1', 'max:20'],
            'grading_bands.*.minimum_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'grading_bands.*.maximum_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'grading_bands.*.grade' => ['required', 'string', 'max:20'],
            'grading_bands.*.remark' => ['nullable', 'string', 'max:100'],
            'traits' => ['required', 'array', 'min:1', 'max:30'],
            'traits.*.name' => ['required', 'string', 'max:100'],
            'traits.*.rating_options' => ['required', 'array', 'min:2', 'max:12'],
            'traits.*.rating_options.*' => ['required', 'string', 'max:50'],
        ]);

        foreach ($validated['grading_bands'] as $band) {
            if ((float) $band['minimum_score'] > (float) $band['maximum_score']) {
                throw ValidationException::withMessages(['grading_bands' => 'Each grade minimum must be less than or equal to its maximum.']);
            }
        }

        $validated['education_level'] = $validated['education_levels'][0];

        return $validated;
    }
}
