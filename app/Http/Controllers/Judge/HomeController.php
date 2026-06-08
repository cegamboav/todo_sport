<?php

namespace App\Http\Controllers\Judge;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class HomeController extends Controller
{
    // Root view: judge.blade.php → app-judge.ts (via HandleInertiaRequests::rootView for /judge/*)

    public function index(Request $request): Response|HttpResponse
    {
        abort_unless($request->user()?->isCorner(), 403);

        return Inertia::render('Judge/Home');
    }
}
