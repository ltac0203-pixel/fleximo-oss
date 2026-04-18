import { useEffect, useRef } from "react";

interface CardRegistrationFormProps {
    isReady: boolean;
    isLoading: boolean;
    error: string | null;
    isSubmitting: boolean;
    isDefault: boolean;
    onDefaultChange: (checked: boolean) => void;
    onMount: (containerId: string) => void;
    onUnmount: () => void;
    onSubmit: () => void;
}

// fincode SDK は mount(elementId) 時に #{elementId} と #{elementId}-form の両方を参照する。
const FINCODE_MOUNT_ID = "fincode-register";
const FINCODE_FORM_ID = `${FINCODE_MOUNT_ID}-form`;

// SDK埋め込みと登録導線を一体化し、カード追加操作を1コンポーネントで完結させる。
export default function CardRegistrationForm({
    isReady,
    isLoading,
    error,
    isSubmitting,
    isDefault,
    onDefaultChange,
    onMount,
    onUnmount,
    onSubmit,
}: CardRegistrationFormProps) {
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

    const canSubmit = isReady && !isLoading && !isSubmitting;

    return (
        <div className="bg-white border p-3 sm:p-4 rounded-lg max-w-2xl mx-auto">
            <h2 className="text-base sm:text-lg font-semibold text-ink mb-3 sm:mb-4">新規カード登録</h2>

            {/* SDK読込中に空白表示を避け、待機状態を明示する。 */}
            {isLoading && (
                <div className="flex items-center justify-center py-6 sm:py-8">
                    <div className="animate-spin rounded-full h-6 w-6 sm:h-8 sm:w-8 border-b-2 border-sky-500"></div>
                    <span className="ml-2 sm:ml-3 text-sm sm:text-base text-muted">
                        フォームを読み込んでいます...
                    </span>
                </div>
            )}

            {/* 失敗理由を即時表示し、再試行判断をしやすくする。 */}
            {error && (
                <div className="mb-3 sm:mb-4 p-3 sm:p-4 bg-red-50 border border-red-200 rounded">
                    <div className="flex items-center gap-2">
                        <svg
                            className="w-4 h-4 sm:w-5 sm:h-5 text-red-500 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
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

            {/* 初回利用カードをここで決められるようにし、決済時の選択操作を減らす。 */}
            {!isLoading && !error && (
                <div className="mt-3 sm:mt-4">
                    <label className="flex items-start sm:items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            checked={isDefault}
                            onChange={(e) => onDefaultChange(e.target.checked)}
                            className="w-4 h-4 text-sky-600 border-edge-strong rounded focus:ring-primary mt-0.5 sm:mt-0 flex-shrink-0"
                        />
                        <span className="text-xs sm:text-sm text-ink-light leading-tight">
                            このカードをメインカードに設定する
                        </span>
                    </label>
                </div>
            )}

            {/* カード情報の取り扱い不安を下げ、離脱を抑える。 */}
            {!isLoading && !error && (
                <div className="mt-3 sm:mt-4 flex items-start gap-2 text-xs text-muted">
                    <svg
                        className="w-3.5 h-3.5 sm:w-4 sm:h-4 text-green-500 flex-shrink-0 mt-0.5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
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

            {/* 入力準備完了時のみ有効化し、無効送信を防ぐ。 */}
            {!isLoading && !error && (
                <button
                    onClick={onSubmit}
                    disabled={!canSubmit}
                    className={`
                        mt-4 sm:mt-6 w-full py-2.5 sm:py-3 px-4 font-semibold text-sm sm:text-base rounded
                        flex items-center justify-center gap-2
                        transition-colors
                        ${
                            canSubmit
                                ? "bg-sky-600 hover:bg-sky-700 text-white"
                                : "bg-edge-strong text-muted cursor-not-allowed"
                        }
                    `}
                >
                    {isSubmitting ? (
                        <>
                            <div
                                className="h-4 w-4 animate-spin rounded-full border-b-2 border-white sm:h-5 sm:w-5"
                                aria-hidden="true"
                            />
                            <span className="sr-only">処理中</span>
                        </>
                    ) : (
                        <>
                            <svg
                                className="w-4 h-4 sm:w-5 sm:h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                />
                            </svg>
                            <span>このカードを登録する</span>
                        </>
                    )}
                </button>
            )}
        </div>
    );
}
