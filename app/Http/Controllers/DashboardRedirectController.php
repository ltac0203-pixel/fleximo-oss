<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        return match ($user->role) {
            UserRole::Admin => redirect()->route('admin.dashboard'),
            UserRole::TenantAdmin => redirect()->route('tenant.dashboard'),
            UserRole::TenantStaff => redirect()->route('tenant.dashboard'),
            default => redirect()->route('customer.home'),
        };
    }
}
