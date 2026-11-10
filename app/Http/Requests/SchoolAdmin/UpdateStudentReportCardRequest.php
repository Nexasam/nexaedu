<?php

namespace App\Http\Requests\SchoolAdmin;

use App\Models\ReportCardRun;
use App\Models\StudentReportCard;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStudentReportCardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $school = $this->user()?->currentSchool;
        $reportCardRun = $this->route('reportCardRun');
        $studentReportCard = $this->route('studentReportCard');

        return $school !== null
            && $reportCardRun instanceof ReportCardRun
            && $studentReportCard instanceof StudentReportCard
            && $reportCardRun->school_id === $school->id
            && $studentReportCard->school_id === $school->id
            && $studentReportCard->report_card_run_id === $reportCardRun->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'school_days_opened' => ['nullable', 'integer', 'min:0', 'max:366'],
            'days_present' => ['nullable', 'integer', 'min:0', 'max:366'],
            'days_absent' => ['nullable', 'integer', 'min:0', 'max:366'],
            'times_late' => ['nullable', 'integer', 'min:0', 'max:366'],
            'class_teacher_comment' => ['nullable', 'string', 'max:2000'],
            'principal_comment' => ['nullable', 'string', 'max:2000'],
            'promotion_decision' => ['required', 'in:pending,promoted,repeated,graduated,withdrawn'],
            'next_term_starts_on' => ['nullable', 'date'],
            'ratings' => ['nullable', 'array'],
            'ratings.*.trait_definition_id' => [
                'required',
                'integer',
                Rule::exists('report_card_trait_definitions', 'id')->where('school_id', $this->user()?->currentSchool?->id),
            ],
            'ratings.*.rating' => ['nullable', 'string', 'max:50'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $opened = $this->integer('school_days_opened');
            $present = $this->integer('days_present');
            $absent = $this->integer('days_absent');

            if ($opened > 0 && $present + $absent > $opened) {
                $validator->errors()->add('days_present', 'Days present and absent cannot be more than the school days opened.');
            }
        }];
    }
}
