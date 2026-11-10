<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformReportCardTemplate;
use App\Models\PlatformReportCardTemplateAccess;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReportCardTemplateAccessController extends Controller
{
    public function store(Request $request, PlatformReportCardTemplate $platformReportCardTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'payment_note' => ['nullable', 'string', 'max:1000'],
        ]);
        $school = School::query()->findOrFail($validated['school_id']);

        PlatformReportCardTemplateAccess::query()->updateOrCreate(
            [
                'school_id' => $school->id,
                'platform_report_card_template_id' => $platformReportCardTemplate->id,
            ],
            [
                'status' => 'granted',
                'payment_method' => 'offline',
                'payment_reference' => $validated['payment_reference'] ?? null,
                'amount' => $validated['amount'] ?? null,
                'currency' => 'NGN',
                'payment_note' => $validated['payment_note'] ?? null,
                'granted_by' => $request->user()->id,
                'granted_at' => now(),
                'revoked_at' => null,
            ],
        );

        return back()->with('status', "{$platformReportCardTemplate->name} is now available to {$school->name}.");
    }

    public function destroy(Request $request, PlatformReportCardTemplateAccess $platformReportCardTemplateAccess): RedirectResponse
    {
        $platformReportCardTemplateAccess->update([
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);

        return back()->with('status', 'School access to the report-card template has been revoked.');
    }
}
