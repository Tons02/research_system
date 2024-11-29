<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentRequest extends FormRequest
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
            'departments' => 'required|array',
            'departments.*.sync_id' => 'required|integer|distinct', 
            'departments.*.department_code' => 'required|string|distinct',// i want this not to take effect on my table i want it to check on the payload  
            'departments.*.department_name' => 'required|string',
            'departments.*.business_unit_id' => 'required|exists:business_units,sync_id',
            'departments.*.updated_at' => 'required|date',
            'departments.*.deleted_at' => 'nullable|date',
        ];
    }


    public function messages()
    {
        return [
            'departments.sync_id.unique' => 'The sync_id must be unique.',
            'departments.department_code.unique' => 'The department_code must be unique.',
        ];
    }
}
