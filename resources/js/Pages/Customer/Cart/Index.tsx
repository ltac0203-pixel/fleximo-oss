import CartSection from "@/Components/Customer/Cart/CartSection";
import CartSummary from "@/Components/Customer/Cart/CartSummary";
import GradientBackground from "@/Components/GradientBackground";
import Spinner from "@/Components/Loading/Spinner";
import ToastContainer from "@/Components/UI/ToastContainer";
import { useCart } from "@/Hooks/useCart";
import { useToast } from "@/Hooks/useToast";
import { CartPageProps, PageProps } from "@/types";
import ConfirmModal from "@/Components/ConfirmModal";
import { Head, router, usePage } from "@inertiajs/react";
import { useState, useCallback } from "react";

export default function CartIndex(_props: CartPageProps) {
    const { carts, isLoading, error, updateQuantity, removeItem, clearCart, getTotalItemCount, getGrandTotal } =
        useCart();

    const { toasts, showToast, hideToast } = useToast();
    const { flash } = usePage<PageProps>().props;

    const [confirmClearCartId, setConfirmClearCartId] = useState<number | null>(null);

    const handleClearCartRequest = useCallback((cartId: number) => {
        setConfirmClearCartId(cartId);
    }, []);

    const hasOpenTenantWithItems = carts.some((cart) => cart.tenant?.is_open !== false && cart.items.length > 0);

    const totalItemCount = getTotalItemCount();
    const grandTotal = getGrandTotal();
    const hasItems = totalItemCount > 0;

    const handleUpdateQuantity = (itemId: number, quantity: number) => {
        void updateQuantity(itemId, quantity).then((success) => {
            if (!success) {
                showToast({ type: "error", message: "数量の変更に失敗しました" });
            }
        });
    };

    const handleRemoveItem = (itemId: number) => {
        void removeItem(itemId).then((success) => {
            if (!success) {
                showToast({ type: "error", message: "商品の削除に失敗しました" });
            }
        });
    };

    const handleClearCart = (cartId: number) => {
        setConfirmClearCartId(null);
        void clearCart(cartId).then((success) => {
            if (!success) {
                showToast({ type: "error", message: "カートの削除に失敗しました" });
            }
        });
    };

    const handleCheckout = () => {
        if (!hasOpenTenantWithItems) return;
        router.visit(route("order.checkout.index"));
    };

    return (
        <>
            <Head title="カート" />

            <div className="relative min-h-screen bg-slate-50">
                <GradientBackground variant="customer" />

                {/* ヘッダー */}
                <header className="safe-top fixed top-0 left-0 right-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur-sm">
                    <div className="mx-auto flex h-14 max-w-lg items-center justify-between px-4 lg:max-w-5xl">
                        <button
                            onClick={() => window.history.back()}
                            className="text-slate-500 hover:text-sky-700"
                            aria-label="前のページに戻る"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M15 19l-7-7 7-7"
                                />
                            </svg>
                        </button>
                        <h1 className="text-lg font-semibold text-slate-900">カート</h1>
                        <div className="w-6" />
                    </div>
                </header>

                {/* メインコンテンツ */}
                <main className="relative mx-auto max-w-lg pt-14 pb-32 lg:max-w-5xl">
                    {/* ローディング */}
                    {isLoading && (
                        <div className="px-4 pt-6">
                            <div className="geo-surface flex items-center justify-center border-slate-200 bg-white/80 py-12">
                                <Spinner size="md" />
                            </div>
                        </div>
                    )}

                    {/* エラー表示: useCart 由来のエラーを優先し、なければ flash.error を出す。
                        同時に両方表示すると情報過多になるため単一表示に絞る。 */}
                    {error ? (
                        <div
                            className="geo-surface mx-4 mt-4 border-red-200 bg-red-50/80 p-4"
                            role="alert"
                        >
                            <p className="text-sm text-red-600">{error}</p>
                        </div>
                    ) : flash?.error ? (
                        <div
                            className="geo-surface mx-4 mt-4 border-amber-200 bg-amber-50/80 p-4"
                            role="alert"
                        >
                            <p className="text-sm text-amber-700">{flash.error}</p>
                        </div>
                    ) : null}

                    {/* 空カート */}
                    {!isLoading && !hasItems && (
                        <div className="px-4 pt-6">
                            <div className="geo-surface border-sky-200/80 bg-white/85 px-6 py-12 text-center">
                                <svg
                                    className="mx-auto mb-4 h-16 w-16 text-slate-300"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={1.5}
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                                    />
                                </svg>
                                <p className="text-center text-slate-600">カートに商品がありません</p>
                                <button
                                    onClick={() => window.history.back()}
                                    className="mt-5 border border-sky-600 bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-geo-sky hover:border-sky-700 hover:bg-sky-700"
                                >
                                    メニュー選択ページに戻る
                                </button>
                            </div>
                        </div>
                    )}

                    {/* カートセクション（テナント別） */}
                    {!isLoading && hasItems && (
                        <div className="space-y-4 px-4 pt-4">
                            <div className="geo-surface border-sky-200/80 bg-sky-50/50 px-4 py-3">
                                <div className="flex items-center justify-between gap-3">
                                    <p className="text-sm font-medium text-slate-700">現在のカート内容</p>
                                    <p className="text-sm font-semibold text-sky-700">
                                        {totalItemCount}点 / {grandTotal.toLocaleString()}円
                                    </p>
                                </div>
                            </div>
                            {carts.map((cart) => (
                                <CartSection
                                    key={cart.id}
                                    cart={cart}
                                    onUpdateQuantity={handleUpdateQuantity}
                                    onRemoveItem={handleRemoveItem}
                                    onClearCart={handleClearCartRequest}
                                    disabled={isLoading}
                                />
                            ))}
                        </div>
                    )}
                </main>

                <ConfirmModal
                    show={confirmClearCartId !== null}
                    onClose={() => setConfirmClearCartId(null)}
                    onConfirm={() => {
                        if (confirmClearCartId !== null) {
                            handleClearCart(confirmClearCartId);
                        }
                    }}
                    title="カート削除"
                    message="このお店のカートをすべて削除しますか？"
                />

                {/* チェックアウトサマリー */}
                {hasItems && (
                    <CartSummary
                        grandTotal={grandTotal}
                        itemCount={totalItemCount}
                        onCheckout={handleCheckout}
                        checkoutDisabled={isLoading || !hasOpenTenantWithItems}
                    />
                )}

                <ToastContainer toasts={toasts} onClose={hideToast} />
            </div>
        </>
    );
}
