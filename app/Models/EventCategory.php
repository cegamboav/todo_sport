<?php

namespace App\Models;

use App\Enums\CategoryGenderScope;
use App\Enums\EventCategoryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventCategory extends Model
{
    protected $fillable = [
        'event_id',
        'internal_code',
        'name',
        'modality_id',
        'gender_scope',
        'ring_id',
        'competition_order',
        'status',
        'notes',
        'reference_age',
        'reference_grade',
        'reference_weight',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EventCategoryStatus::class,
            'gender_scope' => CategoryGenderScope::class,
            'competition_order' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function modality(): BelongsTo
    {
        return $this->belongsTo(Modality::class);
    }

    public function ring(): BelongsTo
    {
        return $this->belongsTo(Ring::class);
    }

    public function categoryCompetitors(): HasMany
    {
        return $this->hasMany(CategoryCompetitor::class)->orderBy('sort_order')->orderBy('id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(CategoryMatch::class)
            ->orderBy('round_number')
            ->orderBy('bout_order')
            ->orderBy('id');
    }
}
