import { create } from "zustand";
import { api } from "@/api";
import { Cart, CartItem } from "@/types";

// ---------------------------------------------------------------------------
// 型定義（内部用）
// ---------------------------------------------------------------------------

interface QuantityRollbackContext {
    cartId: number;
    cartItemId: number;
    requestedQuantity: number;
    previousQuantity: number;
    previousSubtotal: number;
    operationId: number;
}

interface RemoveItemRollbackContext {
    cartId: number;
    cartItemId: number;
    removedItem: CartItem;
    removedItemIndex: number;
}

interface ClearCartRollbackContext {
    cart: Cart;
    removedCartIndex: number;
}

interface PendingQuantityUpdate {
    cartItemId: number;
    quantity: number;
    operationId: number;
    rollbackContext: QuantityRollbackContext | null;
    resolvers: Array<(success: boolean) => void>;
}

// ---------------------------------------------------------------------------
// 純粋関数ヘルパー（useCart.ts から変更なしで移動）
// ---------------------------------------------------------------------------

const clampIndex = (index: number, length: number): number => {
    return Math.min(Math.max(index, 0), length);
};

const recalculateCart = (cart: Cart): Cart => {
    const itemCount = cart.items.reduce((sum, item) => sum + item.quantity, 0);
    const total = cart.items.reduce((sum, item) => sum + item.subtotal, 0);

    return {
        ...cart,
        item_count: itemCount,
        total,
        is_empty: cart.items.length === 0,
    };
};

const applyOptimisticQuantityUpdate = (
    carts: Cart[],
    cartItemId: number,
    quantity: number,
    operationId: number,
): { nextCarts: Cart[]; rollbackContext: QuantityRollbackContext | null } => {
    let rollbackContext: QuantityRollbackContext | null = null;

    const nextCarts = carts.map((cart) => {
        const itemIndex = cart.items.findIndex((item) => item.id === cartItemId);
        if (itemIndex < 0) {
            return cart;
        }

        const currentItem = cart.items[itemIndex];
        const unitPrice = currentItem.quantity > 0 ? currentItem.subtotal / currentItem.quantity : currentItem.subtotal;
        const updatedItem: CartItem = {
            ...currentItem,
            quantity,
            subtotal: unitPrice * quantity,
        };

        const updatedItems = cart.items.map((item, idx) => idx === itemIndex ? updatedItem : item);

        rollbackContext = {
            cartId: cart.id,
            cartItemId,
            requestedQuantity: quantity,
            previousQuantity: currentItem.quantity,
            previousSubtotal: currentItem.subtotal,
            operationId,
        };

        return recalculateCart({
            ...cart,
            items: updatedItems,
        });
    });

    return { nextCarts, rollbackContext };
};

const rollbackOptimisticQuantityUpdate = (
    carts: Cart[],
    context: QuantityRollbackContext,
    latestOperationId: number,
): Cart[] => {
    if (latestOperationId !== context.operationId) {
        return carts;
    }

    const cartIndex = carts.findIndex((cart) => cart.id === context.cartId);
    if (cartIndex < 0) {
        return carts;
    }

    const cart = carts[cartIndex];
    const itemIndex = cart.items.findIndex((item) => item.id === context.cartItemId);
    if (itemIndex < 0) {
        return carts;
    }

    const currentItem = cart.items[itemIndex];
    if (currentItem.quantity !== context.requestedQuantity) {
        return carts;
    }

    const updatedItems = cart.items.map((item, idx) =>
        idx === itemIndex
            ? { ...currentItem, quantity: context.previousQuantity, subtotal: context.previousSubtotal }
            : item,
    );

    return carts.map((c, idx) =>
        idx === cartIndex ? recalculateCart({ ...cart, items: updatedItems }) : c,
    );
};

