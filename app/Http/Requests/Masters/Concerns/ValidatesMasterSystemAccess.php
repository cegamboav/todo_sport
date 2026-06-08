<?php

namespace App\Http\Requests\Masters\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesMasterSystemAccess
{
    /**
     * @return array<string, mixed>
     */
    protected function requiredSystemAccessRules(): array
    {
        return [
            'access_username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')],
            'access_password' => ['required', 'string', 'min:4', 'max:100'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function optionalSystemAccessUpdateRules(?int $existingUserId = null): array
    {
        $usernameUnique = Rule::unique('users', 'username');
        if ($existingUserId !== null) {
            $usernameUnique = $usernameUnique->ignore($existingUserId);
        }

        if ($existingUserId === null) {
            return $this->requiredSystemAccessRules();
        }

        return [
            'update_system_password' => ['sometimes', 'boolean'],
            'access_password' => [
                Rule::requiredIf(fn () => $this->boolean('update_system_password')),
                'nullable',
                'string',
                'min:4',
                'max:100',
            ],
        ];
    }
}
