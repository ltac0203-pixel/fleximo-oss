import { api, buildQuery, ENDPOINTS } from "@/api";
import type { ApiDataResponse } from "@/api";
import { DateRange, getDateRangeParams } from "@/Components/Dashboard/DateRangeSelector";
import useDashboardDataFetcher from "@/Hooks/useDashboardDataFetcher";
import { PaymentMethodStats, PaymentMethodStatsItem } from "@/types";
import { logger } from "@/Utils/logger";
import { useCallback, useEffect, useState } from "react";

interface PaymentMethodStatsResponse {
    methods?: PaymentMethodStatsItem[] | null;
    total_count?: number | null;
    total_amount?: number | null;
}

const EMPTY_PAYMENT_METHOD_STATS: PaymentMethodStats = {
    methods: [],
    total_count: 0,
    total_amount: 0,
};

function normalizePaymentMethodStats(stats: PaymentMethodStatsResponse | null | undefined): PaymentMethodStats {
    return {
        methods: stats?.methods ?? [],
        total_count: stats?.total_count ?? 0,
        total_amount: stats?.total_amount ?? 0,
    };
}

interface UsePaymentMethodDataResult {
    range: DateRange;
    data: PaymentMethodStats;
    loading: boolean;
    fetchError: boolean;
    onRangeChange: (range: DateRange) => void;
}

export function usePaymentMethodData(): UsePaymentMethodDataResult {
    const [range, setRange] = useState<DateRange>("month");

    const fetchPaymentMethods = useCallback(async (selectedRange: DateRange): Promise<PaymentMethodStats> => {
        const { start_date, end_date } = getDateRangeParams(selectedRange);
        const url = `${ENDPOINTS.tenant.dashboard.paymentMethods}${buildQuery({ start_date, end_date })}`;

        const { data: result, error } = await api.cachedGet<ApiDataResponse<PaymentMethodStatsResponse | null>>(url);

        if (error || !result) {
            throw error ?? "empty result";
        }

        return normalizePaymentMethodStats(result.data);
    }, []);

    const handleFetchError = useCallback((error: unknown, selectedRange: DateRange) => {
        logger.error("Dashboard payment methods fetch failed", error, {
            range: selectedRange,
        });
    }, []);

    const {
        data,
        loading,
        fetchError,
        fetchData,
    } = useDashboardDataFetcher<PaymentMethodStats, DateRange>({
        initialData: EMPTY_PAYMENT_METHOD_STATS,
        initialLoading: true,
        fetcher: fetchPaymentMethods,
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
