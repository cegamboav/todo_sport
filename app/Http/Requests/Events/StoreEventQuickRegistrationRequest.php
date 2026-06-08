<?php

namespace App\Http\Requests\Events;

use App\Models\Event;
use App\Models\EventCombo;
use App\Models\EventModality;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEventQuickRegistrationRequest extends FormRequest
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
            'event_modality_ids' => ['sometimes', 'array'],
            'event_modality_ids.*' => ['integer'],
            'event_combo_ids' => ['sometimes', 'array'],
            'event_combo_ids.*' => ['integer'],
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

            $modalityIds = array_values(array_unique(array_map('intval', $this->input('event_modality_ids', []))));
            if ($modalityIds !== []) {
                $validCount = EventModality::query()
                    ->where('event_id', $event->id)
                    ->where('enabled', true)
                    ->whereIn('id', $modalityIds)
                    ->count();

                if ($validCount !== count($modalityIds)) {
                    $validator->errors()->add('event_modality_ids', 'Una o más modalidades no son válidas para este evento.');
                }
            }

            $comboIds = array_values(array_unique(array_map('intval', $this->input('event_combo_ids', []))));
            if ($comboIds !== []) {
                $validCount = EventCombo::query()
                    ->where('event_id', $event->id)
                    ->where('enabled', true)
                    ->whereIn('id', $comboIds)
                    ->count();

                if ($validCount !== count($comboIds)) {
                    $validator->errors()->add('event_combo_ids', 'Uno o más combos no son válidos para este evento.');
                }
            }
        });
    }
}
