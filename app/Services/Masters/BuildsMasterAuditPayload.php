<?php

namespace App\Services\Masters;

use Illuminate\Database\Eloquent\Model;

trait BuildsMasterAuditPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function auditSnapshot(Model $model): array
    {
        return $model->only($model->getFillable());
    }
}
