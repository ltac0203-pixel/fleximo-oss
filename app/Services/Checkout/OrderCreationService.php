<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOptionSelectionException;
use App\Exceptions\ItemNotAvailableException;
use App\Exceptions\OrderNumberGenerationException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\MenuItem;
use App\Models\Option;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderNumberGenerator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;

class OrderCreationService
{
    private const MAX_ORDER_CODE_RETRIES = 10;

    public function __construct(
        private readonly OrderNumberGenerator $orderNumberGenerator
    ) {}

    // カートから注文を作成する
    // 注文番号の生成、注文レコードの作成、注文アイテムのスナップショット作成、
    // 合計金額の計算を行う
    public function createFromCart(Cart $cart): Order
    {
        // N+1を防ぎ、以降のアイテム走査で追加クエリが発生しないようにする
        $cart->load(['items.menuItem', 'items.options.option']);

        // 価格改ざん防止: メニューアイテムの現在価格をロック付きで再取得し、販売可能性を検証する
        $lockedMenuItems = $this->verifyItemsWithLock($cart);

        // 価格改ざん防止: オプションの現在価格もロック付きで再取得し、TOCTOU攻撃を防止する
        $lockedOptions = $this->lockOptionsFromCart($cart);

        // 営業日を基準に表示用注文番号を付与する
        $businessDate = $this->orderNumberGenerator->getBusinessDate();

        // ユニーク制約競合時は再採番して再試行し、注文の外枠を確実に確保する
        $order = $this->createOrderWithRetry($cart, $businessDate);

        // メニュー変更に影響されないよう、注文時点の商品名・価格をスナップショットとして保存する
        // ロック取得した最新のメニューアイテム・オプション情報を使用し、TOCTOU攻撃を防止する
        $this->createOrderItems($order, $cart, $lockedMenuItems, $lockedOptions);

        // アイテム作成後に合計を算出することで、オプション価格を含む正確な金額を保証する
        $totalAmount = $this->calculateTotalAmount($order);
        $order->total_amount = $totalAmount;
        $order->save();
        $order->refresh();

        return $order;
    }

    // 注文の合計金額を計算する
    public function calculateTotalAmount(Order $order): int
    {
        $order->load('items.options');
        $total = 0;

        foreach ($order->items as $item) {
            $itemTotal = $item->price * $item->quantity;
            $optionsTotal = $item->options->sum('price') * $item->quantity;
            $total += $itemTotal + $optionsTotal;
        }

        return $total;
    }

