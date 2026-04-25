import { act, renderHook } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { useCheckout } from "@/Hooks/useCheckout";
import { SavedCard } from "@/types";

const apiPostMock = vi.hoisted(() => vi.fn());
const useFincodeMock = vi.hoisted(() => vi.fn());
const createTokenMock = vi.hoisted(() => vi.fn());
const routerVisitMock = vi.hoisted(() => vi.fn());

vi.mock("@/api", async (importOriginal) => {
    const actual = await importOriginal<typeof import("@/api")>();
    return {
        ...actual,
        api: {
            post: apiPostMock,
        },
    };
});

vi.mock("@/Hooks/useFincode", () => ({
    useFincode: useFincodeMock,
}));

vi.mock("@inertiajs/react", () => ({
    router: {
        visit: routerVisitMock,
    },
}));

function createSavedCards(): SavedCard[] {
    return [
        {
            id: 11,
            card_no_display: "**** **** **** 4242",
            brand: "VISA",
            expire: "12/30",
            is_default: true,
        },
    ];
}

describe("useCheckout", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        createTokenMock.mockReset();
        createTokenMock.mockResolvedValue("tok_test_123");
        useFincodeMock.mockReturnValue({
            isReady: true,
            isLoading: false,
            error: null,
            mountUI: vi.fn(),
            unmountUI: vi.fn(),
            createToken: createTokenMock,
            clearForm: vi.fn(),
        });
    });

    it("defaults to saved_card when savedCards exist", () => {
        const { result } = renderHook(() =>
            useCheckout({
                cartId: 1,
                tenantId: 1,
                fincodePublicKey: "pk_test_123",
                isProduction: false,
                savedCards: createSavedCards(),
            }),
        );

        expect(result.current.paymentMethod).toBe("saved_card");
    });

    it("derives savedCardId from the default saved card", () => {
        const { result } = renderHook(() =>
            useCheckout({
                cartId: 1,
                tenantId: 1,
                fincodePublicKey: "pk_test_123",
                isProduction: false,
                savedCards: createSavedCards(),
            }),
        );

        expect(result.current.savedCardId).toBe(11);
    });

    it("defaults to null when savedCards is empty", () => {
        const { result } = renderHook(() =>
            useCheckout({
                cartId: 1,
                tenantId: 1,
                fincodePublicKey: "pk_test_123",
                isProduction: false,
                savedCards: [],
            }),
        );

        expect(result.current.paymentMethod).toBeNull();
    });

    it("completes checkout with a saved card and navigates to complete page", async () => {
        apiPostMock
            .mockResolvedValueOnce({
                data: {
                    data: {
                        order: {
                            id: 101,
                            order_code: "ORD-101",
                            status: "pending_payment",
                            status_label: "支払い待ち",
                            total_amount: 1000,
                        },
                        payment: {
                            id: 501,
                            requires_redirect: false,
                            requires_token: false,
                        },
                    },
                },
                error: null,
            })
            .mockResolvedValueOnce({
                data: {
                    data: {
                        order: {
                            id: 101,
                        },
                    },
                },
                error: null,
            });

        const { result } = renderHook(() =>
            useCheckout({
                cartId: 5,
                tenantId: 1,
                fincodePublicKey: "pk_test_123",
                isProduction: false,
                savedCards: createSavedCards(),
            }),
        );

        await act(async () => {
            await result.current.handleCheckout();
        });

        expect(apiPostMock).toHaveBeenNthCalledWith(1, "/api/customer/checkout", {
            cart_id: 5,
            payment_method: "card",
            card_id: 11,
        });
        expect(apiPostMock).toHaveBeenNthCalledWith(2, "/api/customer/payments/finalize", {
            payment_id: 501,
        });
        expect(routerVisitMock).toHaveBeenCalledWith("/order.checkout.complete?order=101");
    });

    it("navigates to failed page when tokenization fails for a new card", async () => {
        createTokenMock.mockRejectedValueOnce(new Error("tokenize failed"));
        apiPostMock.mockResolvedValueOnce({
            data: {
                data: {
                    order: {
                        id: 77,
                        order_code: "ORD-077",
                        status: "pending_payment",
                        status_label: "支払い待ち",
                        total_amount: 1500,
                    },
                    payment: {
                        id: 700,
                        requires_redirect: false,
                        requires_token: true,
                    },
                },
            },
            error: null,
        });

        const { result } = renderHook(() =>
            useCheckout({
                cartId: 9,
                tenantId: 1,
                fincodePublicKey: "pk_test_123",
                isProduction: false,
                savedCards: [],
            }),
        );

        act(() => {
            result.current.setPaymentMethod("new_card");
        });

        await act(async () => {
            await result.current.handleCheckout();
        });

        expect(createTokenMock).toHaveBeenCalledTimes(1);
        expect(apiPostMock).toHaveBeenCalledTimes(1);
        expect(routerVisitMock).toHaveBeenCalledWith("/order.checkout.failed?order=77");
    });

    it("sets a normalized error when PayPay checkout response lacks redirect URL", async () => {
        apiPostMock.mockResolvedValueOnce({
            data: {
                data: {
                    order: {
                        id: 88,
                        order_code: "ORD-088",
                        status: "pending_payment",
                        status_label: "支払い待ち",
                        total_amount: 2200,
                    },
                    payment: {
                        id: 801,
                        requires_redirect: false,
                        requires_token: false,
                    },
                },
            },
            error: null,
        });

        const { result } = renderHook(() =>
            useCheckout({
                cartId: 12,
                tenantId: 1,
                fincodePublicKey: "pk_test_123",
                isProduction: false,
                savedCards: createSavedCards(),
            }),
        );

        act(() => {
            result.current.setPaymentMethod("paypay");
        });

        await act(async () => {
            await result.current.handleCheckout();
        });

        expect(result.current.error).toBe("PayPay決済のリダイレクトURLを取得できませんでした");
        expect(result.current.isProcessing).toBe(false);
        expect(routerVisitMock).not.toHaveBeenCalled();
    });

    it("disables checkout when paymentMethod is null (no saved cards, nothing selected)", () => {
        const { result } = renderHook(() =>
            useCheckout({
                cartId: 10,
                tenantId: 1,
                fincodePublicKey: "pk_test_123",
                isProduction: false,
                savedCards: [],
            }),
        );

        expect(result.current.paymentMethod).toBeNull();
        expect(result.current.savedCardId).toBeNull();
        expect(result.current.isCheckoutDisabled).toBe(true);
    });

    it("does not save a new card when saveCard is disabled", async () => {
        createTokenMock.mockResolvedValueOnce("tok_no_save");
        apiPostMock
            .mockResolvedValueOnce({
                data: {
                    data: {
                        order: {
                            id: 201,
                            order_code: "ORD-201",
                            status: "pending_payment",
                            status_label: "支払い待ち",
                            total_amount: 1800,
                        },
                        payment: {
                            id: 901,
                            requires_redirect: false,
                            requires_token: true,
                        },
                    },
                },
                error: null,
            })
            .mockResolvedValueOnce({
                data: {
                    data: {
                        order: {
                            id: 201,
                        },
                    },
                },
                error: null,
            });

        const { result } = renderHook(() =>
            useCheckout({
                cartId: 20,
                tenantId: 7,
                fincodePublicKey: "pk_test_123",
                isProduction: false,
                savedCards: [],
            }),
        );

        act(() => {
            result.current.setPaymentMethod("new_card");
        });

        await act(async () => {
            await result.current.handleCheckout();
        });

        expect(apiPostMock).toHaveBeenCalledTimes(2);
        expect(apiPostMock).toHaveBeenNthCalledWith(2, "/api/customer/payments/finalize", {
            payment_id: 901,
            token: "tok_no_save",
        });
        expect(routerVisitMock).toHaveBeenCalledWith("/order.checkout.complete?order=201");
    });

    it("saves a new card after successful payment when saveCard is enabled", async () => {
        createTokenMock.mockResolvedValueOnce("tok_save_false_default");
        apiPostMock
            .mockResolvedValueOnce({
                data: {
                    data: {
                        order: {
                            id: 301,
                            order_code: "ORD-301",
                            status: "pending_payment",
                            status_label: "支払い待ち",
                            total_amount: 2400,
                        },
                        payment: {
                            id: 1001,
                            requires_redirect: false,
                            requires_token: true,
                        },
                    },
                },
                error: null,
            })
            .mockResolvedValueOnce({
                data: {
                    data: {
                        order: {
                            id: 301,
                        },
                    },
                },
                error: null,
            });

        const { result } = renderHook(() =>
            useCheckout({
                cartId: 30,
                tenantId: 7,
                fincodePublicKey: "pk_test_123",
                isProduction: false,
                savedCards: [],
            }),
        );

        act(() => {
            result.current.setPaymentMethod("new_card");
            result.current.setSaveCard(true);
        });

        await act(async () => {
            await result.current.handleCheckout();
        });

        expect(apiPostMock).toHaveBeenCalledTimes(2);
        expect(apiPostMock).toHaveBeenNthCalledWith(2, "/api/customer/payments/finalize", {
            payment_id: 1001,
            token: "tok_save_false_default",
            save_card: true,
            save_as_default: false,
        });
        expect(routerVisitMock).toHaveBeenCalledWith("/order.checkout.complete?order=301");
    });

    it("passes is_default=true when saveAsDefault is enabled", async () => {
        createTokenMock.mockResolvedValueOnce("tok_save_true_default");
        apiPostMock
            .mockResolvedValueOnce({
                data: {
                    data: {
                        order: {
                            id: 401,
                            order_code: "ORD-401",
                            status: "pending_payment",
                            status_label: "支払い待ち",
                            total_amount: 2600,
                        },
                        payment: {
                            id: 1101,
                            requires_redirect: false,
                            requires_token: true,
                        },
                    },
                },
                error: null,
            })
            .mockResolvedValueOnce({
                data: {
                    data: {
                        order: {
                            id: 401,
                        },
                    },
                },
                error: null,
            });

        const { result } = renderHook(() =>
            useCheckout({
                cartId: 40,
                tenantId: 8,
                fincodePublicKey: "pk_test_123",
                isProduction: false,
                savedCards: [],
            }),
        );

        act(() => {
            result.current.setPaymentMethod("new_card");
            result.current.setSaveCard(true);
            result.current.setSaveAsDefault(true);
        });

        await act(async () => {
            await result.current.handleCheckout();
        });

        expect(apiPostMock).toHaveBeenCalledTimes(2);
        expect(apiPostMock).toHaveBeenNthCalledWith(2, "/api/customer/payments/finalize", {
            payment_id: 1101,
            token: "tok_save_true_default",
            save_card: true,
            save_as_default: true,
        });
        expect(routerVisitMock).toHaveBeenCalledWith("/order.checkout.complete?order=401");
    });

    it("does not update state after unmount when checkout fails", async () => {
        apiPostMock.mockRejectedValueOnce(new Error("network error"));

        const { result, unmount } = renderHook(() =>
            useCheckout({
                cartId: 99,
                tenantId: 1,
                fincodePublicKey: "pk_test_123",
                isProduction: false,
                savedCards: createSavedCards(),
            }),
        );

        // チェックアウトを開始するが、まだ await しない
        let checkoutPromise: Promise<void>;
        act(() => {
            checkoutPromise = result.current.handleCheckout();
        });

        // 非同期処理完了前にアンマウントする
        unmount();

        // Promise の解決を待つ
        await act(async () => {
            await checkoutPromise!;
        });

        // アンマウント後は error が null のままで、isProcessing は true のままになるはず
        // （isMountedRef ガードにより state 更新を防止）
        expect(result.current.error).toBeNull();
        expect(result.current.isProcessing).toBe(true);
    });

    it("includes save_card flag in finalize request when saveCard is enabled", async () => {
        createTokenMock.mockResolvedValueOnce("tok_save_fail");
        apiPostMock
            .mockResolvedValueOnce({
                data: {
                    data: {
                        order: {
                            id: 501,
                            order_code: "ORD-501",
                            status: "pending_payment",
                            status_label: "支払い待ち",
                            total_amount: 2000,
                        },
                        payment: {
                            id: 1201,
                            requires_redirect: false,
                            requires_token: true,
                        },
                    },
                },
                error: null,
            })
            .mockResolvedValueOnce({
                data: {
                    data: {
                        order: {
                            id: 501,
                        },
                    },
                },
                error: null,
            });

        const { result } = renderHook(() =>
            useCheckout({
                cartId: 50,
                tenantId: 9,
                fincodePublicKey: "pk_test_123",
                isProduction: false,
                savedCards: [],
            }),
        );

        act(() => {
            result.current.setPaymentMethod("new_card");
            result.current.setSaveCard(true);
        });

        await act(async () => {
            await result.current.handleCheckout();
        });

        expect(apiPostMock).toHaveBeenCalledTimes(2);
        expect(apiPostMock).toHaveBeenNthCalledWith(2, "/api/customer/payments/finalize", {
            payment_id: 1201,
            token: "tok_save_fail",
            save_card: true,
            save_as_default: false,
        });
        expect(routerVisitMock).toHaveBeenCalledWith("/order.checkout.complete?order=501");
    });

    it("keeps saveAsDefault false when saveCard is disabled", () => {
        const { result } = renderHook(() =>
            useCheckout({
                cartId: 60,
                tenantId: 10,
                fincodePublicKey: "pk_test_123",
                isProduction: false,
                savedCards: [],
            }),
        );

        act(() => {
            result.current.setPaymentMethod("new_card");
            result.current.setSaveAsDefault(true);
        });

        expect(result.current.saveCard).toBe(false);
        expect(result.current.saveAsDefault).toBe(false);
    });
});
