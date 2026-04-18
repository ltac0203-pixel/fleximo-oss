import { api } from "@/api";
import { ENDPOINTS } from "@/api/endpoints";
import { router } from "@inertiajs/react";
import { useState } from "react";

interface UseMenuItemSoldOutParams {
    itemId: number;
    isSoldOut: boolean;
    onSuccess?: (message: string) => void;
    onError?: (message: string) => void;
}

interface UseMenuItemSoldOutReturn {
    toggleSoldOut: () => Promise<void>;
    toggling: boolean;
}

export function useMenuItemSoldOut({ itemId, isSoldOut, onSuccess, onError }: UseMenuItemSoldOutParams): UseMenuItemSoldOutReturn {
    const [toggling, setToggling] = useState(false);

    const toggleSoldOut = async () => {
        setToggling(true);
        try {
            const { error } = await api.patch(ENDPOINTS.tenant.menu.itemSoldOut(itemId));
            if (error) {
                onError?.("売り切れ状態の切り替えに失敗しました。");
                return;
            }

            router.reload({ only: ["items"] });
            onSuccess?.(isSoldOut ? "在庫を復活しました" : "売り切れに設定しました");
        } catch {
            onError?.("売り切れ状態の切り替えに失敗しました。");
        } finally {
            setToggling(false);
        }
    };

    return { toggleSoldOut, toggling };
}
