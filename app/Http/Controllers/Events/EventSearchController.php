<?php

namespace App\Http\Controllers\Events;

use App\Enums\MasterStatus;
use App\Enums\ParticipantEnrollmentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Competitor;
use App\Models\Event;
use App\Models\EventCompetitor;
use App\Models\EventStaff;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventSearchController extends Controller
{
    public function competitors(Request $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $search = trim((string) $request->query('q', ''));
        if (mb_strlen($search) < 2) {
            return response()->json(['results' => []]);
        }

        $enrolledIds = EventCompetitor::query()
            ->where('event_id', $event->id)
            ->where('status', ParticipantEnrollmentStatus::Active)
            ->pluck('competitor_id');

        $like = '%'.$search.'%';
        $fullNameExpr = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)
            ? "concat(first_name, ' ', last_name)"
            : "(first_name || ' ' || last_name)";

        $query = Competitor::query()
            ->with(['school:id,name,abbreviation', 'grade:id,name'])
            ->where('status', MasterStatus::Active)
            ->where(function ($builder) use ($like, $fullNameExpr) {
                $builder->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhereRaw("{$fullNameExpr} like ?", [$like]);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(15);

        if ($enrolledIds->isNotEmpty()) {
            $query->whereNotIn('id', $enrolledIds);
        }

        $results = $query->get()->map(fn (Competitor $competitor) => [
            'id' => $competitor->id,
            'label' => $competitor->fullName(),
            'school' => $competitor->school?->abbreviation ?: $competitor->school?->name,
            'grade' => $competitor->grade?->name,
            'age' => $competitor->age,
        ]);

        return response()->json(['results' => $results]);
    }

    public function pendingCompetitors(Request $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $search = trim((string) $request->query('q', ''));
        $limit = min(max((int) $request->query('limit', 25), 5), 50);

        $enrolledIds = EventCompetitor::query()
            ->where('event_id', $event->id)
            ->where('status', ParticipantEnrollmentStatus::Active)
            ->pluck('competitor_id');

        $like = $search !== '' ? '%'.$search.'%' : null;
        $fullNameExpr = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)
            ? "concat(first_name, ' ', last_name)"
            : "(first_name || ' ' || last_name)";

        $query = Competitor::query()
            ->with(['school:id,name,abbreviation', 'grade:id,name'])
            ->where('status', MasterStatus::Active)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit($limit);

        if ($enrolledIds->isNotEmpty()) {
            $query->whereNotIn('id', $enrolledIds);
        }

        if ($like !== null && mb_strlen($search) >= 1) {
            $query->where(function ($builder) use ($like, $fullNameExpr) {
                $builder->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhereRaw("{$fullNameExpr} like ?", [$like]);
            });
        }

        $results = $query->get()->map(fn (Competitor $competitor) => [
            'id' => $competitor->id,
            'label' => $competitor->fullName(),
            'school' => $competitor->school?->abbreviation ?: $competitor->school?->name,
            'grade' => $competitor->grade?->name,
            'age' => $competitor->age,
        ]);

        return response()->json([
            'results' => $results,
            'total_hint' => $search === '' ? 'Escribe para filtrar o desplázate en la lista inicial.' : null,
        ]);
    }

    public function staffUsers(Request $request, Event $event): JsonResponse
    {
        $this->authorize('manageStaff', $event);

        $search = trim((string) $request->query('q', ''));
        if (mb_strlen($search) < 2) {
            return response()->json(['results' => []]);
        }

        $assignedIds = EventStaff::query()
            ->where('event_id', $event->id)
            ->pluck('user_id');

        $results = User::query()
            ->whereIn('role', [UserRole::Staff, UserRole::Mesa, UserRole::Corner])
            ->where('status', UserStatus::Active)
            ->whereNotIn('id', $assignedIds)
            ->where('username', 'like', "%{$search}%")
            ->orderBy('username')
            ->limit(15)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'label' => $user->username,
                'role' => $user->role->value,
                'subtitle' => $user->role->value,
            ]);

        return response()->json(['results' => $results]);
    }
}
