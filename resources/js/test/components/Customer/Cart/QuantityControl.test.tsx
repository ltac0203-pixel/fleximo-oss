import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { beforeEach, describe, expect, it, vi } from "vitest";
import QuantityControl from "@/Components/Customer/Cart/QuantityControl";

describe("QuantityControl", () => {
    const mockOnIncrease = vi.fn();
    const mockOnDecrease = vi.fn();
    const mockOnRemove = vi.fn();

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("数量が表示される", () => {
        render(<QuantityControl quantity={3} onIncrease={mockOnIncrease} onDecrease={mockOnDecrease} />);
        expect(screen.getByText("3")).toBeInTheDocument();
    });

    it("+ボタンクリックで onIncrease が呼ばれる", async () => {
        const user = userEvent.setup();
        render(<QuantityControl quantity={3} onIncrease={mockOnIncrease} onDecrease={mockOnDecrease} />);
        await user.click(screen.getByRole("button", { name: "増やす" }));
        expect(mockOnIncrease).toHaveBeenCalledTimes(1);
    });

    it("-ボタンクリック（quantity > min）で onDecrease が呼ばれる", async () => {
        const user = userEvent.setup();
        render(<QuantityControl quantity={3} onIncrease={mockOnIncrease} onDecrease={mockOnDecrease} />);
        await user.click(screen.getByRole("button", { name: "減らす" }));
        expect(mockOnDecrease).toHaveBeenCalledTimes(1);
    });

    it("quantity === min かつ onRemove ありのとき、-ボタンで onRemove が呼ばれる", async () => {
        const user = userEvent.setup();
        render(
            <QuantityControl
                quantity={1}
                onIncrease={mockOnIncrease}
                onDecrease={mockOnDecrease}
                onRemove={mockOnRemove}
                min={1}
            />,
        );
        await user.click(screen.getByRole("button", { name: "削除" }));
        expect(mockOnRemove).toHaveBeenCalledTimes(1);
        expect(mockOnDecrease).not.toHaveBeenCalled();
    });

    it("quantity === min かつ onRemove ありのとき、-ボタンの aria-label が「削除」", () => {
        render(
            <QuantityControl
                quantity={1}
                onIncrease={mockOnIncrease}
                onDecrease={mockOnDecrease}
                onRemove={mockOnRemove}
                min={1}
            />,
        );
        expect(screen.getByRole("button", { name: "削除" })).toBeInTheDocument();
    });

    it("quantity >= max のとき +ボタンが disabled", () => {
        render(<QuantityControl quantity={5} onIncrease={mockOnIncrease} onDecrease={mockOnDecrease} max={5} />);
        expect(screen.getByRole("button", { name: "増やす" })).toBeDisabled();
    });

    it("disabled=true のとき両ボタンが disabled", () => {
        render(
            <QuantityControl quantity={3} onIncrease={mockOnIncrease} onDecrease={mockOnDecrease} disabled={true} />,
        );
        expect(screen.getByRole("button", { name: "増やす" })).toBeDisabled();
        expect(screen.getByRole("button", { name: "減らす" })).toBeDisabled();
    });
});
