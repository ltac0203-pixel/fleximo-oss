import "@testing-library/jest-dom";
import { cleanup } from "@testing-library/react";
import { afterEach } from "vitest";
import i18next from "i18next";
import { initReactI18next } from "react-i18next";
import { resetChartMockState } from "./helpers/chartMock";

import jaCommon from "../i18n/locales/ja/common.json";
import jaCustomer from "../i18n/locales/ja/customer.json";
import jaAuth from "../i18n/locales/ja/auth.json";
import jaErrors from "../i18n/locales/ja/errors.json";
import enCommon from "../i18n/locales/en/common.json";
import enCustomer from "../i18n/locales/en/customer.json";
import enAuth from "../i18n/locales/en/auth.json";
import enErrors from "../i18n/locales/en/errors.json";

// vitest 実行時は HTTP バックエンドや動的 import を経由せず同期的に i18next を初期化する。
// プロダクションの初期化（resources/js/i18n/index.ts）と同じ namespace 構成を再現し、
// useTranslation() がレンダリング即時に翻訳結果を返せるようにする。
void i18next.use(initReactI18next).init({
    lng: "ja",
    fallbackLng: "en",
    supportedLngs: ["ja", "en"],
    ns: ["common", "customer", "auth", "errors"],
    defaultNS: "common",
    interpolation: { escapeValue: false },
    react: { useSuspense: false },
    returnNull: false,
    resources: {
        ja: { common: jaCommon, customer: jaCustomer, auth: jaAuth, errors: jaErrors },
        en: { common: enCommon, customer: enCustomer, auth: enAuth, errors: enErrors },
    },
});

// テスト間のDOM汚染を防ぎ、ケース順序に依存しない実行結果を担保する。
afterEach(() => {
    cleanup();
    resetChartMockState();
});

// ルーティング依存のUIテストを軽量に回すため、最小限のroute関数だけ差し込む。
global.route = (name: string, params?: Record<string, string>) => {
    return `/${name}${params ? "?" + new URLSearchParams(params).toString() : ""}`;
};
