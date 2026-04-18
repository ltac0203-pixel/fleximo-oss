import { useCallback, useState } from "react";

export function useHelpPanel() {
    const [showHelp, setShowHelp] = useState(false);

    const openHelp = useCallback(() => setShowHelp(true), []);
    const closeHelp = useCallback(() => setShowHelp(false), []);

    return {
        showHelp,
        openHelp,
        closeHelp,
    };
}
