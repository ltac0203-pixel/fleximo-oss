import { useCallback, useState } from "react";
import { api } from "@/api/client";
import { ENDPOINTS } from "@/api/endpoints";

interface OrderPauseToggleResponse {
    data: {
        is_order_paused: boolean;
        order_paused_at: string | null;
    };
    message: string;
}

interface UseOrderPauseReturn {
    isOrderPaused: boolean;
    isToggling: boolean;
    toggleOrderPause: () => Promise<void>;
}

export function useOrderPause(initialPaused: boolean): UseOrderPauseReturn {
    const [isOrderPaused, setIsOrderPaused] = useState(initialPaused);
    const [isToggling, setIsToggling] = useState(false);

    const toggleOrderPause = useCallback(async () => {
        setIsToggling(true);
        try {
            const { data, error } = await api.post<OrderPauseToggleResponse>(
                ENDPOINTS.tenant.orderPause.toggle,
            );

            if (error || !data) {
                throw new Error(error ?? "注文受付状態の切り替えに失敗しました");
            }

            setIsOrderPaused(data.data.is_order_paused);
        } finally {
            setIsToggling(false);
        }
    }, []);

    return { isOrderPaused, isToggling, toggleOrderPause };
}
