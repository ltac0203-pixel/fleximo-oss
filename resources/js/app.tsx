import "../css/app.css";
import "./bootstrap";
import "./i18n";

import { createInertiaApp } from "@inertiajs/react";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { Suspense } from "react";
import { createRoot } from "react-dom/client";
import { I18nextProvider } from "react-i18next";
import GlobalLoadingOverlay from "@/Components/Loading/GlobalLoadingOverlay";
import GlobalOnboardingTour from "@/Components/Common/Onboarding/GlobalOnboardingTour";
import NavigationProgressBar from "@/Components/Loading/NavigationProgressBar";
import PageTransition from "@/Components/PageTransition";
import ErrorBoundary, { FallbackProps } from "@/Components/ErrorBoundary";
import ErrorLayout from "@/Components/Error/ErrorLayout";
import i18n from "@/i18n";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

// i18n 初期化前に発火する例外も捕捉するため、useTranslation は使わず
// blade で注入された window.__APP_LOCALE__ から静的マップを引く。
const ROOT_FALLBACK_TEXTS = {
    ja: {
        title: "予期しないエラー",
        message: "アプリケーションでエラーが発生しました。ページを再読み込みしてください。",
        retry: "再試行",
        reload: "ページを再読み込み",
    },
    en: {
        title: "Unexpected error",
        message: "Something went wrong. Please reload the page.",
        retry: "Retry",
        reload: "Reload page",
    },
} as const;

function RootErrorFallback({ resetError }: FallbackProps) {
    const locale = window.__APP_LOCALE__ === "en" ? "en" : "ja";
    const t = ROOT_FALLBACK_TEXTS[locale];

    return (
        <ErrorLayout errorCode={500} title={t.title} message={t.message}>
            <button
                onClick={resetError}
                className="inline-flex items-center justify-center border border-transparent bg-sky-500 px-6 py-3 text-base font-medium text-white hover:bg-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
            >
                {t.retry}
            </button>
            <button
                onClick={() => window.location.reload()}
                className="inline-flex items-center justify-center border border-slate-300 bg-white px-6 py-3 text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
            >
                {t.reload}
            </button>
        </ErrorLayout>
    );
}

// 初回描画前に主要 namespace をロードしておくことで、useTranslation が
// Suspense を発火して白画面が一瞬出るのを避ける。Customer 画面は次 PR で
// 翻訳化するためここでは未指定。読み込まれたあと createInertiaApp に進む。
void i18n.loadNamespaces(["common", "auth", "errors"]).then(() => {
    void createInertiaApp({
        title: (title) => (title ? `${title} - ${appName}` : appName),
        resolve: (name) =>
            resolvePageComponent(`./Pages/${name}.tsx`, import.meta.glob("./Pages/**/*.tsx")),
        setup({ el, App, props }) {
            const root = createRoot(el);

            // GlobalOnboardingTour などは usePage() を使うため、Inertia の <App> が提供する
            // PageContext のスコープ内で描画する必要がある。children render-prop を渡し、
            // ページ本体・遷移・グローバル UI をすべて context 配下に配置する。
            root.render(
                <ErrorBoundary fallback={RootErrorFallback}>
                    <I18nextProvider i18n={i18n}>
                        <Suspense fallback={null}>
                            <App {...props}>
                                {({ Component, props: pageProps, key }) => (
                                    <>
                                        <PageTransition>
                                            <Component key={key} {...pageProps} />
                                        </PageTransition>
                                        <NavigationProgressBar />
                                        <GlobalLoadingOverlay />
                                        <GlobalOnboardingTour />
                                    </>
                                )}
                            </App>
                        </Suspense>
                    </I18nextProvider>
                </ErrorBoundary>,
            );
        },
        progress: false,
    });
});
