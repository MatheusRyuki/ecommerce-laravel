<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the administrative dashboard.
     */
    public function __invoke(Request $request): View
    {
        return view('admin.dashboard', [
            'admin' => $request->user(),
        ]);
    }
}
