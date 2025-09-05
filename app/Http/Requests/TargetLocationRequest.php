<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
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
            "title" => [
                "required",
                "sometimes",
                $this->route()->target_location
                    ? "unique:target_locations,title," . $this->route()->target_location
                    : "unique:target_locations,title",
            ],
            "region_psgc_id" => [
                "required",
                "sometimes"
            ],
            "region" => [
                "required",
                "sometimes"
            ],
            "city_municipality_psgc_id" => [
                "required",
                "sometimes"
            ],
            "city_municipality" => [
                "required",
                "sometimes"
            ],
            "barangay" => [
                "required",
                "sometimes"
            ],
            "street" => [
                "required",
                "sometimes"
            ],

            "barangay_psgc_id" => [
                "required",
                "sometimes",
                $this->route()->target_location
                    ? "unique:target_locations,barangay_psgc_id," . $this->route()->target_location
                    : "unique:target_locations,barangay_psgc_id",
            ],

            "form_id" => [
                "required",
                "sometimes",
                "exists:forms,id"
            ],
            "form_history_id" => [
                "required",
                "sometimes",
                "exists:form_histories,id"
            ],
            "response_limit" => [
                "required",
                "sometimes",
                "integer",
                "min:1"
            ],
            "is_final" => [
                "boolean",
                "sometimes"
            ],
            "is_done" => [
                "boolean",
                "sometimes"
            ],

        ];
    }

    public function messages()
    {
        return [
            'barangay_psgc_id.unique' => 'The selected barangay already exists with the same region and city/municipality.',
        ];
    }
}
