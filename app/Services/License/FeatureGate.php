<?php

namespace App\Services\License;

class FeatureGate
{
    public function __construct(
        private readonly LicenseService $licenseService,
    ) {}

    public function enabled(string $feature): bool
    {
        return $this->licenseService->canUseFeature($feature);
    }

    /**
     * @param  list<string>  $features
     */
    public function all(array $features): bool
    {
        foreach ($features as $feature) {
            if (! $this->enabled($feature)) {
                return false;
            }
        }

        return true;
    }
}
