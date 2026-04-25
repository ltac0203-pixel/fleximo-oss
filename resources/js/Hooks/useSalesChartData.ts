import { api, buildQuery, ENDPOINTS } from "@/api";
import type { ApiDataResponse } from "@/api";
import useDashboardDataFetcher from "@/Hooks/useDashboardDataFetcher";
import { SalesData, SalesPeriod } from "@/types";
import { logger } from "@/Utils/logger";
import { useCallback, useMemo, useState } from "react";

type PeriodOffset = { unit: "date" | "month"; value: number };

/** 期間ごとの日付オフセット設定 */
const PERIOD_OFFSETS: Record<SalesPeriod, PeriodOffset> = {
    daily: { unit: "date", value: 6 },
    weekly: { unit: "date", value: 28 },
    monthly: { unit: "month", value: 5 },
};

interface DateRange {
    startDate: string;
    endDate: string;
}

export interface SalesChartPoint extends SalesData {
    dateLabel: string;
}

interface UseSalesChartDataResult {
    period: SalesPeriod;
    chartData: SalesChartPoint[];
    loading: boolean;
    fetchError: boolean;
    onPeriodChange: (period: SalesPeriod) => void;
}

function toIsoDate(date: Date): string {
    return date.toISOString().split("T")[0];
}

function resolveDateRange(period: SalesPeriod): DateRange {
    const endDate = new Date();
    const startDate = new Date();
    const offset = PERIOD_OFFSETS[period];

    if (offset.unit === "date") {
        startDate.setDate(startDate.getDate() - offset.value);
    } else {
        startDate.setMonth(startDate.getMonth() - offset.value);
    }

    return {
        startDate: toIsoDate(startDate),
        endDate: toIsoDate(endDate),
    };
}

function formatDateLabel(date: string, period: SalesPeriod): string {
    if (period === "monthly") {
        const [, month] = date.split("-");
        return `${month}月`;
    }

    const parsedDate = new Date(date);
    return `${parsedDate.getMonth() + 1}/${parsedDate.getDate()}`;
}

export function useSalesChartData(initialData: SalesData[]): UseSalesChartDataResult {
    const [period, setPeriod] = useState<SalesPeriod>("daily");

    const fetchSalesData = useCallback(async (selectedPeriod: SalesPeriod): Promise<SalesData[]> => {
        const { startDate, endDate } = resolveDateRange(selectedPeriod);
        const url = `${ENDPOINTS.tenant.dashboard.sales}${buildQuery({
            period: selectedPeriod,
            start_date: startDate,
            end_date: endDate,
        })}`;

        const { data: result, error } = await api.cachedGet<ApiDataResponse<SalesData[]>>(url);

        if (error || !result) {
            throw error ?? "empty result";
        }

        return result.data;
    }, []);

    const handleFetchError = useCallback((error: unknown, selectedPeriod: SalesPeriod) => {
        logger.error("Dashboard sales fetch failed", error, {
            period: selectedPeriod,
        });
    }, []);

    const { data, loading, fetchError, fetchData } = useDashboardDataFetcher<SalesData[], SalesPeriod>({
        initialData: initialData ?? [],
        fetcher: fetchSalesData,
        onFetchError: handleFetchError,
    });

    const onPeriodChange = useCallback(
        (nextPeriod: SalesPeriod) => {
            setPeriod(nextPeriod);
            void fetchData(nextPeriod);
        },
        [fetchData],
    );

    const chartData = useMemo(
        () =>
            (data ?? []).map((item) => ({
                ...item,
                dateLabel: formatDateLabel(item.date, period),
            })),
        [data, period],
    );

    return {
        period,
        chartData,
        loading,
        fetchError,
        onPeriodChange,
    };
}
