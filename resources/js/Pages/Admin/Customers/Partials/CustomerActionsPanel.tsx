import { CustomerDetail } from "@/types";

interface CustomerActionsPanelProps {
    customer: CustomerDetail;
    onOpenSuspendModal: () => void;
    onOpenBanModal: () => void;
    onOpenReactivateModal: () => void;
    onOpenExportModal: () => void;
}

export default function CustomerActionsPanel({
    customer,
    onOpenSuspendModal,
    onOpenBanModal,
    onOpenReactivateModal,
    onOpenExportModal,
}: CustomerActionsPanelProps) {
    const getStatusBadgeClass = (color: string) => {
        const colorMap: Record<string, string> = {
            green: "bg-green-100 text-green-800",
            yellow: "bg-yellow-100 text-yellow-800",
            red: "bg-red-100 text-red-800",
        };
        return colorMap[color] || "bg-surface-dim text-ink";
    };

    return (
        <div className="space-y-6">
            {/* ステータス＆アクション */}
            <div className="overflow-hidden bg-white border border-edge">
                <div className="border-b border-edge px-6 py-4">
                    <h3 className="text-lg font-medium text-ink">アクション</h3>
                </div>
                <div className="px-6 py-4">
                    {/* 現在のステータス */}
                    <div className="mb-4 text-center">
                        <span
                            className={`inline-flex rounded-full px-3 py-1 text-sm font-medium ${getStatusBadgeClass(customer.account_status_color)}`}
                        >
                            {customer.account_status_label}
                        </span>
                    </div>

                    <div className="space-y-3">
                        {/* アクティブ時: 一時停止とBANボタン */}
                        {customer.account_status === "active" && (
                            <>
                                <button
                                    onClick={onOpenSuspendModal}
                                    className="w-full bg-yellow-600 px-4 py-2 text-sm font-medium text-white hover:bg-yellow-700"
                                >
                                    一時停止
                                </button>
                                <button
                                    onClick={onOpenBanModal}
                                    className="w-full bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                                >
                                    BAN
                                </button>
                            </>
                        )}

                        {/* 一時停止中またはBAN中: 再有効化ボタン */}
                        {(customer.account_status === "suspended" || customer.account_status === "banned") && (
                            <button
                                onClick={onOpenReactivateModal}
                                className="w-full bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                            >
                                再有効化
                            </button>
                        )}

                        {/* データエクスポートは常に表示 */}
                        <button
                            onClick={onOpenExportModal}
                            className="w-full border border-edge-strong bg-white px-4 py-2 text-sm font-medium text-ink-light hover:bg-surface"
                        >
                            データエクスポート
                        </button>
                    </div>
                </div>
            </div>

            {/* ステータス変更情報 */}
            {customer.account_status_changed_by && (
                <div className="overflow-hidden bg-white border border-edge">
                    <div className="border-b border-edge px-6 py-4">
                        <h3 className="text-lg font-medium text-ink">ステータス変更情報</h3>
                    </div>
                    <div className="px-6 py-4">
                        <dl className="space-y-3">
                            <div>
                                <dt className="text-sm font-medium text-muted">変更者</dt>
                                <dd className="mt-1 text-sm text-ink">{customer.account_status_changed_by.name}</dd>
                            </div>
                            {customer.account_status_changed_at && (
                                <div>
                                    <dt className="text-sm font-medium text-muted">変更日時</dt>
                                    <dd className="mt-1 text-sm text-ink">
                                        {new Date(customer.account_status_changed_at).toLocaleString("ja-JP")}
                                    </dd>
                                </div>
                            )}
                        </dl>
                    </div>
                </div>
            )}
        </div>
    );
}
