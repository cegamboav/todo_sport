<?php

namespace App\Services\Masters;

use App\Enums\RefereeSpecialty;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Professor;
use App\Models\Referee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait SyncsMasterSystemAccess
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function stripSystemAccessFields(array &$data): void
    {
        unset(
            $data['create_system_access'],
            $data['access_username'],
            $data['access_password'],
            $data['update_system_password'],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function syncProfessorSystemAccess(Professor $professor, array $data): void
    {
        $this->syncSystemAccess($professor, $data, UserRole::Professor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function syncRefereeSystemAccess(Referee $referee, array $data): void
    {
        $specialtyValue = $data['specialty'] ?? $referee->specialty?->value ?? RefereeSpecialty::Corner->value;
        $role = RefereeSpecialty::from($specialtyValue)->systemRole();

        $this->syncSystemAccess($referee, $data, $role);

        if ($referee->user_id !== null) {
            $referee->user()->update(['role' => $role]);
        }
    }

    /**
     * @param  Professor|Referee  $master
     * @param  array<string, mixed>  $data
     */
    private function syncSystemAccess(Model $master, array $data, UserRole $role): void
    {
        if ($master->user_id !== null) {
            if (! empty($data['update_system_password']) && ! empty($data['access_password'])) {
                $master->user()->update([
                    'password' => $data['access_password'],
                ]);
            }

            return;
        }

        $user = User::query()->create([
            'username' => $data['access_username'],
            'password' => $data['access_password'],
            'role' => $role,
            'status' => UserStatus::Active,
        ]);

        $master->update(['user_id' => $user->id]);
    }
}
