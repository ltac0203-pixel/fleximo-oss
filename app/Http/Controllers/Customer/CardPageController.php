<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\CardResource;
use App\Models\Tenant;
use App\Services\FincodeCustomerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CardPageController extends Controller
{
    public function __construct(
        protected FincodeCustomerService $customerService
    ) {}

    public function index(Request $request, Tenant $tenant): Response
    {
        $user = $request->user();
        $cards = $this->customerService->getCards($user, $tenant);

        $fincodePublicKey = config('fincode.public_key');
        if (empty($fincodePublicKey)) {
            \Log::error('Fincode public key is not configured. Card management page will be non-functional.', [
                'config_cached' => app()->configurationIsCached(),
                'user_id' => $request->user()->id,
            ]);
        }

        return Inertia::render('Customer/Cards/Index', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'cards' => CardResource::collection($cards)->resolve(),
            'fincodePublicKey' => $fincodePublicKey ?? '',
            'isProduction' => (bool) config('fincode.is_production'),
        ]);
    }
}
