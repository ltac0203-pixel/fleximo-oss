import AdminLayout from "@/Layouts/AdminLayout";
import CustomerActionsPanel from "@/Pages/Admin/Customers/Partials/CustomerActionsPanel";
import SuspendCustomerModal from "@/Pages/Admin/Customers/Partials/SuspendCustomerModal";
import BanCustomerModal from "@/Pages/Admin/Customers/Partials/BanCustomerModal";
import ReactivateCustomerModal from "@/Pages/Admin/Customers/Partials/ReactivateCustomerModal";
import ExportCustomerDataModal from "@/Pages/Admin/Customers/Partials/ExportCustomerDataModal";
import { Head, Link, usePage } from "@inertiajs/react";
import { PageProps, CustomerDetail, CustomerOrderItem } from "@/types";
import { useState } from "react";
import HelpButton from "@/Components/Common/Help/HelpButton";
import HelpPanel from "@/Components/Common/Help/HelpPanel";
import { useHelpPanel } from "@/Hooks/useHelpPanel";
import { adminHelpContent } from "@/data/adminHelpContent";
import Badge from "@/Components/UI/Badge";
import { toAccountStatusTone } from "@/constants/statusColors";

interface CustomerShowProps extends PageProps {
    customer: CustomerDetail;
    recentOrders: CustomerOrderItem[];
}

