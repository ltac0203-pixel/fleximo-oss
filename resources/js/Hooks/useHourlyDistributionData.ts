import { api, ENDPOINTS } from "@/api";
import type { ApiDataResponse } from "@/api";
import useDashboardDataFetcher from "@/Hooks/useDashboardDataFetcher";
import { HourlyData } from "@/types";
import { logger } from "@/Utils/logger";
import { useCallback, useEffect, useMemo, useState } from "react";

export interface HourlyChartPoint extends HourlyData {
    hourLabel: string;
}

interface UseHourlyDistributionDataResult {
    selectedDate: string;
    maxDate: string;
    chartData: HourlyChartPoint[];
    loading: boolean;
    fetchError: boolean;
    onDateChange: (date: string) => void;
}

function getTodayIsoDate(): string {
    return new Date().toISOString().split("T")[0];
}

export function useHourlyDistributionData(): UseHourlyDistributionDataResult {
    const [maxDate] = useState(getTodayIsoDate);
    const [selectedDate, setSelectedDate] = useState(maxDate);

    const fetchHourlyData = useCallback(async (date: string): Promise<HourlyData[]> => {
        const params = new URLSearchParams({ date });

        const { data: result, error } = await api.cachedGet<ApiDataResponse<HourlyData[]>>(
            `${ENDPOINTS.tenant.dashboard.hourly}?${params.toString()}`,
        );

        if (error || !result) {
            throw error ?? "empty result";
        }

        return result.data;
    }, []);

    const handleFetchError = useCallback((error: unknown, date: string) => {
        logger.error("Dashboard hourly distribution fetch failed", error, {
            date,
        });
    }, []);

    const { data, loading, fetchError, fetchData } = useDashboardDataFetcher<HourlyData[], string>({
        initialData: [],
        initialLoading: true,
        fetcher: fetchHourlyData,
        onFetchError: handleFetchError,
    });

    useEffect(() => {
        void fetchData(selectedDate);
    }, [selectedDate, fetchData]);

    const onDateChange = useCallback((date: string) => {
        setSelectedDate(date);
    }, []);

    const chartData = useMemo(
        () =>
            data.map((item) => ({
                ...item,
                hourLabel: `${item.hour}`,
            })),
        [data],
    );

    return {
        selectedDate,
        maxDate,
        chartData,
        loading,
        fetchError,
        onDateChange,
    };
}
