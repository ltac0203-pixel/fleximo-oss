import { useEffect, useState } from "react";
import { type Breakpoint, getMediaQuery } from "@/constants/breakpoints";

/**
 * 現在のウィンドウ幅が指定ブレークポイント以上かを判定するReact Hook
 *
 * matchMedia APIを使用した効率的なリスナー実装により、
 * リサイズイベントを最小限に抑えながらブレークポイントの変化を検知します。
 *
 * @param breakpoint - 判定するブレークポイント名
 * @returns ブレークポイント以上の場合true
 *
 * @example
 * function MyComponent() {
 *   const isLg = useBreakpoint('lg'); // 1024px以上でtrue
 *   const isMd = useBreakpoint('md'); // 768px以上でtrue
 *
 *   return (
 *     <div>
 *       {isLg && <p>Large screen</p>}
 *       {isMd && !isLg && <p>Medium screen</p>}
 *       {!isMd && <p>Small screen</p>}
 *     </div>
 *   );
 * }
 */
export function useBreakpoint(breakpoint: Breakpoint): boolean {
    const [matches, setMatches] = useState<boolean>(() => {
        if (typeof window === "undefined") return false;

        const mediaQuery = getMediaQuery(breakpoint);
        return window.matchMedia(mediaQuery).matches;
    });

    useEffect(() => {
        const mediaQuery = getMediaQuery(breakpoint);
        const mediaQueryList = window.matchMedia(mediaQuery);

        // 初期値を設定（レンダリング時の値とmatchMedia結果の同期）
        setMatches(mediaQueryList.matches);

        // matchMedia変化時のリスナー
        const handleChange = (event: MediaQueryListEvent) => {
            setMatches(event.matches);
        };

        // モダンブラウザ向けリスナー登録
        mediaQueryList.addEventListener("change", handleChange);

        return () => {
            mediaQueryList.removeEventListener("change", handleChange);
        };
    }, [breakpoint]);

    return matches;
}
