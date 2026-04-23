import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

const incrementMock = vi.hoisted(() => vi.fn());
const decrementMock = vi.hoisted(() => vi.fn());
const attachGlobalErrorHandlersMock = vi.hoisted(() => vi.fn());

vi.mock("@/stores/loadingStore", () => ({
    loadingStore: {
        increment: incrementMock,
        decrement: decrementMock,
    },
}));

vi.mock("@/Utils/logger", () => ({
    attachGlobalErrorHandlers: attachGlobalErrorHandlersMock,
}));

describe("bootstrap", () => {
    let originalFetch: typeof window.fetch;

    beforeEach(async () => {
        vi.resetModules();
        vi.clearAllMocks();

        originalFetch = window.fetch;
        const fetchMock = vi.fn().mockResolvedValue(new Response(null, { status: 204 }));
        window.fetch = fetchMock as typeof window.fetch;

        await import("@/bootstrap");
    });

    afterEach(() => {
        window.fetch = originalFetch;
    });

    it("tracks requests by default", async () => {
        await window.fetch("/api/test");

        expect(incrementMock).toHaveBeenCalledTimes(1);
        expect(decrementMock).toHaveBeenCalledTimes(1);
    });

    it("skips tracking when suppressGlobalLoading is true", async () => {
        await window.fetch("/api/test", { suppressGlobalLoading: true } as RequestInit);

        expect(incrementMock).not.toHaveBeenCalled();
        expect(decrementMock).not.toHaveBeenCalled();
    });

    it("attaches global error handlers on boot", () => {
        expect(attachGlobalErrorHandlersMock).toHaveBeenCalledTimes(1);
    });
});
