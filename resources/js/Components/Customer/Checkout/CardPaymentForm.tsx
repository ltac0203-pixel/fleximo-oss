import { useEffect, useRef } from "react";
import GeoSurface from "@/Components/GeoSurface";

interface CardPaymentFormProps {
    isReady: boolean;
    isLoading: boolean;
    error: string | null;
    onMount: (containerId: string) => void;
    onUnmount: () => void;
}

// fincode SDK は mount(elementId) 時に #{elementId} と #{elementId}-form の両方を参照する。
// wrapper(form)のIDが {mountId}-form になるよう命名する。
const FINCODE_MOUNT_ID = "fincode-checkout";
const FINCODE_FORM_ID = `${FINCODE_MOUNT_ID}-form`;

// カード入力UIをSDKへ委譲し、アプリ側で機微情報を保持しない構成を徹底する。
export default function CardPaymentForm({ isReady, isLoading, error, onMount, onUnmount }: CardPaymentFormProps) {
    const mountedRef = useRef(false);

    useEffect(() => {
        if (isReady && !isLoading && !error && !mountedRef.current) {
            onMount(FINCODE_MOUNT_ID);
            mountedRef.current = true;
        }

        return () => {
            if (mountedRef.current) {
                onUnmount();
                mountedRef.current = false;
            }
        };
    }, [error, isLoading, isReady, onMount, onUnmount]);

    return (
        <GeoSurface topAccent elevated className="p-3 sm:p-4">
            <h2 className="text-base sm:text-lg font-semibold text-ink mb-3 sm:mb-4">カード情報</h2>

            {/* SDK初期化待ちを明示し、入力不可状態での混乱を防ぐ。 */}
            {isLoading && (
                <div className="flex items-center justify-center py-6 sm:py-8">
                    <div
                        className="animate-spin rounded-full h-6 w-6 sm:h-8 sm:w-8 border-b-2 border-sky-500"
                        role="status"
                        aria-label="読み込み中"
                    ></div>
                    <span className="ml-2 sm:ml-3 text-sm sm:text-base text-muted">決済フォームを読み込んでいます...</span>
                </div>
            )}

            {/* 失敗理由を表示して、再読み込みや決済手段変更の判断を促す。 を明示し、実装意図の誤読を防ぐ。 */}
            {error && (
                <div className="mb-3 border border-red-200 bg-red-50 p-3 sm:mb-4 sm:p-4">
                    <div className="flex items-center gap-2">
                        <svg className="w-4 h-4 sm:w-5 sm:h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <span className="text-xs sm:text-sm text-red-600">{error}</span>
                    </div>
                </div>
            )}

            {/* fincode SDK が #{mountId} と #{mountId}-form の両方を参照するため、form wrapper が必須。 */}
            <div id={FINCODE_FORM_ID} className={`w-full overflow-hidden ${isLoading ? "hidden" : ""}`}>
                <div id={FINCODE_MOUNT_ID} className="w-full" />
            </div>

            {/* 安全性を明示し、カード入力前の心理的障壁を下げる。 */}
            {!isLoading && !error && (
                <div className="mt-3 sm:mt-4 flex items-start gap-2 text-xs text-muted">
                    <svg className="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-green-500 sm:h-4 sm:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                        />
                    </svg>
                    <span className="leading-tight">カード情報は暗号化されて安全に送信されます</span>
                </div>
            )}
        </GeoSurface>
    );
}
