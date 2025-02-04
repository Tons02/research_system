<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationRequest extends FormRequest
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
            'locations' => 'required|array',
            'locations.*.sync_id' => 'required|integer|distinct',
            'locations.*.location_code' => 'required|string',
            'locations.*.location_name' => 'required|string',
            // 'locations' => 'distinct:strict',
            'locations.*.sub_units' => 'required|array|exists:sub_units,sync_id',
            'locations.*.updated_at' => 'required|date',
            'locations.*.deleted_at' => 'nullable|date',
        ];
    }


    public function messages()
    {
        return [
            'locations.sync_id.unique' => 'The sync_id must be unique.',
            'locations.department_code.unique' => 'The department_code must be unique.',
        ];
    }
}
