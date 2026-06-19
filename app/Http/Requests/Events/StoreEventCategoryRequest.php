<?php

namespace App\Http\Requests\Events;

use App\Enums\CategoryGenderScope;
use App\Models\Event;
use App\Models\EventModality;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEventCategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'modality_id' => ['required', 'integer', 'exists:modalities,id'],
            'gender_scope' => ['required', Rule::enum(CategoryGenderScope::class)],
            'ring_id' => ['nullable', 'integer', 'exists:rings,id'],
            'competition_order' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'reference_age' => ['nullable', 'string', 'max:100'],
            'reference_grade' => ['nullable', 'string', 'max:100'],
            'reference_weight' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Event $event */
            $event = $this->route('event');
            $isCreate = $this->route('category') === null;

            $modalityId = (int) $this->input('modality_id');
            $enabled = EventModality::query()
                ->where('event_id', $event->id)
                ->where('modality_id', $modalityId)
                ->where('enabled', true)
                ->exists();

            if (! $enabled) {
                $validator->errors()->add('modality_id', 'La modalidad no está habilitada en este evento.');
            }

            $ringId = $this->input('ring_id');
            if ($ringId !== null && $ringId !== '') {
                $validator->errors()->add('ring_id', 'Ring se asigna desde otro workspace.');
            }

            if ($isCreate && ($this->input('competition_order') !== null && $this->input('competition_order') !== '')) {
                $validator->errors()->add('competition_order', 'El orden de competencia se define después.');
            }
        });
    }
}
