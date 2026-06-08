<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventModality extends Model
{
    protected $fillable = [
        'event_id',
        'modality_id',
        'enabled',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'price' => 'decimal:2',
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

    public function registrationItems(): HasMany
    {
        return $this->hasMany(RegistrationItem::class);
    }
}
