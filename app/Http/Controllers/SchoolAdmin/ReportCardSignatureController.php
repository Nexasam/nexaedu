<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\StudentReportCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportCardSignatureController extends Controller
{
    public function __invoke(Request $request, StudentReportCard $studentReportCard, string $type): StreamedResponse
    {
        $school = $request->user()->currentSchool;

        abort_if($school === null, 403);
        abort_unless($studentReportCard->school_id === $school->id, 404);
        abort_unless(in_array($type, ['class_teacher', 'principal'], true), 404);
        $path = data_get($studentReportCard->signature_snapshot, "{$type}.path");
        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Content-Disposition' => 'inline']);
    }
}
