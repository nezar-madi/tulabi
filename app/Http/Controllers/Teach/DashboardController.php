<?php

namespace App\Http\Controllers\Teach;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teach\Concerns\FindsOwnClass;
use App\Support\Teach\TeachHome;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The instructor's home (the data is App\Support\Teach\TeachHome, shared with the app).
 */
class DashboardController extends Controller
{
    use FindsOwnClass;

    public function __invoke(Request $request, TeachHome $home): Response
    {
        return Inertia::render('Teach/Dashboard', $home->for($this->instructor($request)));
    }
}
