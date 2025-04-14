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
                "before_or_equal:" . now()->toDateString(),
                function ($attribute, $value, $fail) {
                    $targetLocationId = request('target_location_id');
                    // dd($value); this return  2025-03-26 and it's existing on vehicle_counts.date but still accepting

                    if (!$targetLocationId) {
                        $fail("The target location ID is required.");
                        return;
                    }

                    $existingEntries = DB::table('target_locations_vehicle_counts')
                        ->join('vehicle_counts', 'target_locations_vehicle_counts.vehicle_count_id', '=', 'vehicle_counts.id')
                        ->whereDate('vehicle_counts.date', $value)
                        ->where('target_locations_vehicle_counts.target_location_id', $targetLocationId)
                        ->select('vehicle_counts.time')
                        ->get();

                    $amExists = $existingEntries->contains(fn ($entry) => Carbon::parse($entry->time)->format('A') === 'AM');
                    $pmExists = $existingEntries->contains(fn ($entry) => Carbon::parse($entry->time)->format('A') === 'PM');

                    $currentTime = request('time') ? Carbon::parse(request('time'))->format('A') : null;

                    if (!$currentTime) {
                        $fail("The time field is required.");
                        return;
                    }

                    if (($amExists && $currentTime === 'AM') || ($pmExists && $currentTime === 'PM')) {
                        $fail("Only one AM and one PM entry are allowed per day for this target location.");
                    }

                }
            ],
           "time" => [
                "required",
                "date_format:H:i:s",
                "regex:/^(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d$/"
           ],
            "total_left" => "required|integer",
            "total_right" => "required|integer",
            "target_location_id" => ["required", "exists:target_locations,id"],
        ];
    }

    public function messages(){
        return [
            'time.date_format' => "The time field must match the format 00:00:00",
        ];
    }
}
