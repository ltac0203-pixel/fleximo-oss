import { act, render } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import PayPayCallback from "@/Pages/Customer/Checkout/PayPayCallback";
import { PayPayCallbackProps } from "@/types";

const apiPostMock = vi.hoisted(() => vi.fn());
const routerVisitMock = vi.hoisted(() => vi.fn());
const loggerErrorMock = vi.hoisted(() => vi.fn());
const loggerWarnMock = vi.hoisted(() => vi.fn());

vi.mock("@/api", () => ({
    api: {
        post: apiPostMock,
    },
}));

vi.mock("@inertiajs/react", () => ({
    Head: () => null,
    router: {
        visit: routerVisitMock,
    },
}));

vi.mock("@/Utils/logger", () => ({
    logger: {
        debug: vi.fn(),
        info: vi.fn(),
        warn: loggerWarnMock,
        error: loggerErrorMock,
        exception: vi.fn(),
    },
}));

function createProps(overrides?: Partial<PayPayCallbackProps>): PayPayCallbackProps {
    return {
        auth: {
            user: {
                id: 1,
                name: "テストユーザー",
                email: "user@example.com",
                role: "customer",
            },
        },
        flash: {
            success: null,
            error: null,
        },
        payment: {
            id: 10,
            status: "processing",
            order_id: 101,
        },
        success: true,
        ...overrides,
    };
}

describe("PayPayCallback", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.clearAllTimers();
        vi.useRealTimers();
    });

    const flushPromises = async () => {
        await Promise.resolve();
        await Promise.resolve();
    };

    it("polls while payment is pending and redirects to complete page when finalized", async () => {
        apiPostMock
            .mockResolvedValueOnce({
                data: {
                    data: {
                        payment_pending: true,
                        order_id: 101,
                    },
                },
                error: null,
                status: 200,
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
                status: 200,
            });

        render(<PayPayCallback {...createProps()} />);

        await act(async () => {
            await flushPromises();
        });

        expect(apiPostMock).toHaveBeenCalledTimes(1);
        expect(routerVisitMock).not.toHaveBeenCalled();

        await act(async () => {
            await vi.advanceTimersByTimeAsync(2000);
            await flushPromises();
        });

        expect(apiPostMock).toHaveBeenCalledTimes(2);
        expect(routerVisitMock).toHaveBeenCalledWith("/order.checkout.complete?order=101");
    });

    it("redirects to failed page when pending state exceeds timeout window", async () => {
        apiPostMock.mockResolvedValue({
            data: {
                data: {
                    payment_pending: true,
                    order_id: 101,
                },
            },
            error: null,
            status: 200,
        });

        render(<PayPayCallback {...createProps()} />);

        await act(async () => {
            await flushPromises();
        });

        expect(apiPostMock).toHaveBeenCalledTimes(1);

        await act(async () => {
            await vi.advanceTimersByTimeAsync(61000);
            await flushPromises();
        });

        expect(routerVisitMock).toHaveBeenCalledWith("/order.checkout.failed?order=101");
    });

    it("fails immediately on unauthorized finalize response", async () => {
        apiPostMock.mockResolvedValue({
            data: null,
            error: "Unauthorized",
            status: 401,
        });

        render(<PayPayCallback {...createProps()} />);

        await act(async () => {
            await flushPromises();
        });

        expect(apiPostMock).toHaveBeenCalledTimes(1);
        expect(routerVisitMock).toHaveBeenCalledWith("/order.checkout.failed?order=101");

        await act(async () => {
            await vi.advanceTimersByTimeAsync(10000);
            await flushPromises();
        });
        expect(apiPostMock).toHaveBeenCalledTimes(1);
    });

    it("fails immediately on validation error response", async () => {
        apiPostMock.mockResolvedValue({
            data: null,
            error: "PAYMENT_FAILED",
            status: 422,
        });

        render(<PayPayCallback {...createProps()} />);

        await act(async () => {
            await flushPromises();
        });

        expect(apiPostMock).toHaveBeenCalledTimes(1);
        expect(routerVisitMock).toHaveBeenCalledWith("/order.checkout.failed?order=101");

        await act(async () => {
            await vi.advanceTimersByTimeAsync(10000);
            await flushPromises();
        });
        expect(apiPostMock).toHaveBeenCalledTimes(1);
    });

    it("clears polling timer on unmount", async () => {
        apiPostMock.mockResolvedValue({
            data: {
                data: {
                    payment_pending: true,
                    order_id: 101,
                },
            },
            error: null,
            status: 200,
        });

        const { unmount } = render(<PayPayCallback {...createProps()} />);

        await act(async () => {
            await flushPromises();
        });

        expect(apiPostMock).toHaveBeenCalledTimes(1);
        unmount();

        await act(async () => {
            await vi.advanceTimersByTimeAsync(5000);
            await flushPromises();
        });
        expect(apiPostMock).toHaveBeenCalledTimes(1);
    });
});
