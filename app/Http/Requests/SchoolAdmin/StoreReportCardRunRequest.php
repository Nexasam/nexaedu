<?php

namespace App\Http\Requests\SchoolAdmin;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportCardRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->currentSchool !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'report_card_template_id' => ['required', 'integer'],
            'academic_session_id' => ['required', 'integer'],
            'term_id' => ['required', 'integer'],
            'class_arm_id' => ['required', 'integer'],
        ];
    }
}
