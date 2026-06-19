<?php

namespace App\Models;

use App\Enums\MatchStatus;
use App\Enums\MatchType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryMatch extends Model
{
    protected $fillable = [
        'event_id',
        'event_category_id',
        'match_code',
        'red_event_competitor_id',
        'blue_event_competitor_id',
        'winner_id',
        'bout_order',
        'stage_label',
        'round_number',
        'match_type',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bout_order' => 'integer',
            'round_number' => 'integer',
            'match_type' => MatchType::class,
            'status' => MatchStatus::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function redCompetitor(): BelongsTo
    {
        return $this->belongsTo(EventCompetitor::class, 'red_event_competitor_id');
    }

    public function blueCompetitor(): BelongsTo
    {
        return $this->belongsTo(EventCompetitor::class, 'blue_event_competitor_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(EventCompetitor::class, 'winner_id');
    }

    public function isByeMatch(): bool
    {
        return $this->match_type === MatchType::Bye;
    }

    public function hasBothCompetitors(): bool
    {
        return $this->red_event_competitor_id !== null
            && $this->blue_event_competitor_id !== null;
    }

    public function hasExactlyOneCompetitor(): bool
    {
        $red = $this->red_event_competitor_id !== null;
        $blue = $this->blue_event_competitor_id !== null;

        return $red xor $blue;
    }
}
