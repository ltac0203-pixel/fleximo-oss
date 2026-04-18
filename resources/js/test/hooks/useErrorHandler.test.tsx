import { renderHook } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { useErrorHandler } from "@/Hooks/useErrorHandler";
import type { ApiResponse } from "@/api";

const loggerMock = vi.hoisted(() => ({
    error: vi.fn(),
}));

const routerMock = vi.hoisted(() => ({
    visit: vi.fn(),
}));

vi.mock("@/Utils/logger", () => ({
    logger: loggerMock,
}));

vi.mock("@inertiajs/react", () => ({
    router: routerMock,
}));

function ok<T>(data: T): ApiResponse<T> {
    return { data, errorData: null, error: null, status: 200 };
}

function fail<E = unknown>(status: number, error: string, errorData: E | null = null): ApiResponse<unknown, E> {
    return { data: null, errorData, error, status };
}

describe("useErrorHandler", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("成功レスポンスでは何もしない", () => {
        const showToast = vi.fn();
        const { result } = renderHook(() => useErrorHandler({ showToast }));

        const ret = result.current.handleApiError(ok({ id: 1 }), "test");

        expect(ret).toBeUndefined();
        expect(loggerMock.error).not.toHaveBeenCalled();
        expect(routerMock.visit).not.toHaveBeenCalled();
        expect(showToast).not.toHaveBeenCalled();
    });

    it("401で /login にリダイレクトする", () => {
        const { result } = renderHook(() => useErrorHandler());

        result.current.handleApiError(fail(401, "Unauthenticated"), "test");

        expect(routerMock.visit).toHaveBeenCalledWith("/login");
        expect(loggerMock.error).toHaveBeenCalledWith("test", "Unauthenticated", { status: 401 });
    });

    it("401 + loginRedirectPath: false でリダイレクトしない", () => {
        const { result } = renderHook(() => useErrorHandler());

        result.current.handleApiError(fail(401, "Unauthenticated"), "test", {
            loginRedirectPath: false,
        });

        expect(routerMock.visit).not.toHaveBeenCalled();
    });

    it("401 + カスタムリダイレクトパス", () => {
        const { result } = renderHook(() => useErrorHandler());

        result.current.handleApiError(fail(401, "Unauthenticated"), "test", {
            loginRedirectPath: "/tenant/login",
        });

        expect(routerMock.visit).toHaveBeenCalledWith("/tenant/login");
    });

    it("403でエラートーストを表示する", () => {
        const showToast = vi.fn();
        const { result } = renderHook(() => useErrorHandler({ showToast }));

        result.current.handleApiError(fail(403, "Forbidden"), "test");

        expect(showToast).toHaveBeenCalledWith({
            type: "error",
            message: "アクセス権限がありません",
        });
        expect(loggerMock.error).toHaveBeenCalledWith("test", "Forbidden", { status: 403 });
    });

    it("403 + カスタムメッセージ", () => {
        const showToast = vi.fn();
        const { result } = renderHook(() => useErrorHandler({ showToast }));

        result.current.handleApiError(fail(403, "Forbidden"), "test", {
            forbiddenMessage: "この操作はできません",
        });

        expect(showToast).toHaveBeenCalledWith({
            type: "error",
            message: "この操作はできません",
        });
    });

    it("403 + showToast 未指定ではトーストをスキップ", () => {
        const { result } = renderHook(() => useErrorHandler());

        result.current.handleApiError(fail(403, "Forbidden"), "test");

        expect(loggerMock.error).toHaveBeenCalled();
    });

    it("403 + forbiddenMessage: false ではトーストをスキップ", () => {
        const showToast = vi.fn();
        const { result } = renderHook(() => useErrorHandler({ showToast }));

        result.current.handleApiError(fail(403, "Forbidden"), "test", {
            forbiddenMessage: false,
        });

        expect(showToast).not.toHaveBeenCalled();
    });

    it("419でトースト表示 + ページリロードが実行される", () => {
        const showToast = vi.fn();
        const reloadMock = vi.fn();
        Object.defineProperty(window, "location", {
            value: { ...window.location, reload: reloadMock },
            writable: true,
        });
        const { result } = renderHook(() => useErrorHandler({ showToast }));

        result.current.handleApiError(fail(419, "CSRF token mismatch"), "test");

        expect(showToast).toHaveBeenCalledWith({
            type: "error",
            message: "セッションの有効期限が切れました。ページを再読み込みします。",
        });
        expect(reloadMock).toHaveBeenCalled();
        expect(loggerMock.error).toHaveBeenCalledWith("test", "CSRF token mismatch", { status: 419 });
    });

    it("419 + showToast 未指定でもリロードは実行される", () => {
        const reloadMock = vi.fn();
        Object.defineProperty(window, "location", {
            value: { ...window.location, reload: reloadMock },
            writable: true,
        });
        const { result } = renderHook(() => useErrorHandler());

        result.current.handleApiError(fail(419, "CSRF token mismatch"), "test");

        expect(reloadMock).toHaveBeenCalled();
    });

    it("422で errorData を返す", () => {
        const showToast = vi.fn();
        const errorData = { errors: { name: "必須です" } };
        const { result } = renderHook(() => useErrorHandler({ showToast }));

        const ret = result.current.handleApiError(fail(422, "Validation Error", errorData), "test");

        expect(ret).toEqual(errorData);
        expect(showToast).not.toHaveBeenCalled();
    });

    it("422 + errorData が null なら undefined を返す", () => {
        const { result } = renderHook(() => useErrorHandler());

        const ret = result.current.handleApiError(fail(422, "Validation Error", null), "test");

        expect(ret).toBeUndefined();
    });

    it("500+ でサーバーエラートーストを表示する", () => {
        const showToast = vi.fn();
        const { result } = renderHook(() => useErrorHandler({ showToast }));

        result.current.handleApiError(fail(500, "Internal Server Error"), "test");

        expect(showToast).toHaveBeenCalledWith({
            type: "error",
            message: "サーバーエラーが発生しました",
        });
    });

    it("500+ + カスタムメッセージ", () => {
        const showToast = vi.fn();
        const { result } = renderHook(() => useErrorHandler({ showToast }));

        result.current.handleApiError(fail(502, "Bad Gateway"), "test", {
            serverErrorMessage: "サーバーが応答しません",
        });

        expect(showToast).toHaveBeenCalledWith({
            type: "error",
            message: "サーバーが応答しません",
        });
    });

    it("500+ + serverErrorMessage: false ではトーストをスキップ", () => {
        const showToast = vi.fn();
        const { result } = renderHook(() => useErrorHandler({ showToast }));

        result.current.handleApiError(fail(500, "Internal Server Error"), "test", {
            serverErrorMessage: false,
        });

        expect(showToast).not.toHaveBeenCalled();
    });

    it("その他ステータス（408等）では error メッセージでトースト表示", () => {
        const showToast = vi.fn();
        const { result } = renderHook(() => useErrorHandler({ showToast }));

        result.current.handleApiError(fail(408, "Request Timeout"), "test");

        expect(showToast).toHaveBeenCalledWith({
            type: "error",
            message: "Request Timeout",
        });
    });

    it("全エラーで logger.error が呼ばれる", () => {
        const { result } = renderHook(() => useErrorHandler());

        for (const status of [401, 403, 422, 500, 408]) {
            loggerMock.error.mockClear();
            result.current.handleApiError(fail(status, `Error ${status}`), `ctx-${status}`);
            expect(loggerMock.error).toHaveBeenCalledWith(`ctx-${status}`, `Error ${status}`, { status });
        }
    });
});
