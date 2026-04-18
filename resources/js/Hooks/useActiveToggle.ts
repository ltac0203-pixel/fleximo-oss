import { useCallback, useState } from "react";

export function useActiveToggle() {
    const [showActiveConfirm, setShowActiveConfirm] = useState(false);
    const [pendingActiveValue, setPendingActiveValue] = useState<boolean | null>(null);

    const requestToggle = useCallback((newValue: boolean) => {
        setPendingActiveValue(newValue);
        setShowActiveConfirm(true);
    }, []);

    const confirmToggle = useCallback(
        (callback: (value: boolean) => void) => {
            if (pendingActiveValue !== null) {
                callback(pendingActiveValue);
            }
            setShowActiveConfirm(false);
            setPendingActiveValue(null);
        },
        [pendingActiveValue],
    );

    const cancelToggle = useCallback(() => {
        setShowActiveConfirm(false);
        setPendingActiveValue(null);
    }, []);

    return {
        showActiveConfirm,
        pendingActiveValue,
        requestToggle,
        confirmToggle,
        cancelToggle,
    };
}
