import { useCallback } from "react";
import { KdsOrderStatus } from "@/types";
import { getNextStatus, getStatusButtonText } from "@/Utils/kdsHelpers";

interface StatusActionButtonProps {
    currentStatus: KdsOrderStatus;
    onStatusUpdate: (newStatus: KdsOrderStatus) => void;
    disabled?: boolean;
}

export default function StatusActionButton({
    currentStatus,
    onStatusUpdate,
    disabled = false,
}: StatusActionButtonProps) {
    const nextStatus = getNextStatus(currentStatus);

    const handleClick = useCallback(() => {
        // キッチン現場の即時操作で押下感を返し、状態更新の確信を持たせる。 を明示し、実装意図の誤読を防ぐ。
        navigator.vibrate?.(50);
        if (nextStatus) {
            onStatusUpdate(nextStatus);
        }
    }, [onStatusUpdate, nextStatus]);

    if (!nextStatus) {
        return null;
    }

    const buttonText = getStatusButtonText(nextStatus);

    return (
        <button
            onClick={handleClick}
            disabled={disabled}
            className="w-full py-2 text-sm font-medium text-white bg-sky-600 border border-sky-500 hover:bg-sky-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            {buttonText}
        </button>
    );
}

interface CompleteButtonProps {
    onComplete: () => void;
    disabled?: boolean;
}

export function CompleteButton({ onComplete, disabled = false }: CompleteButtonProps) {
    const handleClick = useCallback(() => {
        // 完了操作は誤タップ影響が大きいため、触覚で成立を明確にする。
        navigator.vibrate?.(50);
        onComplete();
    }, [onComplete]);

    return (
        <button
            onClick={handleClick}
            disabled={disabled}
            className="w-full py-2 text-sm font-medium text-white bg-green-600 border border-green-500 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            受け渡し完了
        </button>
    );
}
