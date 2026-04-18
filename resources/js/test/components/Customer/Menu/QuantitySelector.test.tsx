import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { beforeEach, describe, expect, it, vi } from "vitest";
import QuantitySelector from "@/Components/Customer/Menu/QuantitySelector";

describe("QuantitySelector", () => {
    const mockOnChange = vi.fn();

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("初期値が表示される", () => {
        render(<QuantitySelector value={3} onChange={mockOnChange} />);
        expect(screen.getByText("3")).toBeInTheDocument();
    });

    it("+ボタンで onChange(value+1) が呼ばれる", async () => {
        const user = userEvent.setup();
        render(<QuantitySelector value={3} onChange={mockOnChange} />);
        await user.click(screen.getByLabelText("数量を増やす"));
        expect(mockOnChange).toHaveBeenCalledWith(4);
    });

    it("-ボタンで onChange(value-1) が呼ばれる", async () => {
        const user = userEvent.setup();
        render(<QuantitySelector value={3} onChange={mockOnChange} />);
        await user.click(screen.getByLabelText("数量を減らす"));
        expect(mockOnChange).toHaveBeenCalledWith(2);
    });

    it("value === min のとき -ボタンが disabled", () => {
        render(<QuantitySelector value={1} onChange={mockOnChange} min={1} />);
        expect(screen.getByLabelText("数量を減らす")).toBeDisabled();
    });

    it("value === max のとき +ボタンが disabled", () => {
        render(<QuantitySelector value={99} onChange={mockOnChange} max={99} />);
        expect(screen.getByLabelText("数量を増やす")).toBeDisabled();
    });

    it("min/max の境界値テスト", async () => {
        const user = userEvent.setup();
        render(<QuantitySelector value={5} onChange={mockOnChange} min={5} max={5} />);
        expect(screen.getByLabelText("数量を減らす")).toBeDisabled();
        expect(screen.getByLabelText("数量を増やす")).toBeDisabled();

        await user.click(screen.getByLabelText("数量を減らす"));
        await user.click(screen.getByLabelText("数量を増やす"));
        expect(mockOnChange).not.toHaveBeenCalled();
    });
});
