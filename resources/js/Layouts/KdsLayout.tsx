import { PropsWithChildren, useCallback, useEffect, useState } from "react";
import { Head } from "@inertiajs/react";
import CurrentTime from "@/Components/Kds/CurrentTime";
import BusinessDate from "@/Components/Kds/BusinessDate";
import ErrorBoundary from "@/Components/ErrorBoundary";
import ErrorFallback from "@/Components/ErrorFallback";
import OrderPauseToggle from "@/Components/Kds/OrderPauseToggle";
import PollingIndicator from "@/Components/Kds/PollingIndicator";
import SkipToContentLink, { MAIN_CONTENT_ID } from "@/Components/SkipToContentLink";
import { PollingState } from "@/types";

interface KdsLayoutProps extends PropsWithChildren {
    businessDate: string;
    pollingState: PollingState;
    isOrderPaused?: boolean;
    isToggling?: boolean;
    onTogglePause?: () => void;
}

export default function KdsLayout({
    children,
    businessDate,
    pollingState,
    isOrderPaused = false,
    isToggling = false,
    onTogglePause,
}: KdsLayoutProps) {
    const [isFullscreen, setIsFullscreen] = useState(false);

    const toggleFullscreen = useCallback(async () => {
        if (!document.fullscreenElement) {
            await document.documentElement.requestFullscreen();
        } else {
            await document.exitFullscreen();
        }
    }, []);

    useEffect(() => {
        const handleFullscreenChange = () => {
            setIsFullscreen(!!document.fullscreenElement);
        };

        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.key === "F11") {
                e.preventDefault();
                void toggleFullscreen();
            }
        };

        document.addEventListener("fullscreenchange", handleFullscreenChange);
        document.addEventListener("keydown", handleKeyDown);

        return () => {
            document.removeEventListener("fullscreenchange", handleFullscreenChange);
            document.removeEventListener("keydown", handleKeyDown);
        };
    }, [toggleFullscreen]);

    return (
        <div className="min-h-screen bg-slate-50 flex flex-col">
            <Head title="KDS - キッチンディスプレイ" />
            <SkipToContentLink />

            {/* ヘッダー を明示し、実装意図の誤読を防ぐ。 */}
            <header className="flex-shrink-0 bg-white border-b border-slate-200 px-4 py-3">
                <div className="flex items-center justify-between">
                    {/* 左側: タイトルと営業日 を明示し、実装意図の誤読を防ぐ。 */}
                    <div className="flex items-center gap-6">
                        <h1 className="text-xl font-bold text-slate-900">KDS</h1>
                        <BusinessDate date={businessDate} />
                    </div>

                    {/* 中央: 現在時刻 を明示し、実装意図の誤読を防ぐ。 */}
                    <CurrentTime />

                    {/* 右側: 注文停止・ステータス・全画面ボタン */}
                    <div className="flex items-center gap-4">
                        {onTogglePause && (
                            <OrderPauseToggle
                                isOrderPaused={isOrderPaused}
                                isToggling={isToggling}
                                onToggle={onTogglePause}
                            />
                        )}
                        <PollingIndicator state={pollingState} />
                        <button
                            onClick={() => {
                                void toggleFullscreen();
                            }}
                            className="px-3 py-2 text-sm text-slate-600 border border-slate-300 hover:bg-slate-100"
                            title={isFullscreen ? "全画面終了 (F11)" : "全画面表示 (F11)"}
                        >
                            {isFullscreen ? "終了" : "全画面"}
                        </button>
                    </div>
                </div>
            </header>

            {/* 一時停止中の警告バナー */}
            {isOrderPaused && (
                <div className="flex-shrink-0 bg-red-600 text-white px-4 py-2 text-center text-sm font-medium">
                    注文受付を一時停止しています
                </div>
            )}

            {/* メインコンテンツ */}
            <main id={MAIN_CONTENT_ID} tabIndex={-1} className="flex-1 min-h-0 p-4">
                <ErrorBoundary fallback={ErrorFallback}>{children}</ErrorBoundary>
            </main>
        </div>
    );
}
