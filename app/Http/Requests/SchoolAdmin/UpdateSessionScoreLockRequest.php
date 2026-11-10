<?php

namespace App\Http\Requests\SchoolAdmin;

use App\Models\AcademicSession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSessionScoreLockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $school = $this->user()?->currentSchool;
        $academicSession = $this->route('academicSession');

        return $school !== null
            && $academicSession instanceof AcademicSession
            && $academicSession->school_id === $school->id
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
            'score_entries_locked' => ['required', 'boolean'],
            'score_lock_at' => ['nullable', 'date'],
            'score_unlock_at' => ['nullable', 'date'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->filled('score_lock_at') && $this->filled('score_unlock_at') && $this->date('score_unlock_at')->lte($this->date('score_lock_at'))) {
                $validator->errors()->add('score_unlock_at', 'The scheduled unlock must be after the scheduled lock.');
            }
        }];
    }
}
