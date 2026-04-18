import { render } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import CardPaymentForm from "@/Components/Customer/Checkout/CardPaymentForm";

describe("CardPaymentForm", () => {
    it("mounts fincode UI only when form is displayable", () => {
        const onMount = vi.fn();
        const onUnmount = vi.fn();

        const { rerender } = render(
            <CardPaymentForm
                isReady={true}
                isLoading={true}
                error={null}
                onMount={onMount}
                onUnmount={onUnmount}
            />,
        );

        expect(onMount).not.toHaveBeenCalled();

        rerender(
            <CardPaymentForm
                isReady={true}
                isLoading={false}
                error={null}
                onMount={onMount}
                onUnmount={onUnmount}
            />,
        );

        expect(onMount).toHaveBeenCalledTimes(1);
        expect(onMount).toHaveBeenCalledWith("fincode-checkout");

        rerender(
            <CardPaymentForm
                isReady={true}
                isLoading={false}
                error={null}
                onMount={onMount}
                onUnmount={onUnmount}
            />,
        );

        expect(onMount).toHaveBeenCalledTimes(1);
    });

    it("calls onUnmount on component unmount", () => {
        const onMount = vi.fn();
        const onUnmount = vi.fn();

        const { unmount } = render(
            <CardPaymentForm
                isReady={true}
                isLoading={false}
                error={null}
                onMount={onMount}
                onUnmount={onUnmount}
            />,
        );

        expect(onMount).toHaveBeenCalledTimes(1);

        unmount();

        expect(onUnmount).toHaveBeenCalledTimes(1);
    });
});
