<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SurveyAnswerRequest extends FormRequest
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
            "target_location_id" => ["required", "exists:target_locations,id"],
            "family_size" => [
                "required",
                "integer"
            ],
            "name" => [
                "required",
            ],
            "age" => [
                "required",
                "integer"
            ],
            "gender" => [
                "required",
                "in:male,female"
            ],
            "address" => [
                "required",
            ],
            "contact_number" => [
                "required",
                "regex:/^\+63\d{10}$/",
            ],
            "date" => [
                "required",
                "date",
            ],
            "monthly_utility_expenses" => [
                "required",
            ],
            "educational_attainment" => [
                "required",
            ],
            "occupation" => [
                "required",
            ],
            "structure_of_house" => [
                "required",
            ],
            "questionnaire_answer" => [
                "required",
                "array",
            ],
            "created_at" => [
                "required",
            ],
        ];
    }
}
