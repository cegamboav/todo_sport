<?php

namespace App\Models;

use App\Enums\ParticipantEnrollmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventCompetitor extends Model
{
    protected $fillable = [
        'event_id',
        'competitor_id',
        'notes',
        'status',
        'withdrawn_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ParticipantEnrollmentStatus::class,
            'withdrawn_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }

    public function registrationItems(): HasMany
    {
        return $this->hasMany(RegistrationItem::class);
    }

    public function categoryCompetitors(): HasMany
    {
        return $this->hasMany(CategoryCompetitor::class);
    }

    public function redMatches(): HasMany
    {
        return $this->hasMany(CategoryMatch::class, 'red_event_competitor_id');
    }

    public function blueMatches(): HasMany
    {
        return $this->hasMany(CategoryMatch::class, 'blue_event_competitor_id');
    }

    public function isActive(): bool
    {
        return $this->status === ParticipantEnrollmentStatus::Active;
    }

    public function isWithdrawn(): bool
    {
        return $this->status === ParticipantEnrollmentStatus::Withdrawn;
    }

    /**
     * @param  Builder<EventCompetitor>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ParticipantEnrollmentStatus::Active);
    }
}
