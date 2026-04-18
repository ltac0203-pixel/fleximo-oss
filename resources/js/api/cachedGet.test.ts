import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { cachedGet } from "./cachedGet";
import { apiCache } from "./cache";

function jsonResponse(payload: unknown, status = 200): Response {
    return new Response(JSON.stringify(payload), {
        status,
        headers: { "Content-Type": "application/json" },
    });
}

describe("cachedGet", () => {
    beforeEach(() => {
        vi.restoreAllMocks();
        document.cookie = "XSRF-TOKEN=test-token";
    });

    afterEach(() => {
        vi.unstubAllGlobals();
        vi.useRealTimers();
        apiCache.clear();
    });

    it("fetches data on cache miss", async () => {
        const payload = { id: 1, name: "test" };
        const fetchMock = vi.fn().mockResolvedValue(jsonResponse(payload));
        vi.stubGlobal("fetch", fetchMock);

        const result = await cachedGet<typeof payload>("/api/test", {
            swr: false,
            maxRetries: 0,
        });

        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(result.data).toEqual(payload);
        expect(result.error).toBeNull();
        expect(result.status).toBe(200);
    });

    it("returns cached data on cache hit without re-fetching", async () => {
        const payload = { id: 1 };
        const fetchMock = vi.fn().mockResolvedValue(jsonResponse(payload));
        vi.stubGlobal("fetch", fetchMock);

        await cachedGet("/api/test", { swr: false, maxRetries: 0 });
        const result = await cachedGet("/api/test", { swr: false, maxRetries: 0 });

        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(result.data).toEqual(payload);
    });

    it("re-fetches after TTL expires when SWR is off", async () => {
        vi.useFakeTimers();

        const payload1 = { version: 1 };
        const payload2 = { version: 2 };
        const fetchMock = vi
            .fn()
            .mockResolvedValueOnce(jsonResponse(payload1))
            .mockResolvedValueOnce(jsonResponse(payload2));
        vi.stubGlobal("fetch", fetchMock);

        const ttl = 5_000;

        await cachedGet("/api/test", { ttl, swr: false, maxRetries: 0 });
        expect(fetchMock).toHaveBeenCalledTimes(1);

        vi.advanceTimersByTime(ttl + 1);

        const result = await cachedGet("/api/test", { ttl, swr: false, maxRetries: 0 });

        expect(fetchMock).toHaveBeenCalledTimes(2);
        expect(result.data).toEqual(payload2);
    });

    it("returns stale data immediately and revalidates in background with SWR", async () => {
        vi.useFakeTimers();

        const stalePayload = { version: 1 };
        const freshPayload = { version: 2 };
        const fetchMock = vi
            .fn()
            .mockResolvedValueOnce(jsonResponse(stalePayload))
            .mockResolvedValueOnce(jsonResponse(freshPayload));
        vi.stubGlobal("fetch", fetchMock);

        const ttl = 5_000;
        const onRevalidate = vi.fn();

        await cachedGet("/api/test", { ttl, swr: true, maxRetries: 0 });
        expect(fetchMock).toHaveBeenCalledTimes(1);

        vi.advanceTimersByTime(ttl + 1);

        const result = await cachedGet("/api/test", {
            ttl,
            swr: true,
            maxRetries: 0,
            onRevalidate,
        });

        expect(result.data).toEqual(stalePayload);

        await vi.runAllTimersAsync();

        expect(fetchMock).toHaveBeenCalledTimes(2);
        expect(onRevalidate).toHaveBeenCalledTimes(1);
        expect(onRevalidate).toHaveBeenCalledWith(
            expect.objectContaining({
                data: freshPayload,
                error: null,
            }),
        );
    });

    it("deduplicates concurrent requests for the same URL", async () => {
        const payload = { id: 1 };
        const fetchMock = vi.fn().mockResolvedValue(jsonResponse(payload));
        vi.stubGlobal("fetch", fetchMock);

        const [result1, result2] = await Promise.all([
            cachedGet("/api/test", { swr: false, maxRetries: 0 }),
            cachedGet("/api/test", { swr: false, maxRetries: 0 }),
        ]);

        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(result1.data).toEqual(payload);
        expect(result2.data).toEqual(payload);
    });

    it("retries on 5xx errors", async () => {
        const errorPayload = { message: "error" };
        const successPayload = { id: 1 };
        const fetchMock = vi
            .fn()
            .mockResolvedValueOnce(jsonResponse(errorPayload, 500))
            .mockResolvedValueOnce(jsonResponse(successPayload));
        vi.stubGlobal("fetch", fetchMock);

        vi.useFakeTimers();

        const promise = cachedGet("/api/test", {
            swr: false,
            maxRetries: 1,
            retryBaseDelay: 100,
        });

        await vi.runAllTimersAsync();
        const result = await promise;

        expect(fetchMock).toHaveBeenCalledTimes(2);
        expect(result.data).toEqual(successPayload);
        expect(result.error).toBeNull();
    });

    it("does not retry on 4xx errors", async () => {
        const errorPayload = { message: "Not found" };
        const fetchMock = vi.fn().mockResolvedValue(jsonResponse(errorPayload, 404));
        vi.stubGlobal("fetch", fetchMock);

        const result = await cachedGet("/api/test", {
            swr: false,
            maxRetries: 2,
        });

        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(result.error).not.toBeNull();
        expect(result.status).toBe(404);
    });

    it("calls onRevalidateError when SWR background revalidation encounters an unexpected error", async () => {
        vi.useFakeTimers();

        const stalePayload = { version: 1 };
        const freshPayload = { version: 2 };
        const fetchMock = vi
            .fn()
            .mockResolvedValueOnce(jsonResponse(stalePayload))
            .mockResolvedValueOnce(jsonResponse(freshPayload));
        vi.stubGlobal("fetch", fetchMock);

        const consoleWarnSpy = vi.spyOn(console, "warn").mockImplementation(() => {});

        const ttl = 5_000;
        const onRevalidateError = vi.fn();

        await cachedGet("/api/test-err", { ttl, swr: true, maxRetries: 0 });
        expect(fetchMock).toHaveBeenCalledTimes(1);

        vi.advanceTimersByTime(ttl + 1);

        const storageError = new Error("Storage quota exceeded");
        vi.spyOn(apiCache, "set").mockImplementation(() => {
            throw storageError;
        });

        const result = await cachedGet("/api/test-err", {
            ttl,
            swr: true,
            maxRetries: 0,
            onRevalidateError,
        });

        expect(result.data).toEqual(stalePayload);

        await vi.runAllTimersAsync();

        expect(fetchMock).toHaveBeenCalledTimes(2);
        expect(onRevalidateError).toHaveBeenCalledTimes(1);
        expect(onRevalidateError).toHaveBeenCalledWith(storageError);
        expect(consoleWarnSpy).toHaveBeenCalledWith(
            "[cachedGet] SWR revalidation failed:",
            "/api/test-err",
            storageError,
        );

        consoleWarnSpy.mockRestore();
    });

    it("does not cache error responses", async () => {
        const errorPayload = { message: "error" };
        const successPayload = { id: 1 };
        const fetchMock = vi
            .fn()
            .mockResolvedValueOnce(jsonResponse(errorPayload, 500))
            .mockResolvedValueOnce(jsonResponse(successPayload));
        vi.stubGlobal("fetch", fetchMock);

        vi.useFakeTimers();

        const promise1 = cachedGet("/api/test", {
            swr: false,
            maxRetries: 0,
        });
        await vi.runAllTimersAsync();
        const result1 = await promise1;

        expect(result1.error).not.toBeNull();

        const promise2 = cachedGet("/api/test", {
            swr: false,
            maxRetries: 0,
        });
        await vi.runAllTimersAsync();
        const result2 = await promise2;

        expect(fetchMock).toHaveBeenCalledTimes(2);
        expect(result2.data).toEqual(successPayload);
        expect(result2.error).toBeNull();
    });
});
