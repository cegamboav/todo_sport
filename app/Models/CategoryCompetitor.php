<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryCompetitor extends Model
{
    protected $fillable = [
        'event_category_id',
        'event_competitor_id',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function eventCategory(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class);
    }

    public function eventCompetitor(): BelongsTo
    {
        return $this->belongsTo(EventCompetitor::class);
    }
}
