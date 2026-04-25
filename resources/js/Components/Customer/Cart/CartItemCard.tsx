import { CartItem } from "@/types";
import { formatPrice } from "@/Utils/formatPrice";
import CartItemOptions from "./CartItemOptions";
import QuantityControl from "./QuantityControl";

interface CartItemCardProps {
    item: CartItem;
    onUpdateQuantity: (itemId: number, quantity: number) => void;
    onRemove: (itemId: number) => void;
    disabled?: boolean;
}

export default function CartItemCard({ item, onUpdateQuantity, onRemove, disabled = false }: CartItemCardProps) {
    const handleIncrease = () => {
        onUpdateQuantity(item.id, item.quantity + 1);
    };

    const handleDecrease = () => {
        if (item.quantity > 1) {
            onUpdateQuantity(item.id, item.quantity - 1);
        }
    };

    const handleRemove = () => {
        onRemove(item.id);
    };

    return (
        <div
            className={`geo-surface border p-2 sm:p-3 ${
                disabled
                    ? "border-edge bg-surface-dim/70"
                    : "geo-hover-frame border-edge bg-white"
            }`}
        >
            <div className="flex items-start gap-2 sm:gap-3">
                {/* 商品情報 */}
                <div className="flex-1 min-w-0">
                    <h3 className="line-clamp-2 text-sm font-semibold text-ink sm:text-base">{item.menu_item.name}</h3>
                    {item.menu_item.description && (
                        <p className="mt-1 line-clamp-1 text-xs text-muted">{item.menu_item.description}</p>
                    )}
                    <CartItemOptions options={item.options} />
                    <div className="mt-2 flex items-center gap-2">
                        <p className="text-sm font-semibold text-sky-700">{formatPrice(item.subtotal)}</p>
                        <span className="text-xs text-muted-light">
                            ({formatPrice(item.menu_item.price)} x {item.quantity})
                        </span>
                    </div>
                </div>

                {/* 数量コントロール */}
                <div className="flex-shrink-0">
                    <QuantityControl
                        quantity={item.quantity}
                        onIncrease={handleIncrease}
                        onDecrease={handleDecrease}
                        onRemove={handleRemove}
                        disabled={disabled}
                    />
                </div>
            </div>

            {/* 売り切れ表示 */}
            {item.menu_item.is_sold_out && (
                <div className="mt-2 border border-rose-200 bg-rose-50/80 px-2 py-1 text-xs text-rose-700">
                    この商品は売り切れです
                </div>
            )}
        </div>
    );
}
