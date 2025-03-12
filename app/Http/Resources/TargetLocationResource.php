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
            'form_id' => $this->form_id,
            'form' => $this->whenLoaded('form', function () {
                return [
                    'id' => $this->form->id,
                    'title' => $this->form->title,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}
