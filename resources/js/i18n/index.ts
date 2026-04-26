import i18next from "i18next";
import { initReactI18next } from "react-i18next";
import resourcesToBackend from "i18next-resources-to-backend";
import type { LocaleCode } from "@/types/common";

const SUPPORTED_LOCALES: readonly LocaleCode[] = ["ja", "en"] as const;

const SUPPORTED_NAMESPACES = ["common", "customer", "auth", "errors"] as const;

const isSupportedLocale = (value: unknown): value is LocaleCode =>
    typeof value === "string" && (SUPPORTED_LOCALES as readonly string[]).includes(value);

// app.blade.php で <html lang> と window.__APP_LOCALE__ を出力しているため、
// 初期 locale はそこから同期的に取得できる。Inertia の sharedProps からも取れるが、
// i18next の init を React レンダリング前に終わらせたいので window 経由を採用する。
const resolveInitialLocale = (): LocaleCode => {
    const fromWindow = typeof window !== "undefined" ? window.__APP_LOCALE__ : undefined;
    if (isSupportedLocale(fromWindow)) {
        return fromWindow;
    }
    const fromHtml =
        typeof document !== "undefined" ? document.documentElement.lang.slice(0, 2) : undefined;
    if (isSupportedLocale(fromHtml)) {
        return fromHtml;
    }
    return "en";
};

const i18n = i18next.createInstance();

void i18n
    .use(initReactI18next)
    .use(
        resourcesToBackend(
            (lng: string, ns: string) =>
                import(`./locales/${lng}/${ns}.json`).then((mod) => mod.default ?? mod),
        ),
    )
    .init({
        lng: resolveInitialLocale(),
        fallbackLng: "en",
        supportedLngs: SUPPORTED_LOCALES as unknown as string[],
        ns: SUPPORTED_NAMESPACES as unknown as string[],
        defaultNS: "common",
        interpolation: {
            escapeValue: false,
        },
        react: {
            useSuspense: true,
        },
        returnNull: false,
    });

export { SUPPORTED_LOCALES, SUPPORTED_NAMESPACES };
export default i18n;