    // 注文番号の重複競合時に再採番して注文を作成する
    private function createOrderWithRetry(Cart $cart, Carbon $businessDate): Order
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_ORDER_CODE_RETRIES; $attempt++) {
            $orderCode = $this->orderNumberGenerator->generate($cart->tenant_id, $businessDate);

            try {
                return $this->createOrder($cart, $orderCode, $businessDate);
            } catch (QueryException $e) {
                if (! $this->isOrderCodeDuplicateError($e)) {
                    throw $e;
                }

                $lastException = $e;

                if ($attempt < self::MAX_ORDER_CODE_RETRIES) {
                    // 同時リトライの再衝突を避けるため指数バックオフ with ジッターする
                    $maxBackoffMicroseconds = min(20000 * (2 ** ($attempt - 1)), 200000);
                    usleep(random_int(0, $maxBackoffMicroseconds));
                }
            }
        }

        throw new OrderNumberGenerationException(
            $cart->tenant_id,
            $businessDate,
            '最大リトライ回数を超えました',
            '',
            0,
            $lastException
        );
    }

    // 注文番号のユニーク制約違反かどうかを判定する
    private function isOrderCodeDuplicateError(QueryException $e): bool
    {
        $code = (string) $e->getCode();
        $message = $e->getMessage();

        $isUniqueViolation = $code === '23000'
            || $code === '23505'
            || str_contains($message, '1062')
            || str_contains($message, 'Duplicate entry')
            || str_contains($message, 'UNIQUE constraint failed')
            || str_contains($message, 'duplicate key value violates unique constraint');

        if (! $isUniqueViolation) {
            return false;
        }

        return str_contains($message, 'orders_tenant_business_code_unique')
            || str_contains($message, 'orders.tenant_id, orders.business_date, orders.order_code')
            || (str_contains($message, 'orders') && str_contains($message, 'order_code'));
    }

    // 注文を作成する
    // user_id, tenant_id, status, total_amount は$fillable外のため直接属性代入で設定する
    private function createOrder(Cart $cart, string $orderCode, Carbon $businessDate): Order
    {
        $order = new Order([
            'order_code' => $orderCode,
            'business_date' => $businessDate,
        ]);
        $order->user_id = $cart->user_id;
        $order->tenant_id = $cart->tenant_id;
        $order->status = OrderStatus::PendingPayment;
        $order->total_amount = 0; // アイテム作成後にオプション込みで正確に再計算するため、初期値は0
        $order->save();

        return $order;
    }

    /**
     * メニューアイテムの販売可能性をロック付きで検証し、最新の価格情報を返す
     *
     * @return Collection<int, MenuItem>
     */
    private function verifyItemsWithLock(Cart $cart): Collection
    {
        $menuItemIds = $cart->items->pluck('menu_item_id')->unique();

        $lockedMenuItems = MenuItem::whereIn('id', $menuItemIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        /** @var CartItem $item */
        foreach ($cart->items as $item) {
            $current = $lockedMenuItems->get($item->menu_item_id);
            if ($current === null) {
                throw new ItemNotAvailableException($item->menuItem ?? new MenuItem(['name' => $item->menu_item_id]));
            }
            if (! $current->isAvailableNow()) {
                throw new ItemNotAvailableException($current);
            }
        }

        return $lockedMenuItems;
    }

    /**
     * カート内の全オプションをロック付きで取得し、最新の価格情報を返す
     * 削除済みオプション等で欠けがあれば stale 情報をフォールバックせず即座に失敗させる
     *
     * @return Collection<int, Option>
     */
    private function lockOptionsFromCart(Cart $cart): Collection
    {
        $optionIds = $cart->items->flatMap(fn ($item) => $item->options->pluck('option_id'))->unique()->values();

        if ($optionIds->isEmpty()) {
            return new Collection;
        }

        $lockedOptions = Option::whereIn('id', $optionIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $missingIds = $optionIds->diff($lockedOptions->keys());
        if ($missingIds->isNotEmpty()) {
            throw new InvalidOptionSelectionException(
                'オプション',
                '選択されたオプションは現在利用できません。カートを確認してください。'
            );
        }

        return $lockedOptions;
    }

    /**
     * 注文アイテムを作成する（スナップショット）
     * ロック取得した最新のメニューアイテム・オプション情報を使用し、価格改ざんを防止する
     * verifyItemsWithLock / lockOptionsFromCart を通過済みのため lockedMenuItems / lockedOptions には
     * カート内の全 ID が必ず存在する不変条件を前提とする
     *
     * @param  Collection<int, MenuItem>  $lockedMenuItems
     * @param  Collection<int, Option>  $lockedOptions
     */
    private function createOrderItems(Order $order, Cart $cart, Collection $lockedMenuItems, Collection $lockedOptions): void
    {
        /** @var CartItem $cartItem */
        foreach ($cart->items as $cartItem) {
            /** @var MenuItem $menuItem */
            $menuItem = $lockedMenuItems->get($cartItem->menu_item_id);
            $orderItem = $this->createOrderItem($order, $cartItem, $menuItem);
            $this->createOrderItemOptions($orderItem, $cartItem, $lockedOptions);
        }
    }

    // 単一の注文アイテムを作成する
    // ロック取得した最新のMenuItemから名前・価格をスナップショットする
    private function createOrderItem(Order $order, CartItem $cartItem, MenuItem $menuItem): OrderItem
    {
        return OrderItem::create([
            'order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
            'menu_item_id' => $cartItem->menu_item_id,
            'name' => $menuItem->name,
            'price' => $menuItem->price,
            'quantity' => $cartItem->quantity,
        ]);
    }

    // 注文アイテムオプションを作成する
    // ロック取得した最新のOptionから名前・価格をスナップショットし、TOCTOU攻撃を防止する
    // lockOptionsFromCart で事前検証済みのため lockedOptions には必ず該当 Option が存在する
    private function createOrderItemOptions(OrderItem $orderItem, CartItem $cartItem, Collection $lockedOptions): void
    {
        if ($cartItem->options->isEmpty()) {
            return;
        }

        $rows = $cartItem->options->map(function ($cartItemOption) use ($lockedOptions, $orderItem) {
            /** @var Option $option */
            $option = $lockedOptions->get($cartItemOption->option_id);

            return [
                'tenant_id' => $orderItem->tenant_id,
                'option_id' => $cartItemOption->option_id,
                'name' => $option->name,
                'price' => $option->price,
            ];
        })->all();

        $orderItem->options()->createMany($rows);
    }
}
