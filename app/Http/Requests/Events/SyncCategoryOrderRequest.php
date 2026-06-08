<?php

namespace App\Http\Requests\Events;

use Illuminate\Foundation\Http\FormRequest;

class SyncCategoryOrderRequest extends FormRequest
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
            'rows.*.competition_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
