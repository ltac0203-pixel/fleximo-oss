import { useCallback, useState } from "react";

interface UseDashboardDataFetcherOptions<TData, TParam> {
    initialData: TData;
    fetcher: (param: TParam) => Promise<TData>;
    initialLoading?: boolean;
    onFetchError?: (error: unknown, param: TParam) => void;
}

interface UseDashboardDataFetcherResult<TData, TParam> {
    data: TData;
    loading: boolean;
    fetchError: boolean;
    fetchData: (param: TParam) => Promise<void>;
}

export default function useDashboardDataFetcher<TData, TParam>({
    initialData,
    fetcher,
    initialLoading = false,
    onFetchError,
}: UseDashboardDataFetcherOptions<TData, TParam>): UseDashboardDataFetcherResult<TData, TParam> {
    const [data, setData] = useState<TData>(initialData);
    const [loading, setLoading] = useState(initialLoading);
    const [fetchError, setFetchError] = useState(false);

    const fetchData = useCallback(
        async (param: TParam) => {
            setLoading(true);
            setFetchError(false);

            try {
                const nextData = await fetcher(param);
                setData(nextData);
            } catch (error) {
                setFetchError(true);
                onFetchError?.(error, param);
            } finally {
                setLoading(false);
            }
        },
        [fetcher, onFetchError],
    );

    return { data, loading, fetchError, fetchData };
}
