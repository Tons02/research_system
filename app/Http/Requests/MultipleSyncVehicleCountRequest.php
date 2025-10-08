<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MultipleSyncVehicleCountRequest extends FormRequest
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
            'vehicle_counts' => 'required|array|min:1',
            'vehicle_counts.*.date' => 'required|date',
            'vehicle_counts.*.time_period' => 'required|in:AM,PM',
            'vehicle_counts.*.time_range' => [
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

                    // Extract index from attribute path (e.g., "vehicle_counts.0.time_range")
                    preg_match('/vehicle_counts\.(\d+)\.time_range/', $attribute, $matches);
                    $index = $matches[1] ?? null;

                    if ($index !== null) {
                        $timePeriod = request("vehicle_counts.{$index}.time_period");

                        if (!isset($allowedRanges[$timePeriod]) || !in_array($value, $allowedRanges[$timePeriod])) {
                            $fail("The selected time range is invalid for the given time period.");
                        }
                    }
                }
            ],
            'vehicle_counts.*.total_left_private_car' => 'required|integer|min:0',
            'vehicle_counts.*.total_left_truck' => 'required|integer|min:0',
            'vehicle_counts.*.total_left_jeepney' => 'required|integer|min:0',
            'vehicle_counts.*.total_left_bus' => 'required|integer|min:0',
            'vehicle_counts.*.total_left_tricycle' => 'required|integer|min:0',
            'vehicle_counts.*.total_left_bicycle' => 'required|integer|min:0',
            'vehicle_counts.*.total_left_e_bike' => 'required|integer|min:0',
            'vehicle_counts.*.total_right_private_car' => 'required|integer|min:0',
            'vehicle_counts.*.total_right_truck' => 'required|integer|min:0',
            'vehicle_counts.*.total_right_jeepney' => 'required|integer|min:0',
            'vehicle_counts.*.total_right_bus' => 'required|integer|min:0',
            'vehicle_counts.*.total_right_tricycle' => 'required|integer|min:0',
            'vehicle_counts.*.total_right_bicycle' => 'required|integer|min:0',
            'vehicle_counts.*.total_right_e_bike' => 'required|integer|min:0',
            'vehicle_counts.*.target_location_id' => 'required|exists:target_locations,id',
            'vehicle_counts.*.created_at' => 'required|date',
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
            'vehicle_counts.required' => 'Vehicle counts data is required.',
            'vehicle_counts.array' => 'Vehicle counts must be an array.',
            'vehicle_counts.min' => 'At least one vehicle count entry is required.',
            'vehicle_counts.*.date.required' => 'Date is required for each entry.',
            'vehicle_counts.*.date.date' => 'Date must be a valid date format.',
            'vehicle_counts.*.target_location_id.required' => 'Target location is required for each entry.',
            'vehicle_counts.*.target_location_id.exists' => 'The selected target location does not exist.',
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
            $vehicleCounts = $this->input('vehicle_counts', []);

            // Check for duplicate entries within the same request
            $seen = [];
            foreach ($vehicleCounts as $index => $count) {
                $key = sprintf(
                    '%s_%s_%s_%s',
                    $count['target_location_id'] ?? '',
                    $count['date'] ?? '',
                    $count['time_range'] ?? '',
                    $count['time_period'] ?? ''
                );

                if (isset($seen[$key])) {
                    $validator->errors()->add(
                        "vehicle_counts.{$index}",
                        "Duplicate entry detected: same target location, date, time range, and time period as entry."
                    );
                } else {
                    $seen[$key] = $index + 1;
                }
            }
        });
    }
}
