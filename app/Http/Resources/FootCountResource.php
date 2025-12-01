<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FootCountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date,
            'time_range' => $this->time_range,
            'time_period' => $this->time_period,
            'total_left_male' => $this->total_left_male,
            'total_right_male' => $this->total_right_male,
            'total_male' => $this->total_male,
            'total_left_female' => $this->total_left_female,
            'total_right_female' => $this->total_right_female,
            'total_female' => $this->total_female,
            'grand_total' => $this->grand_total,
            'surveyor' => [
                'id' => $this->surveyor->id,
                'name' => $this->surveyor->first_name . ' ' . $this->surveyor->middle_name . ' ' . $this->surveyor->last_name,
            ],
            'target_locations' => [
                'id' => $this->target_locations->first()?->id,
                'title' => $this->target_locations->title,
                'target_locations' => implode(', ', array_filter([
                    $this->target_locations->first()?->region ?? null,
                    $this->target_locations->first()?->province ?? null,
                    $this->target_locations->first()?->city_municipality ?? null,
                    $this->target_locations->first()?->sub_municipality ?? null,
                    $this->target_locations->first()?->barangay ?? null,
                ])),
                'is_done' => $this->target_locations->first()?->is_done,
            ],
            'sync_at' => $this->sync_at,
            'created_at' => $this->created_at
        ];
    }
}
