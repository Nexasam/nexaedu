<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\StaffProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StaffSignatureController extends Controller
{
    public function __invoke(Request $request, StaffProfile $staffProfile): RedirectResponse
    {
        $school = $request->user()->currentSchool;

        abort_if($school === null, 403);
        abort_unless($request->user()->hasSchoolRole($school, 'school_super_admin'), 403);
        abort_unless($staffProfile->school_id === $school->id, 404);
        $this->storeSignature($request, $staffProfile);

        return back()->with('status', 'Staff signature uploaded.');
    }

    public function storeMine(Request $request): RedirectResponse
    {
        $school = $request->user()->currentSchool;

        abort_if($school === null, 403);
        $staffProfile = StaffProfile::query()
            ->whereBelongsTo($school)
            ->whereBelongsTo($request->user())
            ->firstOrFail();
        $this->storeSignature($request, $staffProfile);

        return back()->with('status', 'Your signature has been uploaded.');
    }

    private function storeSignature(Request $request, StaffProfile $staffProfile): void
    {
        $validated = $request->validate([
            'signature' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024', 'dimensions:min_width=120,min_height=40,max_width=2400,max_height=1200'],
        ]);

        $staffProfile->forceFill([
            'signature_path' => $validated['signature']->store("staff-signatures/{$staffProfile->school_id}"),
            'signature_uploaded_at' => now(),
        ])->save();
    }
}
