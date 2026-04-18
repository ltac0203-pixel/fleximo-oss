import { CustomerMenuCategory } from "@/types";
import { useCallback, useEffect, useRef } from "react";

interface CategoryTabsProps {
    categories: CustomerMenuCategory[];
    activeCategoryId: number | null;
    onCategoryChange: (categoryId: number) => void;
}

export default function CategoryTabs({ categories, activeCategoryId, onCategoryChange }: CategoryTabsProps) {
    const tabsRef = useRef<HTMLDivElement>(null);
    const activeTabRef = useRef<HTMLButtonElement>(null);
    const tabRefs = useRef<Map<number, HTMLButtonElement>>(new Map());

    // 外部要因でカテゴリが切り替わっても、現在選択を視界内に保って迷子を防ぐ。
    useEffect(() => {
        if (activeTabRef.current && tabsRef.current) {
            const container = tabsRef.current;
            const tab = activeTabRef.current;
            const containerRect = container.getBoundingClientRect();
            const tabRect = tab.getBoundingClientRect();

            // 隠れたままだと選択状態を認識しづらいため、中央寄せで見える位置へ移動する。
            if (tabRect.left < containerRect.left || tabRect.right > containerRect.right) {
                tab.scrollIntoView({
                    behavior: "smooth",
                    block: "nearest",
                    inline: "center",
                });
            }
        }
    }, [activeCategoryId]);

    const handleTabClick = (categoryId: number) => {
        // スクロール制御を親へ集約し、タブ側は選択意図の通知だけに限定する。
        onCategoryChange(categoryId);
    };

    // キーボードナビゲーション: ArrowRight/Left, Home/End で循環移動
    const handleKeyDown = useCallback(
        (e: React.KeyboardEvent, currentId: number) => {
            const currentIndex = categories.findIndex((cat) => cat.id === currentId);
            let nextIndex: number | null = null;

            if (e.key === "ArrowRight") {
                e.preventDefault();
                nextIndex = currentIndex < categories.length - 1 ? currentIndex + 1 : 0;
            } else if (e.key === "ArrowLeft") {
                e.preventDefault();
                nextIndex = currentIndex > 0 ? currentIndex - 1 : categories.length - 1;
            } else if (e.key === "Home") {
                e.preventDefault();
                nextIndex = 0;
            } else if (e.key === "End") {
                e.preventDefault();
                nextIndex = categories.length - 1;
            }

            if (nextIndex !== null) {
                const nextCategory = categories[nextIndex];
                const nextTab = tabRefs.current.get(nextCategory.id);

                // requestAnimationFrameでフォーカス移動を完了させてからスクロール実行
                requestAnimationFrame(() => {
                    if (nextTab) {
                        nextTab.focus();
                        onCategoryChange(nextCategory.id);
                    }
                });
            }
        },
        [categories, onCategoryChange],
    );

    return (
        <div className="relative px-2 py-2 sm:px-4">
            <div
                ref={tabsRef}
                role="tablist"
                aria-label="メニューカテゴリ"
                className="geo-surface scrollbar-hide flex gap-2 overflow-x-auto border-edge/90 bg-white/90 px-2 py-2 shadow-sm"
            >
                {categories.map((category) => {
                    const isActive = activeCategoryId === category.id;
                    return (
                        <button
                            key={category.id}
                            id={`tab-${category.id}`}
                            ref={(el) => {
                                if (el) {
                                    tabRefs.current.set(category.id, el);
                                } else {
                                    tabRefs.current.delete(category.id);
                                }
                                if (isActive && activeTabRef.current !== el) {
                                    activeTabRef.current = el;
                                }
                            }}
                            role="tab"
                            aria-selected={isActive}
                            aria-controls={`panel-${category.id}`}
                            tabIndex={isActive ? 0 : -1}
                            onClick={() => handleTabClick(category.id)}
                            onKeyDown={(e) => handleKeyDown(e, category.id)}
                            className={`group relative flex min-h-[44px] flex-shrink-0 items-center border px-4 py-2 text-sm font-semibold whitespace-nowrap transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:ring-offset-2 lg:px-5 lg:text-[15px] ${
                                isActive
                                    ? "border-sky-500 bg-sky-600 text-white shadow-geo-sky"
                                    : "border-edge bg-white text-ink-light geo-hover-underline hover:bg-sky-50 hover:text-sky-700"
                            }`}
                        >
                            {isActive && (
                                <span
                                    aria-hidden="true"
                                    className="absolute top-0 left-0 h-0.5 w-full bg-cyan-200"
                                />
                            )}
                            <span>{category.name}</span>
                            <span
                                aria-hidden="true"
                                className={`ml-2 inline-flex min-w-[1.5rem] items-center justify-center border px-1.5 py-0.5 text-[11px] font-bold ${
                                    isActive
                                        ? "border-cyan-200/80 bg-white/15 text-cyan-100"
                                        : "border-edge bg-surface text-muted group-hover:border-sky-200 group-hover:bg-sky-100 group-hover:text-sky-700"
                                }`}
                            >
                                {category.items.length}
                            </span>
                        </button>
                    );
                })}
            </div>

            {/* 横スクロール端の見切れをやわらげ、継続スクロール可能性を示す。 */}
            <div
                className="pointer-events-none absolute top-2 bottom-2 left-2 z-10 w-6 bg-gradient-to-r from-white/95 to-transparent sm:left-4"
                aria-hidden="true"
            />
            <div
                className="pointer-events-none absolute top-2 right-2 bottom-2 z-10 w-6 bg-gradient-to-l from-white/95 to-transparent sm:right-4"
                aria-hidden="true"
            />
        </div>
    );
}
