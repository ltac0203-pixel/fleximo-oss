import type { PaginationLink } from "@/types";

export interface KeyedItem<T> {
    item: T;
    key: string;
}

export function withStableKeys<T>(items: readonly T[], getBaseKey: (item: T) => string): Array<KeyedItem<T>> {
    const seenKeys = new Map<string, number>();

    return items.map((item) => {
        const baseKey = getBaseKey(item);
        const occurrence = seenKeys.get(baseKey) ?? 0;

        seenKeys.set(baseKey, occurrence + 1);

        return {
            item,
            key: occurrence === 0 ? baseKey : `${baseKey}|${occurrence}`,
        };
    });
}

export function getPaginationLinkBaseKey(link: PaginationLink): string {
    return `${link.url ?? "no-url"}|${link.label}|${link.active ? "active" : "inactive"}`;
}
