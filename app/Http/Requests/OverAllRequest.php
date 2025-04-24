<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OverAllRequest extends FormRequest
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
            'pagination'         => 'nullable|in:none',
            'search'             => 'nullable|string',
            'page'               => 'nullable|integer|min:1',
            'per_page'           => 'nullable|integer|min:1|max:100',
            'target_location_id' => 'nullable|integer|exists:target_locations,id',
            'surveyor_id'        => 'nullable|integer|exists:users,id',
            'from_date'          => 'nullable|date_format:Y-m-d|before_or_equal:to_date',
            'to_date'            => 'nullable|date_format:Y-m-d|after_or_equal:from_date',
            'status'             => 'nullable|in:inactive,active',
        ];
    }
}
