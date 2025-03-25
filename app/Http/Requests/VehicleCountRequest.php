<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
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
                    "sometimes",
                    "date",
                    "before_or_equal:" . now()->toDateString(),
                    function ($attribute, $value, $fail) {
                        $existingEntries = \App\Models\VehicleCount::whereDate('date', $value)->get();

                        // Allow at most one AM and one PM entry
                        $amExists = $existingEntries->contains(function ($entry) {
                            return \Carbon\Carbon::parse($entry->time)->format('A') === 'AM';
                        });

                        $pmExists = $existingEntries->contains(function ($entry) {
                            return \Carbon\Carbon::parse($entry->time)->format('A') === 'PM';
                        });

                        $currentTime = \Carbon\Carbon::parse(request('time'))->format('A');

                        if (($amExists && $currentTime === 'AM') || ($pmExists && $currentTime === 'PM')) {
                            $fail("Only one AM and one PM entry are allowed per day.");
                        }
                    }
            ],
           "time" => ["sometimes", "required", "date_format:H:i:s", "before_or_equal:23:59:59"],
            "total_left" => "sometimes:required",
            "total_right" => "sometimes:required",
            "target_locations" => ["required","exists:target_locations,id"],
        ];
    }

    public function messages(){
        return [
            'time.date_format' => "The time field must match the format 00:00:00",
        ];
    }
}
