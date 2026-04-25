import { ReactNode } from "react";
import GradientBackground from "@/Components/GradientBackground";
import SkipToContentLink, { MAIN_CONTENT_ID } from "@/Components/SkipToContentLink";

interface ErrorLayoutProps {
    errorCode: number;
    title: string;
    message: string;
    children?: ReactNode;
}

export default function ErrorLayout({ errorCode, title, message, children }: ErrorLayoutProps) {
    return (
        <div className="relative flex min-h-screen items-center justify-center p-4">
            <SkipToContentLink />
            <GradientBackground />

            <main id={MAIN_CONTENT_ID} tabIndex={-1} className="relative z-10 w-full max-w-2xl">
                {/* エラーコード（大きな数字） */}
                <div className="text-center">
                    <div className="text-sky-500 text-8xl font-bold md:text-9xl">{errorCode}</div>
                </div>

                {/* エラーカード */}
                <div className="mt-8 border border-edge bg-white/90 p-8">
                    {/* エラーアイコン */}
                    <div className="mb-6 flex justify-center">
                        <div className="relative">
                            <svg className="h-24 w-24 text-sky-500" viewBox="0 0 100 100">
                                {/* 六角形 */}
                                <polygon
                                    points="50,5 95,27.5 95,72.5 50,95 5,72.5 5,27.5"
                                    fill="currentColor"
                                    opacity="0.1"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                />
                            </svg>
                            {/* アラートシンボル */}
                            <div className="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                                <svg
                                    className="h-12 w-12 text-sky-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* タイトル */}
                    <h1 className="text-center text-3xl font-bold text-ink md:text-4xl">{title}</h1>

                    {/* メッセージ */}
                    <p className="mt-4 text-center text-base leading-relaxed text-ink-light md:text-lg">{message}</p>

                    {/* アクションボタン */}
                    {children && <div className="mt-8 flex flex-col justify-center gap-4 sm:flex-row">{children}</div>}
                </div>
            </main>
        </div>
    );
}
