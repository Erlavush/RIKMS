<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMetadataRequest extends FormRequest
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
            'title' => ['nullable', 'string', 'max:500'],
            'abstract' => ['nullable', 'string', 'max:12000'],
            'methodology' => ['nullable', 'string', 'max:12000'],
            'review_of_related_literature' => ['nullable', 'string', 'max:12000'],
            'theoretical_framework' => ['nullable', 'string', 'max:12000'],
            'results_and_discussion' => ['nullable', 'string', 'max:12000'],
            'keywords' => ['nullable', 'string', 'max:2000'],
            'authors' => ['nullable', 'string', 'max:2000'],
            'public_fields' => ['nullable', 'array'],
            'public_fields.*' => ['string', 'in:title,abstract,methodology,review_of_related_literature,theoretical_framework,results_and_discussion'],
        ];
    }
}
