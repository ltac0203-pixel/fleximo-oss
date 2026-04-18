import { FocusEvent, KeyboardEvent, useCallback, useEffect, useState } from "react";

interface UseKeyboardChartNavigationResult<T> {
    activeItem: T | null;
    isTooltipVisible: boolean;
    onFocus: (event: FocusEvent<HTMLDivElement>) => void;
    onBlur: (event: FocusEvent<HTMLDivElement>) => void;
    onKeyDown: (event: KeyboardEvent<HTMLDivElement>) => void;
}

const NAVIGATION_KEYS = new Set(["ArrowRight", "ArrowDown", "ArrowLeft", "ArrowUp", "Home", "End"]);

export default function useKeyboardChartNavigation<T>(items: T[]): UseKeyboardChartNavigationResult<T> {
    const [activeIndex, setActiveIndex] = useState(-1);
    const [isTooltipVisible, setIsTooltipVisible] = useState(false);

    useEffect(() => {
        if (items.length === 0) {
            setActiveIndex(-1);
            setIsTooltipVisible(false);
            return;
        }

        setActiveIndex((current) => {
            if (current < 0) return 0;
            return Math.min(current, items.length - 1);
        });
    }, [items.length]);

    const onFocus = useCallback(
        (_event: FocusEvent<HTMLDivElement>) => {
            if (items.length === 0) return;

            setIsTooltipVisible(true);
            setActiveIndex((current) => {
                if (current < 0) return 0;
                return Math.min(current, items.length - 1);
            });
        },
        [items.length],
    );

    const onBlur = useCallback((event: FocusEvent<HTMLDivElement>) => {
        const nextTarget = event.relatedTarget;
        if (nextTarget instanceof Node && event.currentTarget.contains(nextTarget)) {
            return;
        }

        setIsTooltipVisible(false);
    }, []);

    const onKeyDown = useCallback(
        (event: KeyboardEvent<HTMLDivElement>) => {
            if (items.length === 0 || !NAVIGATION_KEYS.has(event.key)) return;

            event.preventDefault();
            setIsTooltipVisible(true);
            setActiveIndex((current) => {
                const start = current < 0 ? 0 : current;

                if (event.key === "ArrowRight" || event.key === "ArrowDown") {
                    return Math.min(start + 1, items.length - 1);
                }

                if (event.key === "ArrowLeft" || event.key === "ArrowUp") {
                    return Math.max(start - 1, 0);
                }

                if (event.key === "Home") {
                    return 0;
                }

                return items.length - 1;
            });
        },
        [items.length],
    );

    const activeItem = activeIndex >= 0 && activeIndex < items.length ? items[activeIndex] : null;

    return {
        activeItem,
        isTooltipVisible,
        onFocus,
        onBlur,
        onKeyDown,
    };
}
