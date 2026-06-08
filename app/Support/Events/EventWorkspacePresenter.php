<?php

namespace App\Support\Events;

use App\Enums\CategoryGenderScope;
use App\Enums\EventCategoryStatus;
use App\Enums\Gender;
use App\Enums\EventStatus;
use App\Enums\ParticipantEnrollmentStatus;
use App\Enums\RegistrationItemStatus;
use App\Enums\ThirdPlaceMode;
use App\Enums\UserRole;
use App\Enums\MasterStatus;
use App\Models\Competitor;
use App\Models\Event;
use App\Models\EventCompetitor;
use App\Services\Auth\AdminAccessService;
use App\Services\Competitive\EventCategoryService;
use App\Services\Events\EventService;
use App\Services\Masters\CompetitorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class EventWorkspacePresenter
{
    public function __construct(
        private readonly EventService $events,
        private readonly AdminAccessService $access,
        private readonly CompetitorService $competitors,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function shared(Request $request, Event $event): array
    {
        $event = $this->events->loadHub($event);
        $user = $request->user();

        $pendingItems = 0;
        foreach ($event->eventCompetitors as $participant) {
            foreach ($participant->registrationItems as $item) {
                if ($item->status === RegistrationItemStatus::Pending && $item->is_billable) {
                    $pendingItems++;
                }
            }
        }

        return [
            'event' => $event,
            'workspace' => [
                'title' => $event->name,
                'status' => $event->status->value,
                'status_label' => $event->status->label(),
                'event_date' => $event->event_date?->format('Y-m-d'),
                'venue' => $event->venue,
                'summary' => [
                    'participants' => $event->eventCompetitors->count(),
                    'pending_charges' => $pendingItems,
                ],
            ],
            'canManage' => $this->access->canManageEvent($user, $event),
            'canManageStaff' => $user->can('manageStaff', $event),
            'canAccessEventAdmin' => $this->access->canAccessEventAdminWorkspace($user, $event),
            'canAccessEventOperations' => $this->access->canAccessEventOperationsWorkspace($user, $event),
            'canEnrollParticipants' => $event->status !== EventStatus::Finished
                && $event->status !== EventStatus::Archived,
            'isAdmin' => $user->role === UserRole::Admin,
            'schoolOptions' => $this->competitors->schoolOptions()->map(fn ($school) => [
                'value' => $school->id,
                'label' => $school->abbreviation ?: $school->name,
            ])->values(),
            'gradeOptions' => $this->competitors->gradeOptions()->map(fn ($grade) => [
                'value' => $grade->id,
                'label' => $grade->name,
            ])->values(),
            'statusOptions' => collect(EventStatus::cases())->map(fn (EventStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values(),
            'thirdPlaceOptions' => collect(ThirdPlaceMode::cases())->map(fn (ThirdPlaceMode $mode) => [
                'value' => $mode->value,
                'label' => $mode->label(),
                'description' => $mode->description(),
            ])->values(),
            'registrationStatusOptions' => collect(RegistrationItemStatus::cases())->map(
                fn (RegistrationItemStatus $status) => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
            )->values(),
            'searchUrls' => [
                'competitors' => route('events.search.competitors', $event),
                'pendingCompetitors' => route('events.search.pending-competitors', $event),
                'staffUsers' => route('events.search.staff-users', $event),
            ],
            'participantMetrics' => $this->participantMetrics($event),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function participantMetrics(Event $event): array
    {
        $enrolledIds = EventCompetitor::query()
            ->where('event_id', $event->id)
            ->where('status', ParticipantEnrollmentStatus::Active)
            ->pluck('competitor_id');

        $pendingPayment = 0;
        foreach ($event->eventCompetitors as $participant) {
            foreach ($participant->registrationItems as $item) {
                if ($item->status === RegistrationItemStatus::Pending && $item->is_billable) {
                    $pendingPayment++;
                }
            }
        }

        $pendingToAddQuery = Competitor::query()->where('status', MasterStatus::Active);
        if ($enrolledIds->isNotEmpty()) {
            $pendingToAddQuery->whereNotIn('id', $enrolledIds);
        }

        return [
            'catalog_competitors' => Competitor::query()->where('status', MasterStatus::Active)->count(),
            'enrolled' => $enrolledIds->count(),
            'pending_to_add' => $pendingToAddQuery->count(),
            'pending_payment' => $pendingPayment,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function categories(Request $request, Event $event): array
    {
        $shared = $this->shared($request, $event);

        if (! Schema::hasTable('event_categories')) {
            return array_merge($shared, $this->emptyCategoriesPayload($event));
        }

        $categories = $event->eventCategories()
            ->with([
                'modality:id,code,name',
                'ring:id,name',
                'categoryCompetitors.eventCompetitor.competitor:id,first_name,last_name,school_id,grade_id,gender,birth_date,weight_kg,height_cm',
                'categoryCompetitors.eventCompetitor.competitor.school:id,name,abbreviation',
                'categoryCompetitors.eventCompetitor.competitor.grade:id,name',
            ])
            ->withCount(['categoryCompetitors', 'matches'])
            ->orderBy('competition_order')
            ->orderBy('id')
            ->get();

        return array_merge($shared, [
            'categories' => $categories,
            'rings' => $event->rings()->orderBy('name')->get(['id', 'name']),
            'categoryStatusOptions' => $this->categoryStatusOptions(),
            'categoryGenderOptions' => $this->categoryGenderOptions(),
            'categoryMetrics' => [
                'total' => $categories->count(),
                'without_ring' => $categories->whereNull('ring_id')->count(),
                'draft' => $categories->where('status', EventCategoryStatus::Draft)->count(),
                'bracket_pending' => $categories->where('status', EventCategoryStatus::BracketPending)->count(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function categoryShow(Request $request, Event $event, \App\Models\EventCategory $category): array
    {
        $shared = $this->shared($request, $event);

        $category->load([
            'modality:id,code,name',
            'ring:id,name',
            'matches.redCompetitor.competitor:id,first_name,last_name,gender',
            'matches.blueCompetitor.competitor:id,first_name,last_name,gender',
            'categoryCompetitors.eventCompetitor.competitor:id,first_name,last_name,school_id,grade_id,gender,birth_date,weight_kg,height_cm',
            'categoryCompetitors.eventCompetitor.competitor.school:id,name,abbreviation',
            'categoryCompetitors.eventCompetitor.competitor.grade:id,name',
        ]);

        $assignedIds = $category->categoryCompetitors->pluck('event_competitor_id');

        $eligibleQuery = EventCompetitor::query()
            ->where('event_id', $event->id)
            ->where('status', ParticipantEnrollmentStatus::Active)
            ->when($assignedIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $assignedIds));

        EventCategoryService::applyGenderScopeToCompetitorsQuery($eligibleQuery, $category->gender_scope);

        $eligibleParticipants = $eligibleQuery
            ->with([
                'competitor:id,first_name,last_name,school_id,grade_id,gender,birth_date,weight_kg,height_cm',
                'competitor.school:id,name,abbreviation',
                'competitor.grade:id,name',
            ])
            ->get()
            ->sortBy(fn (EventCompetitor $participant) => strtolower(
                ($participant->competitor->last_name ?? '').' '.($participant->competitor->first_name ?? ''),
            ))
            ->values();

        $categoryCompetitorsForBuilder = $category->categoryCompetitors
            ->filter(fn ($row) => $this->competitorMatchesCategoryGender($row->eventCompetitor->competitor, $category->gender_scope))
            ->values();

        return array_merge($shared, [
            'category' => $category,
            'eligibleParticipants' => $eligibleParticipants,
            'categoryCompetitorsForBuilder' => $categoryCompetitorsForBuilder,
            'rings' => $event->rings()->orderBy('name')->get(['id', 'name']),
            'categoryStatusOptions' => $this->categoryStatusOptions(),
            'categoryGenderOptions' => $this->categoryGenderOptions(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyCategoriesPayload(Event $event): array
    {
        return [
            'categories' => [],
            'rings' => Schema::hasTable('rings')
                ? $event->rings()->orderBy('name')->get(['id', 'name'])
                : [],
            'categoryStatusOptions' => $this->categoryStatusOptions(),
            'categoryGenderOptions' => $this->categoryGenderOptions(),
            'categoryMetrics' => [
                'total' => 0,
                'without_ring' => 0,
                'draft' => 0,
                'bracket_pending' => 0,
            ],
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{value: string, label: string}>
     */
    private function categoryGenderOptions()
    {
        return collect(\App\Enums\CategoryGenderScope::cases())->map(fn (\App\Enums\CategoryGenderScope $gender) => [
            'value' => $gender->value,
            'label' => $gender->label(),
        ])->values();
    }

    private function competitorMatchesCategoryGender(?Competitor $competitor, CategoryGenderScope $scope): bool
    {
        if ($competitor === null || $competitor->gender === null) {
            return false;
        }

        return match ($scope) {
            CategoryGenderScope::Male => $competitor->gender === Gender::Male,
            CategoryGenderScope::Female => $competitor->gender === Gender::Female,
            CategoryGenderScope::Mixed => $competitor->gender === Gender::Male || $competitor->gender === Gender::Female,
        };
    }

    private function categoryStatusOptions()
    {
        return collect(EventCategoryStatus::cases())->map(fn (EventCategoryStatus $status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ])->values();
    }
}
