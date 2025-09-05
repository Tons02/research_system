<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class VehicleCountRequest extends FormRequest
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
            "date" => [
                "required",
                "date",
                Rule::unique('vehicle_counts')
                    ->where(
                        fn($query) =>
                        $query->where('time_range', $this->input('time_range'))
                            ->where('time_period', $this->input('time_period'))
                            ->where('target_location_id', $this->input('target_location_id'))
                    )
                    ->ignore($this->route('vehicle_count'))
            ],
            'time_period' => [
                'required',
                'in:AM,PM'
            ],
            'time_range' => [
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

                    $timePeriod = request('time_period');

                    if (!isset($allowedRanges[$timePeriod]) || !in_array($value, $allowedRanges[$timePeriod])) {
                        $fail("The selected $attribute is invalid for the given time period.");
                    }
                }
            ],
            "total_left_private_car" => "required|integer",
            "total_left_truck" => "required|integer",
            "total_left_jeepney" => "required|integer",
            "total_left_bus" => "required|integer",
            "total_left_tricycle" => "required|integer",
            "total_left_bicycle" => "required|integer",
            "total_left_e_bike" => "required|integer",
            "total_right_private_car" => "required|integer",
            "total_right_truck" => "required|integer",
            "total_right_jeepney" => "required|integer",
            "total_right_bus" => "required|integer",
            "total_right_tricycle" => "required|integer",
            "total_right_bicycle" => "required|integer",
            "total_right_e_bike" => "required|integer",
            "target_location_id" => ["required", "sometimes", "exists:target_locations,id"],
            "created_at" => ["required", "sometimes"],
        ];
    }

    public function messages()
    {
        return [
            'time.date_format' => "The time field must match the format 00:00:00",
        ];
    }
}
