<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    // 管理者ダッシュボードを表示する
    public function index(): Response
    {
        Gate::authorize('admin.access');

        return Inertia::render('Admin/Dashboard');
    }
}
