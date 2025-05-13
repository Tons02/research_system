<?php

namespace App\Http\Requests;

use App\Models\FootCount;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class FootCountRequest extends FormRequest
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
                "sometimes",
                "date",
                "before_or_equal:" . now()->toDateString(),
                function ($attribute, $value, $fail) {
                    $targetLocationId = request('target_location_id');

                    if (!$targetLocationId) {
                        $fail("The target location ID is required.");
                        return;
                    }

                    // Get existing entries from the pivot table
                    $existingEntries = DB::table('target_locations_foot_counts')
                        ->join('foot_counts', 'target_locations_foot_counts.foot_count_id', '=', 'foot_counts.id')
                        ->whereDate('foot_counts.date', $value)
                        ->where('target_locations_foot_counts.target_location_id', $targetLocationId)
                        ->select('foot_counts.time')
                        ->get();

                    // Allow at most one AM and one PM entry per target_location_id
                    $amExists = $existingEntries->contains(function ($entry) {
                        return Carbon::parse($entry->time)->format('A') === 'AM';
                    });

                    $pmExists = $existingEntries->contains(function ($entry) {
                        return Carbon::parse($entry->time)->format('A') === 'PM';
                    });

                    $currentTime = Carbon::parse(request('time'))->format('A');

                    if (($amExists && $currentTime === 'AM') || ($pmExists && $currentTime === 'PM')) {
                        $fail("Only one AM and one PM entry are allowed per day for this target location.");
                    }
                }
            ],
            "time" => ["sometimes", "required", "date_format:H:i:s", "before_or_equal:23:59:59"],
            "total_male" => "sometimes|required|integer",
            "total_female" => "sometimes|required|integer",
            "target_location_id" => ["required", "sometimes", "exists:target_locations,id"],
        ];
    }
}
