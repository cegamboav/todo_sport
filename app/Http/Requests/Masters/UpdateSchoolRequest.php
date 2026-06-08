<?php

namespace App\Http\Requests\Masters;

use App\Http\Requests\Masters\Concerns\NormalizesSchoolAbbreviation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolRequest extends FormRequest
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
        $schoolId = $this->route('school')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'abbreviation' => [
                'sometimes',
                'required',
                'string',
                'max:15',
                'regex:/^[A-Z0-9\-]+$/',
                Rule::unique('schools', 'abbreviation')->ignore($schoolId),
            ],
            'country' => ['sometimes', 'required', 'string', 'max:80'],
            'city' => ['sometimes', 'required', 'string', 'max:80'],
            'director_id' => ['sometimes', 'required', 'integer', 'exists:professors,id'],
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
