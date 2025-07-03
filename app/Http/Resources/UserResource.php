<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'gender' => $this->gender,
            'one_charging' => $this->one_charging,
            'username' => $this->username,
            'role' => [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'access_permission' => $this->role->access_permission
            ],
            'target_locations' => $this->target_locations_users->map(function ($surveyor) {
                return [
                    'id' => $surveyor->id,
                    'target_location' => implode(', ', array_filter([
                        $surveyor->region,
                        $surveyor->province,
                        $surveyor->city_municipality,
                        $surveyor->sub_municipality,
                        $surveyor->barangay
                    ])),
                    'bound_box' => $surveyor->bound_box,
                    'total_response_limit' => $surveyor->response_limit,
                    'user_limit' => $surveyor->pivot->response_limit,
                    'is_done' => $surveyor->pivot->is_done,
                ];
            }),
            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}
