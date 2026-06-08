<?php

namespace App\Services\License;

use App\DTOs\LicenseState;
use App\Enums\LicenseStatus;
use App\Models\License;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class LicenseService
{
    private ?LicenseState $state = null;

    public function load(): LicenseState
    {
        if (config('license.dev_mode')) {
            return $this->state = $this->devState();
        }

        $cached = License::query()->where('is_current', true)->first();
        if ($cached !== null) {
            return $this->state = $this->stateFromModel($cached);
        }

        if (File::exists(config('license.path'))) {
            return $this->state = $this->loadFromFile(config('license.path'));
        }

        return $this->state = new LicenseState(status: LicenseStatus::Missing);
    }

    public function reload(): LicenseState
    {
        $this->state = null;

        return $this->load();
    }

    public function state(): LicenseState
    {
        return $this->state ?? $this->load();
    }

    public function isWritable(): bool
    {
        return $this->state()->isWritable();
    }

    public function canUseFeature(string $feature): bool
    {
        $state = $this->state();

        if (! $state->isWritable()) {
            return false;
        }

        if ($state->features === []) {
            return true;
        }

        return in_array($feature, $state->features, true);
    }

    public function import(UploadedFile $file, ?int $userId = null): LicenseState
    {
        // Placeholder S0: persist raw file; crypto verify in L1.
        $destination = config('license.path');
        File::ensureDirectoryExists(dirname($destination));
        File::put($destination, $file->get());

        $payload = json_decode($file->get(), true);
        if (! is_array($payload)) {
            return $this->state = new LicenseState(status: LicenseStatus::Invalid);
        }

        License::query()->where('is_current', true)->update(['is_current' => false]);

        $inner = $payload['payload'] ?? $payload;
        $license = License::query()->create([
            'license_id' => $inner['license_id'] ?? null,
            'organization_name' => $inner['organization'] ?? null,
            'license_type' => $inner['license_type'] ?? null,
            'edition' => $inner['edition'] ?? null,
            'issued_at' => $inner['issued_at'] ?? null,
            'expires_at' => $inner['expires_at'] ?? null,
            'grace_days' => $inner['grace_days'] ?? config('license.default_grace_days'),
            'max_rings' => $inner['max_rings'] ?? null,
            'features_json' => $inner['features'] ?? [],
            'payload_json' => $inner,
            'signature' => $payload['signature'] ?? null,
            'key_id' => $payload['key_id'] ?? null,
            'status' => LicenseStatus::Active,
            'imported_at' => now(),
            'imported_by_user_id' => $userId,
            'is_current' => true,
        ]);

        return $this->state = $this->stateFromModel($license);
    }

    private function devState(): LicenseState
    {
        return new LicenseState(
            status: LicenseStatus::Active,
            organization: 'Todo Sport (desarrollo)',
            licenseId: 'ts-lic-dev-00001',
            expiresAt: now()->addYear()->toDateString(),
            daysRemaining: 365,
            features: ['combat', 'forms', 'advanced_reports'],
            maxRings: 10,
            banner: null,
        );
    }

    private function loadFromFile(string $path): LicenseState
    {
        $contents = File::get($path);
        $data = json_decode($contents, true);

        if (! is_array($data)) {
            return new LicenseState(status: LicenseStatus::Invalid);
        }

        $inner = $data['payload'] ?? $data;

        return new LicenseState(
            status: LicenseStatus::Active,
            organization: $inner['organization'] ?? null,
            licenseId: $inner['license_id'] ?? null,
            expiresAt: $inner['expires_at'] ?? null,
            daysRemaining: isset($inner['expires_at'])
                ? max(0, (int) now()->diffInDays($inner['expires_at'], false))
                : null,
            features: $inner['features'] ?? [],
            maxRings: $inner['max_rings'] ?? null,
        );
    }

    private function stateFromModel(License $license): LicenseState
    {
        $expiresAt = $license->expires_at?->toDateString();

        return new LicenseState(
            status: $license->status,
            organization: $license->organization_name,
            licenseId: $license->license_id,
            expiresAt: $expiresAt,
            daysRemaining: $license->expires_at
                ? max(0, (int) now()->diffInDays($license->expires_at, false))
                : null,
            features: $license->features_json ?? [],
            maxRings: $license->max_rings,
            banner: $license->status->banner(),
        );
    }
}
