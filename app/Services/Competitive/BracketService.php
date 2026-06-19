<?php

namespace App\Services\Competitive;

use App\Enums\AuditSeverity;
use App\Enums\EventCategoryStatus;
use App\Enums\MatchStatus;
use App\Enums\MatchType;
use App\Models\CategoryMatch;
use App\Models\EventCategory;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BracketService
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function generateAutomatic(EventCategory $category, User $actor, bool $confirmed = false): void
    {
        DB::transaction(function () use ($category, $actor, $confirmed) {
            $this->assertCanGenerate($category);
            $this->assertConfirmedIfMatchesExist($category, $confirmed);

            $competitors = $this->loadCompetitors($category);
            $structure = $this->buildStructure($competitors->count());
            $seeded = $this->seedAutomaticParticipants($competitors, $structure);
            $this->propagateStructureByes($seeded);

            $this->wipeMatches($category);
            $this->persistMatches($category, $seeded, $actor, 'bracket.generated_auto');
            $this->transitionToBracketPending($category);
        });
    }

    public function generateManual(EventCategory $category, User $actor, bool $confirmed = false): void
    {
        DB::transaction(function () use ($category, $actor, $confirmed) {
            $this->assertCanGenerate($category);
            $this->assertConfirmedIfMatchesExist($category, $confirmed);

            $count = $this->loadCompetitors($category)->count();
            $structure = $this->buildStructure($count);

            foreach ($structure as &$row) {
                $row['red'] = null;
                $row['blue'] = null;
            }
            unset($row);

            $this->wipeMatches($category);
            $this->persistMatches($category, $structure, $actor, 'bracket.generated_manual');
            $this->transitionToBracketPending($category);
        });
    }

    /**
     * @return list<array{
     *   match_number: int,
     *   round_number: int,
     *   match_type: MatchType,
     *   stage_label: string,
     *   red: int|null,
     *   blue: int|null
     * }>
     */
    public function buildStructure(int $competitorCount): array
    {
        if ($competitorCount < 2) {
            throw ValidationException::withMessages([
                'category' => 'Se necesitan al menos 2 competidores para generar la llave.',
            ]);
        }

        if ($competitorCount > 16) {
            throw ValidationException::withMessages([
                'category' => 'La generación automática soporta hasta 16 competidores.',
            ]);
        }

        return match (true) {
            $competitorCount === 2 => $this->structureForTwo(),
            $competitorCount === 3 => $this->structureForThree(),
            default => $this->structureForBracket($competitorCount),
        };
    }

    /**
     * @param  Collection<int, object{id: int, school_id: int|null, sort_order: int}>  $competitors
     * @param  list<array{match_number: int, round_number: int, match_type: MatchType, stage_label: string, red: int|null, blue: int|null}>  $structure
     * @return list<array{match_number: int, round_number: int, match_type: MatchType, stage_label: string, red: int|null, blue: int|null}>
     */
    public function seedAutomaticParticipants(Collection $competitors, array $structure): array
    {
        $ids = $competitors->pluck('id')->values()->all();

        if (count($ids) === 2) {
            $structure[0]['red'] = $ids[0];
            $structure[0]['blue'] = $ids[1];

            return $structure;
        }

        if (count($ids) === 3) {
            $structure[0]['red'] = $ids[0];
            $structure[0]['blue'] = null;
            $structure[1]['red'] = $ids[1];
            $structure[1]['blue'] = $ids[2];

            return $structure;
        }

        $bracketSize = $this->bracketSize(count($ids));
        $slots = $this->seedBracketSlots($competitors, $bracketSize);
        $roundOne = collect($structure)->where('round_number', 1)->values();

        foreach ($roundOne as $index => $match) {
            $pair = $this->firstRoundPairs($bracketSize)[$index] ?? null;
            if ($pair === null) {
                continue;
            }

            [$slotA, $slotB] = $pair;
            $idx = $match['match_number'] - 1;
            $structure[$idx]['red'] = $slots[$slotA];
            $structure[$idx]['blue'] = $slots[$slotB];
            $this->applyRoundOneMatchType($structure, $idx);
        }

        return $structure;
    }

    public function matchCodeFor(EventCategory $category, int $matchNumber): string
    {
        if (preg_match('/CAT-(\d+)/', $category->internal_code, $matches)) {
            $short = 'CAT'.(int) $matches[1];
        } else {
            $short = 'CAT'.$category->id;
        }

        return sprintf('%s-M%02d', $short, $matchNumber);
    }

    /**
     * @return list<array{match_number: int, round_number: int, match_type: MatchType, stage_label: string, red: int|null, blue: int|null}>
     */
    private function structureForTwo(): array
    {
        return [
            [
                'match_number' => 1,
                'round_number' => 1,
                'match_type' => MatchType::Final,
                'stage_label' => 'Final',
                'red' => null,
                'blue' => null,
            ],
        ];
    }

    /**
     * @return list<array{match_number: int, round_number: int, match_type: MatchType, stage_label: string, red: int|null, blue: int|null}>
     */
    private function structureForThree(): array
    {
        return [
            [
                'match_number' => 1,
                'round_number' => 1,
                'match_type' => MatchType::Bye,
                'stage_label' => 'R1',
                'red' => null,
                'blue' => null,
            ],
            [
                'match_number' => 2,
                'round_number' => 1,
                'match_type' => MatchType::Normal,
                'stage_label' => 'R1',
                'red' => null,
                'blue' => null,
            ],
            [
                'match_number' => 3,
                'round_number' => 2,
                'match_type' => MatchType::Final,
                'stage_label' => 'Final',
                'red' => null,
                'blue' => null,
            ],
        ];
    }

    /**
     * @return list<array{match_number: int, round_number: int, match_type: MatchType, stage_label: string, red: int|null, blue: int|null}>
     */
    private function structureForBracket(int $competitorCount): array
    {
        $bracketSize = $this->bracketSize($competitorCount);
        $rounds = (int) log($bracketSize, 2);
        $structure = [];
        $matchNumber = 1;

        for ($round = 1; $round <= $rounds; $round++) {
            $matchesInRound = (int) ($bracketSize / (2 ** $round));
            for ($m = 0; $m < $matchesInRound; $m++) {
                $isFinalRound = $round === $rounds;

                if ($isFinalRound && $competitorCount >= 4) {
                    $structure[] = [
                        'match_number' => $matchNumber,
                        'round_number' => $round,
                        'match_type' => MatchType::ThirdPlace,
                        'stage_label' => '3er lugar',
                        'red' => null,
                        'blue' => null,
                    ];
                    $matchNumber++;
                }

                $structure[] = [
                    'match_number' => $matchNumber,
                    'round_number' => $round,
                    'match_type' => $isFinalRound ? MatchType::Final : MatchType::Normal,
                    'stage_label' => $isFinalRound ? 'Final' : 'R'.$round,
                    'red' => null,
                    'blue' => null,
                ];
                $matchNumber++;
            }
        }

        return $structure;
    }

    private function bracketSize(int $count): int
    {
        $size = 4;
        while ($size < $count && $size < 16) {
            $size *= 2;
        }

        return min($size, 16);
    }

    /**
     * @return list<array{0: int, 1: int}>
     */
    private function firstRoundPairs(int $bracketSize): array
    {
        return match ($bracketSize) {
            4 => [[0, 3], [1, 2]],
            8 => [[0, 7], [3, 4], [1, 6], [2, 5]],
            16 => [[0, 15], [7, 8], [3, 12], [4, 11], [1, 14], [6, 9], [2, 13], [5, 10]],
            default => throw ValidationException::withMessages([
                'category' => 'Tamaño de llave no soportado.',
            ]),
        };
    }

    /**
     * @param  Collection<int, object{id: int, school_id: int|null, sort_order: int}>  $competitors
     * @return list<int|null>
     */
    private function seedBracketSlots(Collection $competitors, int $bracketSize): array
    {
        $slots = array_fill(0, $bracketSize, null);
        $pairs = $this->firstRoundPairs($bracketSize);

        $opponentSlot = [];
        foreach ($pairs as [$a, $b]) {
            $opponentSlot[$a] = $b;
            $opponentSlot[$b] = $a;
        }

        $ordered = $competitors
            ->sortBy(fn ($c) => $c->school_id ?? 0)
            ->groupBy(fn ($c) => $c->school_id ?? 0)
            ->sortByDesc(fn ($group) => $group->count())
            ->flatMap(fn ($group) => $group->shuffle())
            ->values();

        foreach ($ordered as $competitor) {
            $bestSlot = null;
            $bestScore = PHP_INT_MIN;

            foreach (array_keys($slots) as $slotIndex) {
                if ($slots[$slotIndex] !== null) {
                    continue;
                }

                $score = 0;
                $opponentIndex = $opponentSlot[$slotIndex] ?? null;
                if ($opponentIndex !== null && $slots[$opponentIndex] !== null) {
                    $opponent = $competitors->firstWhere('id', $slots[$opponentIndex]);
                    if ($opponent !== null && $opponent->school_id === $competitor->school_id) {
                        $score -= 100;
                    }
                }

                $score -= $slotIndex;

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestSlot = $slotIndex;
                }
            }

            if ($bestSlot === null) {
                throw ValidationException::withMessages([
                    'category' => 'No se pudo distribuir competidores en la llave.',
                ]);
            }

            $slots[$bestSlot] = $competitor->id;
        }

        return $slots;
    }

    /**
     * @param  list<array{match_number: int, round_number: int, match_type: MatchType, stage_label: string, red: int|null, blue: int|null}>  $structure
     */
    private function persistMatches(EventCategory $category, array $structure, User $actor, string $auditType): void
    {
        foreach ($structure as $row) {
            $byeWinner = $row['match_type'] === MatchType::Bye
                ? ($row['red'] ?? $row['blue'])
                : null;

            CategoryMatch::query()->create([
                'event_id' => $category->event_id,
                'event_category_id' => $category->id,
                'match_code' => $this->matchCodeFor($category, $row['match_number']),
                'red_event_competitor_id' => $row['red'],
                'blue_event_competitor_id' => $row['blue'],
                'winner_id' => $byeWinner,
                'bout_order' => $row['match_number'],
                'stage_label' => $row['stage_label'],
                'round_number' => $row['round_number'],
                'match_type' => $row['match_type'],
                'status' => MatchStatus::Pending,
            ]);
        }

        $this->audit->record(
            actor: $actor,
            eventType: $auditType,
            severity: AuditSeverity::Info,
            entityType: 'event_category',
            entityId: $category->id,
            summary: "Llave generada para {$category->internal_code} ({$auditType})",
            payloadAfter: ['match_count' => count($structure)],
        );
    }

    /**
     * @param  list<array{match_number: int, round_number: int, match_type: MatchType, stage_label: string, red: int|null, blue: int|null}>  $structure
     */
    private function applyRoundOneMatchType(array &$structure, int $index): void
    {
        $red = $structure[$index]['red'];
        $blue = $structure[$index]['blue'];

        if ($red !== null && $blue !== null) {
            $structure[$index]['match_type'] = MatchType::Normal;

            return;
        }

        $structure[$index]['match_type'] = MatchType::Bye;
    }

    /**
     * @param  list<array{match_number: int, round_number: int, match_type: MatchType, stage_label: string, red: int|null, blue: int|null}>  $structure
     */
    public function propagateStructureByes(array &$structure): void
    {
        $maxRound = (int) max(array_column($structure, 'round_number'));

        for ($round = 1; $round < $maxRound; $round++) {
            $roundMatches = collect($structure)
                ->filter(fn (array $match) => $match['round_number'] === $round)
                ->sortBy('match_number')
                ->values();

            $nextRoundMatches = collect($structure)
                ->filter(fn (array $match) => $match['round_number'] === $round + 1
                    && $match['match_type'] !== MatchType::ThirdPlace)
                ->sortBy('match_number')
                ->values();

            if ($roundMatches->isEmpty() || $nextRoundMatches->isEmpty()) {
                continue;
            }

            foreach ($roundMatches as $index => $match) {
                if ($match['match_type'] !== MatchType::Bye) {
                    continue;
                }

                $winnerId = $match['red'] ?? $match['blue'];
                if ($winnerId === null) {
                    continue;
                }

                $nextMatchNumber = $nextRoundMatches->get(intdiv($index, 2))['match_number'] ?? null;
                if ($nextMatchNumber === null) {
                    continue;
                }

                $structIdx = collect($structure)->search(
                    fn (array $row) => $row['match_number'] === $nextMatchNumber,
                );

                if ($structIdx === false) {
                    continue;
                }

                if ($index % 2 === 0 && $structure[$structIdx]['red'] === null) {
                    $structure[$structIdx]['red'] = $winnerId;
                } elseif ($index % 2 === 1 && $structure[$structIdx]['blue'] === null) {
                    $structure[$structIdx]['blue'] = $winnerId;
                }
            }
        }
    }

    public function propagateByeWinnersForCategory(EventCategory $category): void
    {
        $matchesByRound = CategoryMatch::query()
            ->where('event_category_id', $category->id)
            ->orderBy('round_number')
            ->orderBy('bout_order')
            ->get()
            ->groupBy('round_number');

        $maxRound = (int) $matchesByRound->keys()->max();

        for ($round = 1; $round < $maxRound; $round++) {
            $roundMatches = $matchesByRound->get($round)?->values();
            $nextRoundMatches = $matchesByRound->get($round + 1)?->values()
                ?->filter(fn (CategoryMatch $match) => $match->match_type !== MatchType::ThirdPlace)
                ->values();

            if ($roundMatches === null || $nextRoundMatches === null || $nextRoundMatches->isEmpty()) {
                continue;
            }

            foreach ($roundMatches as $index => $match) {
                if ($match->match_type !== MatchType::Bye) {
                    continue;
                }

                $winnerId = $match->red_event_competitor_id ?? $match->blue_event_competitor_id;
                if ($winnerId === null) {
                    continue;
                }

                $match->update(['winner_id' => $winnerId]);

                $nextMatch = $nextRoundMatches->get(intdiv($index, 2));
                if ($nextMatch === null) {
                    continue;
                }

                if ($index % 2 === 0 && $nextMatch->red_event_competitor_id === null) {
                    $nextMatch->update(['red_event_competitor_id' => $winnerId]);
                } elseif ($index % 2 === 1 && $nextMatch->blue_event_competitor_id === null) {
                    $nextMatch->update(['blue_event_competitor_id' => $winnerId]);
                }
            }
        }
    }

    private function wipeMatches(EventCategory $category): void
    {
        CategoryMatch::query()
            ->where('event_category_id', $category->id)
            ->delete();
    }

    private function assertCanGenerate(EventCategory $category): void
    {
        if ($category->status !== EventCategoryStatus::Draft) {
            throw ValidationException::withMessages([
                'category' => 'La llave solo se genera cuando la categoría está en asignación (draft).',
            ]);
        }

        $competitorCount = $category->categoryCompetitors()->count();
        if ($competitorCount < 2) {
            throw ValidationException::withMessages([
                'category' => 'Se necesitan al menos 2 competidores para generar la llave.',
            ]);
        }
    }

    private function transitionToBracketPending(EventCategory $category): void
    {
        $category->update(['status' => EventCategoryStatus::BracketPending]);
    }

    private function assertConfirmedIfMatchesExist(EventCategory $category, bool $confirmed): void
    {
        $exists = CategoryMatch::query()
            ->where('event_category_id', $category->id)
            ->exists();

        if ($exists && ! $confirmed) {
            throw ValidationException::withMessages([
                'confirmation' => 'Esta acción eliminará todos los encuentros actuales y reconstruirá completamente la llave. ¿Desea continuar?',
            ]);
        }
    }

    /**
     * @return Collection<int, object{id: int, school_id: int|null, sort_order: int}>
     */
    private function loadCompetitors(EventCategory $category): Collection
    {
        return $category->categoryCompetitors()
            ->with('eventCompetitor.competitor:id,school_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => (object) [
                'id' => $row->event_competitor_id,
                'school_id' => $row->eventCompetitor->competitor?->school_id,
                'sort_order' => $row->sort_order,
            ]);
    }
}
