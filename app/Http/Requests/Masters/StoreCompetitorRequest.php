<?php

namespace App\Http\Requests\Masters;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompetitorRequest extends FormRequest
{
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
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'birth_date' => ['required', 'date', 'before:today'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'grade_id' => ['required', 'integer', 'exists:grades,id'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'height_cm' => ['nullable', 'integer', 'min:0', 'max:300'],
            'medical_notes' => ['nullable', 'string'],
        ];
    }
}