export default function Show({ customer, recentOrders }: CustomerShowProps) {
    const { flash } = usePage<PageProps>().props;
    const [showSuspendModal, setShowSuspendModal] = useState(false);
    const [showBanModal, setShowBanModal] = useState(false);
    const [showReactivateModal, setShowReactivateModal] = useState(false);
    const [showExportModal, setShowExportModal] = useState(false);
    const { showHelp, openHelp, closeHelp } = useHelpPanel();

    return (
        <AdminLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <Link
                            href={route("admin.customers.index")}
                            className="text-sm text-muted hover:text-ink-light"
                        >
                            &larr; 一覧に戻る
                        </Link>
                        <h2 className="mt-1 text-xl font-semibold leading-tight text-ink">顧客詳細</h2>
                    </div>
                    <div className="flex items-center gap-3">
                        <Badge
                            tone={toAccountStatusTone(customer.account_status_color)}
                            size="md"
                            shape="pill"
                        >
                            {customer.account_status_label}
                        </Badge>
                        <HelpButton onClick={openHelp} />
                    </div>
                </div>
            }
        >
            <Head title={`顧客詳細 - ${customer.name}`} />

            <div>
                {/* フラッシュメッセージ */}
                {flash?.success && (
                    <div className="mb-6 border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="mb-6 border border-red-200 bg-red-50 p-4 text-sm text-red-700">{flash.error}</div>
                )}

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {/* 左カラム: 顧客情報 */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* 顧客情報 */}
                        <div className="overflow-hidden bg-white border border-edge">
                            <div className="border-b border-edge px-6 py-4">
                                <h3 className="text-lg font-medium text-ink">顧客情報</h3>
                            </div>
                            <div className="px-6 py-4">
                                <dl className="divide-y divide-edge">
                                    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                        <dt className="text-sm font-medium text-muted">名前</dt>
                                        <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                            {customer.name}
                                        </dd>
                                    </div>
                                    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                        <dt className="text-sm font-medium text-muted">メールアドレス</dt>
                                        <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                            <a
                                                href={`mailto:${customer.email}`}
                                                className="text-primary hover:text-primary-light"
                                            >
                                                {customer.email}
                                            </a>
                                        </dd>
                                    </div>
                                    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                        <dt className="text-sm font-medium text-muted">電話番号</dt>
                                        <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                            {customer.phone || "-"}
                                        </dd>
                                    </div>
                                    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                        <dt className="text-sm font-medium text-muted">最終ログイン</dt>
                                        <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                            {customer.last_login_at
                                                ? new Date(customer.last_login_at).toLocaleString("ja-JP")
                                                : "-"}
                                        </dd>
                                    </div>
                                    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                        <dt className="text-sm font-medium text-muted">登録日</dt>
                                        <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                            {new Date(customer.created_at).toLocaleString("ja-JP")}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        {/* 統計カード */}
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div className="overflow-hidden bg-white border border-edge px-6 py-4">
                                <dt className="text-sm font-medium text-muted">総注文数</dt>
                                <dd className="mt-1 text-2xl font-semibold text-ink">
                                    {customer.total_orders}
                                </dd>
                            </div>
                            <div className="overflow-hidden bg-white border border-edge px-6 py-4">
                                <dt className="text-sm font-medium text-muted">総利用金額</dt>
                                <dd className="mt-1 text-2xl font-semibold text-ink">
                                    &yen;{customer.total_spent.toLocaleString()}
                                </dd>
                            </div>
                            <div className="overflow-hidden bg-white border border-edge px-6 py-4">
                                <dt className="text-sm font-medium text-muted">お気に入り店舗数</dt>
                                <dd className="mt-1 text-2xl font-semibold text-ink">
                                    {customer.favorite_tenants_count}
                                </dd>
                            </div>
                        </div>

                        {/* 停止理由（停止中またはBAN中の場合のみ表示） */}
                        {customer.account_status_reason && (
                            <div className="overflow-hidden bg-red-50 border border-red-200">
                                <div className="border-b border-red-200 px-6 py-4">
                                    <h3 className="text-lg font-medium text-red-900">
                                        {customer.account_status === "banned" ? "BAN理由" : "停止理由"}
                                    </h3>
                                </div>
                                <div className="px-6 py-4">
                                    <p className="text-sm text-red-800 whitespace-pre-wrap">
                                        {customer.account_status_reason}
                                    </p>
                                    {customer.account_status_changed_by && (
                                        <p className="mt-2 text-xs text-red-600">
                                            変更者: {customer.account_status_changed_by.name}
                                            {customer.account_status_changed_at && (
                                                <> ({new Date(customer.account_status_changed_at).toLocaleString("ja-JP")})</>
                                            )}
                                        </p>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* 最近の注文 */}
                        <div className="overflow-hidden bg-white border border-edge">
                            <div className="border-b border-edge px-6 py-4 flex items-center justify-between">
                                <h3 className="text-lg font-medium text-ink">最近の注文</h3>
                                <Link
                                    href={route("admin.customers.orders", customer.id)}
                                    className="text-sm text-primary hover:text-primary-dark"
                                >
                                    すべて表示
                                </Link>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-edge">
                                    <thead className="bg-surface">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">
                                                注文番号
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">
                                                店舗
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">
                                                ステータス
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">
                                                金額
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">
                                                日時
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-edge bg-white">
                                        {recentOrders.length === 0 ? (
                                            <tr>
                                                <td colSpan={5} className="px-6 py-8 text-center text-muted">
                                                    注文履歴がありません
                                                </td>
                                            </tr>
                                        ) : (
                                            recentOrders.map((order) => (
                                                <tr key={order.id} className="hover:bg-surface">
                                                    <td className="whitespace-nowrap px-6 py-4 font-mono text-sm text-ink">
                                                        {order.order_code}
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-muted">
                                                        {order.tenant_name || "-"}
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-muted">
                                                        {order.status_label}
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-ink">
                                                        &yen;{order.total_amount.toLocaleString()}
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-muted">
                                                        {new Date(order.created_at).toLocaleDateString("ja-JP")}
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {/* 右カラム: アクションパネル */}
                    <CustomerActionsPanel
                        customer={customer}
                        onOpenSuspendModal={() => setShowSuspendModal(true)}
                        onOpenBanModal={() => setShowBanModal(true)}
                        onOpenReactivateModal={() => setShowReactivateModal(true)}
                        onOpenExportModal={() => setShowExportModal(true)}
                    />
                </div>
            </div>

            {/* 一時停止モーダル */}
            <SuspendCustomerModal
                show={showSuspendModal}
                onClose={() => setShowSuspendModal(false)}
                customerId={customer.id}
            />

            {/* BANモーダル */}
            <BanCustomerModal
                show={showBanModal}
                onClose={() => setShowBanModal(false)}
                customerId={customer.id}
            />

            {/* 再有効化モーダル */}
            <ReactivateCustomerModal
                show={showReactivateModal}
                onClose={() => setShowReactivateModal(false)}
                customerId={customer.id}
            />

            {/* データエクスポートモーダル */}
            <ExportCustomerDataModal
                show={showExportModal}
                onClose={() => setShowExportModal(false)}
                customerId={customer.id}
            />

            <HelpPanel open={showHelp} onClose={closeHelp} content={adminHelpContent["admin-customers-show"]} />
        </AdminLayout>
    );
}
