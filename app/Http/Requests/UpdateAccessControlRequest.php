<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccessControlRequest extends FormRequest
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
            'access_mode' => ['required', 'in:public_download,request_access,restricted_admin,embargo_until_date,external_link_only'],
            'embargo_until' => ['nullable', 'required_if:access_mode,embargo_until_date', 'date', 'after:today'],
            'external_url' => ['nullable', 'required_if:access_mode,external_link_only', 'url', 'max:500'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'owner_email' => ['nullable', 'email', 'max:255'],
            'notify_access_requests' => ['nullable', 'boolean'],
            'notify_research_inquiries' => ['nullable', 'boolean'],
            'send_copy_to_agency_admin' => ['nullable', 'boolean'],
        ];
    }
}
