<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
            "personal_info.id_prefix" => "sometimes|required",
            "personal_info.id_no" => [
                "sometimes",
                "required",
                "unique:users,id_no",
            ],
            "personal_info.first_name" => "sometimes:required",
            "personal_info.last_name" => "sometimes:required",
            "personal_info.mobile_number" => [
                "unique:users,mobile_number," . $this->route()->user,
                "regex:/^\+63\d{10}$/",
            ],
            "personal_info.gender" => "sometimes|required|in:male,female",
            "personal_info.one_charging_sync_id" => ["required", "exists:one_chargings,sync_id"],
            "username" => [
                "required",
                "unique:users,username," . $this->route()->user,
            ],

            "role_id" => ["required", "exists:roles,id"],
        ];
    }

    public function messages()
    {
        return [
            "personal_info.id_prefix.unique" => "The id prefix has already been taken",
            "personal_info.id_prefix.required" => "The id prefix field is required",
            "personal_info.id_no.unique" => "The employee id has already been taken",
            "personal_info.id_no.required" => "The employee id field is required",
            "personal_info.first_name.required" => "The first name field is required.",
            "personal_info.last_name.required" => "The last name field is required.",
            "personal_info.mobile_number.regex" => "The mobile number field format is invalid.",
            "personal_info.mobile_number.unique" => "The contact number has already been taken.",
            "personal_info.gender.required" => "The gender field is required.",
            "personal_info.gender.in" => "The gender must be either 'male' or 'female'.",
            "personal_info.company_id.required" => "The company field is required.",
            "personal_info.company_id.exists" => "The selected company is invalid",
            "personal_info.business_unit_id.required" => "The business unit field is required.",
            "personal_info.business_unit_id.exists" => "The selected business unit is invalid or does not belong to the selected company.",
            "personal_info.department_id.required" => "The department field is required.",
            "personal_info.department_id.exists" => "The selected department is invalid or does not belong to the selected business unit.",
            "personal_info.unit_id.required" => "The unit field is required.",
            "personal_info.unit_id.exists" => "The selected unit is invalid or does not belong to the selected department.",
            "personal_info.sub_unit_id.required" => "The sub unit field is required.",
            "personal_info.sub_unit_id.exists" => "The selected sub unit is invalid or does not belong to the selected unit.",

            "personal_info.location_id.required" => "The location field is required.",
            "personal_info.location_id.exists" => "The selected location is invalid or does not belong to the selected sub unit.",
        ];
    }
}
