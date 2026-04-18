import AdminLayout from "@/Layouts/AdminLayout";
import { Head, Link, router } from "@inertiajs/react";
import { PageProps, PaginatedData, CustomerDetail, CustomerOrderItem } from "@/types";
import { decodeHtmlEntities } from "@/Utils/decodeHtmlEntities";
import { getPaginationLinkBaseKey, withStableKeys } from "@/Utils/stableKeys";

interface CustomerOrdersProps extends PageProps {
    customer: CustomerDetail;
    orders: PaginatedData<CustomerOrderItem>;
    tenantFilter: string | null;
    statusFilter: string | null;
    tenants: Array<{ value: string; label: string }>;
    statuses: Array<{ value: string; label: string }>;
}

export default function Orders({
    customer,
    orders,
    tenantFilter,
    statusFilter,
    tenants,
    statuses,
}: CustomerOrdersProps) {
    const paginationLinks = withStableKeys(orders.links, getPaginationLinkBaseKey);

    const handleFilterChange = (newTenant?: string, newStatus?: string) => {
        router.get(
            route("admin.customers.orders", customer.id),
            {
                tenant: newTenant ?? tenantFilter ?? undefined,
                status: newStatus ?? statusFilter ?? undefined,
            },
            { preserveState: true },
        );
    };

    const handleTenantChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        handleFilterChange(e.target.value || undefined, statusFilter || undefined);
    };

    const handleStatusChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        handleFilterChange(tenantFilter || undefined, e.target.value || undefined);
    };

    return (
        <AdminLayout
            header={
                <div>
                    <nav className="text-sm text-muted">
                        <Link href={route("admin.customers.index")} className="hover:text-ink-light">
                            顧客管理
                        </Link>
                        <span className="mx-2">/</span>
                        <Link href={route("admin.customers.show", customer.id)} className="hover:text-ink-light">
                            {customer.name}
                        </Link>
                        <span className="mx-2">/</span>
                        <span className="text-ink">注文履歴</span>
                    </nav>
                    <h2 className="mt-1 text-xl font-semibold leading-tight text-ink">注文履歴</h2>
                </div>
            }
        >
            <Head title={`注文履歴 - ${customer.name}`} />

            <div>
                {/* フィルター */}
                <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div className="flex gap-4">
                        <select
                            value={tenantFilter || ""}
                            onChange={handleTenantChange}
                            className="rounded-md border border-edge-strong px-3 py-2 text-sm focus:border-primary focus:outline-none"
                        >
                            <option value="">すべての店舗</option>
                            {tenants.map((tenant) => (
                                <option key={tenant.value} value={tenant.value}>
                                    {tenant.label}
                                </option>
                            ))}
                        </select>
                        <select
                            value={statusFilter || ""}
                            onChange={handleStatusChange}
                            className="rounded-md border border-edge-strong px-3 py-2 text-sm focus:border-primary focus:outline-none"
                        >
                            <option value="">すべてのステータス</option>
                            {statuses.map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <Link
                        href={route("admin.customers.show", customer.id)}
                        className="text-sm text-primary hover:text-primary-dark"
                    >
                        &larr; 顧客詳細に戻る
                    </Link>
                </div>

                {/* テーブル */}
                <div className="overflow-x-auto bg-white shadow">
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
                                    決済方法
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">
                                    注文日
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-edge bg-white">
                            {orders.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-6 py-12 text-center text-muted">
                                        注文が見つかりません
                                    </td>
                                </tr>
                            ) : (
                                orders.data.map((order) => (
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
                                            {order.payment?.method_label || "-"}
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

                {/* ページネーション */}
                {orders.last_page > 1 && (
                    <div className="mt-6 flex items-center justify-between">
                        <p className="text-sm text-ink-light">
                            {orders.from} - {orders.to} / {orders.total} 件
                        </p>
                        <div className="flex gap-2">
                            {paginationLinks.map(({ item: link, key }) => (
                                <Link
                                    key={key}
                                    href={link.url || "#"}
                                    className={`px-3 py-2 text-sm ${
                                        link.active
                                            ? "bg-slate-800 text-white"
                                            : link.url
                                              ? "bg-white text-ink-light hover:bg-surface border border-edge-strong"
                                              : "bg-surface-dim text-muted-light cursor-not-allowed"
                                    }`}
                                >
                                    {decodeHtmlEntities(link.label)}
                                </Link>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
