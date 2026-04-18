import { useCartStore } from "@/stores/cartStore";
import { Cart } from "@/types";
import { useEffect, useRef } from "react";

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

    const carts = useCartStore((s) => s.carts);
    const isLoading = useCartStore((s) => s.isLoading);
    const error = useCartStore((s) => s.error);
    const fetchCarts = useCartStore((s) => s.fetchCarts);
    const addToCart = useCartStore((s) => s.addToCart);
    const updateQuantity = useCartStore((s) => s.updateQuantity);
    const removeItem = useCartStore((s) => s.removeItem);
    const clearCart = useCartStore((s) => s.clearCart);
    const getTotalItemCount = useCartStore((s) => s.getTotalItemCount);
    const getGrandTotal = useCartStore((s) => s.getGrandTotal);

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
