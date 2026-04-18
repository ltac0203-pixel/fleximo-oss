import { CheckoutFailedProps } from "@/types";
import { Head, Link } from "@inertiajs/react";

export default function CheckoutFailed({ order, errorMessage }: CheckoutFailedProps) {
    return (
        <>
            <Head title="決済失敗" />

            <div className="min-h-screen bg-white">
                {/* ヘッダー を明示し、実装意図の誤読を防ぐ。 */}
                <header className="fixed top-0 left-0 right-0 z-30 bg-white border-b border-slate-200 ">
                    <div className="h-14 px-4 flex items-center justify-center max-w-lg lg:max-w-5xl mx-auto">
                        <h1 className="text-lg font-semibold text-slate-900">決済失敗</h1>
                    </div>
                </header>

                {/* メインコンテンツ を明示し、実装意図の誤読を防ぐ。 */}
                <main className="pt-14 pb-32 max-w-lg lg:max-w-5xl mx-auto px-4">
                    {/* 失敗アイコン を明示し、実装意図の誤読を防ぐ。 */}
                    <div className="flex flex-col items-center py-8">
                        <div className="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mb-4">
                            <svg
                                className="w-10 h-10 text-red-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </div>
                        <h2 className="text-2xl font-bold text-slate-900 mb-2">決済に失敗しました</h2>
                        <p className="text-slate-600 text-center">
                            {errorMessage || "決済処理中にエラーが発生しました"}
                        </p>
                    </div>

                    {/* エラー詳細 を明示し、実装意図の誤読を防ぐ。 */}
                    <div className="bg-red-50  p-4 mb-4">
                        <div className="flex items-start gap-3">
                            <svg
                                className="w-5 h-5 text-red-500 mt-0.5"
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
                            <div className="text-sm text-red-900">
                                <p className="font-medium mb-1">考えられる原因</p>
                                <ul className="list-disc list-inside text-red-700 space-y-1">
                                    <li>カード番号、有効期限、セキュリティコードに誤りがある</li>
                                    <li>ご利用限度額を超過している</li>
                                    <li>カードの有効期限が切れている</li>
                                    <li>一時的なネットワークエラー</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {/* 注文情報（存在する場合） を明示し、実装意図の誤読を防ぐ。 */}
                    {order && (
                        <div className="bg-white  border p-4 mb-4">
                            <h3 className="font-semibold text-slate-900 mb-3">注文情報</h3>
                            <div className="space-y-2 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-slate-600">注文番号</span>
                                    <span className="font-medium text-slate-900">{order.order_code}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-slate-600">ステータス</span>
                                    <span className="text-red-600">{order.status_label}</span>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* ヘルプ情報 を明示し、実装意図の誤読を防ぐ。 */}
                    <div className="bg-slate-100  p-4">
                        <div className="flex items-start gap-3">
                            <svg
                                className="w-5 h-5 text-slate-500 mt-0.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            <div className="text-sm text-slate-600">
                                <p className="font-medium text-slate-700 mb-1">お困りの場合</p>
                                <p>
                                    問題が解決しない場合は、別の決済方法をお試しいただくか、
                                    お使いのカード会社にお問い合わせください。
                                </p>
                            </div>
                        </div>
                    </div>
                </main>

                {/* フッター */}
                <div className="fixed bottom-0 left-0 right-0 z-30 bg-white border-t p-4">
                    <div className="max-w-lg lg:max-w-5xl mx-auto">
                        <Link
                            href={order ? route("order.menu", { tenant: order.tenant.slug }) : "/"}
                            className="block w-full py-3 px-6 bg-sky-500 hover:bg-sky-600 text-white font-semibold text-center"
                        >
                            メニューに戻る
                        </Link>
                    </div>
                </div>
            </div>
        </>
    );
}
