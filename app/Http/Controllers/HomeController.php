<?php

namespace App\Http\Controllers;

use App\Support\Currency;
use App\Support\TulabiClock;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Home', [
            'today' => TulabiClock::today(),
            'currency' => Currency::CODE,
        ]);
    }
}
