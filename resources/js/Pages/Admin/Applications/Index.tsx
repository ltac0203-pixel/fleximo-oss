import AdminLayout from "@/Layouts/AdminLayout";
import { Head, Link, router } from "@inertiajs/react";
import { PageProps, PaginatedData } from "@/types";
import { decodeHtmlEntities } from "@/Utils/decodeHtmlEntities";
import { getPaginationLinkBaseKey, withStableKeys } from "@/Utils/stableKeys";

interface StatusOption {
    value: string;
    label: string;
}

interface TenantApplicationItem {
    id: number;
    application_code: string;
    applicant_name: string;
    applicant_email: string;
    tenant_name: string;
    business_type_label: string;
    status: string;
    status_label: string;
    status_color: string;
    created_at: string;
}

type SortableApplicationField = "application_code" | "tenant_name" | "applicant_name" | "status" | "created_at";

interface ApplicationsIndexProps extends PageProps {
    applications: PaginatedData<TenantApplicationItem>;
    statusFilter: string | null;
    searchQuery: string | null;
    sortBy: string;
    sortDir: string;
    statuses: StatusOption[];
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

export default function Index({
    applications,
    statusFilter,
    searchQuery,
    sortBy,
    sortDir,
    statuses,
}: ApplicationsIndexProps) {
    const paginationLinks = withStableKeys(applications.links, getPaginationLinkBaseKey);

    const handleSort = (column: SortableApplicationField) => {
        const newDir = sortBy === column ? (sortDir === "asc" ? "desc" : "asc") : "asc";
        router.get(
            route("admin.applications.index"),
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
            route("admin.applications.index"),
            {
                status: status || undefined,
                search: searchQuery || undefined,
                sort: sortBy || undefined,
                sort_dir: sortDir || undefined,
            },
            { preserveState: true },
        );
    };

    const handleSearch = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const formData = new FormData(e.currentTarget);
        const search = formData.get("search") as string;
        router.get(
            route("admin.applications.index"),
            {
                status: statusFilter || undefined,
                search: search || undefined,
                sort: sortBy || undefined,
                sort_dir: sortDir || undefined,
            },
            { preserveState: true },
        );
    };

    const getStatusBadgeClass = (color: string) => {
        const colorMap: Record<string, string> = {
            yellow: "bg-cyan-100 text-cyan-800",
            blue: "bg-sky-100 text-sky-800",
            green: "bg-green-100 text-green-800",
            red: "bg-red-100 text-red-800",
        };
        return colorMap[color] || "bg-surface-dim text-ink";
    };

    return (
        <AdminLayout
            header={<h2 className="text-xl font-semibold leading-tight text-ink">テナント申し込み管理</h2>}
        >
            <Head title="テナント申し込み管理" />

            <div>
                {/* フィルター を明示し、実装意図の誤読を防ぐ。 */}
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
                        {statuses.map((status) => (
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

                    <form onSubmit={handleSearch} className="flex gap-2">
                        <input
                            type="text"
                            name="search"
                            defaultValue={searchQuery || ""}
                            placeholder="検索..."
                            className="rounded-md border border-edge-strong px-3 py-2 text-sm focus:border-primary focus:outline-none"
                        />
                        <button
                            type="submit"
                            className="bg-slate-600 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
                        >
                            検索
                        </button>
                    </form>
                </div>

                {/* テーブル を明示し、実装意図の誤読を防ぐ。 */}
                <div className="overflow-x-auto bg-white shadow">
                    <table className="min-w-full divide-y divide-edge">
                        <thead className="bg-surface">
                            <tr>
                                <th
                                    className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted cursor-pointer select-none hover:bg-surface-dim"
                                    onClick={() => handleSort("application_code")}
                                >
                                    <span className="inline-flex items-center">
                                        申し込み番号
                                        <SortIndicator active={sortBy === "application_code"} direction={sortDir} />
                                    </span>
                                </th>
                                <th
                                    className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted cursor-pointer select-none hover:bg-surface-dim"
                                    onClick={() => handleSort("tenant_name")}
                                >
                                    <span className="inline-flex items-center">
                                        店舗名
                                        <SortIndicator active={sortBy === "tenant_name"} direction={sortDir} />
                                    </span>
                                </th>
                                <th
                                    className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted cursor-pointer select-none hover:bg-surface-dim"
                                    onClick={() => handleSort("applicant_name")}
                                >
                                    <span className="inline-flex items-center">
                                        申請者
                                        <SortIndicator active={sortBy === "applicant_name"} direction={sortDir} />
                                    </span>
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">
                                    業種
                                </th>
                                <th
                                    className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted cursor-pointer select-none hover:bg-surface-dim"
                                    onClick={() => handleSort("status")}
                                >
                                    <span className="inline-flex items-center">
                                        ステータス
                                        <SortIndicator active={sortBy === "status"} direction={sortDir} />
                                    </span>
                                </th>
                                <th
                                    className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted cursor-pointer select-none hover:bg-surface-dim"
                                    onClick={() => handleSort("created_at")}
                                >
                                    <span className="inline-flex items-center">
                                        申し込み日
                                        <SortIndicator active={sortBy === "created_at"} direction={sortDir} />
                                    </span>
                                </th>
                                <th className="relative px-6 py-3">
                                    <span className="sr-only">操作</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-edge bg-white">
                            {applications.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="px-6 py-12 text-center text-muted">
                                        申し込みがありません
                                    </td>
                                </tr>
                            ) : (
                                applications.data.map((application) => (
                                    <tr key={application.id} className="hover:bg-surface">
                                        <td className="whitespace-nowrap px-6 py-4">
                                            <span className="font-mono text-sm text-ink">
                                                {application.application_code}
                                            </span>
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4">
                                            <span className="text-sm font-medium text-ink">
                                                {application.tenant_name}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="text-sm text-ink">{application.applicant_name}</div>
                                            <div className="text-sm text-muted">{application.applicant_email}</div>
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-muted">
                                            {application.business_type_label}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4">
                                            <span
                                                className={`inline-flex rounded-full px-2 py-1 text-xs font-medium ${getStatusBadgeClass(application.status_color)}`}
                                            >
                                                {application.status_label}
                                            </span>
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-muted">
                                            {new Date(application.created_at).toLocaleDateString("ja-JP")}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <Link
                                                href={route("admin.applications.show", application.id)}
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

                {/* ページネーション を明示し、実装意図の誤読を防ぐ。 */}
                {applications.last_page > 1 && (
                    <div className="mt-6 flex items-center justify-between">
                        <p className="text-sm text-ink-light">
                            {applications.from} - {applications.to} / {applications.total} 件
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
