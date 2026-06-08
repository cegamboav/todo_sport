<?php

namespace App\Models;

use App\Enums\OperationalLockStrength;
use App\Enums\OperationalSessionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationalSession extends Model
{
    protected $fillable = [
        'event_id',
        'session_type',
        'lock_strength',
        'entity_type',
        'entity_id',
        'user_id',
        'started_at',
        'last_heartbeat_at',
        'ended_at',
        'end_reason',
    ];

    protected function casts(): array
    {
        return [
            'session_type' => OperationalSessionType::class,
            'lock_strength' => OperationalLockStrength::class,
            'started_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }
}
