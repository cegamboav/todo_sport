<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\MasterStatus;
use App\Enums\RefereeSpecialty;
use App\Models\Competitor;
use App\Models\Grade;
use App\Models\Professor;
use App\Models\Referee;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;

class MasterSeeder extends Seeder
{
    public function run(): void
    {
        $iDan = Grade::query()->where('name', 'I Dan')->firstOrFail();
        $carlosUser = User::query()->where('username', 'carlos')->first();
        $mesaUser = User::query()->where('username', 'mesa.demo')->first();
        $cornerUser = User::query()->where('username', 'corner.demo')->first();

        $professor = Professor::query()->updateOrCreate(
            ['email' => 'director.demo@todo-sport.local'],
            [
                'first_name' => 'Carlos',
                'last_name' => 'Mendoza',
                'phone' => '+52 555 0100',
                'grade_id' => $iDan->id,
                'status' => MasterStatus::Active,
                'user_id' => $carlosUser?->id,
                'notes' => 'Director demo — portal profesores (sin acceso admin por defecto)',
            ],
        );

        School::query()->updateOrCreate(
            ['abbreviation' => 'DEMO'],
            [
                'name' => 'Escuela Demo Todo Sport',
                'country' => 'México',
                'city' => 'Ciudad de México',
                'director_id' => $professor->id,
                'status' => MasterStatus::Active,
                'notes' => 'Escuela demo S1',
            ],
        );

        $school = School::query()->where('abbreviation', 'DEMO')->firstOrFail();

        Competitor::query()->updateOrCreate(
            [
                'first_name' => 'Ana',
                'last_name' => 'López',
                'school_id' => $school->id,
            ],
            [
                'gender' => Gender::Female,
                'birth_date' => '2012-03-15',
                'grade_id' => Grade::query()->where('name', 'Verde')->value('id'),
                'weight_kg' => 42.50,
                'height_cm' => 148,
                'status' => MasterStatus::Active,
            ],
        );

        Competitor::query()->updateOrCreate(
            [
                'first_name' => 'Diego',
                'last_name' => 'Ruiz',
                'school_id' => $school->id,
            ],
            [
                'gender' => Gender::Male,
                'birth_date' => '2011-08-22',
                'grade_id' => Grade::query()->where('name', 'Azul')->value('id'),
                'weight_kg' => 48.00,
                'height_cm' => 155,
                'status' => MasterStatus::Active,
            ],
        );

        Referee::query()->updateOrCreate(
            ['email' => 'mesa.demo@todo-sport.local'],
            [
                'first_name' => 'Rosa',
                'last_name' => 'Mesa',
                'phone' => '+52 555 0300',
                'grade_id' => $iDan->id,
                'specialty' => RefereeSpecialty::Table,
                'status' => MasterStatus::Active,
                'user_id' => $mesaUser?->id,
                'notes' => 'Árbitro mesa demo',
            ],
        );

        Referee::query()->updateOrCreate(
            ['email' => 'corner.demo@todo-sport.local'],
            [
                'first_name' => 'Pedro',
                'last_name' => 'Esquina',
                'phone' => '+52 555 0400',
                'grade_id' => $iDan->id,
                'specialty' => RefereeSpecialty::Corner,
                'status' => MasterStatus::Active,
                'user_id' => $cornerUser?->id,
                'notes' => 'Juez esquina demo',
            ],
        );
    }
}
