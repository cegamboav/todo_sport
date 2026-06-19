<?php

namespace Tests\Unit;

use App\Enums\MatchType;
use App\Services\Competitive\BracketService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BracketServiceTest extends TestCase
{
    private BracketService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BracketService::class);
    }

    public function test_structure_for_two_competitors_is_single_final(): void
    {
        $structure = $this->service->buildStructure(2);

        $this->assertCount(1, $structure);
        $this->assertSame(MatchType::Final, $structure[0]['match_type']);
        $this->assertSame(1, $structure[0]['round_number']);
    }

    public function test_structure_for_three_competitors(): void
    {
        $structure = $this->service->buildStructure(3);

        $this->assertCount(3, $structure);
        $this->assertSame(MatchType::Bye, $structure[0]['match_type']);
        $this->assertSame(MatchType::Normal, $structure[1]['match_type']);
        $this->assertSame(MatchType::Final, $structure[2]['match_type']);
    }

    public function test_structure_for_four_competitors_includes_third_place_before_final(): void
    {
        $structure = $this->service->buildStructure(4);

        $this->assertCount(4, $structure);
        $this->assertSame(MatchType::ThirdPlace, $structure[2]['match_type']);
        $this->assertSame(MatchType::Final, $structure[3]['match_type']);
        $this->assertSame(3, $structure[2]['match_number']);
        $this->assertSame(4, $structure[3]['match_number']);
    }

    public function test_structure_for_eight_competitors_has_third_place_before_final(): void
    {
        $structure = $this->service->buildStructure(8);

        $thirdPlace = collect($structure)->firstWhere('match_type', MatchType::ThirdPlace);
        $final = collect($structure)->firstWhere('match_type', MatchType::Final);

        $this->assertNotNull($thirdPlace);
        $this->assertNotNull($final);
        $this->assertLessThan($final['match_number'], $thirdPlace['match_number']);
    }

    public function test_structure_for_eight_competitors_has_full_tree(): void
    {
        $structure = $this->service->buildStructure(8);

        $this->assertCount(8, $structure);
        $this->assertSame(4, collect($structure)->where('round_number', 1)->count());
        $this->assertSame(2, collect($structure)->where('round_number', 2)->count());
        $this->assertSame(1, collect($structure)->where('match_type', MatchType::Final)->count());
        $this->assertSame(1, collect($structure)->where('match_type', MatchType::ThirdPlace)->count());
    }

    public function test_structure_rejects_more_than_sixteen_competitors(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->buildStructure(17);
    }

    public function test_automatic_seeding_for_three_competitors(): void
    {
        $structure = $this->service->buildStructure(3);
        $competitors = collect([
            (object) ['id' => 10, 'school_id' => 1, 'sort_order' => 1],
            (object) ['id' => 20, 'school_id' => 2, 'sort_order' => 2],
            (object) ['id' => 30, 'school_id' => 3, 'sort_order' => 3],
        ]);

        $seeded = $this->service->seedAutomaticParticipants($competitors, $structure);

        $this->assertSame(10, $seeded[0]['red']);
        $this->assertNull($seeded[0]['blue']);
        $this->assertSame(20, $seeded[1]['red']);
        $this->assertSame(30, $seeded[1]['blue']);
        $this->assertNull($seeded[2]['red']);
        $this->assertNull($seeded[2]['blue']);
    }

    public function test_propagate_structure_byes_advances_winner_to_next_round(): void
    {
        $structure = $this->service->buildStructure(5);
        $competitors = collect([
            (object) ['id' => 101, 'school_id' => 1, 'sort_order' => 1],
            (object) ['id' => 102, 'school_id' => 2, 'sort_order' => 2],
            (object) ['id' => 103, 'school_id' => 3, 'sort_order' => 3],
            (object) ['id' => 104, 'school_id' => 4, 'sort_order' => 4],
            (object) ['id' => 105, 'school_id' => 5, 'sort_order' => 5],
        ]);

        $seeded = $this->service->seedAutomaticParticipants($competitors, $structure);
        $this->service->propagateStructureByes($seeded);

        $roundOne = collect($seeded)->where('round_number', 1)->sortBy('match_number')->values();
        $firstBye = $roundOne->first(fn (array $match) => $match['match_type'] === MatchType::Bye);
        $this->assertNotNull($firstBye);

        $lauraId = $firstBye['red'] ?? $firstBye['blue'];
        $this->assertNotNull($lauraId);

        $m5 = collect($seeded)->firstWhere('match_number', 5);
        $this->assertNotNull($m5);
        $this->assertTrue(
            $m5['red'] === $lauraId || $m5['blue'] === $lauraId,
            'El ganador del bye en M1 debe aparecer inmediatamente en M5.',
        );
    }

    public function test_propagate_structure_byes_for_three_six_and_seven_competitors(): void
    {
        foreach ([3, 6, 7] as $count) {
            $structure = $this->service->buildStructure($count);
            $competitors = collect(range(1, $count))->map(
                fn (int $i) => (object) ['id' => $i * 10, 'school_id' => $i, 'sort_order' => $i],
            );

            $seeded = $this->service->seedAutomaticParticipants($competitors, $structure);
            $this->service->propagateStructureByes($seeded);

            $roundOneByes = collect($seeded)
                ->where('round_number', 1)
                ->filter(fn (array $match) => $match['match_type'] === MatchType::Bye
                    && (($match['red'] ?? $match['blue']) !== null));

            foreach ($roundOneByes as $byeMatch) {
                $winnerId = $byeMatch['red'] ?? $byeMatch['blue'];
                $propagated = collect($seeded)->contains(
                    fn (array $match) => $match['round_number'] > 1
                        && ($match['red'] === $winnerId || $match['blue'] === $winnerId),
                );

                $this->assertTrue(
                    $propagated,
                    "Competidor {$winnerId} del bye M{$byeMatch['match_number']} ({$count} comp.) debe propagarse.",
                );
            }
        }
    }

    public function test_automatic_seeding_marks_bye_matches_for_partial_bracket(): void
    {
        $structure = $this->service->buildStructure(5);
        $competitors = collect([
            (object) ['id' => 1, 'school_id' => 1, 'sort_order' => 1],
            (object) ['id' => 2, 'school_id' => 2, 'sort_order' => 2],
            (object) ['id' => 3, 'school_id' => 3, 'sort_order' => 3],
            (object) ['id' => 4, 'school_id' => 4, 'sort_order' => 4],
            (object) ['id' => 5, 'school_id' => 5, 'sort_order' => 5],
        ]);

        $seeded = $this->service->seedAutomaticParticipants($competitors, $structure);
        $roundOne = collect($seeded)->where('round_number', 1);

        foreach ($roundOne as $match) {
            $hasBoth = $match['red'] !== null && $match['blue'] !== null;
            $hasOne = ($match['red'] !== null) xor ($match['blue'] !== null);
            $hasNone = $match['red'] === null && $match['blue'] === null;

            if ($hasBoth) {
                $this->assertSame(MatchType::Normal, $match['match_type']);
            } else {
                $this->assertSame(MatchType::Bye, $match['match_type']);
                $this->assertTrue($hasOne || $hasNone);
            }
        }
    }
}
