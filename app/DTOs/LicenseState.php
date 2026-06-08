<?php

namespace App\DTOs;

use App\Enums\LicenseStatus;

final readonly class LicenseState
{
    /**
     * @param  list<string>  $features
     */
    public function __construct(
        public LicenseStatus $status,
        public ?string $organization = null,
        public ?string $licenseId = null,
        public ?string $expiresAt = null,
        public ?int $daysRemaining = null,
        public array $features = [],
        public ?int $maxRings = null,
        public ?string $banner = null,
    ) {}

    public function isWritable(): bool
    {
        return $this->status->isOperational();
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'organization' => $this->organization,
            'license_id' => $this->licenseId,
            'expires_at' => $this->expiresAt,
            'days_remaining' => $this->daysRemaining,
            'features' => $this->features,
            'max_rings' => $this->maxRings,
            'banner' => $this->banner,
            'is_writable' => $this->isWritable(),
        ];
    }
}
