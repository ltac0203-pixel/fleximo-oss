import { FallbackProps } from "@/Components/ErrorBoundary";

const isDev = import.meta.env.DEV;

export default function ErrorFallback({ error, resetError }: FallbackProps) {
    return (
        <div className="flex items-center justify-center p-8">
            <div className="w-full max-w-lg border border-edge bg-white p-8 text-center shadow-sm">
                <div className="mb-4 flex justify-center">
                    <svg className="h-12 w-12 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                </div>

                <h2 className="text-xl font-bold text-ink">エラーが発生しました</h2>
                <p className="mt-2 text-sm text-ink-light">
                    予期しないエラーが発生しました。再試行するか、ページを再読み込みしてください。
                </p>

                {isDev && (
                    <details className="mt-4 text-left">
                        <summary className="cursor-pointer text-sm font-medium text-muted hover:text-ink-light">
                            エラー詳細（開発環境のみ）
                        </summary>
                        <pre className="mt-2 max-h-48 overflow-auto rounded bg-surface-dim p-3 text-xs text-red-700">
                            {error.message}
                            {"\n\n"}
                            {error.stack}
                        </pre>
                    </details>
                )}

                <div className="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                    <button
                        onClick={resetError}
                        className="inline-flex items-center justify-center border border-transparent bg-sky-500 px-4 py-2 text-sm font-medium text-white hover:bg-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
                    >
                        再試行
                    </button>
                    <button
                        onClick={() => window.location.reload()}
                        className="inline-flex items-center justify-center border border-edge-strong bg-white px-4 py-2 text-sm font-medium text-ink-light hover:bg-surface focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
                    >
                        ページを再読み込み
                    </button>
                </div>
            </div>
        </div>
    );
}
