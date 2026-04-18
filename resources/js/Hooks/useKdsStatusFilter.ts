import { useState, useCallback } from "react";
import { KdsOrderStatus } from "@/types";
import { KDS_STATUSES } from "@/Utils/kdsHelpers";

const STORAGE_KEY = "kds-active-statuses";

function loadFromStorage(): Set<KdsOrderStatus> {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
            const parsed: string[] = JSON.parse(stored);
            const valid = parsed.filter((s): s is KdsOrderStatus =>
                KDS_STATUSES.includes(s as KdsOrderStatus),
            );
            if (valid.length > 0) {
                return new Set(valid);
            }
        }
    } catch {
        // localStorage が使えない環境でも動作する
    }
    return new Set(KDS_STATUSES);
}

function saveToStorage(statuses: Set<KdsOrderStatus>): void {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify([...statuses]));
    } catch {
        // 保存失敗は無視
    }
}

export function useKdsStatusFilter() {
    const [activeStatuses, setActiveStatuses] = useState<Set<KdsOrderStatus>>(loadFromStorage);

    const toggleStatus = useCallback((status: KdsOrderStatus) => {
        setActiveStatuses((prev) => {
            const next = new Set(prev);
            if (next.has(status)) {
                // 最低1つはアクティブにする
                if (next.size <= 1) return prev;
                next.delete(status);
            } else {
                next.add(status);
            }
            saveToStorage(next);
            return next;
        });
    }, []);

    return { activeStatuses, toggleStatus };
}
