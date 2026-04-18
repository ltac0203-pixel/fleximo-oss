import { Cart } from "@/types";
import { formatPrice } from "@/Utils/formatPrice";
import GeoSurface from "@/Components/GeoSurface";

interface OrderSummaryProps {
    cart: Cart;
}

// 支払い直前に注文内容を再確認できるよう、誤注文検知の最後の壁を作る。
export default function OrderSummary({ cart }: OrderSummaryProps) {
    return (
        <GeoSurface topAccent elevated className="p-4">
            <h2 className="text-lg font-semibold text-ink mb-4">注文内容</h2>

            {/* 店舗名を明示して、複数店舗利用時の支払い先誤認を防ぐ。 */}
            <div className="mb-4 flex items-center gap-2 border-b border-edge pb-4">
                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-sky-100">
                    <svg className="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                        />
                    </svg>
                </div>
                <span className="font-medium text-ink">{cart.tenant?.name}</span>
            </div>

            {/* 商品と数量を一覧化し、オプション漏れを送信前に確認できるようにする。 */}
            <div className="space-y-4">
                {cart.items.map((item) => (
                    <div key={item.id} className="flex justify-between items-start">
                        <div className="flex-1 min-w-0">
                            <div className="flex items-center gap-2">
                                <span className="text-ink truncate">{item.menu_item?.name}</span>
                                <span className="text-sm text-muted">x{item.quantity}</span>
                            </div>
                            {(item.options?.length ?? 0) > 0 && (
                                <div className="mt-1 text-xs text-muted line-clamp-2">
                                    {item.options?.map((opt) => opt?.name).join(", ")}
                                </div>
                            )}
                        </div>
                        <span className="text-ink font-medium">{formatPrice(item.subtotal)}</span>
                    </div>
                ))}
            </div>

            {/* 合計金額を下段で強調し、支払意思決定をしやすくする。 */}
            <div className="mt-4 border-t border-edge pt-4">
                <div className="flex justify-between items-center">
                    <span className="text-ink-light">小計（{cart.item_count}点）</span>
                    <span className="text-ink">{formatPrice(cart.total)}</span>
                </div>
                <div className="flex justify-between items-center mt-2">
                    <span className="text-lg font-semibold text-ink">合計</span>
                    <span className="text-xl font-bold text-sky-600">{formatPrice(cart.total)}</span>
                </div>
            </div>
        </GeoSurface>
    );
}
