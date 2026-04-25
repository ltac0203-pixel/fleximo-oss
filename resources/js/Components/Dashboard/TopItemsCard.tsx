import { TOP_ITEMS_PERIOD_OPTIONS, useTopItemsData } from "@/Hooks/useTopItemsData";
import { formatCurrency, formatNumber } from "@/Utils/formatPrice";
import GeoSurface from "@/Components/GeoSurface";
import SegmentedControl from "@/Components/UI/SegmentedControl";
import DashboardAsyncState from "./DashboardAsyncState";

export default function TopItemsCard() {
    const { period, items, loading, fetchError, onPeriodChange } = useTopItemsData();

    return (
        <GeoSurface topAccent elevated className="p-4">
            <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-medium text-ink">人気商品</h3>
                <SegmentedControl
                    options={TOP_ITEMS_PERIOD_OPTIONS}
                    selected={period}
                    onChange={onPeriodChange}
                />
            </div>

            <DashboardAsyncState
                loading={loading}
                fetchError={fetchError}
                isEmpty={items.length === 0}
                heightClassName="h-64"
            >
                <div className="space-y-2">
                    {items.map((item) => (
                        <div
                            key={item.menu_item_id}
                            className="geo-surface geo-hover-frame flex items-center gap-3 p-2"
                        >
                            <span className="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-sky-100 text-sm font-medium text-sky-700">
                                {item.rank}
                            </span>
                            <span className="flex-1 text-sm text-ink truncate">{item.name}</span>
                            <span className="flex-shrink-0 text-sm text-ink-light">
                                {formatNumber(item.quantity)}個
                            </span>
                            <span className="flex-shrink-0 text-sm font-medium text-ink">
                                {formatCurrency(item.revenue)}
                            </span>
                        </div>
                    ))}
                </div>
            </DashboardAsyncState>
        </GeoSurface>
    );
}
