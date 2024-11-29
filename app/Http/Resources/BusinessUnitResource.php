<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessUnitResource extends JsonResource
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
            'sync_id' => $this->sync_id,
            'business_unit_code' => $this->business_unit_code,
            'business_unit_name' => $this->business_unit_name,
            'company' => [
                'sync_id' => $this->company->sync_id,
                'company_code' => $this->company->company_code,
                'company_name' => $this->company->company_name
            ],
            'created_at' => $this->created_at
        ];
    }
}
