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
            'target_location_id' => $this->target_location_id,
            'target_location' => [
                'id' => $this->target_location->id,
                'target_location' => implode(', ', array_filter([
                    $this->target_location->region ?? null,
                    $this->target_location->province ?? null,
                    $this->target_location->city_municipality ?? null,
                    $this->target_location->sub_municipality ?? null,
                    $this->target_location->barangay ?? null,
                ])),
            ],
            'name' => $this->name,
            'age' => $this->age,
            'gender' => $this->gender,
            'address' => $this->address,
            'contact_number' => $this->contact_number,
            'date' => $this->date,
            'family_size' => $this->family_size,
            'income_class' => $this->income_class,
            'sub_income_class' => $this->sub_income_class,
            'monthly_utility_expenses' => $this->monthly_utility_expenses,
            'sub_monthly_utility_expenses' => $this->sub_monthly_utility_expenses,
            'educational_attainment' => $this->educational_attainment,
            'employment_status' => $this->employment_status,
            'occupation' => $this->occupation,
            'structure_of_house' => $this->structure_of_house,
            'ownership_of_house' => $this->ownership_of_house,
            'house_rent' => $this->house_rent,
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
