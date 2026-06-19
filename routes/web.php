<?php

use App\Http\Controllers\Admin\LicenseController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Events\EventCategoryController;
use App\Http\Controllers\Events\EventComboController;
use App\Http\Controllers\Events\EventController;
use App\Http\Controllers\Events\EventModalityController;
use App\Http\Controllers\Events\EventOperationsController;
use App\Http\Controllers\Events\EventParticipantController;
use App\Http\Controllers\Events\EventSearchController;
use App\Http\Controllers\Events\EventSettingController;
use App\Http\Controllers\Events\EventStaffController;
use App\Http\Controllers\Events\EventWorkspaceController;
use App\Http\Controllers\Events\ModalityController;
use App\Http\Controllers\Judge\HomeController as JudgeHomeController;
use App\Http\Controllers\Masters\CompetitorController;
use App\Http\Controllers\Masters\ProfessorController;
use App\Http\Controllers\Masters\RefereeController;
use App\Http\Controllers\Masters\SchoolController;
use App\Http\Controllers\Professor\HomeController as ProfessorHomeController;
use App\Http\Controllers\Rings\HomeController as RingsHomeController;
use App\Http\Middleware\RedirectByRole;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/school/login', [LoginController::class, 'createProfessor'])->name('professor.login');
    Route::post('/school/login', [LoginController::class, 'storeProfessor']);

    Route::get('/judge/login', [LoginController::class, 'createJudge'])->name('judge.login');
    Route::post('/judge/login', [LoginController::class, 'storeJudge']);
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [LogoutController::class, 'destroy'])->name('logout');

    Route::middleware('admin.dashboard')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/license', [LicenseController::class, 'show'])->name('license.show');
            Route::post('/license/import', [LicenseController::class, 'import'])->name('license.import');
        });
    });

    Route::middleware('rings.access')->group(function () {
        Route::get('/rings', [RingsHomeController::class, 'index'])->name('rings.home');
    });

    Route::middleware('professor.portal')->group(function () {
        Route::get('/school', [ProfessorHomeController::class, 'index'])->name('professor.home');
    });

    Route::middleware('judge.access')->group(function () {
        Route::get('/judge', [JudgeHomeController::class, 'index'])->name('judge.home');
    });

    Route::get('/home', fn () => redirect(RedirectByRole::pathFor(auth()->user())))->name('home');

    Route::prefix('config')->name('config.')->middleware('admin.dashboard')->group(function () {
        Route::resource('modalities', ModalityController::class)->except(['show']);
    });

    Route::prefix('events')->name('events.')->middleware('admin.dashboard')->group(function () {
        Route::get('/', [EventController::class, 'index'])->name('index');
        Route::get('create', [EventController::class, 'create'])->name('create');
        Route::post('/', [EventController::class, 'store'])->name('store');
        Route::get('{event}/edit', [EventController::class, 'edit'])->name('edit');
        Route::put('{event}', [EventController::class, 'update'])->name('update');
        Route::delete('{event}', [EventController::class, 'destroy'])->name('destroy');

        Route::get('{event}/operations', [EventOperationsController::class, 'index'])->name('operations');
        Route::get('{event}/hub', [EventWorkspaceController::class, 'redirectLegacyHub'])->name('hub');
        Route::get('{event}/participants', [EventWorkspaceController::class, 'participants'])->name('participants');
        Route::get('{event}/categories', [EventCategoryController::class, 'index'])->name('categories');
        Route::post('{event}/categories', [EventCategoryController::class, 'store'])->name('categories.store');
        Route::put('{event}/categories/order', [EventCategoryController::class, 'syncOrder'])->name('categories.order');
        Route::get('{event}/categories/{category}', [EventCategoryController::class, 'show'])->name('categories.show');
        Route::redirect('{event}/registrations', '/events/{event}/participants');
        Route::redirect('{event}/modalities', '/events/{event}/config/modalities');
        Route::redirect('{event}/combos', '/events/{event}/config/combos');
        Route::redirect('{event}/staff', '/events/{event}/config/staff');
        Route::redirect('{event}/settings', '/events/{event}/config/settings');
        Route::redirect('{event}/config', '/events/{event}/config/modalities');
        Route::get('{event}/config/modalities', [EventWorkspaceController::class, 'configModalities'])->name('config.modalities');
        Route::get('{event}/config/combos', [EventWorkspaceController::class, 'configCombos'])->name('config.combos');
        Route::get('{event}/config/staff', [EventWorkspaceController::class, 'configStaff'])->name('config.staff');
        Route::get('{event}/config/settings', [EventWorkspaceController::class, 'configSettings'])->name('config.settings');
        Route::get('{event}/search/competitors', [EventSearchController::class, 'competitors'])->name('search.competitors');
        Route::get('{event}/search/pending-competitors', [EventSearchController::class, 'pendingCompetitors'])
            ->name('search.pending-competitors');
        Route::get('{event}/search/staff-users', [EventSearchController::class, 'staffUsers'])->name('search.staff-users');
        Route::put('{event}/status', [EventController::class, 'transitionStatus'])->name('status');
        Route::put('{event}/modalities', [EventModalityController::class, 'sync'])->name('modalities.sync');
        Route::put('{event}/settings', [EventSettingController::class, 'update'])->name('settings.update');
        Route::post('{event}/combos', [EventComboController::class, 'store'])->name('combos.store');
        Route::put('{event}/combos/{combo}', [EventComboController::class, 'update'])->name('combos.update');
        Route::delete('{event}/combos/{combo}', [EventComboController::class, 'destroy'])->name('combos.destroy');
        Route::post('{event}/participants', [EventParticipantController::class, 'store'])->name('participants.store');
        Route::post('{event}/participants/quick-create', [EventParticipantController::class, 'quickCreate'])
            ->name('participants.quick-create');
        Route::post('{event}/participants/quick-register', [EventParticipantController::class, 'quickRegister'])
            ->name('participants.quick-register');
        Route::delete('{event}/participants/{participant}', [EventParticipantController::class, 'withdraw'])
            ->name('participants.withdraw');
        Route::put('{event}/categories/{category}', [EventCategoryController::class, 'update'])->name('categories.update');
        Route::put('{event}/categories/{category}/status', [EventCategoryController::class, 'updateStatus'])
            ->name('categories.status');
        Route::delete('{event}/categories/{category}', [EventCategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('{event}/categories/{category}/competitors', [EventCategoryController::class, 'assignCompetitor'])
            ->name('categories.competitors.assign');
        Route::delete('{event}/categories/{category}/competitors/{assignment}', [EventCategoryController::class, 'removeCompetitor'])
            ->name('categories.competitors.remove');
        Route::post('{event}/categories/{category}/matches', [EventCategoryController::class, 'storeMatch'])
            ->name('categories.matches.store');
        Route::put('{event}/categories/{category}/matches', [EventCategoryController::class, 'syncMatches'])
            ->name('categories.matches.sync');
        Route::post('{event}/categories/{category}/bracket/generate-auto', [EventCategoryController::class, 'generateBracketAuto'])
            ->name('categories.bracket.generate-auto');
        Route::post('{event}/categories/{category}/bracket/generate-manual', [EventCategoryController::class, 'generateBracketManual'])
            ->name('categories.bracket.generate-manual');
        Route::delete('{event}/categories/{category}/matches/{match}', [EventCategoryController::class, 'destroyMatch'])
            ->name('categories.matches.destroy');
        Route::post('{event}/participants/{participant}/items', [EventParticipantController::class, 'storeRegistrationItem'])
            ->name('participants.items.store');
        Route::put('{event}/registration-items/{registrationItem}/status', [EventParticipantController::class, 'updateRegistrationItemStatus'])
            ->name('registration-items.status');
        Route::delete('{event}/registration-items/{registrationItem}', [EventParticipantController::class, 'destroyRegistrationItem'])
            ->name('registration-items.destroy');
        Route::post('{event}/staff', [EventStaffController::class, 'store'])->name('staff.store');
        Route::delete('{event}/staff/{user}', [EventStaffController::class, 'destroy'])->name('staff.destroy');
        Route::get('{event}', [EventWorkspaceController::class, 'overview'])->name('show');
    });

    Route::prefix('masters')->name('masters.')->middleware('masters.panel')->group(function () {
        Route::resource('schools', SchoolController::class)->except(['show']);
        Route::post('schools/{school}/restore', [SchoolController::class, 'restore'])
            ->name('schools.restore')
            ->withTrashed();

        Route::resource('professors', ProfessorController::class)->except(['show']);
        Route::post('professors/{professor}/restore', [ProfessorController::class, 'restore'])
            ->name('professors.restore')
            ->withTrashed();

        Route::resource('competitors', CompetitorController::class)->except(['show']);
        Route::post('competitors/{competitor}/restore', [CompetitorController::class, 'restore'])
            ->name('competitors.restore')
            ->withTrashed();

        Route::resource('referees', RefereeController::class)->except(['show']);
        Route::post('referees/{referee}/restore', [RefereeController::class, 'restore'])
            ->name('referees.restore')
            ->withTrashed();
    });
});
