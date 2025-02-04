<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
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
            "name" => [
                "required",
                "string",
                $this->route()->role
                    ? "unique:roles,name," . $this->route()->role
                    : "unique:roles,name",
            ],
            "access_permission" => [
                "required",
                "array",
                "distinct",
                "min:1", // Ensure at least one permission is selected
                function ($attribute, $value, $fail) {
                    $allowedValues = [
                        "masterlist",
                        "masterlist:companies:sync",
                        "masterlist:business-units:sync",
                        "masterlist:departments:sync",
                        "masterlist:units:sync",
                        "masterlist:sub-units:sync",
                        "masterlist:locations:sync",
                        "user-management",
                        "user-management:user-accounts:crud",
                        "user-management:role-management:crud",
                        "form-management:crud",
                        "target-locations:crud"
                        
                    ];

                    foreach ($value as $permission) {
                        if (!in_array($permission, $allowedValues)) {
                            $fail("The {$attribute} contains an invalid value: {$permission}.");
                        }
                    }
                }
            ],
            "access_permission.*" => [
                "distinct",
                "required"
            ]
        ];
    }
}
