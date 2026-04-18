import { useCallback } from "react";
import { KdsOrderStatus, KdsStatusUpdateTarget } from "@/types";

export type KdsStatusUpdateHandler = (orderId: number, newStatus: KdsStatusUpdateTarget) => void;

interface UseKdsStatusUpdateHandlersOptions {
    orderId: number;
    onStatusUpdate?: KdsStatusUpdateHandler;
    onAfterUpdate?: () => void;
}

interface UseKdsStatusUpdateHandlersReturn {
    handleStatusUpdate: (newStatus: KdsOrderStatus) => void;
    handleComplete: () => void;
}

export function useKdsStatusUpdateHandlers({
    orderId,
    onStatusUpdate,
    onAfterUpdate,
}: UseKdsStatusUpdateHandlersOptions): UseKdsStatusUpdateHandlersReturn {
    const handleStatusUpdate = useCallback(
        (newStatus: KdsOrderStatus) => {
            onStatusUpdate?.(orderId, newStatus);
            onAfterUpdate?.();
        },
        [onAfterUpdate, onStatusUpdate, orderId],
    );

    const handleComplete = useCallback(() => {
        onStatusUpdate?.(orderId, "completed");
        onAfterUpdate?.();
    }, [onAfterUpdate, onStatusUpdate, orderId]);

    return {
        handleStatusUpdate,
        handleComplete,
    };
}
