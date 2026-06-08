<?php

namespace App\Http\Requests\Events;

use Illuminate\Foundation\Http\FormRequest;

class AssignCategoryCompetitorRequest extends FormRequest
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
            'event_competitor_id' => ['required', 'integer', 'exists:event_competitors,id'],
        ];
    }
}
