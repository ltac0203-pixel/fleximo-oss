import { Head, Link } from "@inertiajs/react";
import { OrderShowPageProps } from "@/types";
import StatusBadge from "@/Components/Customer/Orders/StatusBadge";
import OrderTimeline from "@/Components/Customer/Orders/OrderTimeline";
import OrderItemList from "@/Components/Customer/Orders/OrderItemList";
import OrderReadyNotifier from "@/Components/Customer/Orders/OrderReadyNotifier";
import GradientBackground from "@/Components/GradientBackground";
import Spinner from "@/Components/Loading/Spinner";
import { useReorder } from "@/Hooks/useReorder";
import ReorderResultModal from "@/Components/Customer/Orders/ReorderResultModal";

function formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString("ja-JP", {
        year: "numeric",
        month: "long",
        day: "numeric",
        weekday: "short",
    });
}

function formatDateTime(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleString("ja-JP", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

export default function Show({ order }: OrderShowPageProps) {
    const { reorder, isLoading, error, result, clearResult } = useReorder();

    return (
        <>
            <Head title={`注文 #${order.order_code}`} />

            <div className="relative min-h-screen bg-white">
                <GradientBackground />

                {/* Fixed Header を明示し、実装意図の誤読を防ぐ。 */}
                <header className="fixed top-0 left-0 right-0 z-30 bg-white border-b border-slate-200">
                    <div className="h-14 px-4 flex items-center justify-between max-w-lg lg:max-w-5xl mx-auto">
                        <Link href={route("order.orders.index")} className="text-slate-500 hover:text-slate-700">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M15 19l-7-7 7-7"
                                />
                            </svg>
                        </Link>
                        <h1 className="text-lg font-semibold text-slate-900">#{order.order_code}</h1>
                        <div className="w-6" />
                    </div>
                </header>

                {/* Main Content を明示し、実装意図の誤読を防ぐ。 */}
                <OrderReadyNotifier
                    orderId={order.id}
                    orderCode={order.order_code}
                    initialStatus={order.status}
                    initialStatusLabel={order.status_label}
                >
                    {(polling) => (
                        <main className="pt-14 pb-8 max-w-lg lg:max-w-5xl mx-auto px-4">
                            {/* Order Header を明示し、実装意図の誤読を防ぐ。 */}
                            <div className="bg-white border border-slate-200 p-4 mt-4">
                                <div className="flex justify-between items-start mb-3">
                                    <div>
                                        <h2 className="text-lg font-bold text-slate-900">{order.tenant.name}</h2>
                                        {order.tenant.address && (
                                            <p className="text-sm text-slate-500 mt-0.5">{order.tenant.address}</p>
                                        )}
                                    </div>
                                    <StatusBadge
                                        status={polling.status}
                                        label={polling.statusLabel}
                                        size="md"
                                    />
                                </div>

                                <div className="border-t border-slate-100 pt-3 mt-3">
                                    <div className="grid grid-cols-2 gap-2 text-sm">
                                        <div>
                                            <span className="text-slate-500">注文日時</span>
                                            <p className="font-medium text-slate-900">
                                                {formatDateTime(order.created_at)}
                                            </p>
                                        </div>
                                        <div>
                                            <span className="text-slate-500">営業日</span>
                                            <p className="font-medium text-slate-900">
                                                {formatDate(order.business_date)}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {order.payment && (
                                    <div className="border-t border-slate-100 pt-3 mt-3">
                                        <div className="text-sm">
                                            <span className="text-slate-500">お支払い方法</span>
                                            <p className="font-medium text-slate-900">
                                                {order.payment.method_label}
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* 準備完了バナー */}
                            {polling.isReady && (
                                <div className="bg-green-50 p-4 mt-4">
                                    <div className="flex items-start gap-3">
                                        <svg
                                            className="w-5 h-5 text-green-600 mt-0.5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth={2}
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                        <div className="text-sm text-green-900">
                                            <p className="font-medium mb-1">準備ができました！</p>
                                            <p className="text-green-700">
                                                注文番号「{order.order_code}
                                                」の商品をカウンターにてお受け取りください。
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Timeline を明示し、実装意図の誤読を防ぐ。 */}
                            <div className="mt-4">
                                <OrderTimeline order={order} />
                            </div>

                            {/* Order Items を明示し、実装意図の誤読を防ぐ。 */}
                            <div className="mt-4">
                                <OrderItemList items={order.items} totalAmount={order.total_amount} />
                            </div>

                            {/* 再注文ボタン（completedのみ） */}
                            {order.status === "completed" && (
                                <div className="mt-4">
                                    <button
                                        type="button"
                                        onClick={() => void reorder(order.id)}
                                        disabled={isLoading}
                                        aria-busy={isLoading || undefined}
                                        className="w-full py-3 px-4 text-sm font-medium text-white bg-sky-500 rounded-lg hover:bg-sky-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors inline-flex items-center justify-center"
                                    >
                                        {isLoading ? (
                                            <>
                                                <Spinner size="sm" variant="white" label="処理中" />
                                            </>
                                        ) : (
                                            "もう一度注文する"
                                        )}
                                    </button>
                                    {error && (
                                        <p className="mt-2 text-sm text-red-600 text-center">{error}</p>
                                    )}
                                </div>
                            )}

                            {/* 再注文結果モーダル */}
                            <ReorderResultModal result={result} onClose={clearResult} />
                        </main>
                    )}
                </OrderReadyNotifier>
            </div>
        </>
    );
}
