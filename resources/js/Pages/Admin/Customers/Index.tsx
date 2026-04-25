import AdminLayout from "@/Layouts/AdminLayout";
import { Head, Link, router } from "@inertiajs/react";
import { PageProps, PaginatedData, CustomerListItem, AccountStatus } from "@/types";
import HelpButton from "@/Components/Common/Help/HelpButton";
import HelpPanel from "@/Components/Common/Help/HelpPanel";
import { useHelpPanel } from "@/Hooks/useHelpPanel";
import { adminHelpContent } from "@/data/adminHelpContent";
import AdminPagination from "@/Components/UI/AdminPagination";
import AdminSearchForm from "@/Components/UI/AdminSearchForm";
import EmptyRow from "@/Components/UI/EmptyRow";
import Badge from "@/Components/UI/Badge";
import { toAccountStatusTone } from "@/constants/statusColors";

type SortableCustomerField = "name" | "email" | "account_status" | "orders_count" | "last_login_at" | "created_at";

interface CustomersIndexProps extends PageProps {
    customers: PaginatedData<CustomerListItem>;
    statusFilter: AccountStatus | null;
    searchQuery: string | null;
    sortBy: string;
    sortDir: string;
}

function SortIndicator({ active, direction }: { active: boolean; direction: string }) {
    if (!active) {
        return (
            <svg className="ml-1 inline h-3 w-3 text-muted-light" viewBox="0 0 10 14" fill="currentColor">
                <path d="M5 0L9 5H1L5 0Z" />
                <path d="M5 14L1 9H9L5 14Z" />
            </svg>
        );
    }
    return (
        <svg className="ml-1 inline h-3 w-3 text-ink-light" viewBox="0 0 10 8" fill="currentColor">
            {direction === "asc" ? <path d="M5 0L10 8H0L5 0Z" /> : <path d="M5 8L0 0H10L5 8Z" />}
        </svg>
    );
}

const statusFilters: Array<{ value: AccountStatus; label: string }> = [
    { value: "active", label: "アクティブ" },
    { value: "suspended", label: "一時停止" },
    { value: "banned", label: "BAN" },
];

