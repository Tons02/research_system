<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MultipleSyncSurveyAnswerRequest extends FormRequest
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
            "survey_answers" => [
                "required",
                "array",
                "min:1"
            ],
            "survey_answers.*.target_location_id" => [
                "required",
                "exists:target_locations,id"
            ],
            "survey_answers.*.family_size" => [
                "required",
                "integer"
            ],
            "survey_answers.*.age" => [
                "required",
                "integer"
            ],
            "survey_answers.*.gender" => [
                "required",
                "in:male,female"
            ],
            "survey_answers.*.address" => [
                "required",
            ],
            "survey_answers.*.contact_number" => [
                "nullable",
                "regex:/^\+63\d{10}$/",
            ],
            "survey_answers.*.date" => [
                "required",
                "date",
            ],
            "survey_answers.*.income_class" => [
                "nullable",
            ],
            "survey_answers.*.sub_income_class" => [
                "nullable",
            ],
            "survey_answers.*.monthly_utility_expenses" => [
                "required",
            ],
            "survey_answers.*.sub_monthly_utility_expenses" => [
                "nullable",
            ],
            "survey_answers.*.educational_attainment" => [
                "required",
            ],
            "survey_answers.*.employment_status" => [
                "nullable",
            ],
            "survey_answers.*.occupation" => [
                "required",
            ],
            "survey_answers.*.structure_of_house" => [
                "required",
            ],
            "survey_answers.*.ownership_of_house" => [
                "nullable",
            ],
            "survey_answers.*.house_rent" => [
                "nullable",
            ],
            "survey_answers.*.questionnaire_answer" => [
                "required",
                "array",
            ],
            "survey_answers.*.created_at" => [
                "required",
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            "survey_answers.required" => "The survey array is required.",
            "survey_answers.array" => "The survey must be an array.",
            "survey_answers.min" => "At least one survey is required.",
            "survey_answers.*.target_location_id.required" => "Target location ID is required for all survey_answers.",
            "survey_answers.*.target_location_id.exists" => "Invalid target location ID in survey #:position.",
            "survey_answers.*.contact_number.regex" => "Contact number must be in format +63XXXXXXXXXX for survey #:position.",
        ];
    }
}
