import { getXsrfToken } from "@/Utils/xsrfToken";
import { logger } from "@/Utils/logger";
import { normalizeErrorMessage, toError } from "@/Utils/errorHelpers";
import { cachedGet } from "./cachedGet";
import type { CachedGetOptions } from "./cachedGet";

export interface ApiResponse<T, E = unknown> {
    data: T | null;
    errorData: E | null;
    error: string | null;
    status: number;
}

export interface ApiDataResponse<T> {
    data: T;
}

export interface ApiRequestOptions extends RequestInit {
    // バックグラウンド取得時に全画面ローディングを抑止する。
    suppressGlobalLoading?: boolean;
}

type ApiOperationOptions = Omit<ApiRequestOptions, "method" | "body">;

const REQUEST_TIMEOUT_MS = 30_000;

const DEFAULT_HEADERS = {
    "Content-Type": "application/json",
    Accept: "application/json",
};

const IDEMPOTENT_POST_PATHS = new Set([
    "/api/customer/checkout",
    "/api/customer/payments/finalize",
    "/api/customer/payments/3ds-callback",
]);

const isRecord = (value: unknown): value is Record<string, unknown> => typeof value === "object" && value !== null;

const resolveRequestPath = (url: string): string => {
    try {
        const base = typeof window !== "undefined" ? window.location.origin : "http://localhost";
        return new URL(url, base).pathname;
    } catch {
        return url.split("?")[0];
    }
};

const shouldAttachIdempotencyKey = (url: string, method: string): boolean =>
    method.toUpperCase() === "POST" && IDEMPOTENT_POST_PATHS.has(resolveRequestPath(url));

const hasIdempotencyKeyHeader = (headers?: HeadersInit): boolean => {
    if (!headers) {
        return false;
    }

    const normalizedHeaders = new Headers(headers);
    return normalizedHeaders.has("Idempotency-Key");
};

const generateIdempotencyKey = (): string => {
    if (typeof crypto !== "undefined" && typeof crypto.randomUUID === "function") {
        return crypto.randomUUID();
    }

    // randomUUID が利用できない環境向けの UUIDv4 フォールバック。
    return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, (character) => {
        const random = Math.floor(Math.random() * 16);
        const value = character === "x" ? random : (random & 0x3) | 0x8;
        return value.toString(16);
    });
};

const safeParseJson = async (response: Response): Promise<unknown> => {
    if (response.status === 204) {
        return null;
    }

    const contentType = response.headers.get("content-type")?.toLowerCase() ?? "";
    if (!contentType.includes("json")) {
        return null;
    }

    try {
        return await response.json();
    } catch {
        return null;
    }
};

const extractErrorMessage = (payload: unknown): string | null => {
    if (!isRecord(payload)) {
        return null;
    }

    if (typeof payload.message === "string") {
        return payload.message;
    }

    if (isRecord(payload.error) && typeof payload.error.message === "string") {
        return payload.error.message;
    }

    return null;
};

type FetchRequestInit = RequestInit & { suppressGlobalLoading?: boolean };

export async function apiRequest<T, E = unknown>(
    url: string,
    options: ApiRequestOptions = {},
): Promise<ApiResponse<T, E>> {
    try {
        const method = (options.method ?? "GET").toUpperCase();
        const requiresIdempotencyHeader = shouldAttachIdempotencyKey(url, method);
        const shouldGenerateIdempotencyKey = requiresIdempotencyHeader && !hasIdempotencyKeyHeader(options.headers);
        const requestHeaders = new Headers(options.headers);

        if (shouldGenerateIdempotencyKey) {
            requestHeaders.set("Idempotency-Key", generateIdempotencyKey());
        }

        const headers: HeadersInit = {
            ...DEFAULT_HEADERS,
            "X-XSRF-TOKEN": getXsrfToken(),
            ...Object.fromEntries(requestHeaders.entries()),
        };

        const signal = options.signal ?? AbortSignal.timeout(REQUEST_TIMEOUT_MS);

        const response = await fetch(url, {
            ...options,
            method,
            headers,
            credentials: "include",
            signal,
        } as FetchRequestInit);

        const payload = await safeParseJson(response);

        if (!response.ok) {
            const fallbackMessage = `エラーが発生しました (${response.status})`;
            return {
                data: null,
                errorData: (payload as E | null) ?? null,
                error: normalizeErrorMessage(extractErrorMessage(payload), fallbackMessage),
                status: response.status,
            };
        }

        return {
            data: (payload as T | null) ?? null,
            errorData: null,
            error: null,
            status: response.status,
        };
    } catch (err) {
        const error = toError(err);

        if (error.name === "TimeoutError") {
            logger.error("API request timeout", error, { url });
            return {
                data: null,
                errorData: null,
                error: "リクエストがタイムアウトしました",
                status: 0,
            };
        }

        if (error.name === "AbortError") {
            logger.error("API request aborted", error, { url });
            return {
                data: null,
                errorData: null,
                error: "リクエストが中断されました",
                status: 0,
            };
        }

        logger.error("API request error", error, { url });
        return {
            data: null,
            errorData: null,
            error: "通信エラーが発生しました",
            status: 0,
        };
    }
}

export const api = {
    get: <T, E = unknown>(url: string, options: ApiOperationOptions = {}) =>
        apiRequest<T, E>(url, { ...options, method: "GET" }),
    post: <T, E = unknown>(url: string, body?: unknown, options: ApiOperationOptions = {}) =>
        apiRequest<T, E>(url, {
            ...options,
            method: "POST",
            body: body === undefined ? undefined : JSON.stringify(body),
        }),
    patch: <T, E = unknown>(url: string, body?: unknown, options: ApiOperationOptions = {}) =>
        apiRequest<T, E>(url, {
            ...options,
            method: "PATCH",
            body: body === undefined ? undefined : JSON.stringify(body),
        }),
    put: <T, E = unknown>(url: string, body?: unknown, options: ApiOperationOptions = {}) =>
        apiRequest<T, E>(url, {
            ...options,
            method: "PUT",
            body: body === undefined ? undefined : JSON.stringify(body),
        }),
    delete: <T, E = unknown>(url: string, options: ApiOperationOptions = {}) =>
        apiRequest<T, E>(url, { ...options, method: "DELETE" }),
    cachedGet: <T, E = unknown>(url: string, options?: CachedGetOptions<T, E>) => cachedGet<T, E>(url, options),
};
