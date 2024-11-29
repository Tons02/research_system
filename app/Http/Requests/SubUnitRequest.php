<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubUnitRequest extends FormRequest
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
            'sub_units' => 'required|array',
            'sub_units.*.sync_id' => 'required|integer|distinct', 
            'sub_units.*.sub_unit_code' => 'required|string|distinct',// i want this not to take effect on my table i want it to check on the payload  
            'sub_units.*.sub_unit_name' => 'required|string',
            'sub_units.*.unit_id' => 'required|exists:units,sync_id',
            'sub_units.*.updated_at' => 'required|date',
            'sub_units.*.deleted_at' => 'nullable|date',
        ];
    }


    public function messages()
    {
        return [
            'sub_units.sync_id.unique' => 'The sync id must be unique.',
            'sub_units.sub_unit_code.unique' => 'The sub unit code must be unique.',
        ];
    }
}
