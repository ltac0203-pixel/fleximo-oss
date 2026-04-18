import type { CSSProperties } from "react";

/**
 * デザイントークン - カラーパレット
 *
 * Fleximoブランドカラーとセマンティックカラーの定義
 * すべてのランディングページで統一して使用する
 */
export const COLORS = {
    // プライマリカラー (sky-500)
    PRIMARY: "#0ea5e9",
    // プライマリライト (sky-300)
    PRIMARY_LIGHT: "#7dd3fc",
    // プライマリダーク (sky-600)
    PRIMARY_DARK: "#0284c7",
    // アクセントカラー (cyan-500)
    ACCENT: "#06b6d4",
    // テキストカラー - ダーク (slate-900)
    INK: "#0f172a",
    // テキストカラー - ミュート (slate-500)
    MUTED: "#64748b",
    // 公開LP用サーフェス (sky-50)
    SURFACE_TINT: "#f0f9ff",
    // 公開LP用ハイライト (cyan-50)
    SURFACE_HALO: "#ecfeff",
} as const;

/**
 * デザイントークン - フォントファミリー
 */
export const FONTS = {
    BODY: '"Inter", ui-sans-serif, system-ui',
} as const;

/**
 * CSS変数オブジェクト
 *
 * Welcome.tsx と ForBusiness/Index.tsx で style属性に適用する
 *
 * 使用例:
 * ```tsx
 * <div style={cssVariables}>
 *   ...
 * </div>
 * ```
 */
export const cssVariables = {
    "--primary": COLORS.PRIMARY,
    "--primary-light": COLORS.PRIMARY_LIGHT,
    "--primary-dark": COLORS.PRIMARY_DARK,
    "--accent": COLORS.ACCENT,
    "--ink": COLORS.INK,
    "--muted": COLORS.MUTED,
    "--public-surface": COLORS.SURFACE_TINT,
    "--public-surface-halo": COLORS.SURFACE_HALO,
    "--public-shell": "rgba(255, 255, 255, 0.82)",
    "--public-shell-strong": "rgba(255, 255, 255, 0.92)",
    "--public-shell-muted": "rgba(248, 250, 252, 0.82)",
    "--public-border": "rgba(125, 211, 252, 0.38)",
    "--public-glow-sky": "rgba(14, 165, 233, 0.16)",
    "--public-glow-sky-strong": "rgba(14, 165, 233, 0.28)",
    "--public-glow-cyan": "rgba(6, 182, 212, 0.18)",
    "--public-glow-cyan-strong": "rgba(6, 182, 212, 0.26)",
    "--public-glow-ice": "rgba(125, 211, 252, 0.18)",
    "--font-body": FONTS.BODY,
} as CSSProperties;
