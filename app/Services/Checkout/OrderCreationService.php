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
use App\Models\OrderItemOption;
use App\Services\OrderNumberGenerator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

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

        // 営業日を基準に表示用注文番号を付与する
        $businessDate = $this->orderNumberGenerator->getBusinessDate();

        // ユニーク制約競合時は再採番して再試行し、注文の外枠を確実に確保する
        return $this->createOrderWithRetry($cart, $businessDate);
    }

    // 注文の合計金額を計算する
    // OrderItem::$subtotal アクセサが (price + options.sum(price)) * quantity を提供する
    public function calculateTotalAmount(Order $order): int
    {
        $order->load('items.options');

        return (int) $order->items->sum('subtotal');
    }

    // 注文番号の重複競合時に再採番して注文を作成する
    // 各 attempt は単一トランザクションでロック取得→注文作成→アイテム作成→合計計算までを
    // atomic に実行する。途中で例外が発生した場合はロールバックされ、Order が部分書き込みで残らない。
    // また verifyItemsWithLock / lockOptionsFromCart の lockForUpdate がトランザクション内で
    // 確実に効くよう、ロックもこのトランザクション内で取得する。
    private function createOrderWithRetry(Cart $cart, Carbon $businessDate): Order
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_ORDER_CODE_RETRIES; $attempt++) {
            $orderCode = $this->orderNumberGenerator->generate($cart->tenant_id, $businessDate);

            try {
                return DB::transaction(function () use ($cart, $orderCode, $businessDate) {
                    // 価格改ざん防止: メニューアイテムの現在価格をロック付きで再取得し、販売可能性を検証する
                    $lockedMenuItems = $this->verifyItemsWithLock($cart);

                    // 価格改ざん防止: オプションの現在価格もロック付きで再取得し、TOCTOU攻撃を防止する
                    $lockedOptions = $this->lockOptionsFromCart($cart);

                    $order = $this->createOrder($cart, $orderCode, $businessDate);

                    // メニュー変更に影響されないよう、注文時点の商品名・価格をスナップショットとして保存する
                    $this->createOrderItems($order, $cart, $lockedMenuItems, $lockedOptions);

                    // アイテム作成後に合計を算出することで、オプション価格を含む正確な金額を保証する
                    $order->total_amount = $this->calculateTotalAmount($order);
                    $order->save();
                    $order->refresh();

                    return $order;
                });
            } catch (UniqueConstraintViolationException $e) {
                if (! $this->isOrderCodeConstraint($e)) {
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

    // 衝突したユニーク制約が order_code 由来かどうかを制約名で判定する
    private function isOrderCodeConstraint(UniqueConstraintViolationException $e): bool
    {
        $message = $e->getMessage();

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
     * options は OrderItemOption::insert() で 1 クエリにまとめ N+1 INSERT を回避する。
     * Eloquent イベント（observer 等）は発火しないため、将来 OrderItemOption に
     * observer を追加する場合は createMany ベースに戻すか、bulk insert と
     * イベント再発火の両立を検討すること。
     *
     * @param  Collection<int, MenuItem>  $lockedMenuItems
     * @param  Collection<int, Option>  $lockedOptions
     */
    private function createOrderItems(Order $order, Cart $cart, Collection $lockedMenuItems, Collection $lockedOptions): void
    {
        $now = now();
        $optionRows = [];

        /** @var CartItem $cartItem */
        foreach ($cart->items as $cartItem) {
            /** @var MenuItem $menuItem */
            $menuItem = $lockedMenuItems->get($cartItem->menu_item_id);
            $orderItem = $this->createOrderItem($order, $cartItem, $menuItem);

            foreach ($cartItem->options as $cartItemOption) {
                /** @var Option $option */
                $option = $lockedOptions->get($cartItemOption->option_id);
                $optionRows[] = [
                    'order_item_id' => $orderItem->id,
                    'tenant_id' => $orderItem->tenant_id,
                    'option_id' => $cartItemOption->option_id,
                    'name' => $option->name,
                    'price' => $option->price,
                    'created_at' => $now,
                ];
            }
        }

        if (! empty($optionRows)) {
            OrderItemOption::insert($optionRows);
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
}
