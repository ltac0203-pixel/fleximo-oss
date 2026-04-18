import { useRef, useEffect } from "react";

// 非同期処理から常に最新値を参照できるようにし、stale closure由来の不整合を防ぐ。
export function useLatest<T>(value: T): React.MutableRefObject<T> {
    const ref = useRef<T>(value);

    useEffect(() => {
        ref.current = value;
    });

    return ref;
}
