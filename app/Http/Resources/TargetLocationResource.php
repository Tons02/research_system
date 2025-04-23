<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TargetLocationResource extends JsonResource
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
            'target_location' => implode(', ', array_filter([
                $this->region,
                $this->province,
                $this->city_municipality,
                $this->sub_municipality,
                $this->barangay
            ])),
            'region_psgc_id' => $this->region_psgc_id,
            'region' => $this->region,
            'province_psgc_id' => $this->province_psgc_id,
            'province' => $this->province,
            'city_municipality_psgc_id' => $this->city_municipality_psgc_id,
            'city_municipality' => $this->city_municipality,
            'sub_municipality_psgc_id' => $this->sub_municipality_psgc_id,
            'sub_municipality' => $this->sub_municipality,
            'barangay_psgc_id' => $this->barangay_psgc_id,
            'barangay' => $this->barangay,
            'street' => $this->street,
            'bound_box' => $this->bound_box,
            'response_limit' => $this->response_limit,
            'form_history_id' => $this->form_history_id,
            'form_history_id' => [
                'id' => $this->form_histories->id,
                'title' => $this->form_histories->title,
                'description' => $this->form_histories->description,
                'sections' => $this->form_histories->sections,
            ],

            'form' => $this->form,
            'form' => $this->form ? [
                'id' => $this->form->id,
                'title' => $this->form->title,
                'description' => $this->form->description,
                'sections' => $this->form->sections,
            ] : null,

            'surveyors' => $this->target_locations_users->map(function ($surveyor) {
                return [
                    'id' => $surveyor->id,
                    'first_name' => $surveyor->first_name,
                    'middle_name' => $surveyor->middle_name,
                    'last_name' => $surveyor->last_name,
                    'username' => $surveyor->username,
                    'mobile_number' => $surveyor->mobile_number,
                    'response_limit' => $surveyor->pivot->response_limit,
                ];
            }),
            'vehicle_counted_by' => [
                'id' => $this->vehicle_counted_by_user->id,
                'first_name' => $this->vehicle_counted_by_user->first_name,
                'middle_name' => $this->vehicle_counted_by_user->middle_name,
                'last_name' => $this->vehicle_counted_by_user->last_name,
                'username' => $this->vehicle_counted_by_user->username,
            ],
            'foot_counted_by' => [
                'id' => $this->foot_counted_by_user->id,
                'first_name' => $this->foot_counted_by_user->first_name,
                'middle_name' => $this->foot_counted_by_user->middle_name,
                'last_name' => $this->foot_counted_by_user->last_name,
                'username' => $this->foot_counted_by_user->username,
            ],
            'is_final' => $this->is_final,
            'is_done' => $this->is_done,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}
