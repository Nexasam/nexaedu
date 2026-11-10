<?php

namespace App\Http\Requests\SchoolAdmin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReportCardPolicyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->currentSchool !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'term_result_mode' => ['required', Rule::in(['independent', 'cumulative'])],
            'term_weights' => ['required_if:term_result_mode,cumulative', 'array', 'max:3'],
            'term_weights.*' => ['nullable', 'numeric', 'min:0.01', 'max:100'],
        ];
    }
}
