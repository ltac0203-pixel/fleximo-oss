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
