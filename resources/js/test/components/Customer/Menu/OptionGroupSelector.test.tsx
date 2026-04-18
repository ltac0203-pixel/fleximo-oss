import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { beforeEach, describe, expect, it, vi } from "vitest";
import OptionGroupSelector from "@/Components/Customer/Menu/OptionGroupSelector";
import { CustomerMenuOptionGroup } from "@/types";

const singleSelectGroup: CustomerMenuOptionGroup = {
    id: 1,
    name: "サイズ",
    required: true,
    min_select: 1,
    max_select: 1,
    options: [
        { id: 10, name: "レギュラー", price: 0 },
        { id: 11, name: "ラージ", price: 100 },
    ],
};

const multiSelectGroup: CustomerMenuOptionGroup = {
    id: 2,
    name: "トッピング",
    required: false,
    min_select: 0,
    max_select: 3,
    options: [
        { id: 20, name: "チーズ", price: 50 },
        { id: 21, name: "卵", price: 30 },
        { id: 22, name: "ベーコン", price: 80 },
        { id: 23, name: "アボカド", price: 100 },
    ],
};

describe("OptionGroupSelector", () => {
    const mockOnChange = vi.fn();

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("グループ名が表示される", () => {
        render(<OptionGroupSelector group={singleSelectGroup} selectedOptions={[]} onChange={mockOnChange} />);
        expect(screen.getByText("サイズ")).toBeInTheDocument();
    });

    it("required=true のとき「必須」を含むラベルが表示される", () => {
        render(<OptionGroupSelector group={singleSelectGroup} selectedOptions={[]} onChange={mockOnChange} />);
        expect(screen.getByText(/必須/)).toBeInTheDocument();
    });

    it("required=false のとき「任意」を含むラベルが表示される", () => {
        render(<OptionGroupSelector group={multiSelectGroup} selectedOptions={[]} onChange={mockOnChange} />);
        expect(screen.getByText(/任意/)).toBeInTheDocument();
    });

    it("min_select === max_select のとき「必須・N個選択」と表示される", () => {
        render(<OptionGroupSelector group={singleSelectGroup} selectedOptions={[]} onChange={mockOnChange} />);
        expect(screen.getByText("必須・1個選択")).toBeInTheDocument();
    });

    it("min_select !== max_select のとき「必須・N〜M個選択」と表示される", () => {
        const group: CustomerMenuOptionGroup = {
            ...multiSelectGroup,
            required: true,
            min_select: 1,
            max_select: 3,
        };
        render(<OptionGroupSelector group={group} selectedOptions={[]} onChange={mockOnChange} />);
        expect(screen.getByText("必須・1〜3個選択")).toBeInTheDocument();
    });

    it("max_select === 1 のとき radio input が表示される", () => {
        render(<OptionGroupSelector group={singleSelectGroup} selectedOptions={[]} onChange={mockOnChange} />);
        const radios = screen.getAllByRole("radio");
        expect(radios).toHaveLength(2);
    });

    it("max_select > 1 のとき checkbox input が表示される", () => {
        render(<OptionGroupSelector group={multiSelectGroup} selectedOptions={[]} onChange={mockOnChange} />);
        const checkboxes = screen.getAllByRole("checkbox");
        expect(checkboxes).toHaveLength(4);
    });

    it("単一選択: クリックで onChange([optionId]) が呼ばれる", async () => {
        const user = userEvent.setup();
        render(<OptionGroupSelector group={singleSelectGroup} selectedOptions={[]} onChange={mockOnChange} />);
        await user.click(screen.getByText("ラージ"));
        expect(mockOnChange).toHaveBeenCalledWith([11]);
    });

    it("複数選択: チェックで onChange([...selectedOptions, optionId]) が呼ばれる", async () => {
        const user = userEvent.setup();
        render(<OptionGroupSelector group={multiSelectGroup} selectedOptions={[20]} onChange={mockOnChange} />);
        await user.click(screen.getByText("卵"));
        expect(mockOnChange).toHaveBeenCalledWith([20, 21]);
    });

    it("複数選択: max_select 到達で未選択のチェックボックスが disabled になる", () => {
        render(<OptionGroupSelector group={multiSelectGroup} selectedOptions={[20, 21, 22]} onChange={mockOnChange} />);
        const checkboxes = screen.getAllByRole("checkbox");
        const avocadoCheckbox = checkboxes.find(
            (cb) => !cb.hasAttribute("checked") && cb.closest("label")?.textContent?.includes("アボカド"),
        );
        expect(avocadoCheckbox).toBeDisabled();
    });
});
