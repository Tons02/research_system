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
            'time' => $this->time,
            'time_period' => $this->time_period,
            'total_male' => $this->total_male,
            'total_female' => $this->total_female,
            'grand_total' => $this->grand_total,
            'surveyor' => [
                'id' => $this->surveyor->id,
                'name' => $this->surveyor->first_name . ' ' . $this->surveyor->middle_name . ' ' . $this->surveyor->last_name,
            ],
            'target_location' => $this->target_locations,
            'target_location' => [
                'id' => $this->target_locations->first()?->id,
                'target_locations' => implode(', ', array_filter([
                    $this->target_locations->first()?->region ?? null,
                    $this->target_locations->first()?->province ?? null,
                    $this->target_locations->first()?->city_municipality ?? null,
                    $this->target_locations->first()?->sub_municipality ?? null,
                    $this->target_locations->first()?->barangay ?? null,
                ])),
            ],
            'created_at' => $this->created_at
        ];
    }
}
