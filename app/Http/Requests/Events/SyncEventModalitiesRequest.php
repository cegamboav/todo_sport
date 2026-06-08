<?php

namespace App\Http\Requests\Events;

use Illuminate\Foundation\Http\FormRequest;

class SyncEventModalitiesRequest extends FormRequest
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
            'modalities' => ['required', 'array'],
            'modalities.*.modality_id' => ['required', 'integer', 'exists:modalities,id'],
            'modalities.*.enabled' => ['required', 'boolean'],
            'modalities.*.price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }
}
