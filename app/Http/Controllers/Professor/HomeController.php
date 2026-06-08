<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class HomeController extends Controller
{
    // Root view: professor.blade.php → app-professor.ts (via HandleInertiaRequests::rootView for /school/*)

    public function index(Request $request): Response|HttpResponse
    {
        abort_unless($request->user()?->isProfessor(), 403);

        return Inertia::render('Professor/ComingSoon', [
            'message' => 'Portal escuela — próximamente',
        ]);
    }
}
