<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TargetLocationRequest extends FormRequest
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
            "region_psgc_id" => ["required"],
            "region" => ["required"],
            "city_municipality_psgc_id" => ["required"],
            "city_municipality" => ["required"],
            "barangay" => ["required"],
            "street" => ["required"],

            "barangay_psgc_id" => [
                "required",
                $this->route()->target_location
                    ? "unique:target_locations,barangay_psgc_id," . $this->route()->target_location
                    : "unique:target_locations,barangay_psgc_id",
            ],

            "form_id" => ["required","exists:forms,id"],
            "response_limit" => ["required", "integer", "min:1"]
        ];
    }

    public function messages()
    {
        return [
            'barangay_psgc_id.unique' => 'The selected barangay already exists with the same region and city/municipality.',
        ];
    }
}
