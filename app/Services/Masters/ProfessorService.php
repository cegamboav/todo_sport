<?php

namespace App\Services\Masters;

use App\Enums\AuditSeverity;
use App\Enums\MasterStatus;
use App\Models\Grade;
use App\Models\Professor;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProfessorService
{
    use BuildsMasterAuditPayload;
    use SyncsMasterSystemAccess;

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Professor::query()
            ->with(['grade:id,name'])
            ->orderBy('last_name')
            ->orderBy('first_name');

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['grade_id'])) {
            $query->where('grade_id', $filters['grade_id']);
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
    public function create(array $data, User $actor): Professor
    {
        return DB::transaction(function () use ($data, $actor) {
            $accessData = $data;
            $this->stripSystemAccessFields($data);

            $professor = Professor::query()->create([
                ...$data,
                'status' => $data['status'] ?? MasterStatus::Active,
            ]);

            $this->syncProfessorSystemAccess($professor, $accessData);

            $this->audit->record(
                actor: $actor,
                eventType: 'professor.created',
                severity: AuditSeverity::Info,
                entityType: 'professor',
                entityId: $professor->id,
                summary: "Profesor creado: {$professor->fullName()}",
                payloadAfter: $this->auditSnapshot($professor),
            );

            return $professor->load('grade');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Professor $professor, array $data, User $actor): Professor
    {
        return DB::transaction(function () use ($professor, $data, $actor) {
            $accessData = $data;
            $this->stripSystemAccessFields($data);

            $before = $this->auditSnapshot($professor);
            $professor->update($data);

            $this->syncProfessorSystemAccess($professor->fresh(), $accessData);

            $this->audit->record(
                actor: $actor,
                eventType: 'professor.updated',
                severity: AuditSeverity::Info,
                entityType: 'professor',
                entityId: $professor->id,
                summary: "Profesor actualizado: {$professor->fullName()}",
                payloadBefore: $before,
                payloadAfter: $this->auditSnapshot($professor->fresh()),
            );

            return $professor->fresh(['grade']);
        });
    }

    public function deactivate(Professor $professor, User $actor): Professor
    {
        return DB::transaction(function () use ($professor, $actor) {
            $before = $this->auditSnapshot($professor);
            $professor->update(['status' => MasterStatus::Inactive]);
            $professor->delete();

            $this->audit->record(
                actor: $actor,
                eventType: 'professor.deactivated',
                severity: AuditSeverity::Warning,
                entityType: 'professor',
                entityId: $professor->id,
                summary: "Profesor desactivado: {$professor->fullName()}",
                payloadBefore: $before,
                payloadAfter: ['status' => MasterStatus::Inactive->value],
            );

            return $professor;
        });
    }

    public function restore(Professor $professor, User $actor): Professor
    {
        return DB::transaction(function () use ($professor, $actor) {
            $professor->restore();
            $professor->update(['status' => MasterStatus::Active]);

            $this->audit->record(
                actor: $actor,
                eventType: 'professor.restored',
                severity: AuditSeverity::Info,
                entityType: 'professor',
                entityId: $professor->id,
                summary: "Profesor restaurado: {$professor->fullName()}",
                payloadAfter: $this->auditSnapshot($professor->fresh()),
            );

            return $professor->fresh(['grade']);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Grade>
     */
    public function gradeOptions(): \Illuminate\Database\Eloquent\Collection
    {
        return Grade::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name']);
    }
}
