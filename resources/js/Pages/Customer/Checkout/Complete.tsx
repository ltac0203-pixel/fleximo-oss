import { CheckoutCompleteProps, OrderStatusValue } from "@/types";
import { Head, Link } from "@inertiajs/react";
import StatusBadge from "@/Components/Customer/Orders/StatusBadge";
import OrderTimeline from "@/Components/Customer/Orders/OrderTimeline";
import OrderItemList from "@/Components/Customer/Orders/OrderItemList";
import OrderReadyNotifier from "@/Components/Customer/Orders/OrderReadyNotifier";
import GradientBackground from "@/Components/GradientBackground";
import { formatPrice } from "@/Utils/formatPrice";

type StatusSummaryTone = "info" | "success" | "warning" | "danger";

interface StatusSummary {
    headline: string;
    nextAction: string;
    tone: StatusSummaryTone;
}

const statusSummaries: Record<OrderStatusValue, StatusSummary> = {
    pending_payment: {
        headline: "現在、お支払い確認中です",
        nextAction: "決済完了後に注文受付へ進みます。",
        tone: "warning",
    },
    paid: {
        headline: "現在、注文を受け付けました",
        nextAction: "店舗で確認後、調理に進みます。",
        tone: "info",
    },
    accepted: {
        headline: "現在、店舗で注文内容を確認中です",
        nextAction: "確認が完了次第、商品の準備を開始します。",
        tone: "info",
    },
    in_progress: {
        headline: "現在、商品を準備中です",
        nextAction: "準備ができ次第、この画面でお知らせします。",
        tone: "info",
    },
    ready: {
        headline: "現在、商品の準備ができています",
        nextAction: "カウンターで注文番号をお伝えください。",
        tone: "success",
    },
    completed: {
        headline: "商品の受け取りが完了しました",
        nextAction: "ご利用ありがとうございました。",
        tone: "success",
    },
    cancelled: {
        headline: "この注文はキャンセルされました",
        nextAction: "詳細は店舗へお問い合わせください。",
        tone: "danger",
    },
    payment_failed: {
        headline: "決済に失敗しました",
        nextAction: "支払い方法を確認のうえ、再度お試しください。",
        tone: "danger",
    },
    refunded: {
        headline: "この注文は返金済みです",
        nextAction: "返金状況の詳細はご利用明細をご確認ください。",
        tone: "danger",
    },
};

const summaryToneStyles: Record<
    StatusSummaryTone,
    { container: string; headline: string; nextAction: string; label: string }
> = {
    info: {
        container: "bg-sky-50 border border-sky-200",
        headline: "text-sky-900",
        nextAction: "text-sky-700",
        label: "text-sky-700",
    },
    success: {
        container: "bg-green-50 border border-green-200",
        headline: "text-green-900",
        nextAction: "text-green-700",
        label: "text-green-700",
    },
    warning: {
        container: "bg-amber-50 border border-amber-200",
        headline: "text-amber-900",
        nextAction: "text-amber-700",
        label: "text-amber-700",
    },
    danger: {
        container: "bg-red-50 border border-red-200",
        headline: "text-red-900",
        nextAction: "text-red-700",
        label: "text-red-700",
    },
};

function getStatusSummary(status: OrderStatusValue, statusLabel: string): StatusSummary {
    return (
        statusSummaries[status] ?? {
            headline: `現在のステータス: ${statusLabel}`,
            nextAction: "注文状況をご確認ください。",
            tone: "info",
        }
    );
}

