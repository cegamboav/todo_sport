<?php

namespace App\Http\Requests\Masters;

use App\Enums\RefereeSpecialty;
use App\Http\Requests\Masters\Concerns\ValidatesMasterSystemAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRefereeRequest extends FormRequest
{
    use ValidatesMasterSystemAccess;

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
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'grade_id' => ['nullable', 'integer', 'exists:grades,id'],
            'specialty' => ['sometimes', Rule::enum(RefereeSpecialty::class)],
            'notes' => ['nullable', 'string'],
            ...$this->requiredSystemAccessRules(),
        ];
    }
}
