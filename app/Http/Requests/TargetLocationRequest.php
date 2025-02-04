<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TargetLocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "target_location" => [
                "required",
                "string",
                $this->route()->target_location
                    ? "unique:target_locations,target_location," . $this->route()->target_location
                    : "unique:target_locations,target_location",
            ],
            "form_id" => ["required","exists:forms,id"],
        ];
    }
}
