<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Event extends Model
{
    protected $fillable = [
        'name',
        'event_date',
        'venue',
        'host_school_id',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'event_date' => 'date:Y-m-d',
        ];
    }

    public function hostSchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'host_school_id');
    }

    public function rings(): HasMany
    {
        return $this->hasMany(Ring::class);
    }

    public function operationalSessions(): HasMany
    {
        return $this->hasMany(OperationalSession::class);
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class);
    }

    public function eventStaff(): HasMany
    {
        return $this->hasMany(EventStaff::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(EventSetting::class);
    }

    public function eventModalities(): HasMany
    {
        return $this->hasMany(EventModality::class);
    }

    public function combos(): HasMany
    {
        return $this->hasMany(EventCombo::class);
    }

    public function eventCompetitors(): HasMany
    {
        return $this->hasMany(EventCompetitor::class);
    }

    public function eventCategories(): HasMany
    {
        return $this->hasMany(EventCategory::class);
    }

    public function allowsRegistration(): bool
    {
        return $this->status->allowsRegistration();
    }

    public function allowsRingsOperationalAccess(): bool
    {
        return $this->status->allowsRingsOperationalAccess();
    }
}
