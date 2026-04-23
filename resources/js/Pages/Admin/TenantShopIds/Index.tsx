import AdminLayout from "@/Layouts/AdminLayout";
import { Head, router } from "@inertiajs/react";
import { PageProps, PaginatedData } from "@/types";
import { decodeHtmlEntities } from "@/Utils/decodeHtmlEntities";
import { getPaginationLinkBaseKey, withStableKeys } from "@/Utils/stableKeys";
import { useState } from "react";
import HelpButton from "@/Components/Common/Help/HelpButton";
import HelpPanel from "@/Components/Common/Help/HelpPanel";
import InlineHelp from "@/Components/Common/Help/InlineHelp";
import { useHelpPanel } from "@/Hooks/useHelpPanel";
import { adminHelpContent } from "@/data/adminHelpContent";
import Badge from "@/Components/UI/Badge";

interface TenantShopIdItem {
    id: number;
    name: string;
    email: string | null;
    fincode_shop_id: string | null;
    status: string;
    is_active: boolean;
}

interface TenantShopIdsIndexProps extends PageProps {
    tenants: PaginatedData<TenantShopIdItem>;
    searchQuery: string | null;
}

export default function Index({ tenants, searchQuery, flash }: TenantShopIdsIndexProps) {
    const [editingId, setEditingId] = useState<number | null>(null);
    const [editValue, setEditValue] = useState("");
    const [processing, setProcessing] = useState(false);
    const { showHelp, openHelp, closeHelp } = useHelpPanel();
    const paginationLinks = withStableKeys(tenants.links, getPaginationLinkBaseKey);

    const handleSearch = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const formData = new FormData(e.currentTarget);
        const search = formData.get("search") as string;
        router.get(route("admin.tenant-shop-ids.index"), { search: search || undefined }, { preserveState: true });
    };

    const startEdit = (tenant: TenantShopIdItem) => {
        setEditingId(tenant.id);
        setEditValue(tenant.fincode_shop_id || "");
    };

    const cancelEdit = () => {
        setEditingId(null);
        setEditValue("");
    };

    const saveShopId = (tenantId: number) => {
        setProcessing(true);
        router.patch(
            route("admin.tenant-shop-ids.update", tenantId),
            { fincode_shop_id: editValue || null },
            {
                preserveState: true,
                onSuccess: () => {
                    setEditingId(null);
                    setEditValue("");
                },
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <AdminLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-ink">Shop ID管理</h2>
                    <HelpButton onClick={openHelp} />
                </div>
            }
        >
            <Head title="Shop ID管理" />

            <div>
                {flash?.success && (
                    <div className="mb-4 rounded bg-green-50 p-4 text-sm text-green-700">{flash.success}</div>
                )}
                {flash?.error && <div className="mb-4 rounded bg-red-50 p-4 text-sm text-red-700">{flash.error}</div>}

                {/* 検索 を明示し、実装意図の誤読を防ぐ。 */}
                <div className="mb-6 flex justify-end">
                    <form onSubmit={handleSearch} className="flex gap-2">
                        <input
                            type="text"
                            name="search"
                            defaultValue={searchQuery || ""}
                            placeholder="テナント名・メール・Shop IDで検索..."
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
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">
                                    テナント名
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">
                                    メール
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">
                                    <span className="inline-flex items-center gap-2">
                                        Shop ID
                                        <InlineHelp contentKey="tenant-shop-id" />
                                    </span>
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">
                                    ステータス
                                </th>
                                <th className="relative px-6 py-3">
                                    <span className="sr-only">操作</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-edge bg-white">
                            {tenants.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-6 py-12 text-center text-muted">
                                        テナントがありません
                                    </td>
                                </tr>
                            ) : (
                                tenants.data.map((tenant) => (
                                    <tr key={tenant.id} className="hover:bg-surface">
                                        <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-ink">
                                            {tenant.name}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-muted">
                                            {tenant.email || "-"}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4">
                                            {editingId === tenant.id ? (
                                                <div className="flex items-center gap-2">
                                                    <input
                                                        type="text"
                                                        value={editValue}
                                                        onChange={(e) => setEditValue(e.target.value)}
                                                        className="rounded border border-edge-strong px-2 py-1 text-sm focus:border-primary focus:outline-none"
                                                        placeholder="Shop IDを入力"
                                                        autoFocus
                                                        onKeyDown={(e) => {
                                                            if (e.key === "Enter") {
                                                                saveShopId(tenant.id);
                                                            }
                                                            if (e.key === "Escape") {
                                                                cancelEdit();
                                                            }
                                                        }}
                                                    />
                                                    <button
                                                        onClick={() => saveShopId(tenant.id)}
                                                        disabled={processing}
                                                        aria-busy={processing || undefined}
                                                        className="rounded bg-slate-600 px-2 py-1 text-xs text-white hover:bg-slate-700 disabled:opacity-50 inline-flex items-center justify-center min-w-10"
                                                    >
                                                        {processing ? (
                                                            <>
                                                                <span
                                                                    className="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"
                                                                    aria-hidden="true"
                                                                />
                                                                <span className="sr-only">処理中</span>
                                                            </>
                                                        ) : (
                                                            "保存"
                                                        )}
                                                    </button>
                                                    <button
                                                        onClick={cancelEdit}
                                                        className="rounded bg-edge px-2 py-1 text-xs text-ink-light hover:bg-surface-dim"
                                                    >
                                                        キャンセル
                                                    </button>
                                                </div>
                                            ) : (
                                                <span className="font-mono text-sm text-ink">
                                                    {tenant.fincode_shop_id || "-"}
                                                </span>
                                            )}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4">
                                            {tenant.status === "active" && tenant.is_active ? (
                                                <Badge tone="green" size="sm" shape="pill">
                                                    有効
                                                </Badge>
                                            ) : (
                                                <Badge tone="neutral" size="sm" shape="pill">
                                                    無効
                                                </Badge>
                                            )}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            {editingId !== tenant.id && (
                                                <button
                                                    onClick={() => startEdit(tenant)}
                                                    className="text-primary hover:text-primary-dark"
                                                >
                                                    編集
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* ページネーション を明示し、実装意図の誤読を防ぐ。 */}
                {tenants.last_page > 1 && (
                    <div className="mt-6 flex items-center justify-between">
                        <p className="text-sm text-ink-light">
                            {tenants.from} - {tenants.to} / {tenants.total} 件
                        </p>
                        <div className="flex gap-2">
                            {paginationLinks.map(({ item: link, key }) => (
                                <button
                                    key={key}
                                    onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                    disabled={!link.url}
                                    className={`px-3 py-2 text-sm ${
                                        link.active
                                            ? "bg-slate-800 text-white"
                                            : link.url
                                              ? "bg-white text-ink-light hover:bg-surface border border-edge-strong"
                                              : "bg-surface-dim text-muted-light cursor-not-allowed"
                                    }`}
                                >
                                    {decodeHtmlEntities(link.label)}
                                </button>
                            ))}
                        </div>
                    </div>
                )}
            </div>

            <HelpPanel open={showHelp} onClose={closeHelp} content={adminHelpContent["admin-tenant-shop-ids"]} />
        </AdminLayout>
    );
}
