import { renderHook, waitFor, act } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { useCart } from "@/Hooks/useCart";
import { useCartStore, _setQuantityDebounceMs } from "@/stores/cartStore";
import { Cart } from "@/types";

const apiMock = vi.hoisted(() => ({
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
}));

vi.mock("@/api", () => ({
    api: apiMock,
}));

function createDeferred<T>() {
    let resolve!: (value: T) => void;
    const promise = new Promise<T>((res) => {
        resolve = res;
    });

    return { promise, resolve };
}

function createCart({
    cartId = 1,
    tenantId = 1,
    itemId = 10,
    menuItemId = 100,
    itemName = "カレー",
    quantity = 2,
    unitPrice = 500,
}: {
    cartId?: number;
    tenantId?: number;
    itemId?: number;
    menuItemId?: number;
    itemName?: string;
    quantity?: number;
    unitPrice?: number;
} = {}): Cart {
    return {
        id: cartId,
        user_id: 1,
        tenant_id: tenantId,
        tenant: {
            id: tenantId,
            name: `テスト店舗${tenantId}`,
            slug: `test-shop-${tenantId}`,
        },
        items: [
            {
                id: itemId,
                menu_item: {
                    id: menuItemId,
                    name: itemName,
                    description: null,
                    price: unitPrice,
                    is_sold_out: false,
                },
                quantity,
                options: [],
                subtotal: quantity * unitPrice,
            },
        ],
        total: quantity * unitPrice,
        item_count: quantity,
        is_empty: false,
    };
}

function renderUseCart(options?: { autoFetch?: boolean }) {
    return renderHook(() => useCart(options));
}

