<?php

namespace App\Http\Requests\Masters;

use App\Http\Requests\Masters\Concerns\NormalizesSchoolAbbreviation;
use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolRequest extends FormRequest
{
    use NormalizesSchoolAbbreviation;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'abbreviation' => ['required', 'string', 'max:15', 'regex:/^[A-Z0-9\-]+$/', 'unique:schools,abbreviation'],
            'country' => ['required', 'string', 'max:80'],
            'city' => ['required', 'string', 'max:80'],
            'director_id' => ['required', 'integer', 'exists:professors,id'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'abbreviation.regex' => 'La abreviatura solo puede contener letras mayúsculas, números y guiones.',
        ];
    }
}
