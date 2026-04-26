<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\AddCartItemRequest;
use App\Http\Requests\Customer\UpdateCartItemRequest;
use App\Http\Resources\CartItemResource;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $carts = $this->cartService->getUserCarts($request->user());

        return CartResource::collection($carts);
    }

    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $request->validated('tenant_id');

        // 既存カートに他テナントの商品を混在させないため、再利用時だけ所有権を確認する。
        $existingCart = $this->cartService->findUserCartForTenant($user, $tenantId);

        if ($existingCart) {
            $this->authorize('addItem', $existingCart);
        }
        // 新規作成側の整合性確認は FormRequest に寄せ、Controller は分岐判断だけに留める。

        $cartItem = $this->cartService->addItem(
            user: $user,
            tenantId: $tenantId,
            menuItemId: $request->validated('menu_item_id'),
            quantity: $request->validated('quantity'),
            optionIds: $request->validated('option_ids', [])
        );

        $cart = $this->cartService->getCartWithRelationsOrFail($cartItem->cart_id);

        return (new CartResource($cart))
            ->response()
            ->setStatusCode(201);
    }

    // カート商品を更新
    public function updateItem(UpdateCartItemRequest $request, CartItem $cartItem): CartItemResource
    {
        $this->authorize('update', $cartItem);

        $cartItem = $this->cartService->updateItem(
            cartItem: $cartItem,
            quantity: $request->validated('quantity'),
            optionIds: $request->validated('option_ids')
        );

        return new CartItemResource($cartItem);
    }

    // カート商品を削除
    public function removeItem(CartItem $cartItem): JsonResponse
    {
        $this->authorize('delete', $cartItem);

        $this->cartService->removeItem($cartItem);

        return response()->json(null, 204);
    }

    // カートを全削除
    public function clearCart(Cart $cart): JsonResponse
    {
        $this->authorize('delete', $cart);

        $this->cartService->clearCart($cart);

        return response()->json(null, 204);
    }
}
