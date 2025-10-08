<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class MultipleSyncFootCountRequest extends FormRequest
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
            'foot_counts' => 'required|array|min:1',
            'foot_counts.*.date' => 'required|date',
            'foot_counts.*.time_period' => 'required|in:AM,PM',
            'foot_counts.*.time_range' => [
                'required',
                function ($attribute, $value, $fail) {
                    $allowedRanges = [
                        'AM' => [
                            '8:00 - 9:00',
                            '9:00 - 10:00',
                            '10:00 - 11:00',
                            '11:00 - 12:00',
                        ],
                        'PM' => [
                            '1:00 - 2:00',
                            '2:00 - 3:00',
                            '3:00 - 4:00',
                            '4:00 - 5:00',
                        ],
                    ];

                    // Extract index from attribute path (e.g., "foot_counts.0.time_range")
                    preg_match('/foot_counts\.(\d+)\.time_range/', $attribute, $matches);
                    $index = $matches[1] ?? null;

                    if ($index !== null) {
                        $timePeriod = request("foot_counts.{$index}.time_period");

                        if (!isset($allowedRanges[$timePeriod]) || !in_array($value, $allowedRanges[$timePeriod])) {
                            $fail("The selected time range is invalid for the given time period.");
                        }
                    }
                }
            ],
            'foot_counts.*.total_left_male' => 'required|integer|min:0',
            'foot_counts.*.total_left_female' => 'required|integer|min:0',
            'foot_counts.*.total_right_male' => 'required|integer|min:0',
            'foot_counts.*.total_right_female' => 'required|integer|min:0',
            'foot_counts.*.target_location_id' => 'required|exists:target_locations,id',
            'foot_counts.*.created_at' => 'required|date',
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
            'foot_counts.required' => 'Foot counts data is required.',
            'foot_counts.array' => 'Foot counts must be an array.',
            'foot_counts.min' => 'At least one foot count entry is required.',
            'foot_counts.*.date.required' => 'Date is required for each entry.',
            'foot_counts.*.date.date' => 'Date must be a valid date format.',
            'foot_counts.*.time_period.required' => 'Time period is required for each entry.',
            'foot_counts.*.time_period.in' => 'Time period must be either AM or PM.',
            'foot_counts.*.time_range.required' => 'Time range is required for each entry.',
            'foot_counts.*.total_left_male.required' => 'Total left male is required for each entry.',
            'foot_counts.*.total_left_male.integer' => 'Total left male must be an integer.',
            'foot_counts.*.total_left_male.min' => 'Total left male must be at least 0.',
            'foot_counts.*.total_left_female.required' => 'Total left female is required for each entry.',
            'foot_counts.*.total_left_female.integer' => 'Total left female must be an integer.',
            'foot_counts.*.total_left_female.min' => 'Total left female must be at least 0.',
            'foot_counts.*.total_right_male.required' => 'Total right male is required for each entry.',
            'foot_counts.*.total_right_male.integer' => 'Total right male must be an integer.',
            'foot_counts.*.total_right_male.min' => 'Total right male must be at least 0.',
            'foot_counts.*.total_right_female.required' => 'Total right female is required for each entry.',
            'foot_counts.*.total_right_female.integer' => 'Total right female must be an integer.',
            'foot_counts.*.total_right_female.min' => 'Total right female must be at least 0.',
            'foot_counts.*.target_location_id.required' => 'Target location is required for each entry.',
            'foot_counts.*.target_location_id.exists' => 'The selected target location does not exist.',
            'foot_counts.*.created_at.required' => 'Created at timestamp is required for each entry.',
            'foot_counts.*.created_at.date' => 'Created at must be a valid date format.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $footCounts = $this->input('foot_counts', []);

            // Check for duplicate entries within the same request
            $seen = [];
            foreach ($footCounts as $index => $count) {
                $key = sprintf(
                    '%s_%s_%s_%s',
                    $count['target_location_id'] ?? '',
                    $count['date'] ?? '',
                    $count['time_range'] ?? '',
                    $count['time_period'] ?? ''
                );

                if (isset($seen[$key])) {
                    $validator->errors()->add(
                        "foot_counts.{$index}",
                        "Duplicate entry detected: same target location, date, time range, and time period as entry #{$seen[$key]}."
                    );
                } else {
                    $seen[$key] = $index + 1;
                }
            }

            // Check for existing records in database
            foreach ($footCounts as $index => $count) {
                if (
                    !isset($count['target_location_id']) ||
                    !isset($count['date']) ||
                    !isset($count['time_range']) ||
                    !isset($count['time_period'])
                ) {
                    continue; // Skip if required fields are missing (will be caught by other validations)
                }

                $exists = DB::table('foot_counts')
                    ->where('target_location_id', $count['target_location_id'])
                    ->where('date', $count['date'])
                    ->where('time_range', $count['time_range'])
                    ->where('time_period', $count['time_period'])
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        "foot_counts.{$index}",
                        "This foot count entry already exists for the given date, time range, and location."
                    );
                }
            }
        });
    }
}
