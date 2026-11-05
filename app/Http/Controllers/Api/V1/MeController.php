<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\UserPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The signed-in account: who it is and which sides it has (student, instructor). The app
 * calls it at start-up to decide what to show.
 */
class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(['user' => UserPresenter::forApp($request->user())]);
    }
}
