<?php



namespace App\Services\Masters;



use App\Enums\AuditSeverity;

use App\Enums\MasterStatus;

use App\Enums\RefereeSpecialty;

use App\Models\Grade;

use App\Models\Referee;

use App\Models\User;

use App\Services\Audit\AuditService;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use Illuminate\Support\Facades\DB;



class RefereeService
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

        $query = Referee::query()

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



        if (! empty($filters['specialty'])) {

            $query->where('specialty', $filters['specialty']);

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

    public function create(array $data, User $actor): Referee

    {

        return DB::transaction(function () use ($data, $actor) {
            $accessData = $data;
            $this->stripSystemAccessFields($data);

            $referee = Referee::query()->create([
                ...$data,
                'status' => $data['status'] ?? MasterStatus::Active,
                'specialty' => $data['specialty'] ?? RefereeSpecialty::Corner,
            ]);

            $this->syncRefereeSystemAccess($referee, $accessData);

            $this->audit->record(

                actor: $actor,

                eventType: 'referee.created',

                severity: AuditSeverity::Info,

                entityType: 'referee',

                entityId: $referee->id,

                summary: "Árbitro creado: {$referee->fullName()}",

                payloadAfter: $this->auditSnapshot($referee),

            );



            return $referee->load('grade');

        });

    }



    /**

     * @param  array<string, mixed>  $data

     */

    public function update(Referee $referee, array $data, User $actor): Referee

    {

        return DB::transaction(function () use ($referee, $data, $actor) {
            $accessData = $data;
            $this->stripSystemAccessFields($data);

            $before = $this->auditSnapshot($referee);
            $referee->update($data);

            $this->syncRefereeSystemAccess($referee->fresh(), $accessData);

            $this->audit->record(

                actor: $actor,

                eventType: 'referee.updated',

                severity: AuditSeverity::Info,

                entityType: 'referee',

                entityId: $referee->id,

                summary: "Árbitro actualizado: {$referee->fullName()}",

                payloadBefore: $before,

                payloadAfter: $this->auditSnapshot($referee->fresh()),

            );



            return $referee->fresh(['grade']);

        });

    }



    public function deactivate(Referee $referee, User $actor): Referee

    {

        return DB::transaction(function () use ($referee, $actor) {

            $before = $this->auditSnapshot($referee);

            $referee->update(['status' => MasterStatus::Inactive]);

            $referee->delete();



            $this->audit->record(

                actor: $actor,

                eventType: 'referee.deactivated',

                severity: AuditSeverity::Warning,

                entityType: 'referee',

                entityId: $referee->id,

                summary: "Árbitro desactivado: {$referee->fullName()}",

                payloadBefore: $before,

                payloadAfter: ['status' => MasterStatus::Inactive->value],

            );



            return $referee;

        });

    }



    public function restore(Referee $referee, User $actor): Referee

    {

        return DB::transaction(function () use ($referee, $actor) {

            $referee->restore();

            $referee->update(['status' => MasterStatus::Active]);



            $this->audit->record(

                actor: $actor,

                eventType: 'referee.restored',

                severity: AuditSeverity::Info,

                entityType: 'referee',

                entityId: $referee->id,

                summary: "Árbitro restaurado: {$referee->fullName()}",

                payloadAfter: $this->auditSnapshot($referee->fresh()),

            );



            return $referee->fresh(['grade']);

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

            ->get(['id', 'name', 'category']);

    }

}

