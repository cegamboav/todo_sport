<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\MasterStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Competitor extends Model
{
    use SoftDeletes;

    protected $appends = [
        'age',
    ];

    protected $fillable = [
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'school_id',
        'grade_id',
        'weight_kg',
        'height_cm',
        'medical_notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'birth_date' => 'date:Y-m-d',
            'weight_kg' => 'decimal:2',
            'height_cm' => 'integer',
            'status' => MasterStatus::class,
        ];
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date?->age;
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }
}
