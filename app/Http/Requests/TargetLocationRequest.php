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
        $existingUserIds = DB::table('target_locations_users')
            ->where('target_location_id', $this->route('target_location')) // adjust route name if needed
            ->pluck('user_id')
            ->toArray();

        return [
            "title" => [
                "required",
                "sometimes"
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
            "surveyors" => ["required", "sometimes", "array"],
            "surveyors.*.user_id" => [
                "required",
                "distinct",
                "sometimes",
                "exists:users,id",
                function ($attribute, $value, $fail) use ($existingUserIds) {
                    // Skip if the user_id is already assigned (unchanged)
                    if (in_array($value, $existingUserIds)) {
                        return;
                    }

                    // Validate only if it's a new user
                    $exists = DB::table('target_locations_users')
                        ->where('user_id', $value)
                        ->where('is_done', false)
                        ->exists();

                    if ($exists) {
                        // i want to show the name of surveyor on the error message
                        $fail("The user in surveyors is already assigned to another incomplete target location.");
                    }
                },
            ],
            'vehicle_counted_by_user_id' => [
                'required',
                "sometimes",
                'exists:users,id',
                'different:foot_counted_by_user_id',
                Rule::notIn($this->input('surveyors.*.user_id')),
                function ($attribute, $value, $fail)  use ($existingUserIds) {
                    // Get the ID of the current record being updated
                    $currentTargetLocationId = $this->route('target_location');

                    // Get the current assigned user from the DB
                    $currentUserId = DB::table('target_locations')
                        ->where('id', $currentTargetLocationId)
                        ->value('vehicle_counted_by_user_id');

                    // If the value hasn't changed, skip this validation
                    if ($value == $currentUserId) {
                        return;
                    }

                    $exists = DB::table('target_locations')
                        ->where(function ($query) use ($value) {
                            $query->where('vehicle_counted_by_user_id', $value)
                                ->orWhere('foot_counted_by_user_id', $value);
                        })
                        ->where('is_done', false)
                        ->exists();


                    if ($exists) {
                        $fail("The user in vehicle count tracker is already assigned to a location that is not yet marked as done.");
                    }
                },
            ],
            'foot_counted_by_user_id' => [
                'required',
                'sometimes',
                'exists:users,id',
                'different:vehicle_counted_by_user_id',
                Rule::notIn($this->input('surveyors.*.user_id')),
                function ($attribute, $value, $fail) {
                    // Get the ID of the current record being updated
                    $currentTargetLocationId = $this->route('target_location');

                    // Get the current assigned user from the DB
                    $currentUserId = DB::table('target_locations')
                        ->where('id', $currentTargetLocationId)
                        ->value('foot_counted_by_user_id');

                    // If the value hasn't changed, skip this validation
                    if ($value == $currentUserId) {
                        return;
                    }

                    // Check if the same user is assigned to another active (not done) location
                    $exists = DB::table('target_locations')
                        ->where(function ($query) use ($value) {
                            $query->where('vehicle_counted_by_user_id', $value)
                                ->orWhere('foot_counted_by_user_id', $value);
                        })
                        ->where('is_done', false)
                        ->where('id', '!=', $currentTargetLocationId)
                        ->exists();

                    if ($exists) {
                        $fail("The user in foot count tracker is already assigned to a location that is not yet marked as done.");
                    }
                },
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
            'surveyors.*.id.distinct' => 'The id cannot be selected again.',
            'surveyors.*.id.exists' => 'The selected id is not existing in the system.',
            'surveyors.*.user_id.distinct' => 'The selected user cannot be selected again.',
            'surveyors.*.user_id.exists' => 'The selected user is not existing in the system.',
            'surveyors.*.user_id.unique' => 'The selected user is already assigned and must finish the current target location first.',
            'vehicle_counted_by_user_id.not_in' => 'The selected user on vehicle count is already selected as a surveyor.',
            'foot_counted_by_user_id.not_in' => 'The selected user is already selected as a surveyor.',

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
