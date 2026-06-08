<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventCombo extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'price',
        'enabled',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function modalities(): BelongsToMany
    {
        return $this->belongsToMany(Modality::class, 'event_combo_modalities');
    }

    public function registrationItems(): HasMany
    {
        return $this->hasMany(RegistrationItem::class);
    }
}
