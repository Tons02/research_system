<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
            'old_password' => ['required', 'current_password'],
            'new_password' => [
                'required',
                'min:4',
                'different:old_password',
                'not_in:' . auth()->user()->username,
                'confirmed'
            ],
        ];
    }

    public function messages()
    {
        return [
            "new_password.not_in" => "The new password and username must be different",
        ];
    }
}
