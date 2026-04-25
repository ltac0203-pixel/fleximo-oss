import { useCallback, useRef } from "react";

interface UseLongPressOptions {
    onLongPress: () => void;
    onClick?: () => void;
    threshold?: number;
}

interface UseLongPressReturn {
    onMouseDown: () => void;
    onMouseUp: () => void;
    onMouseLeave: () => void;
    onTouchStart: () => void;
    onTouchEnd: () => void;
}

export function useLongPress({ onLongPress, onClick, threshold = 500 }: UseLongPressOptions): UseLongPressReturn {
    const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const isLongPressRef = useRef(false);

    const startPress = useCallback(() => {
        isLongPressRef.current = false;
        timerRef.current = setTimeout(() => {
            isLongPressRef.current = true;
            // 長押し成立を指先へ返し、誤操作と成功操作の区別をつけやすくする。
            if (navigator.vibrate) {
                navigator.vibrate(100);
            }
            onLongPress();
        }, threshold);
    }, [onLongPress, threshold]);

    const endPress = useCallback(() => {
        if (timerRef.current) {
            clearTimeout(timerRef.current);
            timerRef.current = null;
        }
        // 短押しのみ click 扱いにし、長押しとの二重発火を防ぐ。
        if (!isLongPressRef.current && onClick) {
            onClick();
        }
    }, [onClick]);

    const cancelPress = useCallback(() => {
        if (timerRef.current) {
            clearTimeout(timerRef.current);
            timerRef.current = null;
        }
    }, []);

    return {
        onMouseDown: startPress,
        onMouseUp: endPress,
        onMouseLeave: cancelPress,
        onTouchStart: startPress,
        onTouchEnd: endPress,
    };
}
