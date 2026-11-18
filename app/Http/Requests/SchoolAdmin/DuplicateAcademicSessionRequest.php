<?php

namespace App\Http\Requests\SchoolAdmin;

use App\Models\AcademicSession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DuplicateAcademicSessionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_current' => $this->boolean('is_current'),
            'copy_enrollments' => $this->boolean('copy_enrollments'),
            'copy_teacher_assignments' => $this->boolean('copy_teacher_assignments'),
            'copy_assessment_flows' => $this->boolean('copy_assessment_flows'),
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $school = $this->user()?->currentSchool;
        $sourceSession = $this->route('academicSession');

        return $school !== null
            && $sourceSession instanceof AcademicSession
            && $sourceSession->school_id === $school->id
            && $this->user()->hasSchoolRole($school, 'school_super_admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('academic_sessions')->where('school_id', $this->user()->currentSchool->id),
            ],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_current' => ['required', 'boolean'],
            'copy_enrollments' => ['required', 'boolean'],
            'copy_teacher_assignments' => ['required', 'boolean'],
            'copy_assessment_flows' => ['required', 'boolean'],
        ];
    }
}
