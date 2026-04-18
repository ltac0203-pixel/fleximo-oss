import { useEffect, useState } from "react";
import { loadingStore } from "@/Utils/loadingStore";
import { navigateToSafeHome } from "@/Utils/safeHomeNavigation";

const SHOW_DELAY_MS = 150;
const TIMEOUT_MS = 15_000;

export default function GlobalLoadingOverlay() {
    const [isLoading, setIsLoading] = useState(loadingStore.getCount() > 0);
    const [isVisible, setIsVisible] = useState(false);
    const [isTimedOut, setIsTimedOut] = useState(false);

    useEffect(() => {
        return loadingStore.subscribe((count) => {
            setIsLoading(count > 0);
        });
    }, []);

    useEffect(() => {
        let showTimer: number | undefined;
        let timeoutTimer: number | undefined;

        if (isLoading) {
            showTimer = window.setTimeout(() => {
                setIsVisible(true);
            }, SHOW_DELAY_MS);
            timeoutTimer = window.setTimeout(() => {
                setIsTimedOut(true);
            }, TIMEOUT_MS);
        } else {
            setIsVisible(false);
            setIsTimedOut(false);
        }

        return () => {
            if (showTimer) window.clearTimeout(showTimer);
            if (timeoutTimer) window.clearTimeout(timeoutTimer);
        };
    }, [isLoading]);

    if (!isVisible) {
        return null;
    }

    if (isTimedOut) {
        return (
            <div
                className="fixed inset-0 z-50 flex items-center justify-center bg-ink/20"
                role="alert"
                aria-live="assertive"
            >
                <div className="flex flex-col items-center gap-4 bg-white px-8 py-6 border border-edge">
                    <span className="text-sm font-medium text-ink-light">読み込みに時間がかかっています</span>
                    <div className="flex gap-3">
                        <button
                            type="button"
                            onClick={() => window.location.reload()}
                            className="bg-sky-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-sky-600"
                        >
                            再試行
                        </button>
                        <button
                            type="button"
                            onClick={() => navigateToSafeHome()}
                            className="bg-surface-dim px-5 py-2.5 text-sm font-medium text-ink-light hover:bg-edge-strong"
                        >
                            ホームに戻る
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-ink/20"
            role="status"
            aria-live="polite"
            aria-busy="true"
        >
            <div className="flex items-center gap-3 rounded-full bg-white/95 px-4 py-3 border border-edge">
                <div className="h-5 w-5 animate-spin rounded-full border-2 border-edge-strong border-t-sky-500" />
                <span className="text-sm font-medium text-ink-light">読み込み中...</span>
            </div>
        </div>
    );
}
