<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PendingUserRequest extends FormRequest
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
            'id_prefix' => ['required', 'string'],
            'id_no' => [
                'required',
                'string',
            ],
            'first_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'last_name' => 'required|string',
            'suffix' => 'nullable|string',
            'username' => [
                'required',
                'string',
                Rule::unique('users', 'username')->where(function ($query) {
                    return $query->where(function ($q) {
                        $q->where('id_prefix', '!=', $this->id_prefix)
                            ->orWhere('id_no', '!=', $this->id_no);
                    });
                }),
            ],
        ];
    }
}