const applyOptimisticItemRemoval = (
    carts: Cart[],
    cartItemId: number,
): { nextCarts: Cart[]; rollbackContext: RemoveItemRollbackContext | null } => {
    let rollbackContext: RemoveItemRollbackContext | null = null;

    const nextCarts = carts.map((cart) => {
        const itemIndex = cart.items.findIndex((item) => item.id === cartItemId);
        if (itemIndex < 0) {
            return cart;
        }

        const updatedItems = cart.items.filter((item) => item.id !== cartItemId);
        const removedItem = cart.items[itemIndex];

        rollbackContext = {
            cartId: cart.id,
            cartItemId,
            removedItem,
            removedItemIndex: itemIndex,
        };

        return recalculateCart({
            ...cart,
            items: updatedItems,
        });
    });

    return { nextCarts, rollbackContext };
};

const rollbackOptimisticItemRemoval = (carts: Cart[], context: RemoveItemRollbackContext): Cart[] => {
    const cartIndex = carts.findIndex((cart) => cart.id === context.cartId);
    if (cartIndex < 0) {
        return carts;
    }

    const cart = carts[cartIndex];
    if (cart.items.some((item) => item.id === context.cartItemId)) {
        return carts;
    }

    const insertIndex = clampIndex(context.removedItemIndex, cart.items.length);
    const updatedItems = [...cart.items.slice(0, insertIndex), context.removedItem, ...cart.items.slice(insertIndex)];

    return carts.map((c, idx) =>
        idx === cartIndex ? recalculateCart({ ...cart, items: updatedItems }) : c,
    );
};

const applyOptimisticClearCart = (
    carts: Cart[],
    cartId: number,
): { nextCarts: Cart[]; rollbackContext: ClearCartRollbackContext | null } => {
    const removedCartIndex = carts.findIndex((cart) => cart.id === cartId);
    if (removedCartIndex < 0) {
        return { nextCarts: carts, rollbackContext: null };
    }

    return {
        nextCarts: carts.filter((cart) => cart.id !== cartId),
        rollbackContext: {
            cart: carts[removedCartIndex],
            removedCartIndex,
        },
    };
};

const rollbackOptimisticClearCart = (carts: Cart[], context: ClearCartRollbackContext): Cart[] => {
    if (carts.some((cart) => cart.id === context.cart.id)) {
        return carts;
    }

    const insertIndex = clampIndex(context.removedCartIndex, carts.length);
    return [...carts.slice(0, insertIndex), context.cart, ...carts.slice(insertIndex)];
};

// ---------------------------------------------------------------------------
// モジュールスコープの可変状態（useRef を置き換え、再レンダー不要）
// ---------------------------------------------------------------------------

let quantityOperationSeq: Record<number, number> = {};
let pendingQuantityUpdates: Map<number, PendingQuantityUpdate> = new Map();
let quantityFlushTimer: ReturnType<typeof setTimeout> | null = null;
let isFlushingQuantityUpdates = false;
let shouldFlushQuantityUpdates = false;
const DEFAULT_DEBOUNCE_MS = 250;
let currentDebounceMs = DEFAULT_DEBOUNCE_MS;

/** テストヘルパー: `updateQuantity` で使うデバウンス間隔を上書きする。 */
export function _setQuantityDebounceMs(ms: number): void {
    currentDebounceMs = ms;
}

// ---------------------------------------------------------------------------
// ストア
// ---------------------------------------------------------------------------

export interface CartState {
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
    reset: () => void;
}

const initialState = {
    carts: [] as Cart[],
    isLoading: false,
    error: null as string | null,
};

