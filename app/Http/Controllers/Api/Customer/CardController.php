<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\RegisterCardRequest;
use App\Http\Resources\CardResource;
use App\Models\Tenant;
use App\Services\FincodeCustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class CardController extends Controller
{
    public function __construct(
        private readonly FincodeCustomerService $customerService
    ) {}

    public function index(Request $request, Tenant $tenant): AnonymousResourceCollection
    {
        Gate::authorize('card.viewAny');

        $user = $request->user();
        $cards = $this->customerService->getCards($user, $tenant);

        return CardResource::collection($cards);
    }

    // カードを登録する
    public function store(RegisterCardRequest $request, Tenant $tenant): JsonResponse
    {
        Gate::authorize('card.create');

        $user = $request->user();

        $card = $this->customerService->registerCustomerWithCard(
            $user,
            $tenant,
            $request->validated('token'),
            $request->validated('is_default', true)
        );

        return response()->json([
            'data' => new CardResource($card),
            'message' => 'カードを登録しました。',
        ], 201);
    }

    // カードを削除する
    public function destroy(Request $request, Tenant $tenant, int $card): JsonResponse
    {
        Gate::authorize('card.delete');

        $user = $request->user();

        $deleted = $this->customerService->deleteCard($user, $tenant, $card);

        if (! $deleted) {
            return response()->json([
                'error' => [
                    'message' => 'カードが見つかりませんでした。',
                ],
            ], 404);
        }

        return response()->json([
            'message' => 'カードを削除しました。',
        ]);
    }
}
