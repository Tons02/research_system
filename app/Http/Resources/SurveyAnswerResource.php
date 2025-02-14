<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyAnswerResource extends JsonResource
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
            'target_location' => [
                'id' => $this->target_location->id,
                'target_location' => $this->target_location->target_location,
            ],
            'name' => $this->name,
            'age' => $this->age,
            'gender' => $this->gender,
            'address' => $this->address,
            'date' => $this->date,
            'questionnaire_answer' => $this->questionnaire_answer,
            'surveyor' => [
                'id' => $this->user->id,
                'name' => $this->user->first_name . ' ' . $this->user->middle_name . ' ' . $this->user->last_name,
            ],
            'submit_date' => $this->submit_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}
