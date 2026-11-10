<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SchoolSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $school = $this->authorizeSchoolSuperAdmin($request);

        return Inertia::render('SchoolAdmin/Settings/Edit', [
            'school' => [
                'id' => $school->id,
                'name' => $school->name,
                'logo_path' => $school->logo_path,
                'logo_url' => $school->logoUrl(),
                'primary_color' => $school->primary_color ?? '#059669',
                'secondary_color' => $school->secondary_color ?? '#0f172a',
                'brand_font' => $school->brand_font ?? 'Inter',
                'principal_staff_profile_id' => $school->principal_staff_profile_id,
            ],
            'staff' => $school->staffProfiles()->where('status', 'active')->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'job_title', 'signature_path']),
            'fontOptions' => ['Inter', 'Nunito', 'Poppins', 'Roboto', 'Lato', 'Merriweather'],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);

        $validated = $request->validate([
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_font' => ['required', 'string', Rule::in(['Inter', 'Nunito', 'Poppins', 'Roboto', 'Lato', 'Merriweather'])],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'principal_staff_profile_id' => ['nullable', Rule::exists('staff_profiles', 'id')->where('school_id', $school->id)],
        ]);

        if ($request->hasFile('logo')) {
            $previousLogoPath = $school->logo_path;
            $validated['logo_path'] = $request->file('logo')->store('school-logos', 'public');
        }

        unset($validated['logo']);

        $school->update($validated);

        if (isset($previousLogoPath) && $previousLogoPath !== $school->logo_path) {
            Storage::disk('public')->delete($previousLogoPath);
        }

        return back()->with('status', 'School settings updated.');
    }

    private function authorizeSchoolSuperAdmin(Request $request): School
    {
        $school = $request->user()->currentSchool;

        abort_if($school === null, 403);
        abort_unless($request->user()->hasSchoolRole($school, 'school_super_admin'), 403);

        return $school;
    }
}
