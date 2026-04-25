import { useMemo } from "react";
import { CustomerMenuCategory, CustomerMenuItem } from "@/types";

// 1次元リストへ正規化して、仮想化ライブラリで一貫したスクロール計算を可能にする。
export type VirtualRow =
    | {
          type: "category-header";
          categoryId: number;
          categoryName: string;
          itemCount: number;
      }
    | {
          type: "item-row";
          categoryId: number;
          items: CustomerMenuItem[];
      };

interface UseVirtualizedMenuResult {
    // カテゴリ構造を平坦化して描画量を制御するための中間表現。
    rows: VirtualRow[];
    // タブ操作を即座にスクロール位置へ変換できるよう逆引きを持つ。
    categoryRowIndexMap: Map<number, number>;
    // スクロール位置から現在カテゴリを同期するために必要な逆変換。
    getRowCategoryId: (rowIndex: number) => number | null;
}

// カテゴリ階層を固定行へ落とし込み、可変件数でも描画負荷を一定に保つ。
export function useVirtualizedMenu(categories: CustomerMenuCategory[], columns: number): UseVirtualizedMenuResult {
    return useMemo(() => {
        const rows: VirtualRow[] = [];
        const categoryRowIndexMap = new Map<number, number>();

        categories.forEach((category) => {
            // タブ選択時のジャンプ先計算を O(1) にするため先に位置を記録する。
            categoryRowIndexMap.set(category.id, rows.length);

            // 先頭行を明示的に持たせて、カテゴリ境界を仮想リスト内で失わないようにする。
            rows.push({
                type: "category-header",
                categoryId: category.id,
                categoryName: category.name,
                itemCount: category.items.length,
            });

            // 列数に合わせて分割し、レスポンシブ時も行単位の高さ推定を維持する。
            const items = category.items;
            for (let i = 0; i < items.length; i += columns) {
                rows.push({
                    type: "item-row",
                    categoryId: category.id,
                    items: items.slice(i, i + columns),
                });
            }

            // 空カテゴリでも行を作り、スクロール位置とカテゴリ同期のずれを防ぐ。
            if (items.length === 0) {
                rows.push({
                    type: "item-row",
                    categoryId: category.id,
                    items: [],
                });
            }
        });

        // 行配列の正本を通すことで、外部が境界外インデックスを渡しても安全に処理する。
        const getRowCategoryId = (rowIndex: number): number | null => {
            if (rowIndex < 0 || rowIndex >= rows.length) return null;
            return rows[rowIndex].categoryId;
        };

        return {
            rows,
            categoryRowIndexMap,
            getRowCategoryId,
        };
    }, [categories, columns]);
}
