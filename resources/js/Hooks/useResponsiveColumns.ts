import { useCallback, useEffect, useState } from "react";
import { BREAKPOINTS } from "@/constants/breakpoints";

// 画面幅に応じた列数をコード側でも統一し、表示密度と可読性の崩れを防ぐ。
export function useResponsiveColumns(): number {
    const getColumns = useCallback(() => {
        if (typeof window === "undefined") return 2;

        const width = window.innerWidth;
        if (width >= BREAKPOINTS.lg) return 4; // PCでは縦スクロールを減らすため表示量を増やす。
        if (width >= BREAKPOINTS.sm) return 3; // 中間幅は可読性を保ちつつ密度を上げる。
        return 2;
    }, []);

    const [columns, setColumns] = useState(getColumns);

    useEffect(() => {
        const handleResize = () => {
            setColumns(getColumns());
        };

        window.addEventListener("resize", handleResize);
        return () => window.removeEventListener("resize", handleResize);
    }, [getColumns]);

    return columns;
}
