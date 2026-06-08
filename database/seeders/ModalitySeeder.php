<?php

namespace Database\Seeders;

use App\Models\Modality;
use Illuminate\Database\Seeder;

class ModalitySeeder extends Seeder
{
    public function run(): void
    {
        $modalities = [
            ['code' => 'sparring', 'name' => 'Combate', 'sort_order' => 10],
            ['code' => 'patterns', 'name' => 'Formas', 'sort_order' => 20],
            ['code' => 'team_patterns', 'name' => 'Formas por equipo', 'sort_order' => 30],
            ['code' => 'team_sparring', 'name' => 'Combate por equipo', 'sort_order' => 40],
            ['code' => 'special_technique', 'name' => 'Técnicas especiales', 'sort_order' => 50],
            ['code' => 'power_breaking', 'name' => 'Rotura', 'sort_order' => 60],
        ];

        foreach ($modalities as $row) {
            Modality::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
