import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import ProcessingStatus from "@/Components/Loading/ProcessingStatus";

describe("ProcessingStatus", () => {
    it("shows processing state with spinner and title", () => {
        render(
            <ProcessingStatus
                status="processing"
                processingTitle="決済を確認しています"
            />,
        );

        expect(screen.getByRole("heading", { name: "決済を確認しています" })).toBeInTheDocument();
        expect(screen.getByText("しばらくお待ちください...")).toBeInTheDocument();
        expect(screen.getByRole("status")).toBeInTheDocument();
    });

    it("shows custom processing message", () => {
        render(
            <ProcessingStatus
                status="processing"
                processingTitle="認証中"
                processingMessage="少々お待ちください"
            />,
        );

        expect(screen.getByRole("heading", { name: "認証中" })).toBeInTheDocument();
        expect(screen.getByText("少々お待ちください")).toBeInTheDocument();
    });

    it("shows success state with check icon", () => {
        render(
            <ProcessingStatus
                status="success"
                processingTitle="処理中"
            />,
        );

        expect(screen.getByText("決済が完了しました")).toBeInTheDocument();
        expect(screen.getByText("注文完了画面へ移動します...")).toBeInTheDocument();
        expect(screen.queryByRole("status")).not.toBeInTheDocument();
    });

    it("shows custom success messages", () => {
        render(
            <ProcessingStatus
                status="success"
                processingTitle="処理中"
                successTitle="完了！"
                successMessage="リダイレクトします"
            />,
        );

        expect(screen.getByText("完了！")).toBeInTheDocument();
        expect(screen.getByText("リダイレクトします")).toBeInTheDocument();
    });

    it("shows failed state with error message", () => {
        render(
            <ProcessingStatus
                status="failed"
                processingTitle="処理中"
                error="決済がキャンセルされました"
            />,
        );

        expect(screen.getByText("決済に失敗しました")).toBeInTheDocument();
        expect(screen.getByText("決済がキャンセルされました")).toBeInTheDocument();
        expect(screen.getByText("失敗画面へ移動します...")).toBeInTheDocument();
    });

    it("shows custom failed messages", () => {
        render(
            <ProcessingStatus
                status="failed"
                processingTitle="処理中"
                failedTitle="3DS認証に失敗しました"
                failedMessage="チェックアウトに戻ります..."
                error="認証エラー"
            />,
        );

        expect(screen.getByText("3DS認証に失敗しました")).toBeInTheDocument();
        expect(screen.getByText("認証エラー")).toBeInTheDocument();
        expect(screen.getByText("チェックアウトに戻ります...")).toBeInTheDocument();
    });

    it("does not show error text when error is null in failed state", () => {
        render(
            <ProcessingStatus
                status="failed"
                processingTitle="処理中"
                error={null}
            />,
        );

        expect(screen.getByText("決済に失敗しました")).toBeInTheDocument();
        expect(screen.getByText("失敗画面へ移動します...")).toBeInTheDocument();
    });
});
