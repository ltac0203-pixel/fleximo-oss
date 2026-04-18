import { api } from "@/api";
import type { ApiDataResponse } from "@/api";
import useDashboardDataFetcher from "@/Hooks/useDashboardDataFetcher";
import { TopItem } from "@/types";
import { logger } from "@/Utils/logger";
import { useCallback, useEffect, useState } from "react";

export type TopItemsPeriod = "week" | "month" | "year";

/** 人気商品の取得件数上限 */
const DEFAULT_TOP_ITEMS_LIMIT = 10;

export const TOP_ITEMS_PERIOD_OPTIONS: { value: TopItemsPeriod; label: string }[] = [
    { value: "week", label: "週間" },
    { value: "month", label: "月間" },
    { value: "year", label: "年間" },
];

interface UseTopItemsDataResult {
    period: TopItemsPeriod;
    items: TopItem[];
    loading: boolean;
    fetchError: boolean;
    onPeriodChange: (period: TopItemsPeriod) => void;
}

export function useTopItemsData(): UseTopItemsDataResult {
    const [period, setPeriod] = useState<TopItemsPeriod>("month");

    const fetchTopItems = useCallback(async (selectedPeriod: TopItemsPeriod): Promise<TopItem[]> => {
        const params = new URLSearchParams({
            period: selectedPeriod,
            limit: String(DEFAULT_TOP_ITEMS_LIMIT),
        });

        const { data: result, error } = await api.cachedGet<ApiDataResponse<TopItem[]>>(
            `/api/tenant/dashboard/top-items?${params.toString()}`,
        );

        if (error || !result) {
            throw error ?? "empty result";
        }

        return result.data;
    }, []);

    const handleFetchError = useCallback((error: unknown, selectedPeriod: TopItemsPeriod) => {
        logger.error("Dashboard top items fetch failed", error, {
            period: selectedPeriod,
        });
    }, []);

    const {
        data: items,
        loading,
        fetchError,
        fetchData,
    } = useDashboardDataFetcher<TopItem[], TopItemsPeriod>({
        initialData: [],
        initialLoading: true,
        fetcher: fetchTopItems,
        onFetchError: handleFetchError,
    });

    useEffect(() => {
        void fetchData(period);
    }, [period, fetchData]);

    const onPeriodChange = useCallback((nextPeriod: TopItemsPeriod) => {
        setPeriod(nextPeriod);
    }, []);

    return {
        period,
        items,
        loading,
        fetchError,
        onPeriodChange,
    };
}
