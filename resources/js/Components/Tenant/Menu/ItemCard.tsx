import ConfirmModal from "@/Components/ConfirmModal";
import { useMenuItemSoldOut } from "@/Hooks/useMenuItemSoldOut";
import { MenuItem } from "@/types";
import { formatPrice } from "@/Utils/formatPrice";
import { memo, useState } from "react";
import AvailabilityBadge from "./AvailabilityBadge";
import SoldOutBadge from "./SoldOutBadge";

interface ItemCardProps {
    item: MenuItem;
    onDelete?: (item: MenuItem) => void;
    onError?: (message: string) => void;
    onSoldOutToggle?: (message: string) => void;
    canManage?: boolean;
}

function ItemCard({ item, onDelete, onError, onSoldOutToggle, canManage = true }: ItemCardProps) {
    const { toggleSoldOut, toggling } = useMenuItemSoldOut({
        itemId: item.id,
        isSoldOut: item.is_sold_out,
        onSuccess: onSoldOutToggle,
        onError,
    });
    const [showSoldOutConfirm, setShowSoldOutConfirm] = useState(false);

    const executeToggleSoldOut = () => {
        setShowSoldOutConfirm(false);
        void toggleSoldOut();
    };

    return (
        <div className="bg-white border border-edge p-4">
            <div className="flex justify-between items-start">
                <div className="flex-1">
                    <div className="flex items-center gap-2 mb-2">
                        <h4 className="text-lg font-medium text-ink">{item.name}</h4>
                        <AvailabilityBadge isActive={item.is_active} />
                        <SoldOutBadge isSoldOut={item.is_sold_out} />
                    </div>

                    {item.description && <p className="text-sm text-muted mb-2">{item.description}</p>}

                    <p className="text-lg font-semibold text-ink">{formatPrice(item.price)}</p>

                    {item.categories && item.categories.length > 0 && (
                        <div className="mt-2 flex flex-wrap gap-1">
                            {item.categories.map((cat) => (
                                <span
                                    key={cat.id}
                                    className="inline-flex items-center px-2 py-0.5 text-xs bg-surface-dim text-ink-light"
                                >
                                    {cat.name}
                                </span>
                            ))}
                        </div>
                    )}
                </div>

                <div className="flex flex-col gap-2 ml-4">
                    <button
                        onClick={() => setShowSoldOutConfirm(true)}
                        disabled={toggling}
                        className={`px-3 py-1 text-sm border ${
                            item.is_sold_out
                                ? "border-green-300 text-green-700 hover:bg-green-50"
                                : "border-red-300 text-red-700 hover:bg-red-50"
                        } disabled:opacity-50`}
                    >
                        {item.is_sold_out ? "在庫復活" : "売り切れ"}
                    </button>

                    {canManage && (
                        <a
                            href={route("tenant.menu.items.edit", {
                                item: item.id,
                            })}
                            className="px-3 py-1 text-sm text-center text-primary-dark border border-primary hover:bg-sky-50"
                        >
                            編集
                        </a>
                    )}

                    {canManage && onDelete && (
                        <button
                            onClick={() => onDelete(item)}
                            className="px-3 py-1 text-sm text-red-600 border border-red-300 hover:bg-red-50"
                        >
                            削除
                        </button>
                    )}
                </div>
            </div>

            {/* 売り切れ切り替えの確認モーダル */}
            <ConfirmModal
                show={showSoldOutConfirm}
                onClose={() => setShowSoldOutConfirm(false)}
                onConfirm={executeToggleSoldOut}
                title={item.is_sold_out ? "在庫を復活しますか？" : "売り切れに設定しますか？"}
                message={
                    item.is_sold_out
                        ? `「${item.name}」を販売可能にします。顧客がこの商品を注文できるようになります。`
                        : `「${item.name}」を売り切れにします。顧客はこの商品を注文できなくなります。`
                }
                confirmLabel={item.is_sold_out ? "在庫を復活する" : "売り切れにする"}
                processing={toggling}
            />
        </div>
    );
}

export default memo(ItemCard);