export const useCartStore = create<CartState>()((set, get) => {
    // -- 内部ヘルパー: デバウンスした数量更新をフラッシュする -----------------

    const flushQuantityUpdates = async (): Promise<void> => {
        if (isFlushingQuantityUpdates) {
            shouldFlushQuantityUpdates = true;
            return;
        }

        if (quantityFlushTimer !== null) {
            clearTimeout(quantityFlushTimer);
            quantityFlushTimer = null;
        }

        if (pendingQuantityUpdates.size === 0) {
            return;
        }

        isFlushingQuantityUpdates = true;

        try {
            while (pendingQuantityUpdates.size > 0) {
                const queuedUpdates = Array.from(pendingQuantityUpdates.values());
                pendingQuantityUpdates.clear();

                await Promise.all(
                    queuedUpdates.map(async ({ cartItemId, quantity, rollbackContext, resolvers }) => {
                        const { error: updateError } = await api.patch<CartItem>(
                            `/api/customer/cart/items/${cartItemId}`,
                            { quantity },
                        );

                        if (updateError) {
                            if (rollbackContext) {
                                const rollbackState = rollbackContext;
                                set((state) => ({
                                    carts: rollbackOptimisticQuantityUpdate(
                                        state.carts,
                                        rollbackState,
                                        quantityOperationSeq[cartItemId] ?? 0,
                                    ),
                                }));
                            }
                            set({ error: updateError });
                            resolvers.forEach((resolve) => resolve(false));
                            return;
                        }

                        resolvers.forEach((resolve) => resolve(true));
                    }),
                );
            }
        } finally {
            isFlushingQuantityUpdates = false;

            if (shouldFlushQuantityUpdates || pendingQuantityUpdates.size > 0) {
                shouldFlushQuantityUpdates = false;
                if (currentDebounceMs <= 0) {
                    void flushQuantityUpdates();
                } else {
                    quantityFlushTimer = setTimeout(() => {
                        quantityFlushTimer = null;
                        void flushQuantityUpdates();
                    }, currentDebounceMs);
                }
            }
        }
    };

    const scheduleQuantityUpdatesFlush = (): void => {
        if (quantityFlushTimer !== null) {
            clearTimeout(quantityFlushTimer);
            quantityFlushTimer = null;
        }

        if (currentDebounceMs <= 0) {
            void flushQuantityUpdates();
            return;
        }

        quantityFlushTimer = setTimeout(() => {
            quantityFlushTimer = null;
            void flushQuantityUpdates();
        }, currentDebounceMs);
    };

    // -- モジュールスコープの可変状態をリセットする ---------------------------------

    const resetModuleState = (): void => {
        if (quantityFlushTimer !== null) {
            clearTimeout(quantityFlushTimer);
            quantityFlushTimer = null;
        }
        pendingQuantityUpdates.forEach(({ resolvers }) => {
            resolvers.forEach((resolve) => resolve(false));
        });
        pendingQuantityUpdates = new Map();
        quantityOperationSeq = {};
        isFlushingQuantityUpdates = false;
        shouldFlushQuantityUpdates = false;
        currentDebounceMs = DEFAULT_DEBOUNCE_MS;
    };

    return {
        ...initialState,

        fetchCarts: async () => {
            set({ isLoading: true, error: null });

            const { data, error: fetchError } = await api.get<Cart[]>("/api/customer/cart");

            if (fetchError) {
                set({ error: fetchError, isLoading: false });
            } else {
                set({ carts: data ?? [], isLoading: false });
            }
        },

        addToCart: async (tenantId, menuItemId, quantity, optionIds) => {
            set({ error: null });

            const { data, error: addError } = await api.post<Cart>("/api/customer/cart/items", {
                tenant_id: tenantId,
                menu_item_id: menuItemId,
                quantity,
                option_ids: optionIds,
            });

            if (addError) {
                set({ error: addError });
                return { cart: null, error: addError };
            }

            const resolvedCart = data ?? null;

            if (resolvedCart) {
                set((state) => {
                    const prevCarts = state.carts;
                    const cartIndex = prevCarts.findIndex(
                        (cart) => cart.id === resolvedCart.id || cart.tenant_id === resolvedCart.tenant_id,
                    );
                    if (cartIndex >= 0) {
                        return {
                            carts: prevCarts.map((c, idx) =>
                                idx === cartIndex
                                    ? { ...c, ...resolvedCart, tenant: resolvedCart.tenant ?? c.tenant }
                                    : c,
                            ),
                        };
                    }
                    return { carts: [...prevCarts, resolvedCart] };
                });
            }

            return { cart: resolvedCart, error: null };
        },

        updateQuantity: (cartItemId, quantity) => {
            set({ error: null });

            const operationId = (quantityOperationSeq[cartItemId] ?? 0) + 1;
            quantityOperationSeq[cartItemId] = operationId;

            let rollbackContext: QuantityRollbackContext | null = null;
            set((state) => {
                const result = applyOptimisticQuantityUpdate(state.carts, cartItemId, quantity, operationId);
                rollbackContext = result.rollbackContext;
                return { carts: result.nextCarts };
            });

            if (currentDebounceMs <= 0) {
                return (async () => {
                    const { error: updateError } = await api.patch<CartItem>(`/api/customer/cart/items/${cartItemId}`, {
                        quantity,
                    });

                    if (updateError) {
                        if (rollbackContext) {
                            const rollbackState = rollbackContext;
                            set((state) => ({
                                carts: rollbackOptimisticQuantityUpdate(
                                    state.carts,
                                    rollbackState,
                                    quantityOperationSeq[cartItemId] ?? 0,
                                ),
                            }));
                        }
                        set({ error: updateError });
                        return false;
                    }

                    return true;
                })();
            }

            return new Promise<boolean>((resolve) => {
                const existingPendingUpdate = pendingQuantityUpdates.get(cartItemId);

                if (existingPendingUpdate) {
                    existingPendingUpdate.quantity = quantity;
                    existingPendingUpdate.operationId = operationId;
                    existingPendingUpdate.resolvers.push(resolve);
                    if (existingPendingUpdate.rollbackContext) {
                        existingPendingUpdate.rollbackContext = {
                            ...existingPendingUpdate.rollbackContext,
                            requestedQuantity: quantity,
                            operationId,
                        };
                    } else if (rollbackContext) {
                        existingPendingUpdate.rollbackContext = rollbackContext;
                    }
                } else {
                    pendingQuantityUpdates.set(cartItemId, {
                        cartItemId,
                        quantity,
                        operationId,
                        rollbackContext,
                        resolvers: [resolve],
                    });
                }

                scheduleQuantityUpdatesFlush();
            });
        },

        removeItem: async (cartItemId) => {
            set({ error: null });

            let rollbackContext: RemoveItemRollbackContext | null = null;
            set((state) => {
                const result = applyOptimisticItemRemoval(state.carts, cartItemId);
                rollbackContext = result.rollbackContext;
                return { carts: result.nextCarts };
            });

            const { error: removeError } = await api.delete(`/api/customer/cart/items/${cartItemId}`);

            if (removeError) {
                if (rollbackContext) {
                    const rollbackState = rollbackContext;
                    set((state) => ({
                        carts: rollbackOptimisticItemRemoval(state.carts, rollbackState),
                    }));
                }
                set({ error: removeError });
                return false;
            }

            return true;
        },

        clearCart: async (cartId) => {
            set({ error: null });

            let rollbackContext: ClearCartRollbackContext | null = null;
            set((state) => {
                const result = applyOptimisticClearCart(state.carts, cartId);
                rollbackContext = result.rollbackContext;
                return { carts: result.nextCarts };
            });

            const { error: clearError } = await api.delete(`/api/customer/cart/${cartId}`);

            if (clearError) {
                if (rollbackContext) {
                    const rollbackState = rollbackContext;
                    set((state) => ({
                        carts: rollbackOptimisticClearCart(state.carts, rollbackState),
                    }));
                }
                set({ error: clearError });
                return false;
            }

            return true;
        },

        getTotalItemCount: () => {
            return get().carts.reduce((sum, cart) => sum + (cart.item_count ?? 0), 0);
        },

        getGrandTotal: () => {
            return get().carts.reduce((sum, cart) => sum + (cart.total ?? 0), 0);
        },

        reset: () => {
            resetModuleState();
            set(initialState);
        },
    };
});
