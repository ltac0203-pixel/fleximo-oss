import { apiRequest, ApiResponse } from "./client";
import { apiCache, DEFAULT_CACHE_CONFIG } from "./cache";

export interface CachedGetOptions<T = unknown, E = unknown> {
    ttl?: number;
    swr?: boolean;
    maxRetries?: number;
    retryBaseDelay?: number;
    onRevalidate?: (response: ApiResponse<T, E>) => void;
    onRevalidateError?: (error: unknown) => void;
}

function sleep(ms: number): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

function isRetryable(status: number): boolean {
    return status === 0 || (status >= 500 && status <= 599);
}

async function fetchWithRetry<T, E>(
    url: string,
    maxRetries: number,
    retryBaseDelay: number,
): Promise<ApiResponse<T, E>> {
    let lastResponse: ApiResponse<T, E> | undefined;

    for (let attempt = 0; attempt <= maxRetries; attempt++) {
        const response = await apiRequest<T, E>(url, { method: "GET" });

        if (response.error === null || !isRetryable(response.status)) {
            return response;
        }

        lastResponse = response;

        if (attempt < maxRetries) {
            const jitter = Math.random() * 0.5 + 0.75;
            const delay = retryBaseDelay * Math.pow(2, attempt) * jitter;
            await sleep(delay);
        }
    }

    return lastResponse!;
}

export async function cachedGet<T, E = unknown>(
    url: string,
    options?: CachedGetOptions<T, E>,
): Promise<ApiResponse<T, E>> {
    const ttl = options?.ttl ?? DEFAULT_CACHE_CONFIG.ttl;
    const swr = options?.swr ?? DEFAULT_CACHE_CONFIG.swr;
    const maxRetries = options?.maxRetries ?? DEFAULT_CACHE_CONFIG.maxRetries;
    const retryBaseDelay = options?.retryBaseDelay ?? DEFAULT_CACHE_CONFIG.retryBaseDelay;

    const cached = apiCache.get<ApiResponse<T, E>>(url);

    if (cached) {
        if (apiCache.isFresh(cached)) {
            return cached.data;
        }

        if (swr) {
            const revalidate = async () => {
                try {
                    const response = await fetchWithRetry<T, E>(url, maxRetries, retryBaseDelay);
                    if (response.error === null) {
                        apiCache.set(url, response, ttl);
                    }
                    options?.onRevalidate?.(response);
                } catch (error) {
                    console.warn("[cachedGet] SWR revalidation failed:", url, error);
                    options?.onRevalidateError?.(error);
                }
            };

            void revalidate();
            return cached.data;
        }
    }

    const existing = apiCache.getInflight<ApiResponse<T, E>>(url);
    if (existing) {
        return existing;
    }

    const promise = fetchWithRetry<T, E>(url, maxRetries, retryBaseDelay).then((response) => {
        apiCache.deleteInflight(url);
        if (response.error === null) {
            apiCache.set(url, response, ttl);
        }
        return response;
    });

    apiCache.setInflight(url, promise);
    return promise;
}
