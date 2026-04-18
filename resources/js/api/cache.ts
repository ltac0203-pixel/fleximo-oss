export interface CacheEntry<T> {
    data: T;
    timestamp: number;
    expiresAt: number;
}

export interface CacheConfig {
    ttl: number;
    swr: boolean;
    maxRetries: number;
    retryBaseDelay: number;
}

export const DEFAULT_CACHE_CONFIG: CacheConfig = {
    ttl: 60_000,
    swr: true,
    maxRetries: 2,
    retryBaseDelay: 1_000,
};

const MAX_CACHE_ENTRIES = 300;
const store = new Map<string, CacheEntry<unknown>>();
const inflight = new Map<string, Promise<unknown>>();

function touchEntry(key: string, entry: CacheEntry<unknown>): void {
    store.delete(key);
    store.set(key, entry);
}

function pruneExpired(now: number): void {
    for (const [key, entry] of store.entries()) {
        if (entry.expiresAt <= now) {
            store.delete(key);
        }
    }
}

function enforceCapacity(): void {
    while (store.size > MAX_CACHE_ENTRIES) {
        const oldestKey = store.keys().next().value;
        if (oldestKey === undefined) {
            break;
        }
        store.delete(oldestKey);
    }
}

export const apiCache = {
    get<T>(key: string): CacheEntry<T> | undefined {
        const entry = store.get(key) as CacheEntry<T> | undefined;
        if (!entry) {
            return undefined;
        }

        touchEntry(key, entry);
        return entry;
    },

    set<T>(key: string, data: T, ttl: number): void {
        const now = Date.now();
        pruneExpired(now);

        const entry: CacheEntry<T> = {
            data,
            timestamp: now,
            expiresAt: now + ttl,
        };
        store.delete(key);
        store.set(key, entry);
        enforceCapacity();
    },

    isFresh(entry: CacheEntry<unknown>): boolean {
        return Date.now() < entry.expiresAt;
    },

    invalidate(url: string): void {
        store.delete(url);
    },

    invalidateByPrefix(prefix: string): void {
        for (const key of store.keys()) {
            if (key.startsWith(prefix)) {
                store.delete(key);
            }
        }
    },

    clear(): void {
        store.clear();
    },

    size(): number {
        return store.size;
    },

    getInflight<T>(key: string): Promise<T> | undefined {
        return inflight.get(key) as Promise<T> | undefined;
    },

    setInflight<T>(key: string, promise: Promise<T>): void {
        inflight.set(key, promise);
    },

    deleteInflight(key: string): void {
        inflight.delete(key);
    },
};
