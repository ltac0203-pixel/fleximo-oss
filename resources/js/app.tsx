import "../css/app.css";
import "./bootstrap";

import { createInertiaApp } from "@inertiajs/react";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createRoot } from "react-dom/client";
import GlobalLoadingOverlay from "@/Components/Loading/GlobalLoadingOverlay";
import GlobalOnboardingTour from "@/Components/Common/Onboarding/GlobalOnboardingTour";
import NavigationProgressBar from "@/Components/Loading/NavigationProgressBar";
import PageTransition from "@/Components/PageTransition";
import ErrorBoundary, { FallbackProps } from "@/Components/ErrorBoundary";
import ErrorLayout from "@/Components/Error/ErrorLayout";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

function RootErrorFallback({ resetError }: FallbackProps) {
    return (
        <ErrorLayout
            errorCode={500}
            title="予期しないエラー"
            message="アプリケーションでエラーが発生しました。ページを再読み込みしてください。"
        >
            <button
                onClick={resetError}
                className="inline-flex items-center justify-center border border-transparent bg-sky-500 px-6 py-3 text-base font-medium text-white hover:bg-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
            >
                再試行
            </button>
            <button
                onClick={() => window.location.reload()}
                className="inline-flex items-center justify-center border border-slate-300 bg-white px-6 py-3 text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
            >
                ページを再読み込み
            </button>
        </ErrorLayout>
    );
}

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./Pages/${name}.tsx`, import.meta.glob("./Pages/**/*.tsx")),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <>
                <ErrorBoundary fallback={RootErrorFallback}>
                    <PageTransition>
                        <App {...props} />
                    </PageTransition>
                </ErrorBoundary>
                <NavigationProgressBar />
                <GlobalLoadingOverlay />
                <GlobalOnboardingTour />
            </>,
        );
    },
    progress: false,
});
