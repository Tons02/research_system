<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'companies' => 'required|array',
            'companies.*.sync_id' => 'required|integer|distinct', 
            'companies.*.company_code' => 'required|string|distinct',// i want this not to take effect on my table i want it to check on the payload  
            'companies.*.company_name' => 'required|string',
            'companies.*.updated_at' => 'required|date',
            'companies.*.deleted_at' => 'nullable|date',
        ];
    }


    public function messages()
    {
        return [
            'companies.sync_id.unique' => 'The sync_id must be unique.',
            'companies.company_code.unique' => 'The company_code must be unique.',
        ];
    }
}
