<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentFileRequest extends FormRequest
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
        $document = $this->route('document');
        $fileRule = $document && $document->file_path ? 'nullable' : 'required';

        return [
            'document_file' => [$fileRule, 'file', 'mimetypes:application/pdf', 'max:10240'],
            'manual_title' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:3000'],
            'project_start_date' => ['nullable', 'date'],
            'project_end_date' => ['nullable', 'date', 'after_or_equal:project_start_date'],
            'reporting_period' => ['nullable', 'string', 'max:120'],
            'reporting_year' => ['nullable', 'integer', 'between:2020,2035'],
            'agency' => ['nullable', 'string', 'max:255'],
        ];
    }
}
