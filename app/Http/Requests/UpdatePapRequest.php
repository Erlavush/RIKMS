<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePapRequest extends FormRequest
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
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'beneficiary_government' => ['nullable', 'boolean'],
            'beneficiary_academe' => ['nullable', 'boolean'],
            'beneficiary_business' => ['nullable', 'boolean'],
            'beneficiary_civil_society' => ['nullable', 'boolean'],
            'beneficiary_media' => ['nullable', 'boolean'],
        ];
    }
}
