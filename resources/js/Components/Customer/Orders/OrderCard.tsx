import { Link } from "@inertiajs/react";
import { useMemo } from "react";
import { OrderListItem } from "@/types";
import { formatCurrency } from "@/Utils/formatPrice";

interface OrderCardProps {
    order: OrderListItem;
    onReorder?: (orderId: number) => void;
    reorderLoadingOrderId?: number | null;
}

function formatDateTime(dateString: string, now: Date): string {
    const date = new Date(dateString);
    const isToday = date.toDateString() === now.toDateString();

    if (isToday) {
        return date.toLocaleTimeString("ja-JP", {
            hour: "2-digit",
            minute: "2-digit",
        });
    }

    return date.toLocaleDateString("ja-JP", {
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

export default function OrderCard({ order, onReorder, reorderLoadingOrderId = null }: OrderCardProps) {
    const now = useMemo(() => new Date(), []);
    const canReorder = order.status === "completed";
    const isReordering = reorderLoadingOrderId === order.id;

    return (
        <article className="bg-white border border-edge p-4">
            <Link href={route("order.orders.show", order.id)} className="block hover:text-inherit">
                <div className="mb-3">
                    <div className="text-lg font-bold text-ink">#{order.order_code}</div>
                    <div className="text-sm text-muted mt-0.5">{order.tenant.name}</div>
                </div>

                <div className="flex justify-between items-center text-sm">
                    <span className="text-muted">{formatDateTime(order.created_at, now)}</span>
                    <span className="font-semibold text-ink">{formatCurrency(order.total_amount)}</span>
                </div>
            </Link>

            {canReorder && (
                <div className="mt-3 border-t border-edge pt-3">
                    <button
                        type="button"
                        onClick={() => onReorder?.(order.id)}
                        disabled={!onReorder || isReordering}
                        aria-busy={isReordering || undefined}
                        className="w-full py-2.5 px-4 text-sm font-medium text-white bg-sky-500 rounded-lg hover:bg-sky-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors inline-flex items-center justify-center"
                    >
                        {isReordering ? (
                            <>
                                <span
                                    className="h-5 w-5 animate-spin rounded-full border-2 border-white/40 border-t-white"
                                    aria-hidden="true"
                                />
                                <span className="sr-only">処理中</span>
                            </>
                        ) : (
                            "もう一度注文する"
                        )}
                    </button>
                </div>
            )}
        </article>
    );
}
