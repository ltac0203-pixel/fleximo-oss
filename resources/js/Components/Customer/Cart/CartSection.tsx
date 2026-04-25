import { Cart } from "@/types";
import CartItemCard from "./CartItemCard";
import TenantStatusBadge from "@/Components/Customer/Common/TenantStatusBadge";
import { formatPrice } from "@/Utils/formatPrice";

interface CartSectionProps {
    cart: Cart;
    onUpdateQuantity: (itemId: number, quantity: number) => void;
    onRemoveItem: (itemId: number) => void;
    onClearCart: (cartId: number) => void;
    disabled?: boolean;
}

export default function CartSection({
    cart,
    onUpdateQuantity,
    onRemoveItem,
    onClearCart,
    disabled = false,
}: CartSectionProps) {
    const tenantName = cart.tenant?.name || "店舗";
    const isOpen = cart.tenant?.is_open !== false;
    const isOrderPaused = cart.tenant?.is_order_paused === true;
    const todayHours = cart.tenant?.today_business_hours ?? [];
    const businessHours = todayHours.length === 0 ? null : todayHours.map((hours) => `${hours.open_time}〜${hours.close_time}`).join(" / ");

    return (
        <section
            className={`geo-surface p-4 lg:p-5 ${
                isOpen ? "border-sky-200/80 bg-white/90" : "border-edge bg-surface/80"
            }`}
        >
            {/* テナントヘッダー */}
            <div className="flex items-start justify-between gap-4">
                <div>
                    <div className="flex items-center gap-2">
                        <h2 className="text-base font-semibold text-ink">{tenantName}</h2>
                        {isOrderPaused && <TenantStatusBadge isOpen={false} isOrderPaused size="sm" />}
                        {!isOpen && !isOrderPaused && <TenantStatusBadge isOpen={false} size="sm" />}
                    </div>
                    {businessHours && (
                        <p className="mt-1 text-xs text-muted">本日の営業時間: {businessHours}</p>
                    )}
                </div>
                <button
                    type="button"
                    onClick={() => onClearCart(cart.id)}
                    disabled={disabled}
                    className="border border-red-200 bg-red-50 px-2 py-1 text-xs font-medium text-red-600 hover:border-red-300 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    すべて削除
                </button>
            </div>

            {isOrderPaused && isOpen && (
                <div className="mt-3 border border-amber-200 bg-amber-50/80 px-3 py-2 text-sm text-amber-700">
                    現在、注文の受付を一時停止しています。しばらくしてからお試しください。
                </div>
            )}

            {isOpen ? (
                <>
                    {/* カート商品リスト */}
                    <div className="mt-4 space-y-3">
                        {cart.items.map((item) => (
                            <CartItemCard
                                key={item.id}
                                item={item}
                                onUpdateQuantity={onUpdateQuantity}
                                onRemove={onRemoveItem}
                                disabled={disabled}
                            />
                        ))}
                    </div>

                    {/* テナント小計 */}
                    <div className="geo-divider mt-4 flex items-center justify-between pt-3 text-sm">
                        <span className="text-ink-light">小計（{cart.item_count}点）</span>
                        <span className="font-semibold text-ink">{formatPrice(cart.total)}</span>
                    </div>
                </>
            ) : (
                <div className="mt-4 border border-edge bg-surface-dim/70 px-3 py-4">
                    <p className="text-sm text-muted">営業時間外のため、カートの内容を表示できません</p>
                    {businessHours && (
                        <p className="mt-2 text-xs text-muted-light">営業時間: {businessHours}</p>
                    )}
                </div>
            )}
        </section>
    );
}
