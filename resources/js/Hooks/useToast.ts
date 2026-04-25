import { useState, useCallback, useRef, useEffect } from "react";

export type ToastType = "success" | "error" | "info";

export interface ToastState {
    type: ToastType;
    message: string;
}

export interface ToastItem extends ToastState {
    id: string;
}

export interface UseToastReturn {
    // 既存呼び出し側を壊さず段階移行できるよう、単体参照を維持する。
    toast: ToastState | null;
    // 複数表示を可能にして通知の取りこぼしを防ぐため配列で公開する。
    toasts: ToastItem[];
    // 個別に閉じられるよう、表示時に識別子を返す契約にしている。
    showToast: (state: ToastState) => string;
    // 旧API互換を保ったまま新APIへ移行できるよう、ID省略を許容する。
    hideToast: (id?: string) => void;
}

const MAX_TOASTS = 5;
const DEFAULT_AUTO_HIDE_MS = 3000;

function generateId(): string {
    return crypto.randomUUID();
}

// 複数通知と後方互換を両立し、既存画面を止めずに段階移行できる設計にしている。
// 件数上限を設けて、連続通知時の視認性低下とメモリ増加を抑える。
export function useToast(autoHideMs: number = DEFAULT_AUTO_HIDE_MS): UseToastReturn {
    const [toasts, setToasts] = useState<ToastItem[]>([]);
    const timersRef = useRef<Map<string, ReturnType<typeof setTimeout>>>(new Map());

    const clearTimer = useCallback((id: string) => {
        const timer = timersRef.current.get(id);
        if (timer) {
            clearTimeout(timer);
            timersRef.current.delete(id);
        }
    }, []);

    const hideToast = useCallback(
        (id?: string) => {
            if (id) {
                clearTimer(id);
                setToasts((prev) => prev.filter((t) => t.id !== id));
            } else {
                // 既存実装の呼び出し方を維持し、導入コストを下げるため末尾削除を残す。
                setToasts((prev) => {
                    if (prev.length === 0) return prev;
                    const last = prev[prev.length - 1];
                    clearTimer(last.id);
                    return prev.slice(0, -1);
                });
            }
        },
        [clearTimer],
    );

    const showToast = useCallback(
        (state: ToastState): string => {
            const id = generateId();
            const newItem: ToastItem = { ...state, id };

            setToasts((prev) => {
                let next = [...prev, newItem];
                // 新着を優先表示するため、上限超過時は最古から落とす。
                while (next.length > MAX_TOASTS) {
                    const oldest = next[0];
                    clearTimer(oldest.id);
                    next = next.slice(1);
                }
                return next;
            });

            // 閉じ忘れで画面が埋まらないよう、表示時に自動解放を必ず予約する。
            const timer = setTimeout(() => {
                timersRef.current.delete(id);
                setToasts((prev) => prev.filter((t) => t.id !== id));
            }, autoHideMs);
            timersRef.current.set(id, timer);

            return id;
        },
        [autoHideMs, clearTimer],
    );

    // アンマウント後の遅延更新を防ぎ、テスト時のリーク警告も抑える。
    useEffect(() => {
        const timers = timersRef.current;
        return () => {
            timers.forEach((timer) => clearTimeout(timer));
            timers.clear();
        };
    }, []);

    // 既存UIが単体参照を前提としているため、末尾を代表値として返す。
    const toast = toasts.length > 0 ? toasts[toasts.length - 1] : null;

    return { toast, toasts, showToast, hideToast };
}
