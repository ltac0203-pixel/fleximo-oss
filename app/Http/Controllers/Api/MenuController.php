<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\PublicMenuService;
use Illuminate\Http\JsonResponse;

class MenuController extends Controller
{
    public function __construct(
        private readonly PublicMenuService $publicMenuService
    ) {}

    public function index(Tenant $tenant): JsonResponse
    {
        $menu = $this->publicMenuService->getMenu($tenant);

        return response()->json(['data' => $menu]);
    }
}
