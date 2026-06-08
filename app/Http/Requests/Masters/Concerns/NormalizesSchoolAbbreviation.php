<?php

namespace App\Http\Requests\Masters\Concerns;

trait NormalizesSchoolAbbreviation
{
    protected function prepareForValidation(): void
    {
        if ($this->has('abbreviation')) {
            $this->merge([
                'abbreviation' => strtoupper(trim((string) $this->input('abbreviation'))),
            ]);
        }
    }
}
