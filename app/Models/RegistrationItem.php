<?php

namespace App\Models;

use App\Enums\RegistrationItemStatus;
use App\Enums\RegistrationItemType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationItem extends Model
{
    protected $fillable = [
        'event_competitor_id',
        'item_type',
        'event_modality_id',
        'event_combo_id',
        'label',
        'amount',
        'is_billable',
        'admin_override',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'item_type' => RegistrationItemType::class,
            'amount' => 'decimal:2',
            'is_billable' => 'boolean',
            'admin_override' => 'boolean',
            'status' => RegistrationItemStatus::class,
        ];
    }

    public function eventCompetitor(): BelongsTo
    {
        return $this->belongsTo(EventCompetitor::class);
    }

    public function eventModality(): BelongsTo
    {
        return $this->belongsTo(EventModality::class);
    }

    public function eventCombo(): BelongsTo
    {
        return $this->belongsTo(EventCombo::class);
    }
}
