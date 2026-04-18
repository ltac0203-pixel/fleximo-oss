<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Exceptions\InvalidOptionSelectionException;
use App\Exceptions\ItemNotAvailableException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartItemOption;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CartItemWriter
{
    public function __construct(
        private readonly CartQueryService $queryService,
        private readonly CartOptionValidator $validator,
    ) {}

    public function addItem(
        User $user,
        int $tenantId,
        int $menuItemId,
        int $quantity,
        array $optionIds = [],
    ): CartItem {
        try {
            $cartItem = DB::transaction(function () use ($user, $tenantId, $menuItemId, $quantity, $optionIds) {
                // 可用性チェックと追加処理の間でのTOCTOU競合を防ぐため、商品行をロックして再取得する
                $menuItem = MenuItem::where('tenant_id', $tenantId)
                    ->lockForUpdate()
                    ->findOrFail($menuItemId);

                // オプションの検証に必要なため、optionGroups.optionsをロードする
                $menuItem->load('optionGroups.options');

                // 販売時間外や売り切れの商品がカートに入ることを防ぐ
                $menuItem->ensureAvailableNow();

                // 不正なオプション組み合わせで注文が通ることを防ぐため、カート追加時点で検証する
                $this->validator->validateOptionsForMenuItem($menuItem, $optionIds);

                // テナント別にカートを分離するため、user+tenantの組み合わせで取得または新規作成する
                $cart = $this->queryService->getOrCreateCart($user, $tenantId);

                // カートアイテムとオプションを同一トランザクション内で作成し、不整合を防ぐ
                $cartItem = CartItem::create([
                    'cart_id' => $cart->id,
                    'tenant_id' => $tenantId,
                    'menu_item_id' => $menuItem->id,
                    'quantity' => $quantity,
                ]);

                if (! empty($optionIds)) {
                    $cartItem->options()->createMany(
                        array_map(
                            fn (int $optionId) => [
                                'tenant_id' => $tenantId,
                                'option_id' => $optionId,
                            ],
                            $optionIds,
                        ),
                    );
                }

                return $cartItem->load(['menuItem', 'options.option']);
            });

            Log::info('Cart item added', [
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'cart_item_id' => $cartItem->id,
                'menu_item_id' => $menuItemId,
                'quantity' => $quantity,
                'option_ids' => $optionIds,
            ]);

            return $cartItem;
        } catch (ItemNotAvailableException|InvalidOptionSelectionException $e) {
            Log::warning('Cart item add rejected by business rule', [
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'menu_item_id' => $menuItemId,
                'quantity' => $quantity,
                'option_ids' => $optionIds,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        } catch (Throwable $e) {
            Log::error('Failed to add cart item', [
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'menu_item_id' => $menuItemId,
                'quantity' => $quantity,
                'option_ids' => $optionIds,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function updateItem(
        CartItem $cartItem,
        ?int $quantity = null,
        ?array $optionIds = null,
    ): CartItem {
        $menuItem = $cartItem->menuItem;

        // 更新時点でも販売可能かを再チェックし、期限切れ商品の注文を防ぐ
        $menuItem->ensureAvailableNow();

        // オプション変更がある場合のみ検証を行い、数量だけの更新では不要な処理を省く
        if ($optionIds !== null) {
            $menuItem->load('optionGroups.options');
            $this->validator->validateOptionsForMenuItem($menuItem, $optionIds);
        }

        return DB::transaction(function () use ($cartItem, $quantity, $optionIds) {
            // nullの項目は変更しないため、指定されたフィールドのみ更新データに含める
            $updateData = [];
            if ($quantity !== null) {
                $updateData['quantity'] = $quantity;
            }
            if (! empty($updateData)) {
                $cartItem->update($updateData);
            }

            // オプションは差分更新ではなく洗い替え方式で管理する（部分更新だと不整合が生じやすいため）
            if ($optionIds !== null) {
                $cartItem->options()->delete();

                if (! empty($optionIds)) {
                    $cartItem->options()->createMany(
                        array_map(
                            fn (int $optionId) => [
                                'tenant_id' => $cartItem->tenant_id,
                                'option_id' => $optionId,
                            ],
                            $optionIds,
                        ),
                    );
                }
            }

            return $cartItem->fresh(['menuItem', 'options.option']);
        });
    }

    public function removeItem(CartItem $cartItem): void
    {
        try {
            DB::transaction(function () use ($cartItem) {
                // 外部キー制約に従い、子テーブル（オプション）を先に削除する
                $cartItem->options()->delete();
                $cartItem->delete();
            });

            Log::info('Cart item removed', [
                'cart_item_id' => $cartItem->id,
                'cart_id' => $cartItem->cart_id,
                'tenant_id' => $cartItem->tenant_id,
                'menu_item_id' => $cartItem->menu_item_id,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to remove cart item', [
                'cart_item_id' => $cartItem->id,
                'cart_id' => $cartItem->cart_id,
                'tenant_id' => $cartItem->tenant_id,
                'menu_item_id' => $cartItem->menu_item_id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function clearCart(Cart $cart): void
    {
        $itemCount = $cart->relationLoaded('items')
            ? $cart->items->count()
            : $cart->items()->count();

        try {
            DB::transaction(function () use ($cart) {
                $this->clearItems($cart);
                $cart->delete();
            });

            Log::info('Cart cleared', [
                'cart_id' => $cart->id,
                'user_id' => $cart->user_id,
                'tenant_id' => $cart->tenant_id,
                'item_count' => $itemCount,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to clear cart', [
                'cart_id' => $cart->id,
                'user_id' => $cart->user_id,
                'tenant_id' => $cart->tenant_id,
                'item_count' => $itemCount,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // カート内のアイテムのみをクリア（カート自体は削除しない）
    // チェックアウト後など、カートを再利用する場合に使用
    public function clearItems(Cart $cart): void
    {
        DB::transaction(function () use ($cart) {
            // N+1防止のため、既にロード済みならメモリ上のコレクションを使い、未ロードなら1クエリで取得する
            $itemIds = $cart->relationLoaded('items')
                ? $cart->items->pluck('id')
                : $cart->items()->pluck('id');

            if ($itemIds->isNotEmpty()) {
                // 外部キー制約に従い、子テーブル（オプション）→親テーブル（アイテム）の順で一括削除する
                CartItemOption::whereIn('cart_item_id', $itemIds)->delete();
                $cart->items()->delete();
            }
        });
    }
}
