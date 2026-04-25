import CardPaymentForm from "@/Components/Customer/Checkout/CardPaymentForm";
import OrderSummary from "@/Components/Customer/Checkout/OrderSummary";
import PaymentMethodSelector from "@/Components/Customer/Checkout/PaymentMethodSelector";
import SavedCardSelector from "@/Components/Customer/Checkout/SavedCardSelector";
import ErrorBoundary from "@/Components/ErrorBoundary";
import ErrorFallback from "@/Components/ErrorFallback";
import GeoSurface from "@/Components/GeoSurface";
import GradientBackground from "@/Components/GradientBackground";
import Spinner from "@/Components/Loading/Spinner";
import BackButton from "@/Components/UI/BackButton";
import { useCheckout } from "@/Hooks/useCheckout";
import { CheckoutIndexProps } from "@/types";
import { formatPrice } from "@/Utils/formatPrice";
import { Head } from "@inertiajs/react";

export default function CheckoutIndex({ cart, fincodePublicKey, isProduction, savedCards }: CheckoutIndexProps) {
    const {
        paymentMethod,
        setPaymentMethod,
        isProcessing,
        error,
        isCheckoutDisabled,
        fincode,
        handleCheckout,
        savedCardId,
        setSavedCardId,
        saveCard,
        setSaveCard,
        saveAsDefault,
        setSaveAsDefault,
    } = useCheckout({
        cartId: cart.id,
        tenantId: cart.tenant_id,
        fincodePublicKey,
        isProduction,
        savedCards,
    });

    return (
        <>
            <Head title="お支払い" />

            <div className="relative min-h-screen bg-white">
                <GradientBackground variant="customer" />
                {/* ヘッダー */}
                <header className="safe-top fixed top-0 left-0 right-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur-sm">
                    <div className="h-14 px-4 flex items-center justify-between max-w-lg lg:max-w-5xl mx-auto">
                        <BackButton className="text-slate-500 hover:text-slate-700" />
                        <h1 className="text-lg font-semibold text-slate-900">お支払い</h1>
                        <div className="w-6" />
                    </div>
                </header>

                {/* メインコンテンツ */}
                <main className="relative pt-14 pb-32 max-w-lg lg:max-w-5xl mx-auto px-4 geo-fade-in">
                    <div className="space-y-4 pt-4">
                        {/* エラー表示 */}
                        {error && (
                            <GeoSurface className="border-red-200 bg-red-50 p-4">
                                <div className="flex items-center gap-2">
                                    <svg
                                        className="w-5 h-5 text-red-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    <span className="text-sm text-red-600">{error}</span>
                                </div>
                            </GeoSurface>
                        )}

                        {/* 注文内容サマリー */}
                        <OrderSummary cart={cart} />

                        {/* 決済方法選択 */}
                        <PaymentMethodSelector
                            selected={paymentMethod}
                            onChange={setPaymentMethod}
                            disabled={isProcessing}
                        />

                        {/* 新規カード入力フォーム */}
                        {paymentMethod === "new_card" && (
                            <ErrorBoundary fallback={ErrorFallback}>
                                <CardPaymentForm
                                    isReady={fincode.isReady}
                                    isLoading={fincode.isLoading}
                                    error={fincode.error}
                                    onMount={fincode.mountUI}
                                    onUnmount={fincode.unmountUI}
                                />
                                <GeoSurface className="space-y-3 p-4">
                                    <label className="flex items-start gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={saveCard}
                                            onChange={(e) => setSaveCard(e.target.checked)}
                                            disabled={isProcessing}
                                            className="w-4 h-4 mt-0.5 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                                        />
                                        <span className="text-sm text-slate-700 leading-tight">
                                            このカードを保存する
                                        </span>
                                    </label>

                                    {saveCard && (
                                        <label className="flex items-start gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={saveAsDefault}
                                                onChange={(e) => setSaveAsDefault(e.target.checked)}
                                                disabled={isProcessing}
                                                className="w-4 h-4 mt-0.5 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                                            />
                                            <span className="text-sm text-slate-700 leading-tight">
                                                このカードをメインにする
                                            </span>
                                        </label>
                                    )}
                                </GeoSurface>
                            </ErrorBoundary>
                        )}

                        {/* 保存済みカード選択 */}
                        {paymentMethod === "saved_card" && (
                            <SavedCardSelector
                                cards={savedCards}
                                selectedCardId={savedCardId}
                                onSelect={setSavedCardId}
                                disabled={isProcessing}
                                tenantSlug={cart.tenant?.slug}
                            />
                        )}
                    </div>
                </main>

                {/* フッター（確定ボタン） */}
                <div className="safe-bottom fixed bottom-0 left-0 right-0 z-30 border-t border-slate-200 bg-white/95 p-4 backdrop-blur-sm">
                    <div className="max-w-lg lg:max-w-5xl mx-auto">
                        <button
                            onClick={() => void handleCheckout()}
                            disabled={isCheckoutDisabled}
                            aria-busy={isProcessing}
                            aria-disabled={isCheckoutDisabled}
                            className={`
                                w-full border py-4 px-6 text-lg font-semibold transition
                                flex items-center justify-center gap-2
                                ${
                                    isCheckoutDisabled
                                        ? "cursor-not-allowed border-slate-200 bg-slate-200 text-slate-500"
                                        : "border-sky-600 bg-sky-600 text-white shadow-geo-sky hover:border-sky-700 hover:bg-sky-700"
                                }
                            `}
                        >
                            {isProcessing ? (
                                <>
                                    <Spinner size="sm" variant="white" label="処理中" />
                                </>
                            ) : (
                                <>
                                    <span>{formatPrice(cart.total)}を支払う</span>
                                </>
                            )}
                        </button>
                        <p
                            className="mt-2 text-xs text-center text-slate-500"
                            aria-live="polite"
                            aria-atomic="true"
                        >
                            {isProcessing
                                ? "処理しています。しばらくお待ちください。"
                                : "ボタンをタップすると、注文が確定します"}
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}
