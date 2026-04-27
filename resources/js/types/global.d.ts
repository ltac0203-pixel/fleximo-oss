import { PageProps as InertiaPageProps } from "@inertiajs/core";
import { route as ziggyRoute } from "ziggy-js";
import type { PageProps as AppPageProps } from "./";

declare global {
    /* eslint-disable no-var */
    var route: typeof ziggyRoute;
    interface Window {
        // resources/views/app.blade.php で注入される。
        // i18n 初期化前に描画される ErrorBoundary fallback など、
        // useTranslation を使えない箇所から locale を参照するための同期チャネル。
        __APP_LOCALE__?: string;
    }
}

declare module "@inertiajs/core" {
    interface PageProps extends InertiaPageProps, AppPageProps {}
}
