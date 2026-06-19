<?php

namespace App\Http\Requests\Events;

use Illuminate\Foundation\Http\FormRequest;

class GenerateCategoryBracketRequest extends FormRequest
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
            'confirmed' => ['sometimes', 'boolean'],
        ];
    }
}
