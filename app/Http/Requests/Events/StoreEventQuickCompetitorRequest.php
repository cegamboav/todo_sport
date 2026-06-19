<?php

namespace App\Http\Requests\Events;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventQuickCompetitorRequest extends FormRequest
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
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'weight_kg' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'height_cm' => ['required', 'integer', 'min:0', 'max:300'],
            'grade_id' => ['nullable', 'integer', 'exists:grades,id'],
        ];
    }
}
