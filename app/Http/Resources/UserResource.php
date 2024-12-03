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
            'company' => [
                'sync_id' => $this->company->sync_id,
                'name' => $this->company->company_name
            ],
            'business_unit' => [
                'sync_id' => $this->business_unit->sync_id,
                'name' => $this->business_unit->business_unit_name
            ],
            'department' => [
                'sync_id' => $this->department->sync_id,
                'name' => $this->department->department_name
            ],
            'unit' => [
                'sync_id' => $this->unit->sync_id,
                'name' => $this->unit->unit_name
            ],
            'sub_unit' => [
                'sync_id' => $this->sub_unit->sync_id,
                'name' => $this->sub_unit->sub_unit_name
            ],
            'location' => [
                'sync_id' => $this->location->sync_id,
                'name' => $this->location->location_name
            ],
            'username' => $this->username,
            'role' => [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'access_permission' => $this->role->access_permission
            ],
            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}
