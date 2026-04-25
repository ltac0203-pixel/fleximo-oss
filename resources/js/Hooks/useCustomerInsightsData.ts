import { api, buildQuery, ENDPOINTS } from "@/api";
import type { ApiDataResponse } from "@/api";
import { DateRange, getDateRangeParams } from "@/Components/Dashboard/DateRangeSelector";
import useDashboardDataFetcher from "@/Hooks/useDashboardDataFetcher";
import { CustomerInsights } from "@/types";
import { logger } from "@/Utils/logger";
import { useCallback, useEffect, useState } from "react";

interface UseCustomerInsightsDataResult {
    range: DateRange;
    data: CustomerInsights | null;
    loading: boolean;
    fetchError: boolean;
    onRangeChange: (range: DateRange) => void;
}

export function useCustomerInsightsData(): UseCustomerInsightsDataResult {
    const [range, setRange] = useState<DateRange>("month");

    const fetchCustomerInsights = useCallback(async (selectedRange: DateRange): Promise<CustomerInsights | null> => {
        const { start_date, end_date } = getDateRangeParams(selectedRange);
        const url = `${ENDPOINTS.tenant.dashboard.customerInsights}${buildQuery({ start_date, end_date })}`;

        const { data: result, error } = await api.cachedGet<ApiDataResponse<CustomerInsights>>(url);

        if (error || !result) {
            throw error ?? "empty result";
        }

        return result.data;
    }, []);

    const handleFetchError = useCallback((error: unknown, selectedRange: DateRange) => {
        logger.error("Dashboard customer insights fetch failed", error, {
            range: selectedRange,
        });
    }, []);

    const {
        data,
        loading,
        fetchError,
        fetchData,
    } = useDashboardDataFetcher<CustomerInsights | null, DateRange>({
        initialData: null,
        initialLoading: true,
        fetcher: fetchCustomerInsights,
        onFetchError: handleFetchError,
    });

    useEffect(() => {
        void fetchData(range);
    }, [range, fetchData]);

    const onRangeChange = useCallback((nextRange: DateRange) => {
        setRange(nextRange);
    }, []);

    return {
        range,
        data,
        loading,
        fetchError,
        onRangeChange,
    };
}
