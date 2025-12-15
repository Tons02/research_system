<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormsRequest extends FormRequest
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
            "title" => [
                "required",
                $this->route()->form
                    ? "unique:forms,title," . $this->route()->form
                    : "unique:forms,title",
            ],
            "description" => [
                "required",
                "sometimes"
            ],
            "sections" => [
                "required",
                "sometimes",
                "array",
            ],
            "sections.*" => [
                "distinct"
            ],
            'sections.*.section' => [
                'distinct'
            ],
        ];
    }

    public function messages()
    {
        return [
            'sections.*.section.distinct' => 'Section title must be unique. Duplicate section title found.',
        ];
    }
}
