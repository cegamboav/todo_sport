<?php



namespace App\Http\Requests\Events;



use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;



class StoreRegistrationItemRequest extends FormRequest

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

            'item_type' => ['required', Rule::in(['modality', 'combo'])],

            'event_modality_id' => ['required_if:item_type,modality', 'nullable', 'integer', 'exists:event_modalities,id'],

            'event_combo_id' => ['required_if:item_type,combo', 'nullable', 'integer', 'exists:event_combos,id'],

            'is_billable' => ['sometimes', 'boolean'],

            'allow_duplicate_override' => ['sometimes', 'boolean'],

        ];

    }

}


