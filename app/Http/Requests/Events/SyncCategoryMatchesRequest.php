<?php

namespace App\Http\Requests\Events;

use Illuminate\Foundation\Http\FormRequest;

class SyncCategoryMatchesRequest extends FormRequest
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
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.id' => ['required', 'integer'],
            'rows.*.red_event_competitor_id' => ['nullable', 'integer', 'exists:event_competitors,id'],
            'rows.*.blue_event_competitor_id' => ['nullable', 'integer', 'exists:event_competitors,id'],
        ];
    }
}
