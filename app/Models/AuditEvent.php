<?php

namespace App\Models;

use App\Enums\AuditSeverity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'actor_user_id',
        'entity_type',
        'entity_id',
        'event_type',
        'severity',
        'summary',
        'payload_before',
        'payload_after',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'severity' => AuditSeverity::class,
            'payload_before' => 'array',
            'payload_after' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
