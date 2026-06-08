<?php

namespace App\Models;

use App\Enums\LicenseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class License extends Model
{
    protected $fillable = [
        'license_id',
        'organization_name',
        'license_type',
        'edition',
        'issued_at',
        'expires_at',
        'grace_days',
        'max_rings',
        'features_json',
        'payload_json',
        'signature',
        'key_id',
        'status',
        'imported_at',
        'imported_by_user_id',
        'installation_id',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'status' => LicenseStatus::class,
            'features_json' => 'array',
            'payload_json' => 'array',
            'issued_at' => 'date',
            'expires_at' => 'date',
            'imported_at' => 'datetime',
            'is_current' => 'boolean',
        ];
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by_user_id');
    }
}
