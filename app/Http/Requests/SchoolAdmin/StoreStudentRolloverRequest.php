<?php

namespace App\Http\Requests\SchoolAdmin;

use App\Models\School;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStudentRolloverRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $school = $this->user()?->currentSchool;

        return $school instanceof School && $this->user()->hasSchoolRole($school, 'school_super_admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'source_session_id' => ['required', 'different:target_session_id', Rule::exists('academic_sessions', 'id')->where('school_id', $this->user()->current_school_id)],
            'target_session_id' => ['required', Rule::exists('academic_sessions', 'id')->where('school_id', $this->user()->current_school_id)],
            'target_term_id' => ['nullable', Rule::exists('terms', 'id')->where('school_id', $this->user()->current_school_id)],
            'students' => ['required', 'array', 'min:1'],
            'students.*.student_profile_id' => ['required', 'distinct', Rule::exists('student_profiles', 'id')->where('school_id', $this->user()->current_school_id)],
            'students.*.decision' => ['required', Rule::in(['promoted', 'repeated', 'graduated', 'withdrawn'])],
            'students.*.target_class_arm_id' => ['nullable', Rule::exists('class_arms', 'id')->where('school_id', $this->user()->current_school_id)],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ($this->input('students', []) as $index => $student) {
                if (in_array($student['decision'] ?? null, ['promoted', 'repeated'], true) && empty($student['target_class_arm_id'])) {
                    $validator->errors()->add("students.{$index}.target_class_arm_id", 'Select a destination class arm for this student.');
                }
            }
        }];
    }
}
