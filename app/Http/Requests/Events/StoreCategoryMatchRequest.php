<?php

namespace App\Http\Requests\Events;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryMatchRequest extends FormRequest
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
            'red_event_competitor_id' => ['nullable', 'integer', 'exists:event_competitors,id'],
            'blue_event_competitor_id' => ['nullable', 'integer', 'exists:event_competitors,id'],
            'stage_label' => ['nullable', 'string', 'max:40'],
        ];
    }
}