describe("useCart", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        useCartStore.getState().reset();
        _setQuantityDebounceMs(0);
    });

    it("fetches carts on mount and updates state", async () => {
        apiMock.get.mockResolvedValueOnce({
            data: [createCart()],
            error: null,
        });

        const { result } = renderUseCart();

        await waitFor(() => {
            expect(apiMock.get).toHaveBeenCalledWith("/api/customer/cart");
        });
        await waitFor(() => {
            expect(result.current.carts).toHaveLength(1);
        });

        expect(result.current.error).toBeNull();
        expect(result.current.isLoading).toBe(false);
    });

    it("returns cart and null error when addToCart succeeds", async () => {
        apiMock.get.mockResolvedValueOnce({
            data: [createCart()],
            error: null,
        });
        apiMock.post.mockResolvedValueOnce({
            data: createCart(),
            error: null,
        });

        const { result } = renderUseCart();

        await waitFor(() => {
            expect(result.current.carts).toHaveLength(1);
        });

        let addResult: { cart: Cart | null; error: string | null } = { cart: null, error: null };
        await act(async () => {
            addResult = await result.current.addToCart(1, 100, 1, []);
        });

        expect(apiMock.post).toHaveBeenCalledWith("/api/customer/cart/items", {
            tenant_id: 1,
            menu_item_id: 100,
            quantity: 1,
            option_ids: [],
        });
        expect(addResult.error).toBeNull();
        expect(addResult.cart).not.toBeNull();
        expect(result.current.error).toBeNull();
    });

    it("returns null cart and error when addToCart fails", async () => {
        apiMock.get.mockResolvedValueOnce({
            data: [createCart()],
            error: null,
        });
        apiMock.post.mockResolvedValueOnce({
            data: null,
            error: "カートへの追加に失敗しました",
        });

        const { result } = renderUseCart();

        await waitFor(() => {
            expect(result.current.carts).toHaveLength(1);
        });

        let addResult: { cart: Cart | null; error: string | null } = { cart: null, error: null };
        await act(async () => {
            addResult = await result.current.addToCart(1, 100, 1, []);
        });

        expect(addResult.cart).toBeNull();
        expect(addResult.error).toBe("カートへの追加に失敗しました");
        expect(result.current.error).toBe("カートへの追加に失敗しました");
    });

    it("applies optimistic quantity update and keeps changes on success", async () => {
        apiMock.get.mockResolvedValueOnce({
            data: [createCart()],
            error: null,
        });
        const patchDeferred = createDeferred<{ data: null; error: null }>();
        apiMock.patch.mockReturnValueOnce(patchDeferred.promise);

        const { result } = renderUseCart();

        await waitFor(() => {
            expect(result.current.carts[0]?.items[0]?.quantity).toBe(2);
        });
        await act(async () => {
            await Promise.resolve();
        });

        let updatePromise = Promise.resolve(false);
        act(() => {
            updatePromise = result.current.updateQuantity(10, 3);
        });

        await waitFor(() => {
            expect(result.current.carts[0]?.items[0]?.quantity).toBe(3);
        });
        expect(result.current.carts[0]?.total).toBe(1500);
        expect(result.current.carts[0]?.item_count).toBe(3);

        let success = false;
        await act(async () => {
            patchDeferred.resolve({ data: null, error: null });
            success = await updatePromise;
        });

        expect(success).toBe(true);
        expect(result.current.carts[0]?.items[0]?.quantity).toBe(3);
        expect(result.current.carts[0]?.total).toBe(1500);
        expect(result.current.carts[0]?.item_count).toBe(3);
        expect(result.current.error).toBeNull();
    });

    it("rolls back optimistic quantity update when API returns an error", async () => {
        apiMock.get.mockResolvedValueOnce({
            data: [createCart()],
            error: null,
        });
        const patchDeferred = createDeferred<{ data: null; error: string }>();
        apiMock.patch.mockReturnValueOnce(patchDeferred.promise);

        const { result } = renderUseCart();

        await waitFor(() => {
            expect(result.current.carts[0]?.items[0]?.quantity).toBe(2);
        });
        await act(async () => {
            await Promise.resolve();
        });

        let updatePromise = Promise.resolve(true);
        act(() => {
            updatePromise = result.current.updateQuantity(10, 5);
        });

        await waitFor(() => {
            expect(result.current.carts[0]?.items[0]?.quantity).toBe(5);
        });
        expect(result.current.carts[0]?.total).toBe(2500);
        expect(result.current.carts[0]?.item_count).toBe(5);

        let success = true;
        await act(async () => {
            patchDeferred.resolve({ data: null, error: "数量更新に失敗しました" });
            success = await updatePromise;
        });

        expect(success).toBe(false);
        await waitFor(() => {
            expect(result.current.carts[0]?.items[0]?.quantity).toBe(2);
        });
        expect(result.current.carts[0]?.total).toBe(1000);
        expect(result.current.carts[0]?.item_count).toBe(2);
        expect(result.current.error).toBe("数量更新に失敗しました");
    });

    it("batches rapid quantity updates for the same item into a single request", async () => {
        apiMock.get.mockResolvedValueOnce({
            data: [createCart()],
            error: null,
        });
        apiMock.patch.mockResolvedValueOnce({
            data: null,
            error: null,
        });

        _setQuantityDebounceMs(30);
        const { result } = renderUseCart();

        await waitFor(() => {
            expect(result.current.carts[0]?.items[0]?.quantity).toBe(2);
        });

        let firstUpdatePromise = Promise.resolve(false);
        let secondUpdatePromise = Promise.resolve(false);
        let thirdUpdatePromise = Promise.resolve(false);
        act(() => {
            firstUpdatePromise = result.current.updateQuantity(10, 3);
            secondUpdatePromise = result.current.updateQuantity(10, 4);
            thirdUpdatePromise = result.current.updateQuantity(10, 5);
        });

        expect(result.current.carts[0]?.items[0]?.quantity).toBe(5);
        expect(apiMock.patch).not.toHaveBeenCalled();

        await waitFor(() => {
            expect(apiMock.patch).toHaveBeenCalledTimes(1);
        });
        expect(apiMock.patch).toHaveBeenCalledWith("/api/customer/cart/items/10", {
            quantity: 5,
        });

        await expect(firstUpdatePromise).resolves.toBe(true);
        await expect(secondUpdatePromise).resolves.toBe(true);
        await expect(thirdUpdatePromise).resolves.toBe(true);
        expect(result.current.carts[0]?.items[0]?.quantity).toBe(5);
        expect(result.current.error).toBeNull();
    });

    it("rolls back to the original quantity when a batched update fails", async () => {
        apiMock.get.mockResolvedValueOnce({
            data: [createCart()],
            error: null,
        });
        apiMock.patch.mockResolvedValueOnce({
            data: null,
            error: "数量更新に失敗しました",
        });

        _setQuantityDebounceMs(30);
        const { result } = renderUseCart();

        await waitFor(() => {
            expect(result.current.carts[0]?.items[0]?.quantity).toBe(2);
        });

        let firstUpdatePromise = Promise.resolve(true);
        let secondUpdatePromise = Promise.resolve(true);
        act(() => {
            firstUpdatePromise = result.current.updateQuantity(10, 3);
            secondUpdatePromise = result.current.updateQuantity(10, 5);
        });

        expect(result.current.carts[0]?.items[0]?.quantity).toBe(5);

        await waitFor(() => {
            expect(apiMock.patch).toHaveBeenCalledTimes(1);
        });

        await expect(firstUpdatePromise).resolves.toBe(false);
        await expect(secondUpdatePromise).resolves.toBe(false);

        await waitFor(() => {
            expect(result.current.carts[0]?.items[0]?.quantity).toBe(2);
        });
        expect(result.current.carts[0]?.total).toBe(1000);
        expect(result.current.carts[0]?.item_count).toBe(2);
        expect(result.current.error).toBe("数量更新に失敗しました");
    });

    it("ignores stale quantity rollback from an older failed request", async () => {
        apiMock.get.mockResolvedValueOnce({
            data: [createCart()],
            error: null,
        });

        const firstPatchDeferred = createDeferred<{ data: null; error: string }>();
        const secondPatchDeferred = createDeferred<{ data: null; error: null }>();
        apiMock.patch.mockReturnValueOnce(firstPatchDeferred.promise).mockReturnValueOnce(secondPatchDeferred.promise);

        const { result } = renderUseCart();

        await waitFor(() => {
            expect(result.current.carts[0]?.items[0]?.quantity).toBe(2);
        });

        let firstUpdatePromise = Promise.resolve(true);
        let secondUpdatePromise = Promise.resolve(false);
        act(() => {
            firstUpdatePromise = result.current.updateQuantity(10, 5);
        });
        await waitFor(() => {
            expect(result.current.carts[0]?.items[0]?.quantity).toBe(5);
        });

        act(() => {
            secondUpdatePromise = result.current.updateQuantity(10, 4);
        });
        await waitFor(() => {
            expect(result.current.carts[0]?.items[0]?.quantity).toBe(4);
        });

        let secondSuccess = false;
        await act(async () => {
            secondPatchDeferred.resolve({ data: null, error: null });
            secondSuccess = await secondUpdatePromise;
        });
        expect(secondSuccess).toBe(true);

        let firstSuccess = true;
        await act(async () => {
            firstPatchDeferred.resolve({ data: null, error: "数量更新に失敗しました" });
            firstSuccess = await firstUpdatePromise;
        });

        expect(firstSuccess).toBe(false);
        expect(result.current.carts[0]?.items[0]?.quantity).toBe(4);
        expect(result.current.carts[0]?.total).toBe(2000);
        expect(result.current.carts[0]?.item_count).toBe(4);
        expect(result.current.error).toBe("数量更新に失敗しました");
    });

    it("rolls back only the failed cart when quantity update fails during concurrent updates", async () => {
        apiMock.get.mockResolvedValueOnce({
            data: [
                createCart({ cartId: 1, tenantId: 1, itemId: 10, menuItemId: 100, quantity: 2, unitPrice: 500 }),
                createCart({ cartId: 2, tenantId: 2, itemId: 20, menuItemId: 200, quantity: 1, unitPrice: 700 }),
            ],
            error: null,
        });

        const firstPatchDeferred = createDeferred<{ data: null; error: string }>();
        const secondPatchDeferred = createDeferred<{ data: null; error: null }>();
        apiMock.patch.mockReturnValueOnce(firstPatchDeferred.promise).mockReturnValueOnce(secondPatchDeferred.promise);

        const { result } = renderUseCart();

        await waitFor(() => {
            expect(result.current.carts).toHaveLength(2);
        });

        let firstUpdatePromise = Promise.resolve(true);
        let secondUpdatePromise = Promise.resolve(false);
        act(() => {
            firstUpdatePromise = result.current.updateQuantity(10, 5);
        });
        await waitFor(() => {
            expect(result.current.carts[0]?.items[0]?.quantity).toBe(5);
        });

        act(() => {
            secondUpdatePromise = result.current.updateQuantity(20, 3);
        });
        await waitFor(() => {
            expect(result.current.carts[1]?.items[0]?.quantity).toBe(3);
        });

        await act(async () => {
            secondPatchDeferred.resolve({ data: null, error: null });
            await secondUpdatePromise;
        });

        let firstSuccess = true;
        await act(async () => {
            firstPatchDeferred.resolve({ data: null, error: "数量更新に失敗しました" });
            firstSuccess = await firstUpdatePromise;
        });

        expect(firstSuccess).toBe(false);
        expect(result.current.carts[0]?.items[0]?.quantity).toBe(2);
        expect(result.current.carts[0]?.total).toBe(1000);
        expect(result.current.carts[0]?.item_count).toBe(2);

        expect(result.current.carts[1]?.items[0]?.quantity).toBe(3);
        expect(result.current.carts[1]?.total).toBe(2100);
        expect(result.current.carts[1]?.item_count).toBe(3);
    });

    it("does not fetch carts on mount when autoFetch is false", async () => {
        const { result } = renderUseCart({ autoFetch: false });

        await act(async () => {
            await Promise.resolve();
        });

        expect(apiMock.get).not.toHaveBeenCalled();
        expect(result.current.carts).toHaveLength(0);
        expect(result.current.isLoading).toBe(false);
    });

    it("allows manual fetchCarts when autoFetch is false", async () => {
        apiMock.get.mockResolvedValueOnce({
            data: [createCart()],
            error: null,
        });

        const { result } = renderUseCart({ autoFetch: false });

        await act(async () => {
            await Promise.resolve();
        });

        expect(apiMock.get).not.toHaveBeenCalled();

        await act(async () => {
            await result.current.fetchCarts();
        });

        expect(apiMock.get).toHaveBeenCalledWith("/api/customer/cart");
        expect(result.current.carts).toHaveLength(1);
    });

    it("rolls back optimistic removal when item deletion fails", async () => {
        apiMock.get.mockResolvedValueOnce({
            data: [createCart()],
            error: null,
        });
        const deleteDeferred = createDeferred<{ data: null; error: string }>();
        apiMock.delete.mockReturnValueOnce(deleteDeferred.promise);

        const { result } = renderUseCart();

        await waitFor(() => {
            expect(result.current.carts[0]?.items[0]?.id).toBe(10);
        });
        await act(async () => {
            await Promise.resolve();
        });

        let removePromise = Promise.resolve(true);
        act(() => {
            removePromise = result.current.removeItem(10);
        });

        await waitFor(() => {
            expect(result.current.carts[0]?.items).toHaveLength(0);
        });
        expect(result.current.carts[0]?.item_count).toBe(0);
        expect(result.current.carts[0]?.total).toBe(0);

        let success = true;
        await act(async () => {
            deleteDeferred.resolve({ data: null, error: "商品削除に失敗しました" });
            success = await removePromise;
        });

        expect(success).toBe(false);
        await waitFor(() => {
            expect(result.current.carts[0]?.items).toHaveLength(1);
        });
        expect(result.current.carts[0]?.item_count).toBe(2);
        expect(result.current.carts[0]?.total).toBe(1000);
        expect(result.current.error).toBe("商品削除に失敗しました");
    });

    it("restores only the removed item when removeItem fails during concurrent updates", async () => {
        apiMock.get.mockResolvedValueOnce({
            data: [
                createCart({ cartId: 1, tenantId: 1, itemId: 10, menuItemId: 100, quantity: 2, unitPrice: 500 }),
                createCart({ cartId: 2, tenantId: 2, itemId: 20, menuItemId: 200, quantity: 1, unitPrice: 700 }),
            ],
            error: null,
        });

        const removeDeferred = createDeferred<{ data: null; error: string }>();
        const updateDeferred = createDeferred<{ data: null; error: null }>();
        apiMock.delete.mockReturnValueOnce(removeDeferred.promise);
        apiMock.patch.mockReturnValueOnce(updateDeferred.promise);

        const { result } = renderUseCart();

        await waitFor(() => {
            expect(result.current.carts).toHaveLength(2);
        });

        let removePromise = Promise.resolve(true);
        act(() => {
            removePromise = result.current.removeItem(10);
        });
        await waitFor(() => {
            expect(result.current.carts[0]?.items).toHaveLength(0);
        });

        let updatePromise = Promise.resolve(false);
        act(() => {
            updatePromise = result.current.updateQuantity(20, 3);
        });
        await waitFor(() => {
            expect(result.current.carts[1]?.items[0]?.quantity).toBe(3);
        });

        await act(async () => {
            updateDeferred.resolve({ data: null, error: null });
            await updatePromise;
        });

        let removeSuccess = true;
        await act(async () => {
            removeDeferred.resolve({ data: null, error: "商品削除に失敗しました" });
            removeSuccess = await removePromise;
        });

        expect(removeSuccess).toBe(false);
        expect(result.current.carts[0]?.items).toHaveLength(1);
        expect(result.current.carts[0]?.item_count).toBe(2);
        expect(result.current.carts[0]?.total).toBe(1000);

        expect(result.current.carts[1]?.items[0]?.quantity).toBe(3);
        expect(result.current.carts[1]?.item_count).toBe(3);
        expect(result.current.carts[1]?.total).toBe(2100);
        expect(result.current.error).toBe("商品削除に失敗しました");
    });

    it("restores only the cleared cart when clearCart fails during concurrent updates", async () => {
        apiMock.get.mockResolvedValueOnce({
            data: [
                createCart({ cartId: 1, tenantId: 1, itemId: 10, menuItemId: 100, quantity: 2, unitPrice: 500 }),
                createCart({ cartId: 2, tenantId: 2, itemId: 20, menuItemId: 200, quantity: 1, unitPrice: 700 }),
            ],
            error: null,
        });

        const clearDeferred = createDeferred<{ data: null; error: string }>();
        const updateDeferred = createDeferred<{ data: null; error: null }>();
        apiMock.delete.mockReturnValueOnce(clearDeferred.promise);
        apiMock.patch.mockReturnValueOnce(updateDeferred.promise);

        const { result } = renderUseCart();

        await waitFor(() => {
            expect(result.current.carts).toHaveLength(2);
        });

        let clearPromise = Promise.resolve(true);
        act(() => {
            clearPromise = result.current.clearCart(1);
        });
        await waitFor(() => {
            expect(result.current.carts).toHaveLength(1);
        });
        expect(result.current.carts[0]?.id).toBe(2);

        let updatePromise = Promise.resolve(false);
        act(() => {
            updatePromise = result.current.updateQuantity(20, 3);
        });
        await waitFor(() => {
            expect(result.current.carts[0]?.items[0]?.quantity).toBe(3);
        });

        await act(async () => {
            updateDeferred.resolve({ data: null, error: null });
            await updatePromise;
        });

        let clearSuccess = true;
        await act(async () => {
            clearDeferred.resolve({ data: null, error: "カートのクリアに失敗しました" });
            clearSuccess = await clearPromise;
        });

        expect(clearSuccess).toBe(false);
        expect(result.current.carts).toHaveLength(2);
        expect(result.current.carts[0]?.id).toBe(1);
        expect(result.current.carts[0]?.items[0]?.quantity).toBe(2);
        expect(result.current.carts[1]?.id).toBe(2);
        expect(result.current.carts[1]?.items[0]?.quantity).toBe(3);
        expect(result.current.carts[1]?.item_count).toBe(3);
        expect(result.current.carts[1]?.total).toBe(2100);
        expect(result.current.error).toBe("カートのクリアに失敗しました");
    });
});
