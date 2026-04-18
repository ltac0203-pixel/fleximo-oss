import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { beforeEach, describe, expect, it, vi } from "vitest";
import PaymentMethodSelector from "@/Components/Customer/Checkout/PaymentMethodSelector";

describe("PaymentMethodSelector", () => {
    const mockOnChange = vi.fn();

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("常に3つのオプションが表示される", () => {
        render(<PaymentMethodSelector selected="new_card" onChange={mockOnChange} />);
        expect(screen.getByText("新しいカードで支払う")).toBeInTheDocument();
        expect(screen.getByText("保存済みカードで支払う")).toBeInTheDocument();
        expect(screen.getByText("PayPay")).toBeInTheDocument();
    });

    it("selected=null のときどのオプションも選択状態にならない", () => {
        render(<PaymentMethodSelector selected={null} onChange={mockOnChange} />);
        const radios = screen.getAllByRole("radio", { hidden: true });
        radios.forEach((radio) => {
            expect(radio).not.toBeChecked();
        });
    });

    it("selected='new_card' のとき「新しいカードで支払う」が選択状態になる", () => {
        render(<PaymentMethodSelector selected="new_card" onChange={mockOnChange} />);
        const radios = screen.getAllByRole("radio", { hidden: true });
        const newCardRadio = radios.find((r) => r.getAttribute("value") === "new_card");
        expect(newCardRadio).toBeChecked();
    });

    it("PayPay クリックで onChange('paypay') が呼ばれる", async () => {
        const user = userEvent.setup();
        render(<PaymentMethodSelector selected="new_card" onChange={mockOnChange} />);
        await user.click(screen.getByText("PayPay"));
        expect(mockOnChange).toHaveBeenCalledWith("paypay");
    });

    it("disabled=true のとき選択変更不可", async () => {
        const user = userEvent.setup();
        render(
            <PaymentMethodSelector selected="new_card" onChange={mockOnChange} disabled={true} />,
        );
        await user.click(screen.getByText("PayPay"));
        expect(mockOnChange).not.toHaveBeenCalled();
    });
});
