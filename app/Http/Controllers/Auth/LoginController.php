<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RedirectByRole;
use App\Services\Auth\AdminAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function __construct(
        private readonly AdminAccessService $adminAccess,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'portal' => 'admin',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->authenticate($request);

        if ($this->adminAccess->isProfessor($user)) {
            $this->logout($request);

            throw ValidationException::withMessages([
                'username' => 'Los profesores deben usar el portal en /school/login.',
            ]);
        }

        if ($this->adminAccess->isCornerJudge($user)) {
            $this->logout($request);

            throw ValidationException::withMessages([
                'username' => 'Los jueces de esquina deben usar /judge/login.',
            ]);
        }

        if ($this->adminAccess->isOperationalRole($user) && $this->adminAccess->activeEventAssignment($user) === null) {
            $this->logout($request);

            throw ValidationException::withMessages([
                'username' => 'Sin evento abierto asignado. Contacta al administrador del torneo.',
            ]);
        }

        if (! $this->adminAccess->canLoginMainPanel($user)) {
            $this->logout($request);

            throw ValidationException::withMessages([
                'username' => 'Esta cuenta no tiene acceso al panel administrativo.',
            ]);
        }

        return redirect()->intended(RedirectByRole::pathFor($user));
    }

    public function createProfessor(): Response
    {
        return Inertia::render('Auth/Login', [
            'portal' => 'professor',
        ]);
    }

    public function storeProfessor(Request $request): RedirectResponse
    {
        $user = $this->authenticate($request);

        if (! $this->adminAccess->isProfessor($user)) {
            $this->logout($request);

            throw ValidationException::withMessages([
                'username' => 'Esta cuenta no es de profesor.',
            ]);
        }

        return redirect()->intended(route('professor.home', absolute: false));
    }

    public function createJudge(): Response
    {
        return Inertia::render('Auth/Login', [
            'portal' => 'judge',
        ]);
    }

    public function storeJudge(Request $request): RedirectResponse
    {
        $user = $this->authenticate($request);

        if (! $this->adminAccess->isCornerJudge($user)) {
            $this->logout($request);

            throw ValidationException::withMessages([
                'username' => 'Esta cuenta no es de juez de esquina.',
            ]);
        }

        if (! $this->adminAccess->canLoginJudgeApp($user)) {
            $this->logout($request);

            throw ValidationException::withMessages([
                'username' => 'Sin evento abierto asignado. Contacta al administrador del torneo.',
            ]);
        }

        return redirect()->intended(route('judge.home', absolute: false));
    }

    private function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'username' => 'Credenciales incorrectas.',
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if (! $user->status->allowsLogin()) {
            $this->logout($request);

            throw ValidationException::withMessages([
                'username' => $user->status->message() ?? 'Acceso denegado.',
            ]);
        }

        return $user;
    }

    private function logout(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
