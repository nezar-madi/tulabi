<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * tulabi.ly: a server-rendered page in the look of rqmana's landing page (resources/views/home.blade.php).
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user('web');

        return view('home', [
            'user' => $user instanceof User ? $user : null,
        ]);
    }
}
