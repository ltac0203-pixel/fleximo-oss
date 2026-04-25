import { memo, type MutableRefObject, useEffect, useRef } from "react";
import { CustomerMenuCategory, CustomerMenuItem } from "@/types";
import { useResponsiveColumns } from "@/Hooks/useResponsiveColumns";
import { useVirtualizedMenu, VirtualRow } from "@/Hooks/useVirtualizedMenu";
import { useVirtualizedMenuController } from "@/Hooks/useVirtualizedMenuController";
import MenuItemCard from "./MenuItemCard";

interface VirtualizedMenuListProps {
    categories: CustomerMenuCategory[];
    activeCategoryId: number | null;
    onActiveCategoryChange: (categoryId: number) => void;
    onItemClick: (item: CustomerMenuItem) => void;
    // タブ側操作でも同じスクロール計算を再利用できるようIDで受け取る。
    scrollToCategoryId: number | null;
    // 完了通知を親へ返し、要求状態を確実に解放できるようにする。
    onScrollComplete: () => void;
}

function areVirtualizedMenuListPropsEqual(
    prev: VirtualizedMenuListProps,
    next: VirtualizedMenuListProps,
): boolean {
    return (
        prev.categories === next.categories &&
        prev.activeCategoryId === next.activeCategoryId &&
        prev.onActiveCategoryChange === next.onActiveCategoryChange &&
        prev.scrollToCategoryId === next.scrollToCategoryId &&
        prev.onScrollComplete === next.onScrollComplete
    );
}

export default memo(function VirtualizedMenuList({
    categories,
    activeCategoryId,
    onActiveCategoryChange,
    onItemClick,
    scrollToCategoryId,
    onScrollComplete,
}: VirtualizedMenuListProps) {
    const onItemClickRef = useRef(onItemClick);

    useEffect(() => {
        onItemClickRef.current = onItemClick;
    }, [onItemClick]);

    const columns = useResponsiveColumns();
    const { rows, categoryRowIndexMap, getRowCategoryId } = useVirtualizedMenu(categories, columns);
    const { virtualItems, totalSize, scrollMargin } = useVirtualizedMenuController({
        rows,
        categoryRowIndexMap,
        getRowCategoryId,
        activeCategoryId,
        onActiveCategoryChange,
        scrollToCategoryId,
        onScrollComplete,
    });

    return (
        <div className="min-h-screen bg-gradient-to-b from-sky-50/45 via-white/35 to-white px-2 py-3 sm:px-4 sm:py-5 lg:px-6">
            <div
                style={{
                    height: totalSize - scrollMargin,
                    position: "relative",
                }}
            >
                {virtualItems.map((virtualItem) => {
                    const row = rows[virtualItem.index];
                    if (!row) return null;

                    return (
                        <div
                            key={virtualItem.key}
                            style={{
                                position: "absolute",
                                top: 0,
                                left: 0,
                                width: "100%",
                                transform: `translateY(${virtualItem.start - scrollMargin}px)`,
                            }}
                        >
                            <VirtualRowRenderer row={row} columns={columns} onItemClickRef={onItemClickRef} />
                        </div>
                    );
                })}
            </div>
        </div>
    );
}, areVirtualizedMenuListPropsEqual);

interface VirtualRowRendererProps {
    row: VirtualRow;
    columns: number;
    onItemClickRef: MutableRefObject<(item: CustomerMenuItem) => void>;
}

const VirtualRowRenderer = memo(function VirtualRowRenderer({ row, columns, onItemClickRef }: VirtualRowRendererProps) {
    if (row.type === "category-header") {
        return (
            <div
                id={`category-header-${row.categoryId}`}
                role="region"
                aria-label={`${row.categoryName}カテゴリ`}
                tabIndex={-1}
                className="geo-surface flex h-12 items-center justify-between border-sky-200/80 bg-white/85 px-3 lg:px-4"
                data-tab-panel-id={`panel-${row.categoryId}`}
                data-tab-id={`tab-${row.categoryId}`}
            >
                <span className="truncate text-sm font-semibold text-ink lg:text-base">{row.categoryName}</span>
                <span className="ml-2 border border-sky-200 bg-sky-50 px-1.5 py-0.5 text-xs font-medium text-sky-700">
                    {row.itemCount}品
                </span>
            </div>
        );
    }

    // 空状態をここで吸収し、カード描画側の分岐を増やさない。
    if (row.items.length === 0) {
        return (
            <div className="geo-surface border-dashed border-edge bg-white/70 px-3 py-4 text-center text-sm text-muted">
                このカテゴリには商品がありません
            </div>
        );
    }

    return (
        <div
            role="list"
            aria-labelledby={`category-header-${row.categoryId}`}
            className="grid gap-3 sm:gap-4 lg:gap-4"
            style={{
                gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))`,
            }}
        >
            {row.items.map((item) => (
                <MenuItemCard key={item.id} item={item} onItemClickRef={onItemClickRef} />
            ))}
        </div>
    );
});
