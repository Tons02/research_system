<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
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
            'id_prefix' => $this->id_prefix,
            'id_no' => $this->id_no,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'mobile_number' => $this->mobile_number,
            'role' => $this->role,
            'username' => $this->username,
            'role_id' => $this->role_id,
            'role' => $this->role,
            'target_locations' => optional($this->target_locations_users->first(), function ($surveyor) {
                return [
                    'target_location_id' => $surveyor->id,
                    'title' => $surveyor->title,
                    'form_history_id' => $surveyor->form_histories->id,
                    // 'form_history' => $surveyor->form_histories,
                    'target_location' => implode(', ', array_filter([
                        $surveyor->region,
                        $surveyor->province,
                        $surveyor->city_municipality,
                        $surveyor->sub_municipality,
                        $surveyor->barangay
                    ])),
                    'bound_box' => $surveyor->bound_box,
                    'total_response_limit' => $surveyor->response_limit,
                    'user_limit' => optional($surveyor->pivot)->response_limit,
                    'is_done' => optional($surveyor->pivot)->is_done,
                    'is_final' => $surveyor->is_final,
                ];
            }),
            'target_locations_history' => $this->target_locations_users_history->map(function ($surveyor) {
                return [
                    'target_location_id' => $surveyor->id,
                    'form_history' => $surveyor->form_history_id,
                    'target_location' => implode(', ', array_filter([
                        $surveyor->region,
                        $surveyor->province,
                        $surveyor->city_municipality,
                        $surveyor->sub_municipality,
                        $surveyor->barangay
                    ])),
                    'bound_box' => $surveyor->bound_box,
                    'total_response_limit' => $surveyor->response_limit,
                    'user_limit' => optional($surveyor->pivot)->response_limit,
                    'is_done' => optional($surveyor->pivot)->is_done,
                    'is_final' => $surveyor->is_final,
                ];
            }),
            'vehicle_counted_by' => optional($this->vehicle_counted->first(), function ($vehicle_counted) {
                return [
                    'target_location_id' => $vehicle_counted->id,
                    'form_history' => $vehicle_counted->form_history_id,
                    'target_location' => implode(', ', array_filter([
                        $vehicle_counted->region,
                        $vehicle_counted->province,
                        $vehicle_counted->city_municipality,
                        $vehicle_counted->sub_municipality,
                        $vehicle_counted->barangay,
                    ])),
                    'is_final' => $vehicle_counted->is_final,
                    'is_done' => $vehicle_counted->is_done,
                ];
            }),
            'foot_counted_by' => optional($this->foot_counted->first(), function ($foot_counted) {
                return [
                    'target_location_id' => $foot_counted->id,
                    'form_history' => $foot_counted->form_history_id,
                    'target_location' => implode(', ', array_filter([
                        $foot_counted->region,
                        $foot_counted->province,
                        $foot_counted->city_municipality,
                        $foot_counted->sub_municipality,
                        $foot_counted->barangay,
                    ])),
                    'is_final' => $foot_counted->is_final,
                    'is_done' => $foot_counted->is_done,
                ];
            }),
        ];
    }
}
