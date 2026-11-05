<?php

namespace App\Http\Controllers\Teach;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teach\Concerns\FindsOwnClass;
use App\Support\Teach\PaymentOverview;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * «المدفوعات» (the data is App\Support\Teach\PaymentOverview, shared with the app).
 */
class PaymentOverviewController extends Controller
{
    use FindsOwnClass;

    public function __invoke(Request $request, PaymentOverview $overview): Response
    {
        return Inertia::render('Teach/AllPayments', $overview->for($this->instructor($request), $request->query()));
    }
}
