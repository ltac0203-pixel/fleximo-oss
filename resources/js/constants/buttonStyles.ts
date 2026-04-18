/**
 * ボタンスタイル定数
 *
 * Fleximoランディングページで使用するボタンのclassName定数
 * デザインシステムの一貫性を保つため、インラインスタイルではなく
 * この定数を使用してください
 *
 * 統一されたホバー効果:
 * - プライマリボタン: hover:border-sky-600 hover:bg-sky-600
 * - セカンダリボタン: hover:border-slate-400 hover:bg-slate-50
 * - ボーダー幅: すべて border (border-2 から統一)
 */

export type ButtonSize = "sm" | "md" | "lg";

export const BUTTON_SIZE_CLASSES: Record<ButtonSize, string> = {
    sm: "px-3 py-2 text-xs",
    md: "px-5 py-2.5 text-sm",
    lg: "px-6 py-3 text-base",
};

/**
 * プライマリボタン - 大 (px-8 py-4)
 * 主要CTAボタン用。ヒーローセクション、フッターCTAなどで使用
 */
export const PRIMARY_BUTTON_LARGE =
    "inline-flex items-center justify-center gap-3 border border-sky-500 bg-sky-500 px-8 py-4 text-base font-semibold text-white hover:border-sky-600 hover:bg-sky-600";

/**
 * プライマリボタン - 標準 (px-5 py-2.5)
 * 標準サイズのプライマリボタン。ヘッダー、一般的なCTAで使用
 */
export const PRIMARY_BUTTON =
    "inline-flex items-center gap-2 border border-sky-500 bg-sky-500 px-5 py-2.5 text-sm font-medium text-white hover:border-sky-600 hover:bg-sky-600";

/**
 * セカンダリボタン - 大 (px-8 py-4)
 * 副次的なCTAボタン用。プライマリボタンと並べて使用
 */
export const SECONDARY_BUTTON_LARGE =
    "inline-flex items-center justify-center gap-2 border border-slate-200 bg-white px-8 py-4 text-base font-semibold text-slate-700 hover:border-slate-400 hover:bg-slate-50";

/**
 * セカンダリボタン - 標準 (px-5 py-2.5)
 * 標準サイズのセカンダリボタン
 */
export const SECONDARY_BUTTON =
    "inline-flex items-center gap-2 border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:border-slate-400 hover:bg-slate-50";

/**
 * ヘッダー用ログインボタン
 * ヘッダーの「ログイン」ボタン専用。レスポンシブ表示制御を含む
 */
export const HEADER_LOGIN_BUTTON =
    "hidden border border-sky-200 bg-white px-5 py-2 text-sm font-medium text-sky-600 hover:border-slate-400 hover:bg-slate-50 sm:inline-flex";

/**
 * テキストリンクボタン
 * ナビゲーションリンク、フッターリンクなどに使用
 */
export const TEXT_LINK_BUTTON = "text-sm text-slate-600 hover:text-sky-600";

/**
 * 濃色背景上のプライマリボタン (Inverted Primary)
 * 濃色背景（sky-500など）の上に配置するボタン
 */
export const INVERTED_BUTTON_PRIMARY =
    "inline-flex items-center gap-3 border border-white bg-white px-8 py-4 text-base font-semibold text-sky-600 hover:border-white hover:bg-slate-50";

/**
 * 濃色背景上のセカンダリボタン (Inverted Secondary)
 * 濃色背景の上に配置する副次的なボタン
 */
export const INVERTED_BUTTON_SECONDARY =
    "inline-flex items-center gap-2 border border-white/30 bg-white/10 px-8 py-4 text-base font-semibold text-white hover:border-white/50 hover:bg-white/20";
