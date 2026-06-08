<?php



namespace App\Http\Requests\Events;



use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;



class StoreModalityRequest extends FormRequest

{

    public function authorize(): bool

    {

        return true;

    }



    protected function prepareForValidation(): void

    {

        if ($this->has('code') && ! filled($this->input('code'))) {

            $this->merge(['code' => null]);

        }

    }



    /**

     * @return array<string, mixed>

     */

    public function rules(): array

    {

        $modalityId = $this->route('modality')?->id;



        return [

            'code' => [

                'nullable',

                'string',

                'max:40',

                'regex:/^[a-z0-9_]+$/',

                Rule::unique('modalities', 'code')->ignore($modalityId),

            ],

            'name' => ['required', 'string', 'max:120'],

            'description' => ['nullable', 'string'],

            'is_active' => ['sometimes', 'boolean'],

        ];

    }

}

