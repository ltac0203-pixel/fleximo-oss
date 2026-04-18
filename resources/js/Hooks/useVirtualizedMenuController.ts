import { useWindowVirtualizer } from "@tanstack/react-virtual";
import { useCallback, useEffect, useRef } from "react";
import { VirtualRow } from "@/Hooks/useVirtualizedMenu";

// ヘッダー行の見積り誤差でカテゴリ同期がずれないよう、実UIの高さを固定する。
const CATEGORY_HEADER_HEIGHT = 48;
// 再計測コストを避けるため、通常行は固定高として扱う。
const ITEM_ROW_HEIGHT = 132;
// 空カテゴリでも高さを確保し、スクロール位置のジャンプを防ぐ。
const EMPTY_ROW_HEIGHT = 60;
// 固定ヘッダーに隠れない位置へスクロールするための補正値。
const HEADER_OFFSET = 120;
// 高速スクロール時の白画面を防ぐため、前後を少し多めに描画する。
const OVERSCAN = 5;
// スムーズスクロール完了を待つための猶予時間。
const SCROLL_SYNC_SETTLE_MS = 1000;

interface UseVirtualizedMenuControllerOptions {
    rows: VirtualRow[];
    categoryRowIndexMap: Map<number, number>;
    getRowCategoryId: (rowIndex: number) => number | null;
    activeCategoryId: number | null;
    onActiveCategoryChange: (categoryId: number) => void;
    scrollToCategoryId: number | null;
    onScrollComplete: () => void;
}

function resolveRowHeight(row: VirtualRow | undefined): number {
    if (!row) return ITEM_ROW_HEIGHT;
    if (row.type === "category-header") return CATEGORY_HEADER_HEIGHT;
    if (row.items.length === 0) return EMPTY_ROW_HEIGHT;
    return ITEM_ROW_HEIGHT;
}

export function useVirtualizedMenuController({
    rows,
    categoryRowIndexMap,
    getRowCategoryId,
    activeCategoryId,
    onActiveCategoryChange,
    scrollToCategoryId,
    onScrollComplete,
}: UseVirtualizedMenuControllerOptions) {
    // 自前スクロール由来のscrollイベントでカテゴリ判定が暴走しないようにする。
    const isProgrammaticScrollingRef = useRef(false);
    const scrollTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    // 仮想化と手動スクロール計算で同じ高さ推定を使い、座標ズレを防ぐ。
    const estimateSize = useCallback((index: number): number => resolveRowHeight(rows[index]), [rows]);

    // window基準で仮想化し、カテゴリ数が多くてもDOMノード数を抑える。
    const virtualizer = useWindowVirtualizer({
        count: rows.length,
        estimateSize,
        overscan: OVERSCAN,
        scrollMargin: HEADER_OFFSET,
    });

    // スクロール追従でタブ状態を更新し、一覧の現在地を見失わないようにする。
    const handleScroll = useCallback(() => {
        if (isProgrammaticScrollingRef.current) return;

        const scrollTop = window.scrollY;
        const virtualItems = virtualizer.getVirtualItems();

        for (const virtualItem of virtualItems) {
            const rowTop = virtualItem.start - HEADER_OFFSET;
            if (rowTop + virtualItem.size <= scrollTop) continue;

            const categoryId = getRowCategoryId(virtualItem.index);
            if (categoryId !== null && categoryId !== activeCategoryId) {
                onActiveCategoryChange(categoryId);
            }
            break;
        }
    }, [virtualizer, getRowCategoryId, activeCategoryId, onActiveCategoryChange]);

    useEffect(() => {
        window.addEventListener("scroll", handleScroll, { passive: true });
        return () => window.removeEventListener("scroll", handleScroll);
    }, [handleScroll]);

    // タブ選択時にカテゴリIDを行座標へ変換し、一覧とタブを同期する。
    useEffect(() => {
        if (scrollToCategoryId === null) return;

        const rowIndex = categoryRowIndexMap.get(scrollToCategoryId);
        if (rowIndex === undefined) {
            onScrollComplete();
            return;
        }

        isProgrammaticScrollingRef.current = true;

        let scrollPosition = 0;
        for (let index = 0; index < rowIndex; index++) {
            scrollPosition += estimateSize(index);
        }

        window.scrollTo({
            top: scrollPosition,
            behavior: "smooth",
        });

        if (scrollTimeoutRef.current) {
            clearTimeout(scrollTimeoutRef.current);
        }

        scrollTimeoutRef.current = setTimeout(() => {
            isProgrammaticScrollingRef.current = false;
            onScrollComplete();
        }, SCROLL_SYNC_SETTLE_MS);
    }, [scrollToCategoryId, categoryRowIndexMap, estimateSize, onScrollComplete]);

    // アンマウント後の遅延コールバック実行を防ぐ。
    useEffect(() => {
        return () => {
            if (scrollTimeoutRef.current) {
                clearTimeout(scrollTimeoutRef.current);
            }
        };
    }, []);

    return {
        virtualItems: virtualizer.getVirtualItems(),
        totalSize: virtualizer.getTotalSize(),
        scrollMargin: HEADER_OFFSET,
    };
}
