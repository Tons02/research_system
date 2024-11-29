<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnitRequest extends FormRequest
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
            'units' => 'required|array',
            'units.*.sync_id' => 'required|integer|distinct', 
            'units.*.unit_code' => 'required|string|distinct',// i want this not to take effect on my table i want it to check on the payload  
            'units.*.unit_name' => 'required|string',
            'units.*.department_id' => 'required|exists:departments,sync_id',
            'units.*.updated_at' => 'required|date',
            'units.*.deleted_at' => 'nullable|date',
        ];
    }


    public function messages()
    {
        return [
            'units.sync_id.unique' => 'The sync_id must be unique.',
            'units.department_code.unique' => 'The department_code must be unique.',
        ];
    }
}
