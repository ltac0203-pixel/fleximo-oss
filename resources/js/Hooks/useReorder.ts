import { api } from "@/api";
import { ENDPOINTS } from "@/api/endpoints";
import { useErrorHandler } from "@/Hooks/useErrorHandler";
import type { ToastState } from "@/Hooks/useToast";
import { ReorderResponse } from "@/types";
import { useCallback, useState } from "react";

interface UseReorderReturn {
    reorder: (orderId: number) => Promise<void>;
    isLoading: boolean;
    error: string | null;
    result: ReorderResponse | null;
    clearResult: () => void;
}

export function useReorder(params?: {
    showToast?: (state: ToastState) => string;
}): UseReorderReturn {
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [result, setResult] = useState<ReorderResponse | null>(null);
    const { handleApiError } = useErrorHandler({
        showToast: params?.showToast,
    });

    const reorder = useCallback(async (orderId: number): Promise<void> => {
        setIsLoading(true);
        setError(null);

        try {
            const response = await api.post<{ data: ReorderResponse }>(
                ENDPOINTS.customer.orders.reorder(orderId),
            );

            if (response.error || !response.data) {
                handleApiError(response, "再注文に失敗", {
                    loginRedirectPath: false,
                    forbiddenMessage: "この注文を再注文する権限がありません。",
                });

                if (response.status === 422) {
                    setError(response.error ?? "再注文可能な商品がありません。");
                } else if (response.status === 403) {
                    setError("この注文を再注文する権限がありません。");
                } else {
                    setError(response.error ?? "再注文に失敗しました。");
                }
                return;
            }

            setResult(response.data.data);
        } catch {
            setError("通信エラーが発生しました。");
        } finally {
            setIsLoading(false);
        }
    }, [handleApiError]);

    const clearResult = useCallback(() => {
        setResult(null);
        setError(null);
    }, []);

    return {
        reorder,
        isLoading,
        error,
        result,
        clearResult,
    };
}
