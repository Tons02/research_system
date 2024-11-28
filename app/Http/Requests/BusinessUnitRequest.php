<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusinessUnitRequest extends FormRequest
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
            'business_unit' => 'required|array',
            'business_unit.*.sync_id' => 'required|integer|distinct', 
            'business_unit.*.business_unit_code' => 'required|string|distinct',// i want this not to take effect on my table i want it to check on the payload  
            'business_unit.*.business_unit_name' => 'required|string',
            'business_unit.*.company_id' => 'required|exists:companies,sync_id',
            'business_unit.*.updated_at' => 'required|date',
            'business_unit.*.deleted_at' => 'nullable|date',
        ];
    }


    public function messages()
    {
        return [
            'business_unit.sync_id.unique' => 'The sync_id must be unique.',
            'business_unit.business_unit_code.unique' => 'The business_unit_code must be unique.',
        ];
    }
}
