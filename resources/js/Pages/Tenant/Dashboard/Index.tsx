import { lazy, Suspense } from "react";
import TenantLayout from "@/Layouts/TenantLayout";
import { Head } from "@inertiajs/react";
import { DashboardIndexProps, DashboardSummary, SalesData } from "@/types";
import GeoSurface from "@/Components/GeoSurface";
import SummaryCards from "@/Components/Dashboard/SummaryCards";
import TopItemsCard from "@/Components/Dashboard/TopItemsCard";
import CustomerInsightsCard from "@/Components/Dashboard/CustomerInsightsCard";
import ExportControls from "@/Components/Dashboard/ExportControls";
import HelpButton from "@/Components/Common/Help/HelpButton";
import HelpPanel from "@/Components/Common/Help/HelpPanel";
import { useHelpPanel } from "@/Hooks/useHelpPanel";
import { tenantHelpContent } from "@/data/tenantHelpContent";

const SalesChart = lazy(() => import("@/Components/Dashboard/SalesChart"));
const PaymentMethodCard = lazy(() => import("@/Components/Dashboard/PaymentMethodCard"));
const HourlyDistributionCard = lazy(() => import("@/Components/Dashboard/HourlyDistributionCard"));

// 空状態を厳密に判定して、指標カードにゼロ値を並べるより案内UIを優先表示する。
function isEmptyState(summary: DashboardSummary, recentSales: SalesData[]): boolean {
    // 欠損レスポンスでもクラッシュせず判定継続できるよう防御的に参照する。
    const hasNoMonthlyOrders = summary?.this_month?.orders === 0;

    // 直近推移もゼロなら「まだ利用開始前」と判断し、分析UIを出さない。
    const hasNoRecentOrders = !recentSales?.length || recentSales.every((day) => day?.orders === 0);

    return hasNoMonthlyOrders && hasNoRecentOrders;
}

const ChartLoadingFallback = () => (
    <div className="flex items-center justify-center h-64">
        <div className="text-muted">チャートを読み込み中...</div>
    </div>
);

// 毎回再生成を避け、親更新時の不要レンダリングコストを抑えるため外出しする。
const EmptyState = () => (
    <GeoSurface tone="sky" topAccent elevated className="p-12 text-center">
        <div className="max-w-md mx-auto">
            <div className="text-sky-300 mb-4">
                <svg className="mx-auto h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={1.5}
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                    />
                </svg>
            </div>
            <p className="text-lg text-ink-light mb-2">まだデータがありません。</p>
            <p className="text-sm text-muted-light">注文が入ると、ここに売上や統計情報が表示されます。</p>
        </div>
    </GeoSurface>
);

export default function Index({ summary, recentSales }: DashboardIndexProps) {
    const { showHelp, openHelp, closeHelp } = useHelpPanel();

    // 部分データで誤計算した値を出さないため、必須データが揃うまで待機する。
    if (!summary || !recentSales || !summary.this_month || !summary.today) {
        return (
            <TenantLayout title="ダッシュボード">
                <Head title="ダッシュボード" />
                <div className="flex items-center justify-center h-64">
                    <div className="text-muted">データを読み込み中...</div>
                </div>
            </TenantLayout>
        );
    }

    // 初期利用フェーズでは説明UIを優先し、情報過多を避ける。
    const isEmpty = isEmptyState(summary, recentSales);

    return (
        <TenantLayout title="ダッシュボード">
            <Head title="ダッシュボード" />

            <div className="geo-fade-in">
                <div className="mb-4 flex justify-end">
                    <HelpButton onClick={openHelp} />
                </div>
                {isEmpty ? (
                    <EmptyState />
                ) : (
                    <div className="space-y-6">
                        <ExportControls />

                        {/* 最重要KPIを先頭へ置き、意思決定までの視線移動を減らす。 */}
                        <SummaryCards summary={summary} />

                        <Suspense fallback={<ChartLoadingFallback />}>
                            {/* トレンド変化を把握しやすくするため、時系列を中段に置く。 */}
                            <SalesChart initialData={recentSales} />

                            {/* 決済方法別の利用状況を表示する。 */}
                            <PaymentMethodCard />

                            {/* 商品軸と時間軸を同列に置き、施策検討を一画面で完結させる。 */}
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <TopItemsCard />
                                <HourlyDistributionCard />
                            </div>
                        </Suspense>

                        {/* 深掘り指標は最後に置き、概要確認フローを阻害しない。 */}
                        <CustomerInsightsCard />
                    </div>
                )}
            </div>

            <HelpPanel open={showHelp} onClose={closeHelp} content={tenantHelpContent["tenant-dashboard"]} />
        </TenantLayout>
    );
}
