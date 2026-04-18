import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { apiRequest } from "./client";

const UUID_V4_PATTERN = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

const getRequestHeaders = (fetchMock: ReturnType<typeof vi.fn>): Headers => {
    const [, requestInit] = fetchMock.mock.calls[0] as [string, RequestInit];
    return new Headers(requestInit.headers);
};

describe("apiRequest", () => {
    beforeEach(() => {
        vi.restoreAllMocks();
        document.cookie = "XSRF-TOKEN=test-token";
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it("returns parsed data for successful JSON responses", async () => {
        const payload = { data: { id: 1, name: "test" } };
        const fetchMock = vi.fn().mockResolvedValue(
            new Response(JSON.stringify(payload), {
                status: 200,
                headers: { "Content-Type": "application/json" },
            }),
        );
        vi.stubGlobal("fetch", fetchMock);

        const result = await apiRequest<typeof payload>("/api/test");

        expect(result).toEqual({
            data: payload,
            errorData: null,
            error: null,
            status: 200,
        });
        expect(fetchMock).toHaveBeenCalledWith(
            "/api/test",
            expect.objectContaining({
                method: "GET",
                credentials: "include",
                headers: expect.objectContaining({
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-XSRF-TOKEN": "test-token",
                }),
            }),
        );
    });

    it("returns null data for 204 responses", async () => {
        const fetchMock = vi.fn().mockResolvedValue(new Response(null, { status: 204 }));
        vi.stubGlobal("fetch", fetchMock);

        const result = await apiRequest("/api/test");

        expect(result).toEqual({
            data: null,
            errorData: null,
            error: null,
            status: 204,
        });
    });

    it("extracts error message from nested error payload", async () => {
        const fetchMock = vi.fn().mockResolvedValue(
            new Response(
                JSON.stringify({
                    error: { message: "カード登録に失敗しました。" },
                }),
                {
                    status: 400,
                    headers: { "Content-Type": "application/json" },
                },
            ),
        );
        vi.stubGlobal("fetch", fetchMock);

        const result = await apiRequest("/api/test");

        expect(result).toEqual({
            data: null,
            errorData: {
                error: { message: "カード登録に失敗しました。" },
            },
            error: "カード登録に失敗しました。",
            status: 400,
        });
    });

    it("extracts error message from top-level message payload", async () => {
        const fetchMock = vi.fn().mockResolvedValue(
            new Response(JSON.stringify({ message: "権限がありません。" }), {
                status: 403,
                headers: { "Content-Type": "application/json" },
            }),
        );
        vi.stubGlobal("fetch", fetchMock);

        const result = await apiRequest("/api/test");

        expect(result).toEqual({
            data: null,
            errorData: {
                message: "権限がありません。",
            },
            error: "権限がありません。",
            status: 403,
        });
    });

    it("uses fallback message for non-JSON error responses", async () => {
        const fetchMock = vi.fn().mockResolvedValue(
            new Response("Internal Server Error", {
                status: 500,
                headers: { "Content-Type": "text/plain" },
            }),
        );
        vi.stubGlobal("fetch", fetchMock);

        const result = await apiRequest("/api/test");

        expect(result).toEqual({
            data: null,
            errorData: null,
            error: "エラーが発生しました (500)",
            status: 500,
        });
    });

    it("returns network error response when fetch throws", async () => {
        const fetchMock = vi.fn().mockRejectedValue(new Error("Network unreachable"));
        vi.stubGlobal("fetch", fetchMock);

        const result = await apiRequest("/api/test");

        expect(result).toEqual({
            data: null,
            errorData: null,
            error: "通信エラーが発生しました",
            status: 0,
        });
    });

    it("returns validation error payload in errorData for 422 responses", async () => {
        const validationPayload = {
            message: "The given data was invalid.",
            errors: {
                name: ["名前は必須です。"],
            },
        };
        const fetchMock = vi.fn().mockResolvedValue(
            new Response(JSON.stringify(validationPayload), {
                status: 422,
                headers: { "Content-Type": "application/json" },
            }),
        );
        vi.stubGlobal("fetch", fetchMock);

        const result = await apiRequest<unknown, typeof validationPayload>("/api/test");

        expect(result).toEqual({
            data: null,
            errorData: validationPayload,
            error: "エラーが発生しました (422)",
            status: 422,
        });
    });

    it.each(["/api/customer/checkout", "/api/customer/payments/finalize", "/api/customer/payments/3ds-callback"])(
        "adds Idempotency-Key header for %s",
        async (url) => {
            const fetchMock = vi.fn().mockResolvedValue(new Response(null, { status: 204 }));
            vi.stubGlobal("fetch", fetchMock);

            await apiRequest(url, { method: "POST" });

            const headers = getRequestHeaders(fetchMock);
            const idempotencyKey = headers.get("Idempotency-Key");

            expect(idempotencyKey).toBeTruthy();
            expect(idempotencyKey).toMatch(UUID_V4_PATTERN);
        },
    );

    it("does not add Idempotency-Key header for non-payment POST endpoints", async () => {
        const fetchMock = vi.fn().mockResolvedValue(new Response(null, { status: 204 }));
        vi.stubGlobal("fetch", fetchMock);

        await apiRequest("/api/customer/cart/items", { method: "POST" });

        const headers = getRequestHeaders(fetchMock);
        expect(headers.get("Idempotency-Key")).toBeNull();
    });

    it("does not overwrite explicit Idempotency-Key header", async () => {
        const fetchMock = vi.fn().mockResolvedValue(new Response(null, { status: 204 }));
        vi.stubGlobal("fetch", fetchMock);

        await apiRequest("/api/customer/payments/finalize", {
            method: "POST",
            headers: {
                "Idempotency-Key": "11111111-1111-4111-8111-111111111111",
            },
        });

        const headers = getRequestHeaders(fetchMock);
        expect(headers.get("Idempotency-Key")).toBe("11111111-1111-4111-8111-111111111111");
    });

});
