<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * What the app asks before anything else: the API version, whether it must or may update,
 * and the store links. Always answers (no sign-in, no version check).
 */
class MetaController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'api_version' => 1,
            'min_version' => config('tulabi.app.min_version'),
            'latest_version' => config('tulabi.app.latest_version'),
            'store_urls' => config('tulabi.app.store_urls'),
            'timezone' => config('tulabi.timezone'),
        ]);
    }
}
