<?php

namespace App\Http\Controllers\Api\V1\Teach;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teach\Concerns\FindsOwnClass;
use App\Support\Api\AppShape;
use App\Support\Teach\PaymentOverview;
use App\Support\Teach\StudentRoster;
use App\Support\Teach\TeachHome;
use App\Support\UserPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The instructor's lists in the app: the home screen, «الطلاب» and «المدفوعات». The data is the
 * website's (App\Support\Teach), so both always agree.
 */
class HomeController extends Controller
{
    use FindsOwnClass;

    public function home(Request $request, TeachHome $home): JsonResponse
    {
        $data = AppShape::of($this->plain($home->for($this->instructor($request))));

        return response()->json([
            ...$data,
            // Classes per status, always an object (an empty PHP array would be a JSON list).
            'counts' => (object) $data['counts'],
            'pending_requests' => UserPresenter::pendingRequests($request->user()),
        ]);
    }

    /** «الطلاب»: ?filter=active|past|all, q, course, page. */
    public function students(Request $request, StudentRoster $roster): JsonResponse
    {
        return response()->json(AppShape::of($this->plain(
            $roster->for($this->instructor($request), $request->query(), $request->url()),
        )));
    }

    /** «المدفوعات»: ?from, to (Y-m-d), course, method, status=valid|voided|all, q, page. */
    public function payments(Request $request, PaymentOverview $overview): JsonResponse
    {
        return response()->json(AppShape::of($this->plain(
            $overview->for($this->instructor($request), $request->query()),
        )));
    }

    /**
     * Collections and paginators as plain arrays (AppShape then fixes the links inside).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function plain(array $data): array
    {
        return json_decode((string) json_encode($data), true);
    }
}
