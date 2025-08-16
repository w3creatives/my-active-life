<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

final class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard');
    }

    public function show()
    {
        return view('admin.show');
    }
}
