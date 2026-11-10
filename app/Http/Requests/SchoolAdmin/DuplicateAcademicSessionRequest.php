<?php

namespace App\Http\Requests\SchoolAdmin;

use App\Models\AcademicSession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DuplicateAcademicSessionRequest extends FormRequest
{
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
            'is_current' => ['nullable', 'boolean'],
            'copy_enrollments' => ['nullable', 'boolean'],
            'copy_teacher_assignments' => ['nullable', 'boolean'],
            'copy_assessment_flows' => ['nullable', 'boolean'],
        ];
    }
}
