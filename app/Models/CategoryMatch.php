<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryMatch extends Model
{
    protected $fillable = [
        'event_category_id',
        'red_event_competitor_id',
        'blue_event_competitor_id',
        'bout_order',
        'stage_label',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bout_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }

    public function redCompetitor(): BelongsTo
    {
        return $this->belongsTo(EventCompetitor::class, 'red_event_competitor_id');
    }

    public function blueCompetitor(): BelongsTo
    {
        return $this->belongsTo(EventCompetitor::class, 'blue_event_competitor_id');
    }
}
