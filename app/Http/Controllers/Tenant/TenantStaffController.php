<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StaffService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantStaffController extends Controller
{
    public function __construct(
        private StaffService $staffService
    ) {}

    // スタッフ管理ページを表示する
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $tenant = $request->user()->getTenant();

        return Inertia::render('Tenant/Staff/Index', [
            'staff' => $this->staffService->getStaffListForPage($tenant),
        ]);
    }
}
