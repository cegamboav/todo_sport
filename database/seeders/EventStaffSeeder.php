<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use App\Services\Auth\AdminAccessService;
use Illuminate\Database\Seeder;

class EventStaffSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('username', 'admin')->first();

        $event = Event::query()->where('name', 'Torneo Demo S1')->first();

        if ($event === null) {
            $event = Event::query()->create([
                'name' => 'Torneo Demo S1',
                'status' => EventStatus::Operational,
            ]);
        }

        $usernames = ['laura', 'mesa.demo', 'corner.demo'];

        $access = app(AdminAccessService::class);

        foreach ($usernames as $username) {
            $user = User::query()->where('username', $username)->first();
            if ($user !== null) {
                $access->assignUserToEvent($event, $user, $admin);
            }
        }
    }
}
