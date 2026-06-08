<?php

namespace App\Http\Requests\Events;

use App\Enums\ThirdPlaceMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventSettingsRequest extends FormRequest
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
            'third_place_mode' => ['required', Rule::enum(ThirdPlaceMode::class)],
            'allow_team_forms' => ['required', 'boolean'],
            'bronze_mode' => ['nullable', 'string', 'max:40'],
        ];
    }
}
