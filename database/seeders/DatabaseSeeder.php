<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'password' => Hash::make('admin123'),
                'role' => UserRole::Admin,
                'status' => UserStatus::Active,
            ],
        );

        // Staff operativo (sin professor) — acceso admin solo vía event_staff + evento open
        User::query()->updateOrCreate(
            ['username' => 'laura'],
            [
                'password' => Hash::make('laura2026'),
                'role' => UserRole::Staff,
                'status' => UserStatus::Active,
            ],
        );

        User::query()->updateOrCreate(
            ['username' => 'mesa.demo'],
            [
                'password' => Hash::make('mesa2026'),
                'role' => UserRole::Mesa,
                'status' => UserStatus::Active,
            ],
        );

        User::query()->updateOrCreate(
            ['username' => 'corner.demo'],
            [
                'password' => Hash::make('corner2026'),
                'role' => UserRole::Corner,
                'status' => UserStatus::Active,
            ],
        );

        User::query()->updateOrCreate(
            ['username' => 'carlos'],
            [
                'password' => Hash::make('carlos2026'),
                'role' => UserRole::Professor,
                'status' => UserStatus::Active,
            ],
        );

        $this->call([
            GradeSeeder::class,
            ModalitySeeder::class,
            MasterSeeder::class,
            S2aEventSeeder::class,
            EventStaffSeeder::class,
        ]);
    }
}
