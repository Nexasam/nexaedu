<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $card->student->first_name }} {{ $card->student->last_name }} report card</title>
    <style>
        @page { margin: 22px 26px; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #fff; color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 8px; line-height: 1.35; }
        strong { font-weight: 600; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #374151; padding: 4px 5px; vertical-align: middle; }
        th { background: #f3f4f6; color: #1f2937; font-size: 7px; font-weight: 600; text-transform: uppercase; }
        .header { border: 1px solid #374151; padding: 10px 12px 8px; text-align: center; }
        .logo { width: 56px; height: 56px; object-fit: contain; }
        .school-name { margin: 3px 0 2px; color: {{ $school->primary_color ?: '#059669' }}; font-size: 18px; font-weight: 600; text-transform: uppercase; }
        .muted { color: #4b5563; }
        .title { margin-top: 5px; font-size: 10px; font-weight: 600; text-transform: uppercase; }
        .student-band { border: 1px solid #374151; border-top: 0; padding: 7px 9px; }
        .student-band td { width: 33.33%; border: 0; padding: 2px 4px; }
        .label { color: #6b7280; font-weight: 600; }
        .section { margin-top: 9px; }
        .section-title { margin: 0 0 4px; font-size: 8px; font-weight: 600; text-transform: uppercase; }
        .performance { border-top: 2px solid {{ $school->primary_color ?: '#059669' }}; }
        .performance td { text-align: center; }
        .performance td:first-child { text-align: left; font-weight: 600; }
        .grade { color: {{ $school->primary_color ?: '#059669' }}; font-weight: 600; }
        .summary { margin-top: 7px; }
        .summary td { width: 20%; border-color: #d1d5db; background: #f9fafb; text-align: center; }
        .summary strong { font-weight: 600; display: block; margin-top: 2px; font-size: 11px; }
        .two-column { width: 100%; margin-top: 9px; }
        .two-column > tbody > tr > td { width: 50%; border: 0; padding: 0; vertical-align: top; }
        .two-column > tbody > tr > td:first-child { padding-right: 5px; }
        .two-column > tbody > tr > td:last-child { padding-left: 5px; }
        .plain th { background: #f3f4f6; color: #111827; }
        .comments { margin-top: 9px; }
        .comments td { width: 50%; height: 42px; vertical-align: top; }
        .comments strong { font-weight: 600; display: block; margin-bottom: 4px; text-transform: uppercase; }
        .signatures { margin-top: 10px; }
        .signatures td { width: 50%; border: 0; text-align: center; vertical-align: bottom; }
        .signature-image { width: 120px; height: 38px; object-fit: contain; }
        .signature-line { width: 145px; margin: 1px auto 0; border-top: 1px solid #4b5563; padding-top: 2px; }
        .footer { margin-top: 8px; border-top: 1px solid #9ca3af; padding-top: 5px; color: #4b5563; font-size: 7px; }
        .draft { position: fixed; top: 330px; left: 145px; color: rgba(156, 163, 175, .22); font-size: 76px; font-weight: 600; transform: rotate(-22deg); }
    </style>
</head>
<body>
@php
    $subjects = collect($snapshot['subjects'] ?? []);
    $summary = $snapshot['summary'] ?? [];
    $layout = array_merge([
        'show_position' => true,
        'show_class_statistics' => true,
        'show_attendance' => true,
        'show_promotion_decision' => true,
    ], $template->layout ?? []);
    $assessmentColumns = $subjects->flatMap(fn ($subject) => $subject['assessment_items'] ?? [])
        ->unique(fn ($item) => strtolower($item['name']).'-'.$item['raw_max_score'].'-'.$item['weight'])
        ->values();
    $cumulativeTerms = $subjects->flatMap(fn ($subject) => $subject['term_results'] ?? [])->unique('term_id')->values();
    $parseRatingScaleEntry = function ($value) {
        if (preg_match('/^\s*([A-Za-z0-9]+)\s*[-–:]\s*(.+)$/u', (string) $value, $matches)) {
            return ['code' => strtoupper($matches[1]), 'description' => trim($matches[2])];
        }

        return null;
    };
    $ratingOptions = $card->ratings->flatMap(fn ($rating) => $rating->traitDefinition?->rating_options ?? [])->unique()->values();
    $ratingScale = $ratingOptions->map($parseRatingScaleEntry)->filter()->keyBy('code');
    $ratingOptions->filter(fn ($option) => $parseRatingScaleEntry($option) === null)->each(function ($option) use (&$ratingScale) {
        $hasMatchingDescription = $ratingScale->contains(
            fn ($entry) => strcasecmp($entry['description'], (string) $option) === 0
        );

        if (! $hasMatchingDescription) {
            $nextCode = collect(range('A', 'Z'))->first(fn ($code) => ! $ratingScale->has($code));

            if ($nextCode !== null) {
                $ratingScale->put($nextCode, ['code' => $nextCode, 'description' => (string) $option]);
            }
        }
    });
    $ratingScale = $ratingScale->sortKeys()->values();
    $ratingCode = function ($value) use ($parseRatingScaleEntry, $ratingScale) {
        $parsedEntry = $parseRatingScaleEntry($value);

        if ($parsedEntry !== null) {
            return $parsedEntry['code'];
        }

        return $ratingScale->first(
            fn ($entry) => strcasecmp($entry['description'], (string) $value) === 0
        )['code'] ?? (string) $value;
    };
    $position = $summary['position'] ?? null;
    $suffix = $position && $position % 10 === 1 && $position % 100 !== 11 ? 'st'
        : ($position && $position % 10 === 2 && $position % 100 !== 12 ? 'nd'
        : ($position && $position % 10 === 3 && $position % 100 !== 13 ? 'rd' : 'th'));
    $ordinal = function ($value) {
        if (! $value) {
            return '-';
        }

        $suffix = $value % 10 === 1 && $value % 100 !== 11 ? 'st'
            : ($value % 10 === 2 && $value % 100 !== 12 ? 'nd'
            : ($value % 10 === 3 && $value % 100 !== 13 ? 'rd' : 'th'));

        return $value.$suffix;
    };
    $score = fn ($value) => is_numeric($value) ? rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') : '-';
@endphp

@if (! in_array($card->status, ['prepared', 'approved'], true))
    <div class="draft">DRAFT</div>
@endif

<div class="header">
    @if ($logoDataUri)
        <img src="{{ $logoDataUri }}" class="logo" alt="School logo">
    @endif
    <div class="school-name">{{ $school->name }}</div>
    <div class="muted">{{ collect([$school->address, $school->phone, $school->email])->filter()->join(' | ') }}</div>
    <div class="title">{{ $run->term->name }} report sheet</div>
</div>

<div class="student-band">
    <table>
        <tr>
            <td><span class="label">Session:</span> {{ $run->academicSession->name }}</td>
            <td><span class="label">Term:</span> {{ $run->term->name }}</td>
            <td><span class="label">Class:</span> {{ $run->classArm->schoolClass->name }} / {{ $run->classArm->name }}</td>
        </tr>
        <tr>
            <td><span class="label">Student:</span> {{ $card->student->first_name }} {{ $card->student->last_name }}</td>
            <td><span class="label">Admission no:</span> {{ $card->student->admission_no }}</td>
            <td><span class="label">Template:</span> {{ $template->name }}</td>
        </tr>
    </table>
</div>

<table class="summary">
    <tr>
        <td><span class="label">Total</span><strong>{{ $score(data_get($summary, 'total')) }}/{{ $score(data_get($summary, 'maximum_score')) }}</strong></td>
        <td><span class="label">Percentage</span><strong>{{ $score(data_get($summary, 'average')) }}%</strong></td>
        @if ($layout['show_class_statistics'])<td><span class="label">Class average</span><strong>{{ $score(data_get($summary, 'class_average')) }}%</strong></td>@endif
        @if ($layout['show_position'])<td><span class="label">Position</span><strong>{{ $position ? $position.$suffix : '-' }}</strong></td>@endif
        @if ($layout['show_promotion_decision'])<td><span class="label">Decision</span><strong>{{ ucfirst($card->promotion_decision ?: 'pending') }}</strong></td>@endif
    </tr>
</table>

<div class="section">
    <div class="section-title">Academic performance</div>
    <table class="performance">
        <thead>
        <tr>
            <th style="text-align:left">Subjects</th>
            @foreach ($assessmentColumns as $column)
                <th>{{ $column['name'] }}<br>/{{ $score($column['raw_max_score']) }}@if ((float) $column['raw_max_score'] !== (float) $column['weight']) ({{ $score($column['weight']) }}%)@endif</th>
            @endforeach
            <th>Total</th>
            <th>%</th>
            @foreach ($cumulativeTerms as $cumulativeTerm)<th>{{ $cumulativeTerm['term_name'] }} %</th>@endforeach
            @if ($cumulativeTerms->isNotEmpty())<th>Cumulative %</th>@endif
            @if ($layout['show_class_statistics'])
                <th>Class avg. %</th>
                <th>Class highest %</th>
                <th>Class lowest %</th>
            @endif
            @if ($layout['show_position'])<th>Position</th>@endif
            <th>Grade</th>
            <th>Remark</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($subjects as $subject)
            <tr>
                <td>{{ $subject['name'] }}</td>
                @foreach ($assessmentColumns as $column)
                    @php
                        $item = collect($subject['assessment_items'] ?? [])->first(fn ($candidate) => strtolower($candidate['name']) === strtolower($column['name']) && (float) $candidate['raw_max_score'] === (float) $column['raw_max_score'] && (float) $candidate['weight'] === (float) $column['weight']);
                    @endphp
                    <td>{{ $item ? $score($item['raw_score']) : '-' }}</td>
                @endforeach
                <td><strong>{{ $score($subject['score']) }}/{{ $score($subject['maximum_score']) }}</strong></td>
                <td>{{ $score($subject['percentage']) }}</td>
                @foreach ($cumulativeTerms as $cumulativeTerm)
                    <td>{{ $score(collect($subject['term_results'] ?? [])->firstWhere('term_id', $cumulativeTerm['term_id'])['percentage'] ?? null) }}</td>
                @endforeach
                @if ($cumulativeTerms->isNotEmpty())<td><strong>{{ $score($subject['cumulative_percentage'] ?? null) }}</strong></td>@endif
                @if ($layout['show_class_statistics'])
                    <td>{{ $score(data_get($subject, 'class_statistics.class_average')) }}%</td>
                    <td>{{ $score(data_get($subject, 'class_statistics.highest')) }}%</td>
                    <td>{{ $score(data_get($subject, 'class_statistics.lowest')) }}%</td>
                @endif
                @if ($layout['show_position'])<td>{{ $ordinal($subject['position'] ?? null) }}</td>@endif
                <td class="grade">{{ $subject['grade'] }}</td>
                <td>{{ $subject['remark'] ?: '-' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<table class="two-column">
    <tr>
        <td>
            <div class="section-title">Personal development</div>
            <table class="plain">
                <thead><tr><th style="text-align:left">Trait</th><th>Rating</th></tr></thead>
                <tbody>
                @forelse ($card->ratings as $rating)
                    <tr><td>{{ $rating->traitDefinition?->name }}</td><td style="text-align:center"><strong>{{ $ratingCode($rating->rating) }}</strong></td></tr>
                @empty
                    <tr><td colspan="2">No ratings entered.</td></tr>
                @endforelse
                </tbody>
            </table>
        </td>
        <td>
            <div class="section-title">Scale and attendance</div>
            <table class="plain">
                <tbody>
                @foreach ($ratingScale as $entry)
                    <tr><td><strong>{{ $entry['code'] }}</strong></td><td>{{ $entry['description'] }}</td></tr>
                @endforeach
                @if ($layout['show_attendance'])<tr><td colspan="2"><strong>Opened:</strong> {{ $card->school_days_opened ?? '-' }} &nbsp; <strong>Present:</strong> {{ $card->days_present ?? '-' }} &nbsp; <strong>Absent:</strong> {{ $card->days_absent ?? '-' }} &nbsp; <strong>Late:</strong> {{ $card->times_late ?? '-' }}</td></tr>@endif
                </tbody>
            </table>
        </td>
    </tr>
</table>

<table class="comments">
    <tr>
        <td><strong>Class teacher's remark</strong>{{ $card->class_teacher_comment ?: '-' }}</td>
        <td><strong>Principal's remark</strong>{{ $card->principal_comment ?: '-' }}</td>
    </tr>
</table>

@if (!empty($card->signature_snapshot))
<table class="signatures">
    <tr>
        @foreach (['class_teacher' => 'Class Teacher', 'principal' => 'Principal'] as $type => $label)
            @php $signer = data_get($card->signature_snapshot, $type); @endphp
            <td>
                @if ($signer && ($signatureDataUris[$type] ?? null))
                    <img src="{{ $signatureDataUris[$type] }}" class="signature-image" alt="{{ $label }} signature">
                    <div class="signature-line"><strong>{{ data_get($signer, 'name') }}</strong><br><span class="muted">{{ data_get($signer, 'title', $label) }}</span></div>
                @else
                    <div class="signature-line muted">{{ $label }} signature unavailable</div>
                @endif
            </td>
        @endforeach
    </tr>
</table>
@endif

<div class="footer">
    <strong>Grading definition:</strong>
    @foreach ($template->gradingScheme->bands as $band)
        {{ $band->grade }} = {{ $score($band->minimum_score) }}-{{ $score($band->maximum_score) }}@if ($band->remark) ({{ $band->remark }})@endif{{ $loop->last ? '' : ', ' }}
    @endforeach
    <span style="float:right">Next term starts: {{ $card->next_term_starts_on?->format('d M Y') ?? 'To be announced' }}</span>
</div>
</body>
</html>
