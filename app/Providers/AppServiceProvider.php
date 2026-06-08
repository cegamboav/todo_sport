<?php

namespace App\Providers;

use App\Models\Competitor;
use App\Models\Event;
use App\Models\Modality;
use App\Models\Professor;
use App\Models\Referee;
use App\Models\Ring;
use App\Models\School;
use App\Policies\CompetitorPolicy;
use App\Policies\EventPolicy;
use App\Policies\ModalityPolicy;
use App\Policies\ProfessorPolicy;
use App\Policies\RefereePolicy;
use App\Policies\RingPolicy;
use App\Policies\SchoolPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\Auth\AdminAccessService::class);
        $this->app->singleton(\App\Services\OperationalSession\OperationalSessionService::class);
    }

    public function boot(): void
    {
        Gate::policy(Event::class, EventPolicy::class);
        Gate::policy(Modality::class, ModalityPolicy::class);
        Gate::policy(Ring::class, RingPolicy::class);
        Gate::policy(School::class, SchoolPolicy::class);
        Gate::policy(Professor::class, ProfessorPolicy::class);
        Gate::policy(Competitor::class, CompetitorPolicy::class);
        Gate::policy(Referee::class, RefereePolicy::class);
    }
}
