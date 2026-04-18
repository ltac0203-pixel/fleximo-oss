import { render, screen } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import PriceCalculator from "@/Components/Customer/Menu/PriceCalculator";
import { CustomerMenuOption } from "@/types";

const mockOptions: CustomerMenuOption[] = [
    { id: 1, name: "チーズ追加", price: 100 },
    { id: 2, name: "大盛り", price: 200 },
];

describe("PriceCalculator", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("オプションなし: basePrice * quantity = total", () => {
        render(<PriceCalculator basePrice={500} selectedOptions={[]} quantity={2} />);
        expect(screen.getByText("￥1,000")).toBeInTheDocument();
    });

    it("オプションあり: (basePrice + optionTotal) * quantity = total", () => {
        render(<PriceCalculator basePrice={500} selectedOptions={mockOptions} quantity={2} />);
        // (500 + 100 + 200) * 2 = 1600
        expect(screen.getByText("￥1,600")).toBeInTheDocument();
    });

    it("オプションがあるとき基本価格と各オプション名・価格が表示される", () => {
        render(<PriceCalculator basePrice={500} selectedOptions={mockOptions} quantity={1} />);
        expect(screen.getByText("基本価格")).toBeInTheDocument();
        expect(screen.getByText("チーズ追加")).toBeInTheDocument();
        expect(screen.getByText("+￥100")).toBeInTheDocument();
        expect(screen.getByText("大盛り")).toBeInTheDocument();
        expect(screen.getByText("+￥200")).toBeInTheDocument();
    });

    it("オプションがあるとき単価が表示される", () => {
        render(<PriceCalculator basePrice={500} selectedOptions={mockOptions} quantity={1} />);
        expect(screen.getByText("単価")).toBeInTheDocument();
        // 単価と合計が同額(quantity=1)なので getAllByText を使用
        expect(screen.getAllByText("￥800").length).toBeGreaterThanOrEqual(1);
    });

    it("quantity > 1 のとき単価 * 数量の表記になる", () => {
        render(<PriceCalculator basePrice={500} selectedOptions={mockOptions} quantity={3} />);
        expect(screen.getByText("￥800 × 3")).toBeInTheDocument();
    });

    it("quantity === 1 のとき合計表記になる", () => {
        render(<PriceCalculator basePrice={500} selectedOptions={[]} quantity={1} />);
        expect(screen.getByText("合計")).toBeInTheDocument();
    });
});
