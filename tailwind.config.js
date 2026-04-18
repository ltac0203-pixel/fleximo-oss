import defaultTheme from "tailwindcss/defaultTheme";
import colors from "tailwindcss/colors";
import forms from "@tailwindcss/forms";

// @type {import('tailwindcss').Config}
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.tsx",
    ],

    theme: {
        // 【重要】ブレークポイントをカスタマイズする場合の注意:
        // Tailwind CSSのブレークポイントを変更する際は、必ず
        // resources/js/constants/breakpoints.ts の値も同期して更新してください。
        // JavaScript実行時の判定とCSSのブレークポイントを一致させる必要があります。
        //
        // デフォルト値（変更する場合はここに breakpoints: {...} を追加）:
        // sm: 640px, md: 768px, lg: 1024px, xl: 1280px, 2xl: 1536px
        extend: {
            borderRadius: {
                none: "0",
                sm: "0",
                DEFAULT: "0",
                md: "0",
                lg: "0",
                xl: "0",
                "2xl": "0",
                "3xl": "0",
                full: "9999px",
            },
            colors: {
                // セマンティックカラー（デザイントークンと統一）
                primary: {
                    DEFAULT: "#0ea5e9", // sky-500 — ボタン、リンク、アクセント
                    light: "#7dd3fc", // sky-300
                    dark: "#0284c7", // sky-600 — ホバー、リンク色
                },
                accent: {
                    DEFAULT: "#06b6d4", // cyan-500
                },
                ink: {
                    DEFAULT: "#0f172a", // slate-900 — 見出し、ラベル
                    light: "#475569", // slate-600 — 本文、説明文
                },
                muted: {
                    DEFAULT: "#64748b", // slate-500 — キャプション、補助テキスト
                    light: "#94a3b8", // slate-400 — placeholder、disabled
                },
                surface: {
                    DEFAULT: "#f8fafc", // slate-50 — テーブルヘッダ、サブ背景
                    dim: "#f1f5f9", // slate-100 — 中立バッジ、無効領域
                },
                edge: {
                    DEFAULT: "#e2e8f0", // slate-200 — 標準ボーダー、divider
                    strong: "#cbd5e1", // slate-300 — 強調ボーダー
                },
                // 後方互換エイリアス（全移行後に削除）
                indigo: colors.sky,
            },
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
                orbitron: ["Orbitron", "sans-serif"],
            },
            boxShadow: {
                sm: "1px 1px 0 0 rgb(148 163 184 / 0.45)",
                DEFAULT: "2px 2px 0 0 rgb(148 163 184 / 0.45)",
                md: "3px 3px 0 0 rgb(148 163 184 / 0.45)",
                lg: "4px 4px 0 0 rgb(148 163 184 / 0.45)",
                xl: "6px 6px 0 0 rgb(148 163 184 / 0.45)",
                "2xl": "8px 8px 0 0 rgb(148 163 184 / 0.45)",
                inner: "inset 0 0 0 1px rgb(148 163 184 / 0.4)",
                // 幾何学的なカラーシャドウ
                "geo-sky": "3px 3px 0 0 rgb(14 165 233 / 0.3)",
                "geo-cyan": "3px 3px 0 0 rgb(6 182 212 / 0.3)",
                "geo-sky-lg": "4px 4px 0 0 rgb(14 165 233 / 0.25)",
            },
            backgroundImage: {
                "grid-pattern": `url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M60 0L0 0 0 60' fill='none' stroke='%230ea5e9' stroke-width='0.5' opacity='0.08'/%3E%3C/svg%3E")`,
                "dots-pattern": `url("data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='2' cy='2' r='1' fill='%230ea5e9' opacity='0.1'/%3E%3C/svg%3E")`,
            },
        },
    },

    plugins: [forms],
};
