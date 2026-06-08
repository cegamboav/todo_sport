<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ring extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'status',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function eventCategories(): HasMany
    {
        return $this->hasMany(EventCategory::class);
    }
}
