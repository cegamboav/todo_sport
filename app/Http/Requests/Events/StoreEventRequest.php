<?php

namespace App\Http\Requests\Events;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'event_date' => ['nullable', 'date'],
            'venue' => ['nullable', 'string', 'max:200'],
            'host_school_id' => ['nullable', 'integer', 'exists:schools,id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
