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
            'target_location' => $this->target_location,
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
