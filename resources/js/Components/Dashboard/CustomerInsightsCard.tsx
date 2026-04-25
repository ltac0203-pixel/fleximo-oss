import { useCustomerInsightsData } from "@/Hooks/useCustomerInsightsData";
import { formatNumber } from "@/Utils/formatPrice";
import GeoSurface from "@/Components/GeoSurface";
import Spinner from "@/Components/Loading/Spinner";
import DateRangeSelector from "./DateRangeSelector";

export default function CustomerInsightsCard() {
    const { range, data, loading, fetchError, onRangeChange } = useCustomerInsightsData();

    const newPercent = data && data.unique_customers > 0 ? (data.new_customers / data.unique_customers) * 100 : 0;
    const repeatPercent = data && data.unique_customers > 0 ? (data.repeat_customers / data.unique_customers) * 100 : 0;

    return (
        <GeoSurface topAccent elevated className="p-4">
            <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-medium text-ink">顧客分析</h3>
                <DateRangeSelector selected={range} onChange={onRangeChange} />
            </div>

            {loading ? (
                <div className="h-32 flex items-center justify-center">
                    <Spinner variant="muted" />
                </div>
            ) : fetchError ? (
                <div className="h-32 flex items-center justify-center text-red-500">データの取得に失敗しました</div>
            ) : !data ? (
                <div className="h-32 flex items-center justify-center text-muted">データがありません</div>
            ) : (
                <div className="space-y-4">
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div className="text-center">
                            <p className="text-sm text-muted">ユニーク顧客数</p>
                            <p className="text-2xl font-bold text-ink">{formatNumber(data.unique_customers)}</p>
                        </div>
                        <div className="text-center">
                            <p className="text-sm text-muted">新規顧客</p>
                            <p className="text-2xl font-bold text-sky-600">{formatNumber(data.new_customers)}</p>
                        </div>
                        <div className="text-center">
                            <p className="text-sm text-muted">リピート顧客</p>
                            <p className="text-2xl font-bold text-cyan-600">{formatNumber(data.repeat_customers)}</p>
                        </div>
                        <div className="text-center">
                            <p className="text-sm text-muted">リピート率</p>
                            <p className="text-2xl font-bold text-ink">{data.repeat_rate.toFixed(1)}%</p>
                        </div>
                    </div>

                    {data.unique_customers > 0 && (
                        <div>
                            <div className="flex items-center gap-4 mb-2 text-sm text-ink-light">
                                <span className="flex items-center gap-1">
                                    <span className="w-3 h-3 rounded-full bg-sky-500" />
                                    新規 {newPercent.toFixed(1)}%
                                </span>
                                <span className="flex items-center gap-1">
                                    <span className="w-3 h-3 rounded-full bg-cyan-500" />
                                    リピート {repeatPercent.toFixed(1)}%
                                </span>
                            </div>
                            <div className="h-4 flex rounded-full overflow-hidden bg-surface-dim">
                                <div
                                    className="bg-sky-500 transition-all duration-300"
                                    style={{ width: `${newPercent}%` }}
                                />
                                <div
                                    className="bg-cyan-500 transition-all duration-300"
                                    style={{ width: `${repeatPercent}%` }}
                                />
                            </div>
                        </div>
                    )}
                </div>
            )}
        </GeoSurface>
    );
}