export default function Index({
    customers,
    statusFilter,
    searchQuery,
    sortBy,
    sortDir,
}: CustomersIndexProps) {
    const { showHelp, openHelp, closeHelp } = useHelpPanel();

    const handleSort = (column: SortableCustomerField) => {
        const newDir = sortBy === column ? (sortDir === "asc" ? "desc" : "asc") : "asc";
        router.get(
            route("admin.customers.index"),
            {
                status: statusFilter || undefined,
                search: searchQuery || undefined,
                sort: column,
                sort_dir: newDir,
            },
            { preserveState: true },
        );
    };

    const handleStatusChange = (status: string) => {
        router.get(
            route("admin.customers.index"),
            {
                status: status || undefined,
                search: searchQuery || undefined,
                sort: sortBy || undefined,
                sort_dir: sortDir || undefined,
            },
            { preserveState: true },
        );
    };

    const handleSearch = (search: string) => {
        router.get(
            route("admin.customers.index"),
            {
                status: statusFilter || undefined,
                search: search || undefined,
                sort: sortBy || undefined,
                sort_dir: sortDir || undefined,
            },
            { preserveState: true },
        );
    };

    return (
        <AdminLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-ink">顧客管理</h2>
                    <HelpButton onClick={openHelp} />
                </div>
            }
        >
            <Head title="顧客管理" />

            <div>
                {/* フィルター */}
                <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-wrap gap-2">
                        <button
                            onClick={() => handleStatusChange("")}
                            className={`rounded-full px-4 py-2 text-sm font-medium ${
                                !statusFilter
                                    ? "bg-slate-800 text-white"
                                    : "bg-white text-ink-light hover:bg-surface border border-edge-strong"
                            }`}
                        >
                            すべて
                        </button>
                        {statusFilters.map((status) => (
                            <button
                                key={status.value}
                                onClick={() => handleStatusChange(status.value)}
                                className={`rounded-full px-4 py-2 text-sm font-medium ${
                                    statusFilter === status.value
                                        ? "bg-slate-800 text-white"
                                        : "bg-white text-ink-light hover:bg-surface border border-edge-strong"
                                }`}
                            >
                                {status.label}
                            </button>
                        ))}
                    </div>

                    <AdminSearchForm
                        defaultValue={searchQuery || ""}
                        placeholder="名前・メールで検索..."
                        onSubmit={handleSearch}
                    />
                </div>

                {/* テーブル */}
                <div className="overflow-x-auto bg-white shadow">
                    <table className="min-w-full divide-y divide-edge">
                        <thead className="bg-surface">
                            <tr>
                                <th
                                    className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted cursor-pointer select-none hover:bg-surface-dim"
                                    onClick={() => handleSort("name")}
                                >
                                    <span className="inline-flex items-center">
                                        名前
                                        <SortIndicator active={sortBy === "name"} direction={sortDir} />
                                    </span>
                                </th>
                                <th
                                    className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted cursor-pointer select-none hover:bg-surface-dim"
                                    onClick={() => handleSort("email")}
                                >
                                    <span className="inline-flex items-center">
                                        メール
                                        <SortIndicator active={sortBy === "email"} direction={sortDir} />
                                    </span>
                                </th>
                                <th
                                    className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted cursor-pointer select-none hover:bg-surface-dim"
                                    onClick={() => handleSort("account_status")}
                                >
                                    <span className="inline-flex items-center">
                                        ステータス
                                        <SortIndicator active={sortBy === "account_status"} direction={sortDir} />
                                    </span>
                                </th>
                                <th
                                    className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted cursor-pointer select-none hover:bg-surface-dim"
                                    onClick={() => handleSort("orders_count")}
                                >
                                    <span className="inline-flex items-center">
                                        注文数
                                        <SortIndicator active={sortBy === "orders_count"} direction={sortDir} />
                                    </span>
                                </th>
                                <th
                                    className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted cursor-pointer select-none hover:bg-surface-dim"
                                    onClick={() => handleSort("last_login_at")}
                                >
                                    <span className="inline-flex items-center">
                                        最終ログイン
                                        <SortIndicator active={sortBy === "last_login_at"} direction={sortDir} />
                                    </span>
                                </th>
                                <th
                                    className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted cursor-pointer select-none hover:bg-surface-dim"
                                    onClick={() => handleSort("created_at")}
                                >
                                    <span className="inline-flex items-center">
                                        登録日
                                        <SortIndicator active={sortBy === "created_at"} direction={sortDir} />
                                    </span>
                                </th>
                                <th className="relative px-6 py-3">
                                    <span className="sr-only">操作</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-edge bg-white">
                            {customers.data.length === 0 ? (
                                <EmptyRow colSpan={7}>顧客が見つかりません</EmptyRow>
                            ) : (
                                customers.data.map((customer) => (
                                    <tr key={customer.id} className="hover:bg-surface">
                                        <td className="whitespace-nowrap px-6 py-4">
                                            <span className="text-sm font-medium text-ink">
                                                {customer.name}
                                            </span>
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-muted">
                                            {customer.email}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4">
                                            <Badge
                                                tone={toAccountStatusTone(customer.account_status_color)}
                                                size="sm"
                                                shape="pill"
                                            >
                                                {customer.account_status_label}
                                            </Badge>
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-muted">
                                            {customer.orders_count}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-muted">
                                            {customer.last_login_at
                                                ? new Date(customer.last_login_at).toLocaleDateString("ja-JP")
                                                : "-"}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-muted">
                                            {new Date(customer.created_at).toLocaleDateString("ja-JP")}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <Link
                                                href={route("admin.customers.show", customer.id)}
                                                className="text-primary hover:text-primary-dark"
                                            >
                                                詳細
                                            </Link>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* ページネーション */}
                <AdminPagination paginated={customers} />
            </div>

            <HelpPanel open={showHelp} onClose={closeHelp} content={adminHelpContent["admin-customers-index"]} />
        </AdminLayout>
    );
}
