import { renderHook, act, waitFor } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { useApiFormSubmission } from "@/Hooks/useApiFormSubmission";

const loggerMock = vi.hoisted(() => ({
    error: vi.fn(),
}));

vi.mock("@/Utils/logger", () => ({
    logger: loggerMock,
}));

interface TestFormErrors {
    name?: string;
}

function createDeferred<T>() {
    let resolve!: (value: T) => void;
    const promise = new Promise<T>((res) => {
        resolve = res;
    });
    return { promise, resolve };
}

describe("useApiFormSubmission", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("422エラー時はerrorsに反映される", async () => {
        const { result } = renderHook(() => useApiFormSubmission<TestFormErrors>());
        const request = vi.fn().mockResolvedValue({
            data: null,
            errorData: { errors: { name: "名前は必須です" } },
            error: "バリデーションエラー",
            status: 422,
        });

        await act(async () => {
            await result.current.submit(request, { logMessage: "submit failed" });
        });

        expect(result.current.errors).toEqual({ name: "名前は必須です" });
        expect(result.current.generalError).toBe("");
        expect(result.current.processing).toBe(false);
    });

    it("422以外のAPIエラー時はgeneralErrorに反映される", async () => {
        const { result } = renderHook(() => useApiFormSubmission<TestFormErrors>());
        const request = vi.fn().mockResolvedValue({
            data: null,
            errorData: null,
            error: "保存に失敗しました",
            status: 500,
        });

        await act(async () => {
            await result.current.submit(request, { logMessage: "submit failed" });
        });

        expect(result.current.errors).toEqual({});
        expect(result.current.generalError).toBe("保存に失敗しました");
        expect(result.current.processing).toBe(false);
    });

    it("例外発生時はlogger.errorと通信エラーメッセージが設定される", async () => {
        const { result } = renderHook(() => useApiFormSubmission<TestFormErrors>());
        const networkError = new Error("network down");
        const request = vi.fn().mockRejectedValue(networkError);

        await act(async () => {
            await result.current.submit(request, {
                logMessage: "submit failed",
                logContext: { scope: "test" },
            });
        });

        expect(loggerMock.error).toHaveBeenCalledWith("submit failed", networkError, { scope: "test" });
        expect(result.current.generalError).toBe("通信エラーが発生しました。もう一度お試しください。");
        expect(result.current.processing).toBe(false);
    });

    it("送信中はprocessing=trueになり完了後はfalseに戻る", async () => {
        const { result } = renderHook(() => useApiFormSubmission<TestFormErrors>());
        const deferred = createDeferred({
            data: { ok: true },
            errorData: null,
            error: null,
            status: 200,
        });
        const request = vi.fn().mockReturnValue(deferred.promise);

        act(() => {
            void result.current.submit(request, { logMessage: "submit failed" });
        });

        expect(result.current.processing).toBe(true);

        await act(async () => {
            deferred.resolve({
                data: { ok: true },
                errorData: null,
                error: null,
                status: 200,
            });
        });

        await waitFor(() => {
            expect(result.current.processing).toBe(false);
        });
    });
});
