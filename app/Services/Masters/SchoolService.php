<?php

namespace App\Services\Masters;

use App\Enums\AuditSeverity;
use App\Enums\MasterStatus;
use App\Models\Professor;
use App\Models\School;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SchoolService
{
    use BuildsMasterAuditPayload;

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = School::query()
            ->with(['director:id,first_name,last_name,phone'])
            ->orderBy('name');

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('abbreviation', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }

        if (! empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (! empty($filters['director_id'])) {
            $query->where('director_id', $filters['director_id']);
        }

        if (! empty($filters['only_trashed'])) {
            $query->onlyTrashed();
        } elseif (! empty($filters['with_trashed'])) {
            $query->withTrashed();
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->paginate($perPage > 0 ? $perPage : 15)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): School
    {
        return DB::transaction(function () use ($data, $actor) {
            $school = School::query()->create([
                ...$data,
                'status' => $data['status'] ?? MasterStatus::Active,
            ]);

            $this->audit->record(
                actor: $actor,
                eventType: 'school.created',
                severity: AuditSeverity::Info,
                entityType: 'school',
                entityId: $school->id,
                summary: "Escuela creada: {$school->name} ({$school->abbreviation})",
                payloadAfter: $this->auditSnapshot($school),
            );

            return $school->load('director');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(School $school, array $data, User $actor): School
    {
        return DB::transaction(function () use ($school, $data, $actor) {
            $before = $this->auditSnapshot($school);
            $school->update($data);

            $this->audit->record(
                actor: $actor,
                eventType: 'school.updated',
                severity: AuditSeverity::Info,
                entityType: 'school',
                entityId: $school->id,
                summary: "Escuela actualizada: {$school->name}",
                payloadBefore: $before,
                payloadAfter: $this->auditSnapshot($school->fresh()),
            );

            return $school->fresh(['director']);
        });
    }

    public function deactivate(School $school, User $actor): School
    {
        return DB::transaction(function () use ($school, $actor) {
            $before = $this->auditSnapshot($school);
            $school->update(['status' => MasterStatus::Inactive]);
            $school->delete();

            $this->audit->record(
                actor: $actor,
                eventType: 'school.deactivated',
                severity: AuditSeverity::Warning,
                entityType: 'school',
                entityId: $school->id,
                summary: "Escuela desactivada: {$school->name}",
                payloadBefore: $before,
                payloadAfter: ['status' => MasterStatus::Inactive->value],
            );

            return $school;
        });
    }

    public function restore(School $school, User $actor): School
    {
        return DB::transaction(function () use ($school, $actor) {
            $school->restore();
            $school->update(['status' => MasterStatus::Active]);

            $this->audit->record(
                actor: $actor,
                eventType: 'school.restored',
                severity: AuditSeverity::Info,
                entityType: 'school',
                entityId: $school->id,
                summary: "Escuela restaurada: {$school->name}",
                payloadAfter: $this->auditSnapshot($school->fresh()),
            );

            return $school->fresh(['director']);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Professor>
     */
    public function directorOptions(): \Illuminate\Database\Eloquent\Collection
    {
        return Professor::query()
            ->where('status', MasterStatus::Active)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);
    }
}
