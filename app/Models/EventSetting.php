<?php

namespace App\Models;

use App\Enums\ThirdPlaceMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSetting extends Model
{
    protected $fillable = [
        'event_id',
        'third_place_mode',
        'allow_team_forms',
        'bronze_mode',
    ];

    protected function casts(): array
    {
        return [
            'third_place_mode' => ThirdPlaceMode::class,
            'allow_team_forms' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
