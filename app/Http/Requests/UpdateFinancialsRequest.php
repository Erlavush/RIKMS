<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFinancialsRequest extends FormRequest
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
            'allotted_budget' => ['nullable', 'numeric', 'min:0'],
            'released_amount' => ['nullable', 'numeric', 'min:0'],
            'obligated_amount' => ['nullable', 'numeric', 'min:0'],
            'utilized_amount' => ['nullable', 'numeric', 'min:0'],
            'financial_as_of_date' => ['nullable', 'date'],
        ];
    }
}
