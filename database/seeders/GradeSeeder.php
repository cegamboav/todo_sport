<?php

namespace Database\Seeders;

use App\Enums\GradeCategory;
use App\Models\Competitor;
use App\Models\Grade;
use App\Models\Professor;
use App\Models\Referee;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    /**
     * Catálogo ITF Taekwondo — kups + dans (persistente en grades).
     *
     * @var list<array{name: string, category: GradeCategory, sort_order: int}>
     */
    private array $catalog = [
        ['name' => 'Blanco', 'category' => GradeCategory::Kup, 'sort_order' => 1],
        ['name' => 'Blanco/Amarillo', 'category' => GradeCategory::Kup, 'sort_order' => 2],
        ['name' => 'Amarillo', 'category' => GradeCategory::Kup, 'sort_order' => 3],
        ['name' => 'Amarillo/Verde', 'category' => GradeCategory::Kup, 'sort_order' => 4],
        ['name' => 'Verde', 'category' => GradeCategory::Kup, 'sort_order' => 5],
        ['name' => 'Verde/Azul', 'category' => GradeCategory::Kup, 'sort_order' => 6],
        ['name' => 'Azul', 'category' => GradeCategory::Kup, 'sort_order' => 7],
        ['name' => 'Azul/Rojo', 'category' => GradeCategory::Kup, 'sort_order' => 8],
        ['name' => 'Rojo', 'category' => GradeCategory::Kup, 'sort_order' => 9],
        ['name' => 'Rojo/Negro', 'category' => GradeCategory::Kup, 'sort_order' => 10],
        ['name' => 'I Dan', 'category' => GradeCategory::Dan, 'sort_order' => 11],
        ['name' => 'II Dan', 'category' => GradeCategory::Dan, 'sort_order' => 12],
        ['name' => 'III Dan', 'category' => GradeCategory::Dan, 'sort_order' => 13],
        ['name' => 'IV Dan', 'category' => GradeCategory::Dan, 'sort_order' => 14],
        ['name' => 'V Dan', 'category' => GradeCategory::Dan, 'sort_order' => 15],
        ['name' => 'VI Dan', 'category' => GradeCategory::Dan, 'sort_order' => 16],
        ['name' => 'VII Dan', 'category' => GradeCategory::Dan, 'sort_order' => 17],
        ['name' => 'VIII Dan', 'category' => GradeCategory::Dan, 'sort_order' => 18],
        ['name' => 'IX Dan', 'category' => GradeCategory::Dan, 'sort_order' => 19],
    ];

    public function run(): void
    {
        foreach ($this->catalog as $grade) {
            Grade::query()->updateOrCreate(
                ['name' => $grade['name']],
                [
                    'category' => $grade['category'],
                    'sort_order' => $grade['sort_order'],
                    'is_active' => true,
                ],
            );
        }

        $this->retireLegacyGrades();
    }

    private function retireLegacyGrades(): void
    {
        $catalogNames = collect($this->catalog)->pluck('name')->all();
        $legacyNegro = Grade::query()->where('name', 'Negro')->first();
        $iDan = Grade::query()->where('name', 'I Dan')->first();

        if ($legacyNegro !== null && $iDan !== null) {
            Professor::query()->where('grade_id', $legacyNegro->id)->update(['grade_id' => $iDan->id]);
            Competitor::query()->where('grade_id', $legacyNegro->id)->update(['grade_id' => $iDan->id]);
            Referee::query()->where('grade_id', $legacyNegro->id)->update(['grade_id' => $iDan->id]);
        }

        Grade::query()
            ->whereNotIn('name', $catalogNames)
            ->update(['is_active' => false]);
    }
}
