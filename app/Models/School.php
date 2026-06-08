<?php

namespace App\Models;

use App\Enums\MasterStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'abbreviation',
        'country',
        'city',
        'director_id',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => MasterStatus::class,
        ];
    }

    public function director(): BelongsTo
    {
        return $this->belongsTo(Professor::class, 'director_id');
    }

    public function competitors(): HasMany
    {
        return $this->hasMany(Competitor::class);
    }
}
