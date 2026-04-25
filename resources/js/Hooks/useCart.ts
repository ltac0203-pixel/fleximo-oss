import { useCartStore } from "@/stores/cartStore";
import { Cart } from "@/types";
import { useEffect, useRef } from "react";
import { useShallow } from "zustand/react/shallow";

interface UseCartOptions {
    autoFetch?: boolean;
}

interface UseCartReturn {
    carts: Cart[];
    isLoading: boolean;
    error: string | null;
    fetchCarts: () => Promise<void>;
    addToCart: (
        tenantId: number,
        menuItemId: number,
        quantity: number,
        optionIds: number[],
    ) => Promise<{ cart: Cart | null; error: string | null }>;
    updateQuantity: (cartItemId: number, quantity: number) => Promise<boolean>;
    removeItem: (cartItemId: number) => Promise<boolean>;
    clearCart: (cartId: number) => Promise<boolean>;
    getTotalItemCount: () => number;
    getGrandTotal: () => number;
}

export function useCart(options?: UseCartOptions): UseCartReturn {
    const { autoFetch = true } = options ?? {};

    // 浅い等価チェックで複数フィールドを 1 購読にまとめ、selector 登録回数を削減する。
    const { carts, isLoading, error } = useCartStore(
        useShallow((s) => ({ carts: s.carts, isLoading: s.isLoading, error: s.error })),
    );
    // actions は store 生成時に固定された不変参照のため、useShallow で 1 購読にまとめる。
    const { fetchCarts, addToCart, updateQuantity, removeItem, clearCart, getTotalItemCount, getGrandTotal } =
        useCartStore(
            useShallow((s) => ({
                fetchCarts: s.fetchCarts,
                addToCart: s.addToCart,
                updateQuantity: s.updateQuantity,
                removeItem: s.removeItem,
                clearCart: s.clearCart,
                getTotalItemCount: s.getTotalItemCount,
                getGrandTotal: s.getGrandTotal,
            })),
        );

    const didFetchRef = useRef(false);
    useEffect(() => {
        if (autoFetch && !didFetchRef.current) {
            didFetchRef.current = true;
            void useCartStore.getState().fetchCarts();
        }
    }, [autoFetch]);

    return {
        carts,
        isLoading,
        error,
        fetchCarts,
        addToCart,
        updateQuantity,
        removeItem,
        clearCart,
        getTotalItemCount,
        getGrandTotal,
    };
}
