<?php

namespace App\Services\Masters;

use App\Enums\AuditSeverity;
use App\Enums\MasterStatus;
use App\Models\Competitor;
use App\Models\Grade;
use App\Models\School;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CompetitorService
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
        $query = Competitor::query()
            ->with([
                'school:id,name,abbreviation',
                'grade:id,name',
            ])
            ->orderBy('last_name')
            ->orderBy('first_name');

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        if (! empty($filters['school_id'])) {
            $query->where('school_id', $filters['school_id']);
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
    public function create(array $data, User $actor): Competitor
    {
        return DB::transaction(function () use ($data, $actor) {
            $competitor = Competitor::query()->create([
                ...$data,
                'status' => $data['status'] ?? MasterStatus::Active,
            ]);

            $this->audit->record(
                actor: $actor,
                eventType: 'competitor.created',
                severity: AuditSeverity::Info,
                entityType: 'competitor',
                entityId: $competitor->id,
                summary: "Competidor creado: {$competitor->fullName()}",
                payloadAfter: $this->auditSnapshot($competitor),
            );

            return $competitor->load(['school', 'grade']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Competitor $competitor, array $data, User $actor): Competitor
    {
        return DB::transaction(function () use ($competitor, $data, $actor) {
            $before = $this->auditSnapshot($competitor);
            $competitor->update($data);

            $this->audit->record(
                actor: $actor,
                eventType: 'competitor.updated',
                severity: AuditSeverity::Info,
                entityType: 'competitor',
                entityId: $competitor->id,
                summary: "Competidor actualizado: {$competitor->fullName()}",
                payloadBefore: $before,
                payloadAfter: $this->auditSnapshot($competitor->fresh()),
            );

            return $competitor->fresh(['school', 'grade']);
        });
    }

    public function deactivate(Competitor $competitor, User $actor): Competitor
    {
        return DB::transaction(function () use ($competitor, $actor) {
            $before = $this->auditSnapshot($competitor);
            $competitor->update(['status' => MasterStatus::Inactive]);
            $competitor->delete();

            $this->audit->record(
                actor: $actor,
                eventType: 'competitor.deactivated',
                severity: AuditSeverity::Warning,
                entityType: 'competitor',
                entityId: $competitor->id,
                summary: "Competidor desactivado: {$competitor->fullName()}",
                payloadBefore: $before,
                payloadAfter: ['status' => MasterStatus::Inactive->value],
            );

            return $competitor;
        });
    }

    public function restore(Competitor $competitor, User $actor): Competitor
    {
        return DB::transaction(function () use ($competitor, $actor) {
            $competitor->restore();
            $competitor->update(['status' => MasterStatus::Active]);

            $this->audit->record(
                actor: $actor,
                eventType: 'competitor.restored',
                severity: AuditSeverity::Info,
                entityType: 'competitor',
                entityId: $competitor->id,
                summary: "Competidor restaurado: {$competitor->fullName()}",
                payloadAfter: $this->auditSnapshot($competitor->fresh()),
            );

            return $competitor->fresh(['school', 'grade']);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, School>
     */
    public function schoolOptions(): \Illuminate\Database\Eloquent\Collection
    {
        return School::query()
            ->where('status', MasterStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name', 'abbreviation']);
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
