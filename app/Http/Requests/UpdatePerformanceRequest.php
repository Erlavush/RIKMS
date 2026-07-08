<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePerformanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'overall_physical_accomplishment' => ['nullable', 'numeric', 'between:0,150'],
            'rows' => ['nullable', 'array'],
            'rows.*.activity_output_indicator' => ['nullable', 'string', 'max:500'],
            'rows.*.target' => ['nullable', 'numeric', 'min:0'],
            'rows.*.actual' => ['nullable', 'numeric', 'min:0'],
            'rows.*.status' => ['nullable', 'in:Not Started,Ongoing,Completed,Delayed,Exceeded'],
        ];
    }
}
