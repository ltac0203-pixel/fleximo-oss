<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Tenant\BusinessHours\BusinessHoursSchedule;
use App\Enums\ReorderSkipReason;
use App\Exceptions\InvalidOptionSelectionException;
use App\Exceptions\ItemNotAvailableException;
use App\Models\Cart;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReorderService
{
    public function __construct(private CartService $cartService) {}

    // 注文履歴から再注文を実行する
    public function reorder(User $user, Order $order): array
    {
        $order->load('items.options');

        // 対象menu_item_idを一括取得（N+1防止）
        $menuItemIds = $order->items
            ->pluck('menu_item_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $menuItems = MenuItem::with('optionGroups.options')
            ->where('tenant_id', $order->tenant_id)
            ->whereIn('id', $menuItemIds)
            ->get()
            ->keyBy('id');

        // 既存カートの有無を確認
        $existingCart = $this->cartService->getOrCreateCart($user, $order->tenant_id);
        $hadExistingCartItems = $existingCart->items()->exists();

        $addedItems = [];
        $skippedItems = [];

        foreach ($order->items as $orderItem) {
            $result = $this->processOrderItem($user, $order, $orderItem, $menuItems);

            if ($result['status'] === 'added') {
                $addedItems[] = $result['data'];
            } else {
                $skippedItems[] = $result['data'];
            }
        }

        // 追加ゼロ件なら422エラー
        if (empty($addedItems)) {
            throw new HttpException(422, '再注文可能な商品がありません。');
        }

        // カート情報を再取得
        $cart = $this->cartService->getOrCreateCart($user, $order->tenant_id);
        $cart->load(['items.menuItem', 'items.options.option', 'tenant']);

        return [
            'added_items' => $addedItems,
            'skipped_items' => $skippedItems,
            'summary' => [
                'total_items_in_order' => $order->items->count(),
                'items_added' => count($addedItems),
                'items_skipped' => count($skippedItems),
                'had_existing_cart_items' => $hadExistingCartItems,
                'tenant_id' => $order->tenant_id,
                'tenant_name' => $order->tenant->name ?? '',
            ],
            'cart' => $this->formatCart($cart),
        ];
    }

    // 個別注文アイテムを処理する
    private function processOrderItem(
        User $user,
        Order $order,
        OrderItem $orderItem,
        Collection $menuItems,
    ): array {
        // menu_item_idがnull → 商品が削除済み
        if ($orderItem->menu_item_id === null) {
            return $this->skipResult($orderItem, ReorderSkipReason::MenuItemDeleted);
        }

        // MenuItemが見つからない → 削除済み
        $menuItem = $menuItems->get($orderItem->menu_item_id);
        if ($menuItem === null) {
            return $this->skipResult($orderItem, ReorderSkipReason::MenuItemDeleted);
        }

        // 販売可能かチェック
        if (! $menuItem->is_active) {
            return $this->skipResult($orderItem, ReorderSkipReason::Inactive);
        }
        if ($menuItem->is_sold_out) {
            return $this->skipResult($orderItem, ReorderSkipReason::SoldOut);
        }
        if (! $menuItem->isAvailableNow()) {
            return $this->skipResult($orderItem, ReorderSkipReason::OutsideTimeWindow);
        }

        // オプション解決
        $optionResult = $this->resolveOptions($orderItem, $menuItem);
        if ($optionResult['skip']) {
            return $this->skipResult($orderItem, $optionResult['reason']);
        }

        $resolvedOptionIds = $optionResult['option_ids'];

        // CartService::addItem() を呼び出し
        try {
            $this->cartService->addItem(
                $user,
                $order->tenant_id,
                $menuItem->id,
                $orderItem->quantity,
                $resolvedOptionIds,
            );
        } catch (ItemNotAvailableException) {
            return $this->skipResult($orderItem, ReorderSkipReason::SoldOut);
        } catch (InvalidOptionSelectionException) {
            return $this->skipResult($orderItem, ReorderSkipReason::OptionConstraintsChanged);
        }

        // 価格変更を記録
        $optionsAdded = [];
        $optionsSkipped = [];
        foreach ($orderItem->options as $orderOption) {
            $currentOption = null;
            if ($orderOption->option_id !== null) {
                foreach ($menuItem->optionGroups as $group) {
                    $currentOption = $group->options->firstWhere('id', $orderOption->option_id);
                    if ($currentOption) {
                        break;
                    }
                }
            }

            if ($currentOption && in_array($currentOption->id, $resolvedOptionIds)) {
                $optionsAdded[] = [
                    'name' => $orderOption->name,
                    'original_price' => $orderOption->price,
                    'current_price' => $currentOption->price,
                    'price_changed' => $orderOption->price !== $currentOption->price,
                ];
            } else {
                $optionsSkipped[] = $orderOption->name;
            }
        }

        return [
            'status' => 'added',
            'data' => [
                'order_item_name' => $orderItem->name,
                'menu_item_id' => $menuItem->id,
                'quantity' => $orderItem->quantity,
                'original_unit_price' => $orderItem->price,
                'current_unit_price' => $menuItem->price,
                'price_changed' => $orderItem->price !== $menuItem->price,
                'options_added' => $optionsAdded,
                'options_skipped' => $optionsSkipped,
            ],
        ];
    }

    // 注文時のオプションを現在のメニュー状態と照合する
    private function resolveOptions(
        OrderItem $orderItem,
        MenuItem $menuItem,
    ): array {
        if ($orderItem->options->isEmpty()) {
            // オプションなしの場合、必須グループがないか確認
            foreach ($menuItem->optionGroups as $group) {
                if ($group->required && $group->min_select > 0) {
                    return ['skip' => true, 'reason' => ReorderSkipReason::OptionConstraintsChanged];
                }
            }

            return ['skip' => false, 'option_ids' => []];
        }

        // 現在有効なオプションIDのセットを構築
        $validOptionIds = $menuItem->optionGroups
            ->flatMap(fn ($group) => $group->options->where('is_active', true)->pluck('id'))
            ->toArray();

        // 注文時のオプションを現在の状態に照合
        $resolvedOptionIds = [];
        foreach ($orderItem->options as $orderOption) {
            if ($orderOption->option_id !== null && in_array($orderOption->option_id, $validOptionIds)) {
                $resolvedOptionIds[] = $orderOption->option_id;
            }
        }

        // 必須グループの制約チェック
        foreach ($menuItem->optionGroups as $group) {
            $groupOptionIds = $group->options->pluck('id')->toArray();
            $selectedInGroup = array_intersect($resolvedOptionIds, $groupOptionIds);

            if ($group->required && count($selectedInGroup) < $group->min_select) {
                return ['skip' => true, 'reason' => ReorderSkipReason::OptionConstraintsChanged];
            }
        }

        return ['skip' => false, 'option_ids' => $resolvedOptionIds];
    }

    // スキップ結果を生成する
    private function skipResult(
        OrderItem $orderItem,
        ReorderSkipReason $reason,
    ): array {
        return [
            'status' => 'skipped',
            'data' => [
                'order_item_name' => $orderItem->name,
                'menu_item_id' => $orderItem->menu_item_id,
                'quantity' => $orderItem->quantity,
                'reason' => $reason->value,
                'reason_label' => $reason->label(),
            ],
        ];
    }

    // カート情報をフォーマットする
    private function formatCart(Cart $cart): array
    {
        return [
            'id' => $cart->id,
            'user_id' => $cart->user_id,
            'tenant_id' => $cart->tenant_id,
            'tenant' => $cart->tenant ? [
                'id' => $cart->tenant->id,
                'name' => $cart->tenant->name,
                'slug' => $cart->tenant->slug,
                'is_open' => (new BusinessHoursSchedule($cart->tenant->businessHours))->isOpenAt(now()),
            ] : null,
            'items' => $cart->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'menu_item' => [
                        'id' => $item->menuItem->id,
                        'name' => $item->menuItem->name,
                        'description' => $item->menuItem->description,
                        'price' => $item->menuItem->price,
                        'is_sold_out' => $item->menuItem->is_sold_out,
                    ],
                    'quantity' => $item->quantity,
                    'options' => $item->options->map(function ($opt) {
                        return [
                            'id' => $opt->option->id,
                            'name' => $opt->option->name,
                            'price' => $opt->option->price,
                        ];
                    })->values()->toArray(),
                    'subtotal' => ($item->menuItem->price + $item->options->sum(fn ($o) => $o->option->price)) * $item->quantity,
                ];
            })->values()->toArray(),
            'total' => $cart->items->sum(function ($item) {
                return ($item->menuItem->price + $item->options->sum(fn ($o) => $o->option->price)) * $item->quantity;
            }),
            'item_count' => $cart->items->count(),
            'is_empty' => $cart->items->isEmpty(),
        ];
    }
}
