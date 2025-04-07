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

            "form_id" => [
                "required",
                "sometimes"
                ,"exists:forms,id"],
            "response_limit" => ["required", "integer", "min:1"],
            "surveyors" => ["required","array"],
            "surveyors.*.id" => [
                "distinct",
                "required",
                "sometimes"
            ],
            'surveyors.*.user_id' => [
                'required',
                'distinct',
                'exists:users,id',
                Rule::unique('target_locations_users', 'user_id')
                    ->where(fn($q) => $q->where('is_done', 0))
                    ->ignore($this->route()?->target_location, 'target_location_id'),
            ],
        ];
    }

    public function messages()
    {
        return [
            'barangay_psgc_id.unique' => 'The selected barangay already exists with the same region and city/municipality.',
            'surveyors.*.id.distinct' => 'The id cannot be selected again.',
            'surveyors.*.id.exists' => 'The selected id is not existing in the system.',
            'surveyors.*.user_id.distinct' => 'The selected user cannot be selected again.',
            'surveyors.*.user_id.exists' => 'The selected user is not existing in the system.',
            'surveyors.*.user_id.unique' => 'The selected user is already assigned and must finish the current target location first.',
        ];
    }

    public function withValidator($validator)
{
    $validator->after(function ($validator) {
        $responseLimit = $this->input('response_limit'); // Get the main response_limit from the request
        $surveyors = $this->input('surveyors'); // Get the surveyors array

        // Calculate the total response_limit from the surveyors array
        $totalResponseLimit = collect($surveyors)->sum('response_limit');

        // Compare the total response_limit from surveyors with the response_limit in the request
        if ($totalResponseLimit !== (int) $responseLimit) {
            $validator->errors()->add('surveyors', 'The total of the response limits in the surveyors must match the response limit.');
        }
    });
}

}
