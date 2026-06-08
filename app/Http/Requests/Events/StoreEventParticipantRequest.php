<?php

namespace App\Http\Requests\Events;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventParticipantRequest extends FormRequest
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
            'competitor_id' => ['required', 'integer', 'exists:competitors,id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
