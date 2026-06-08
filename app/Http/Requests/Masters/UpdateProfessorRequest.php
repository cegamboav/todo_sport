<?php

namespace App\Http\Requests\Masters;

use App\Http\Requests\Masters\Concerns\ValidatesMasterSystemAccess;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfessorRequest extends FormRequest
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
        $userId = $this->route('professor')?->user_id;

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:80'],
            'last_name' => ['sometimes', 'required', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'grade_id' => ['nullable', 'integer', 'exists:grades,id'],
            'notes' => ['nullable', 'string'],
            ...$this->optionalSystemAccessUpdateRules($userId),
        ];
    }
}
