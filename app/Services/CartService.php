<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\MenuItem;
use App\Models\User;
use App\Services\Cart\CartItemWriter;
use App\Services\Cart\CartOptionValidator;
use App\Services\Cart\CartQueryService;
use Illuminate\Database\Eloquent\Collection;

class CartService
{
    public function __construct(
        private readonly CartQueryService $queryService,
        private readonly CartItemWriter $writer,
        private readonly CartOptionValidator $validator,
    ) {}

    public function findUserCartForTenant(User $user, int $tenantId): ?Cart
    {
        return $this->queryService->findUserCartForTenant($user, $tenantId);
    }

    public function getCartWithRelationsOrFail(int $cartId): Cart
    {
        return $this->queryService->getCartWithRelationsOrFail($cartId);
    }

    public function getUserCarts(User $user): Collection
    {
        return $this->queryService->getUserCarts($user);
    }

    public function getOrCreateCart(User $user, int $tenantId): Cart
    {
        return $this->queryService->getOrCreateCart($user, $tenantId);
    }

    public function getCheckoutCart(User $user): ?Cart
    {
        return $this->queryService->getCheckoutCart($user);
    }

    public function addItem(
        User $user,
        int $tenantId,
        int $menuItemId,
        int $quantity,
        array $optionIds = [],
    ): CartItem {
        return $this->writer->addItem($user, $tenantId, $menuItemId, $quantity, $optionIds);
    }

    public function updateItem(
        CartItem $cartItem,
        ?int $quantity = null,
        ?array $optionIds = null,
    ): CartItem {
        return $this->writer->updateItem($cartItem, $quantity, $optionIds);
    }

    public function removeItem(CartItem $cartItem): void
    {
        $this->writer->removeItem($cartItem);
    }

    public function clearCart(Cart $cart): void
    {
        $this->writer->clearCart($cart);
    }

    public function clearItems(Cart $cart): void
    {
        $this->writer->clearItems($cart);
    }

    public function validateOptionsForMenuItem(MenuItem $menuItem, array $optionIds): void
    {
        $this->validator->validateOptionsForMenuItem($menuItem, $optionIds);
    }
}
