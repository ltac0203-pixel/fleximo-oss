import Spinner from "./Spinner";

interface ProcessingStatusProps {
    status: "processing" | "success" | "failed";
    processingTitle: string;
    processingMessage?: string;
    successTitle?: string;
    successMessage?: string;
    failedTitle?: string;
    failedMessage?: string;
    error?: string | null;
}

export default function ProcessingStatus({
    status,
    processingTitle,
    processingMessage = "しばらくお待ちください...",
    successTitle = "決済が完了しました",
    successMessage = "注文完了画面へ移動します...",
    failedTitle = "決済に失敗しました",
    failedMessage = "失敗画面へ移動します...",
    error,
}: ProcessingStatusProps) {
    return (
        <div className="bg-white border border-slate-200 p-8 text-center">
            {status === "processing" && (
                <>
                    <div className="flex justify-center mb-4">
                        <Spinner size="lg" label={processingTitle} />
                    </div>
                    <h2 className="text-xl font-semibold text-slate-900 mb-2">{processingTitle}</h2>
                    <p className="text-slate-600">{processingMessage}</p>
                </>
            )}

            {status === "success" && (
                <>
                    <div className="w-16 h-16 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
                        <svg
                            className="w-8 h-8 text-green-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                    </div>
                    <h2 className="text-xl font-semibold text-slate-900 mb-2">{successTitle}</h2>
                    <p className="text-slate-600">{successMessage}</p>
                </>
            )}

            {status === "failed" && (
                <>
                    <div className="w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                        <svg
                            className="w-8 h-8 text-red-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </div>
                    <h2 className="text-xl font-semibold text-slate-900 mb-2">{failedTitle}</h2>
                    {error && <p className="text-slate-600 mb-4">{error}</p>}
                    <p className="text-sm text-slate-500">{failedMessage}</p>
                </>
            )}
        </div>
    );
}