export default function CheckoutComplete({ order }: CheckoutCompleteProps) {
    const formatDateTime = (dateString: string) => {
        return new Date(dateString).toLocaleString("ja-JP", {
            month: "long",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    };

    return (
        <>
            <Head title="注文完了" />

            <div className="relative min-h-screen bg-white">
                <GradientBackground />
                {/* ヘッダー を明示し、実装意図の誤読を防ぐ。 */}
                <header className="fixed top-0 left-0 right-0 z-30 bg-white border-b border-slate-200 ">
                    <div className="h-14 px-4 flex items-center justify-center max-w-lg lg:max-w-5xl mx-auto">
                        <h1 className="text-lg font-semibold text-slate-900">注文完了</h1>
                    </div>
                </header>

                {/* メインコンテンツ を明示し、実装意図の誤読を防ぐ。 */}
                <OrderReadyNotifier
                    orderId={order.id}
                    orderCode={order.order_code}
                    initialStatus={order.status}
                    initialStatusLabel={order.status_label}
                >
                    {(polling) => {
                        const statusSummary = getStatusSummary(polling.status, polling.statusLabel);
                        const toneStyles = summaryToneStyles[statusSummary.tone];

                        return (
                            <main className="relative z-10 pt-14 pb-32 max-w-lg lg:max-w-5xl mx-auto px-4">
                                {/* 成功アイコン を明示し、実装意図の誤読を防ぐ。 */}
                                <div className="flex flex-col items-center py-8">
                                    <div className="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-4">
                                        <svg
                                            className="w-10 h-10 text-green-500"
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
                                    </div>
                                    <h2 className="text-2xl font-bold text-slate-900 mb-2">
                                        ご注文ありがとうございます
                                    </h2>
                                    <p className="text-slate-600 text-center">
                                        注文が正常に受け付けられました
                                    </p>
                                </div>

                                {/* 注文番号 を明示し、実装意図の誤読を防ぐ。 */}
                                <div className="bg-white border border-slate-200 p-5 mb-4 text-center">
                                    <p className="text-sm font-medium text-slate-600 mb-2">注文番号</p>
                                    <p className="text-4xl font-extrabold tracking-wide text-sky-600">
                                        #{order.order_code}
                                    </p>
                                </div>

                                {/* 注文状況要約 を明示し、実装意図の誤読を防ぐ。 */}
                                <div className={`p-4 mb-4 ${toneStyles.container}`}>
                                    <div className="flex items-center justify-between gap-3 mb-3">
                                        <span className={`text-sm font-medium ${toneStyles.label}`}>
                                            現在の注文状況
                                        </span>
                                        <StatusBadge
                                            status={polling.status}
                                            label={polling.statusLabel}
                                            size="md"
                                        />
                                    </div>
                                    <p className={`text-base font-semibold ${toneStyles.headline}`}>
                                        {statusSummary.headline}
                                    </p>
                                    <p className={`text-sm mt-1 ${toneStyles.nextAction}`}>
                                        {statusSummary.nextAction}
                                    </p>
                                </div>
                                {/* 注文詳細 を明示し、実装意図の誤読を防ぐ。 */}
                                <div className="bg-white border border-slate-200 p-4 mb-4">
                                    <h3 className="text-base font-semibold text-slate-900">注文詳細</h3>
                                    <div className="space-y-3 pt-4">
                                        <div className="flex justify-between items-center">
                                            <span className="text-slate-600">お店</span>
                                            <span className="font-medium text-slate-900">{order.tenant.name}</span>
                                        </div>
                                        <div className="flex justify-between items-center">
                                            <span className="text-slate-600">注文日時</span>
                                            <span className="text-slate-900">
                                                {formatDateTime(order.created_at)}
                                            </span>
                                        </div>
                                        <div className="flex justify-between items-center">
                                            <span className="text-slate-600">合計金額</span>
                                            <span className="font-bold text-slate-900">
                                                {formatPrice(order.total_amount)}
                                            </span>
                                        </div>
                                        {order.payment && (
                                            <div className="flex justify-between items-center">
                                                <span className="text-slate-600">お支払い方法</span>
                                                <span className="text-slate-900">
                                                    {order.payment.method_label}
                                                </span>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* 注文商品一覧 を明示し、実装意図の誤読を防ぐ。 */}
                                <div className="mb-4">
                                    <OrderItemList items={order.items} totalAmount={order.total_amount} />
                                </div>

                                {/* 注文ステータス を明示し、実装意図の誤読を防ぐ。 */}
                                <div className="mb-4">
                                    <OrderTimeline order={order} />
                                </div>

                                {/* 案内メッセージ を明示し、実装意図の誤読を防ぐ。 */}
                                {polling.isReady ? (
                                    <div className="bg-green-50 p-4 mb-4">
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
                                                    注文番号「{order.order_code}」の商品をカウンターにてお受け取りください。
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="bg-sky-50 p-4 mb-4">
                                        <div className="flex items-start gap-3">
                                            <svg
                                                className="w-5 h-5 text-sky-500 mt-0.5"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={2}
                                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                            <div className="text-sm text-sky-900">
                                                <p className="font-medium mb-1">
                                                    商品の準備ができましたらお知らせします
                                                </p>
                                                <p className="text-sky-700">
                                                    注文番号「{order.order_code}」をお控えください。商品の受け取り時に必要となります。
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </main>
                        );
                    }}
                </OrderReadyNotifier>

                {/* フッター を明示し、実装意図の誤読を防ぐ。 */}
                <div className="fixed bottom-0 left-0 right-0 z-30 bg-white border-t p-4">
                    <div className="max-w-lg lg:max-w-5xl mx-auto">
                        <Link
                            href={route("order.orders.show", {
                                order: order.id,
                            })}
                            className="block w-full py-3 px-6 bg-sky-500 hover:bg-sky-600 text-white font-semibold text-center  "
                        >
                            注文状況を確認する
                        </Link>
                    </div>
                </div>
            </div>
        </>
    );
}
