import { formatPrice } from "@/Utils/formatPrice";
import { Link } from "@inertiajs/react";

interface CartSummaryProps {
    grandTotal: number;
    itemCount: number;
    onCheckout?: () => void;
    checkoutDisabled?: boolean;
    checkoutUrl?: string;
}

export default function CartSummary({
    grandTotal,
    itemCount,
    onCheckout,
    checkoutDisabled = false,
    checkoutUrl,
}: CartSummaryProps) {
    const buttonContent = (
        <>
            <span>注文手続きへ</span>
            <span className="font-semibold">{formatPrice(grandTotal)}</span>
        </>
    );

    const buttonClassName = `flex w-full items-center justify-between border px-5 py-3.5 text-sm font-semibold sm:text-base ${
        checkoutDisabled
            ? "cursor-not-allowed border-edge-strong bg-edge-strong text-muted"
            : "border-sky-600 bg-sky-600 text-white shadow-geo-sky hover:border-sky-700 hover:bg-sky-700"
    }`;

    return (
        <div className="safe-bottom fixed bottom-0 left-0 right-0 z-30 border-t border-edge bg-white/95 p-4 backdrop-blur-sm">
            <div className="mx-auto max-w-lg lg:max-w-6xl">
                {/* 合計情報 */}
                <div className="geo-surface border-edge bg-white/95 p-3">
                    <div className="mb-3 flex items-center justify-between text-sm">
                        <span className="text-ink-light">合計（{itemCount}点）</span>
                        <span className="font-semibold text-ink">{formatPrice(grandTotal)}</span>
                    </div>

                    {/* チェックアウトボタン */}
                    {checkoutUrl ? (
                        <Link href={checkoutUrl} className={buttonClassName} preserveScroll>
                            {buttonContent}
                        </Link>
                    ) : (
                        <button type="button" onClick={onCheckout} disabled={checkoutDisabled} className={buttonClassName}>
                            {buttonContent}
                        </button>
                    )}

                    {/* 注意文言 */}
                    <p className="mt-2 text-center text-xs text-muted-light">税込価格で表示しています</p>
                </div>
            </div>
        </div>
    );
}
