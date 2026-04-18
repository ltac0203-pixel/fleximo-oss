import AdminLayout from "@/Layouts/AdminLayout";
import { Head, Link, router } from "@inertiajs/react";
import { AdminDashboardProps } from "@/types";
import { FormEvent, useState } from "react";
import { formatCurrency } from "@/Utils/formatPrice";

function formatPercent(value: number): string {
    return `${value.toFixed(1)}%`;
}

function formatFeeRateBps(bps: number): string {
    return `${(bps / 100).toFixed(2)}%`;
}

export default function Dashboard({ stats, revenueDashboard, revenueFilters }: AdminDashboardProps) {
    const [startDate, setStartDate] = useState(revenueFilters.start_date);
    const [endDate, setEndDate] = useState(revenueFilters.end_date);
    const [rankingLimit, setRankingLimit] = useState(String(revenueFilters.ranking_limit));

    const submitRevenueFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get(
            route("admin.dashboard"),
            {
                start_date: startDate || undefined,
                end_date: endDate || undefined,
                ranking_limit: rankingLimit ? Number.parseInt(rankingLimit, 10) : undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const revenueOverviewCards = [
        { name: "GMV合計", value: formatCurrency(revenueDashboard.overview.gmv_total) },
        { name: "推定手数料収入", value: formatCurrency(revenueDashboard.overview.estimated_fee_total) },
        { name: "総注文数", value: `${revenueDashboard.overview.order_count_total.toLocaleString()} 件` },
        { name: "平均客単価", value: formatCurrency(revenueDashboard.overview.avg_order_value) },
        { name: "売上発生テナント数", value: `${revenueDashboard.overview.active_tenant_count.toLocaleString()} 件` },
    ];

    const statCards = [
        {
            name: "審査待ち",
            value: stats.pending_count,
            color: "bg-cyan-100 text-cyan-800",
            href: route("admin.applications.index", { status: "pending" }),
        },
        {
            name: "審査中",
            value: stats.under_review_count,
            color: "bg-sky-100 text-sky-800",
            href: route("admin.applications.index", { status: "under_review" }),
        },
        {
            name: "承認済み",
            value: stats.approved_count,
            color: "bg-green-100 text-green-800",
            href: route("admin.applications.index", { status: "approved" }),
        },
        {
            name: "却下",
            value: stats.rejected_count,
            color: "bg-red-100 text-red-800",
            href: route("admin.applications.index", { status: "rejected" }),
        },
    ];

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold leading-tight text-ink">ダッシュボード</h2>}>
            <Head title="管理者ダッシュボード" />

            <div>
                <div className="overflow-hidden border border-edge bg-white">
                    <div className="border-b border-edge p-6">
                        <h3 className="text-lg font-medium text-ink">プラットフォーム売上（GMV）</h3>
                        <p className="mt-1 text-sm text-muted">
                            期間内の全テナント売上、ランキング、推定手数料収入を表示します。
                        </p>
                    </div>

                    <div className="p-6">
                        <form onSubmit={submitRevenueFilters} className="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div>
                                <label htmlFor="start_date" className="block text-sm font-medium text-ink-light">
                                    開始日
                                </label>
                                <input
                                    id="start_date"
                                    type="date"
                                    value={startDate}
                                    onChange={(event) => setStartDate(event.target.value)}
                                    className="mt-1 w-full border border-edge-strong px-3 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label htmlFor="end_date" className="block text-sm font-medium text-ink-light">
                                    終了日
                                </label>
                                <input
                                    id="end_date"
                                    type="date"
                                    value={endDate}
                                    onChange={(event) => setEndDate(event.target.value)}
                                    className="mt-1 w-full border border-edge-strong px-3 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label htmlFor="ranking_limit" className="block text-sm font-medium text-ink-light">
                                    ランキング件数
                                </label>
                                <input
                                    id="ranking_limit"
                                    type="number"
                                    min={1}
                                    max={50}
                                    value={rankingLimit}
                                    onChange={(event) => setRankingLimit(event.target.value)}
                                    className="mt-1 w-full border border-edge-strong px-3 py-2 text-sm"
                                />
                            </div>
                            <div className="flex items-end">
                                <button
                                    type="submit"
                                    className="w-full bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
                                >
                                    適用
                                </button>
                            </div>
                        </form>
                    </div>

                    <div className="grid grid-cols-1 gap-4 border-t border-edge p-6 md:grid-cols-2 xl:grid-cols-5">
                        {revenueOverviewCards.map((card) => (
                            <div key={card.name} className="border border-edge bg-surface p-4">
                                <p className="text-sm text-ink-light">{card.name}</p>
                                <p className="mt-2 text-2xl font-semibold text-ink">{card.value}</p>
                            </div>
                        ))}
                    </div>

                    <div className="grid grid-cols-1 gap-6 border-t border-edge p-6 xl:grid-cols-2">
                        <div className="overflow-hidden border border-edge">
                            <div className="border-b border-edge bg-surface px-4 py-3">
                                <h4 className="text-sm font-medium text-ink">日次GMV推移</h4>
                            </div>
                            <div className="max-h-80 overflow-auto">
                                <table className="min-w-full divide-y divide-edge">
                                    <thead className="bg-white">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">
                                                日付
                                            </th>
                                            <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">
                                                GMV
                                            </th>
                                            <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">
                                                注文数
                                            </th>
                                            <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">
                                                推定手数料
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-edge bg-white">
                                        {revenueDashboard.trend.length === 0 ? (
                                            <tr>
                                                <td colSpan={4} className="px-4 py-8 text-center text-sm text-muted">
                                                    対象期間のデータがありません。
                                                </td>
                                            </tr>
                                        ) : (
                                            revenueDashboard.trend.map((day) => (
                                                <tr key={day.date}>
                                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-ink">
                                                        {day.date}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-ink">
                                                        {formatCurrency(day.gmv)}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-ink">
                                                        {day.order_count.toLocaleString()} 件
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-ink">
                                                        {formatCurrency(day.estimated_fee)}
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div className="overflow-hidden border border-edge">
                            <div className="border-b border-edge bg-surface px-4 py-3">
                                <h4 className="text-sm font-medium text-ink">テナント別売上ランキング</h4>
                            </div>
                            <div className="max-h-80 overflow-auto">
                                <table className="min-w-full divide-y divide-edge">
                                    <thead className="bg-white">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">
                                                順位
                                            </th>
                                            <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">
                                                テナント
                                            </th>
                                            <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">
                                                GMV
                                            </th>
                                            <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">
                                                構成比
                                            </th>
                                            <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">
                                                推定手数料
                                            </th>
                                            <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">
                                                手数料率
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-edge bg-white">
                                        {revenueDashboard.ranking.length === 0 ? (
                                            <tr>
                                                <td colSpan={6} className="px-4 py-8 text-center text-sm text-muted">
                                                    売上データがありません。
                                                </td>
                                            </tr>
                                        ) : (
                                            revenueDashboard.ranking.map((tenant, index) => (
                                                <tr key={tenant.tenant_id}>
                                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-ink">
                                                        {index + 1}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-ink">
                                                        {tenant.tenant_name}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-ink">
                                                        {formatCurrency(tenant.gmv)}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-ink">
                                                        {formatPercent(tenant.share_percent)}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-ink">
                                                        {formatCurrency(tenant.estimated_fee)}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-ink">
                                                        {formatFeeRateBps(tenant.fee_rate_bps)}
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {/* 概要カード を明示し、実装意図の誤読を防ぐ。 */}
                <div className="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    {statCards.map((stat) => (
                        <Link
                            key={stat.name}
                            href={stat.href}
                            className="block overflow-hidden bg-white border border-edge"
                        >
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-1">
                                        <p className="text-sm font-medium text-muted">{stat.name}</p>
                                        <p className="mt-1 text-3xl font-semibold text-ink">{stat.value}</p>
                                    </div>
                                    <div
                                        className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${stat.color}`}
                                    >
                                        件
                                    </div>
                                </div>
                            </div>
                        </Link>
                    ))}
                </div>

                {/* サマリー を明示し、実装意図の誤読を防ぐ。 */}
                <div className="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div className="overflow-hidden bg-white border border-edge">
                        <div className="p-6">
                            <h3 className="text-lg font-medium text-ink">申し込み状況</h3>
                            <dl className="mt-4 space-y-4">
                                <div className="flex items-center justify-between">
                                    <dt className="text-sm text-muted">総申し込み数</dt>
                                    <dd className="text-sm font-medium text-ink">{stats.total_count} 件</dd>
                                </div>
                                <div className="flex items-center justify-between">
                                    <dt className="text-sm text-muted">アクティブテナント数</dt>
                                    <dd className="text-sm font-medium text-ink">
                                        {stats.active_tenant_count} 件
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div className="overflow-hidden bg-white border border-edge">
                        <div className="p-6">
                            <h3 className="text-lg font-medium text-ink">クイックアクション</h3>
                            <div className="mt-4 space-y-3">
                                <Link
                                    href={route("admin.applications.index", {
                                        status: "pending",
                                    })}
                                    className="flex items-center justify-between border border-edge p-4 hover:bg-surface"
                                >
                                    <span className="text-sm font-medium text-ink">審査待ちの申し込みを確認</span>
                                    <svg
                                        className="h-5 w-5 text-muted-light"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        strokeWidth="1.5"
                                        stroke="currentColor"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M8.25 4.5l7.5 7.5-7.5 7.5"
                                        />
                                    </svg>
                                </Link>
                                <Link
                                    href={route("admin.applications.index")}
                                    className="flex items-center justify-between border border-edge p-4 hover:bg-surface"
                                >
                                    <span className="text-sm font-medium text-ink">すべての申し込みを表示</span>
                                    <svg
                                        className="h-5 w-5 text-muted-light"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        strokeWidth="1.5"
                                        stroke="currentColor"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M8.25 4.5l7.5 7.5-7.5 7.5"
                                        />
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
