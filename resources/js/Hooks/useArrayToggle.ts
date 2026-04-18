import { useCallback } from "react";

// 追加/削除ロジックを共通化し、選択UIごとの差分バグを減らす。
export function useArrayToggle<T>(currentItems: T[], onChange: (items: T[]) => void) {
    return useCallback(
        (item: T) => {
            const newItems = currentItems.includes(item)
                ? currentItems.filter((i) => i !== item)
                : [...currentItems, item];
            onChange(newItems);
        },
        [currentItems, onChange],
    );
}
