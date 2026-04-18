import { PollingState } from "@/types";

interface PollingIndicatorProps {
    state: PollingState;
}

export default function PollingIndicator({ state }: PollingIndicatorProps) {
    if (state === "error") {
        return (
            <div className="flex items-center gap-2 text-sm text-red-600">
                <div className="w-2 h-2 rounded-full bg-red-500 animate-pulse" />
                <span>接続エラー</span>
            </div>
        );
    }

    return null;
}
