import { Head, Link, router } from "@inertiajs/react";
import { OrdersIndexPageProps } from "@/types";
import OrderCard from "@/Components/Customer/Orders/OrderCard";
import GradientBackground from "@/Components/GradientBackground";
import ReorderResultModal from "@/Components/Customer/Orders/ReorderResultModal";
import { useReorder } from "@/Hooks/useReorder";
import { useState } from "react";

export default function Index({ orders }: OrdersIndexPageProps) {
    const { reorder, isLoading, error, result, clearResult } = useReorder();
    const [activeReorderOrderId, setActiveReorderOrderId] = useState<number | null>(null);

    const handleLoadMore = () => {
        if (orders.current_page < orders.last_page) {
            router.get(
                route("order.orders.index"),
                {
                    page: orders.current_page + 1,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    only: ["orders"],
                },
            );
        }
    };

    const handleReorder = async (orderId: number) => {
        if (activeReorderOrderId !== null) {
            return;
        }

        setActiveReorderOrderId(orderId);

        try {
            await reorder(orderId);
        } finally {
            setActiveReorderOrderId(null);
        }
    };

    return (
        <>
            <Head title="注文履歴" />

            <div className="relative min-h-screen bg-white">
                <GradientBackground />

                {/* Fixed Header */}
                <header className="fixed top-0 left-0 right-0 z-30 bg-white border-b border-slate-200">
                    <div className="h-14 px-4 flex items-center justify-between max-w-lg lg:max-w-5xl mx-auto">
                        <Link href={route("dashboard")} className="text-slate-500 hover:text-slate-700">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M15 19l-7-7 7-7"
                                />
                            </svg>
                        </Link>
                        <h1 className="text-lg font-semibold text-slate-900">注文履歴</h1>
                        <div className="w-6" />
                    </div>
                </header>

                {/* Main Content */}
                <main className="relative z-10 pt-14 pb-8 max-w-lg lg:max-w-5xl mx-auto px-4">
                    {orders.data.length === 0 ? (
                        <div className="text-center py-12">
                            <svg
                                className="w-16 h-16 mx-auto text-slate-300 mb-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={1.5}
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                />
                            </svg>
                            <p className="text-slate-500">注文履歴がありません</p>
                        </div>
                    ) : (
                        <>
                            <div className="grid grid-cols-1 gap-3 lg:grid-cols-2 lg:gap-4">
                                {orders.data.map((order) => (
                                    <OrderCard
                                        key={order.id}
                                        order={order}
                                        onReorder={(orderId) => void handleReorder(orderId)}
                                        reorderLoadingOrderId={isLoading ? activeReorderOrderId : null}
                                    />
                                ))}
                            </div>

                            {error && (
                                <p className="mt-4 text-sm text-red-600 text-center">{error}</p>
                            )}

                            {orders.current_page < orders.last_page && (
                                <div className="mt-6 text-center">
                                    <button
                                        onClick={handleLoadMore}
                                        className="border border-slate-200 bg-white px-6 py-2 text-sm font-medium text-slate-700 hover:border-slate-300 hover:bg-slate-50"
                                    >
                                        もっと見る
                                    </button>
                                </div>
                            )}

                            <div className="mt-4 text-center text-sm text-slate-500">
                                {orders.total}件中 {orders.from}〜{orders.to}
                                件を表示
                            </div>
                        </>
                    )}

                    <ReorderResultModal result={result} onClose={clearResult} />
                </main>
            </div>
        </>
    );
}
