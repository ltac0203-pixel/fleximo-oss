import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { apiCache } from "./cache";

describe("apiCache", () => {
    const maxEntries = 300;

    beforeEach(() => {
        apiCache.clear();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it("stores and retrieves data with set/get", () => {
        apiCache.set("key1", { id: 1 }, 60_000);

        const entry = apiCache.get<{ id: number }>("key1");

        expect(entry).toBeDefined();
        expect(entry!.data).toEqual({ id: 1 });
    });

    it("returns undefined for missing keys", () => {
        const entry = apiCache.get("nonexistent");

        expect(entry).toBeUndefined();
    });

    it("marks entry as stale after TTL expires", () => {
        vi.useFakeTimers();

        apiCache.set("key1", { id: 1 }, 5_000);
        const entry = apiCache.get("key1")!;

        expect(apiCache.isFresh(entry)).toBe(true);

        vi.advanceTimersByTime(5_001);

        expect(apiCache.isFresh(entry)).toBe(false);
    });

    it("reports isFresh correctly within TTL", () => {
        vi.useFakeTimers();

        apiCache.set("key1", "data", 10_000);
        const entry = apiCache.get("key1")!;

        expect(apiCache.isFresh(entry)).toBe(true);

        vi.advanceTimersByTime(9_999);
        expect(apiCache.isFresh(entry)).toBe(true);

        vi.advanceTimersByTime(2);
        expect(apiCache.isFresh(entry)).toBe(false);
    });

    it("invalidates a single cache entry by exact key", () => {
        apiCache.set("key1", "a", 60_000);
        apiCache.set("key2", "b", 60_000);

        apiCache.invalidate("key1");

        expect(apiCache.get("key1")).toBeUndefined();
        expect(apiCache.get("key2")).toBeDefined();
    });

    it("invalidates multiple entries by prefix", () => {
        apiCache.set("/api/menu/1", "a", 60_000);
        apiCache.set("/api/menu/2", "b", 60_000);
        apiCache.set("/api/orders/1", "c", 60_000);

        apiCache.invalidateByPrefix("/api/menu");

        expect(apiCache.get("/api/menu/1")).toBeUndefined();
        expect(apiCache.get("/api/menu/2")).toBeUndefined();
        expect(apiCache.get("/api/orders/1")).toBeDefined();
    });

    it("clears all cache entries", () => {
        apiCache.set("key1", "a", 60_000);
        apiCache.set("key2", "b", 60_000);

        apiCache.clear();

        expect(apiCache.get("key1")).toBeUndefined();
        expect(apiCache.get("key2")).toBeUndefined();
    });

    it("evicts the least recently used entry when capacity is exceeded", () => {
        for (let i = 0; i < maxEntries; i++) {
            apiCache.set(`key-${i}`, i, 60_000);
        }

        expect(apiCache.size()).toBe(maxEntries);

        apiCache.get("key-0");
        apiCache.set(`key-${maxEntries}`, maxEntries, 60_000);

        expect(apiCache.size()).toBe(maxEntries);
        expect(apiCache.get("key-0")).toBeDefined();
        expect(apiCache.get("key-1")).toBeUndefined();
        expect(apiCache.get(`key-${maxEntries}`)).toBeDefined();
    });

    it("evicts the oldest entry when capacity is exceeded without reads", () => {
        for (let i = 0; i < maxEntries + 1; i++) {
            apiCache.set(`key-${i}`, i, 60_000);
        }

        expect(apiCache.size()).toBe(maxEntries);
        expect(apiCache.get("key-0")).toBeUndefined();
        expect(apiCache.get("key-1")).toBeDefined();
    });

    it("prunes expired entries before applying capacity eviction", () => {
        vi.useFakeTimers();

        for (let i = 0; i < maxEntries - 1; i++) {
            apiCache.set(`live-${i}`, i, 60_000);
        }
        apiCache.set("expired", "stale", 1_000);

        expect(apiCache.size()).toBe(maxEntries);

        vi.advanceTimersByTime(1_001);
        apiCache.set("new-key", "new", 60_000);

        expect(apiCache.size()).toBe(maxEntries);
        expect(apiCache.get("expired")).toBeUndefined();
        expect(apiCache.get("live-0")).toBeDefined();
        expect(apiCache.get("new-key")).toBeDefined();
    });

    it("manages inflight promises", () => {
        const promise = Promise.resolve({ data: "result" });

        expect(apiCache.getInflight("key1")).toBeUndefined();

        apiCache.setInflight("key1", promise);
        expect(apiCache.getInflight("key1")).toBe(promise);

        apiCache.deleteInflight("key1");
        expect(apiCache.getInflight("key1")).toBeUndefined();
    });
});
