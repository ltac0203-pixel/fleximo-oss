import { fireEvent, render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { describe, expect, it, vi } from "vitest";
import ExportControls, { buildDashboardExportUrl } from "@/Components/Dashboard/ExportControls";

describe("ExportControls", () => {
    it("CSV出力ボタンと日付入力が表示される", () => {
        render(<ExportControls />);

        expect(screen.getByRole("heading", { name: "売上データエクスポート" })).toBeInTheDocument();
        expect(screen.getByLabelText("開始日")).toBeInTheDocument();
        expect(screen.getByLabelText("終了日")).toBeInTheDocument();
        expect(screen.getByRole("button", { name: "CSV出力" })).toBeInTheDocument();
    });

    it("開始日が終了日より後の場合はエラー表示される", () => {
        render(<ExportControls />);

        fireEvent.change(screen.getByLabelText("開始日"), { target: { value: "2026-02-10" } });
        fireEvent.change(screen.getByLabelText("終了日"), { target: { value: "2026-02-01" } });

        expect(screen.getByText("終了日は開始日以降を指定してください。")).toBeInTheDocument();
        expect(screen.getByRole("button", { name: "CSV出力" })).toBeDisabled();
    });

    it("期間が366日超の場合はエラー表示される", () => {
        render(<ExportControls />);

        fireEvent.change(screen.getByLabelText("開始日"), { target: { value: "2025-01-01" } });
        fireEvent.change(screen.getByLabelText("終了日"), { target: { value: "2026-01-03" } });

        expect(screen.getByText("期間は366日以内で指定してください。")).toBeInTheDocument();
        expect(screen.getByRole("button", { name: "CSV出力" })).toBeDisabled();
    });

    it("CSV出力で正しいURLが生成される", async () => {
        const user = userEvent.setup();
        const onDownload = vi.fn();
        render(<ExportControls onDownload={onDownload} />);

        fireEvent.change(screen.getByLabelText("開始日"), { target: { value: "2026-01-01" } });
        fireEvent.change(screen.getByLabelText("終了日"), { target: { value: "2026-01-31" } });

        await user.click(screen.getByRole("button", { name: "CSV出力" }));

        expect(onDownload).toHaveBeenCalledWith("/api/tenant/dashboard/export/csv?start_date=2026-01-01&end_date=2026-01-31");
    });
});

describe("buildDashboardExportUrl", () => {
    it("CSVのURLを返す", () => {
        const url = buildDashboardExportUrl("csv", "2026-01-01", "2026-01-31");
        expect(url).toBe("/api/tenant/dashboard/export/csv?start_date=2026-01-01&end_date=2026-01-31");
    });
});
