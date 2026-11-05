<?php

namespace App\Http\Controllers\Teach;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teach\Concerns\FindsOwnClass;
use App\Support\Teach\StudentRoster;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * «الطلاب» (the data is App\Support\Teach\StudentRoster, shared with the app). Joining,
 * removing, payments and password links keep their own actions.
 */
class StudentController extends Controller
{
    use FindsOwnClass;

    public function index(Request $request, StudentRoster $roster): Response
    {
        return Inertia::render('Teach/Students', [
            ...$roster->for($this->instructor($request), $request->query(), $request->url()),
            'resetLink' => $request->session()->get('resetLink'),
        ]);
    }
}
