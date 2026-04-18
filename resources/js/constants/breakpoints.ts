/**
 * Tailwind CSS ブレークポイント定数
 * このファイルは、Tailwind CSSのデフォルトブレークポイント値を
 * TypeScript定数として定義します。
 * 【重要】tailwind.config.jsでブレークポイントをカスタマイズする場合は、
 * このファイルの値も同期して更新してください。
 *
 * @see https://tailwindcss.com/docs/responsive-design
 */

/**
 * Tailwind CSS デフォルトブレークポイント値（px）
 * - sm: 640px  - スマートフォン（横向き）、小型タブレット
 * - md: 768px  - タブレット
 * - lg: 1024px - デスクトップ、大型タブレット
 * - xl: 1280px - 大型デスクトップ
 * - 2xl: 1536px - 超大型ディスプレイ
 */
export const BREAKPOINTS = {
    sm: 640,
    md: 768,
    lg: 1024,
    xl: 1280,
    "2xl": 1536,
} as const;

/**
 * ブレークポイント名の型
 */
export type Breakpoint = keyof typeof BREAKPOINTS;

/**
 * 指定したブレークポイント以上かを判定
 *
 * @param breakpoint - 判定するブレークポイント名
 * @param width - 判定する幅（省略時は現在のウィンドウ幅）
 * @returns ブレークポイント以上の場合true
 *
 * @example
 * if (isBreakpoint('lg')) {
 *   // 1024px以上の処理
 * }
 *
 * @example
 * const width = 800;
 * if (isBreakpoint('md', width)) {
 *   // 指定幅がmd（768px）以上
 * }
 */
export const isBreakpoint = (breakpoint: Breakpoint, width?: number): boolean => {
    const targetWidth = width ?? (typeof window !== "undefined" ? window.innerWidth : 0);
    return targetWidth >= BREAKPOINTS[breakpoint];
};

/**
 * メディアクエリ文字列を生成
 *
 * @param breakpoint - ブレークポイント名
 * @returns メディアクエリ文字列
 *
 * @example
 * const mq = getMediaQuery('lg'); // '(min-width: 1024px)'
 * const matches = window.matchMedia(mq).matches;
 */
export const getMediaQuery = (breakpoint: Breakpoint): string => {
    return `(min-width: ${BREAKPOINTS[breakpoint]}px)`;
};
