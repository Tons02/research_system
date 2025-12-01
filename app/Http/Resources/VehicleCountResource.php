<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleCountResource extends JsonResource
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
            'total_left_private_car' => $this->total_left_private_car,
            'total_left_truck' => $this->total_left_truck,
            'total_left_jeepney' => $this->total_left_jeepney,
            'total_left_bus' => $this->total_left_bus,
            'total_left_motorcycle' => $this->total_left_motorcycle,
            'total_left_tricycle' => $this->total_left_tricycle,
            'total_left_bicycle' => $this->total_left_bicycle,
            'total_left_e_bike' => $this->total_left_e_bike,
            'total_left' => $this->total_left,
            'total_right_private_car' => $this->total_right_private_car,
            'total_right_truck' => $this->total_right_truck,
            'total_right_jeepney' => $this->total_right_jeepney,
            'total_right_bus' => $this->total_right_bus,
            'total_right_motorcycle' => $this->total_right_motorcycle,
            'total_right_tricycle' => $this->total_right_tricycle,
            'total_right_bicycle' => $this->total_right_bicycle,
            'total_right_e_bike' => $this->total_right_e_bike,
            'total_right' => $this->total_right,
            'grand_total' => $this->grand_total,
            'surveyor' => [
                'id' => $this->surveyor->id,
                'name' => $this->surveyor->first_name . ' ' . $this->surveyor->middle_name . ' ' . $this->surveyor->last_name,
            ],
            'target_location' => $this->target_locations,
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
