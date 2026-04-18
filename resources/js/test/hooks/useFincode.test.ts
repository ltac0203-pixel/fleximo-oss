import { act, renderHook, waitFor } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { useFincode } from "@/Hooks/useFincode";

const loggerMock = vi.hoisted(() => ({
    error: vi.fn(),
    warn: vi.fn(),
}));

vi.mock("@/Utils/logger", () => ({
    logger: loggerMock,
}));

const TEST_SCRIPT_URL = "https://js.test.fincode.jp/v1/fincode.js";

type MockFincodeUI = {
    create: ReturnType<typeof vi.fn>;
    mount: ReturnType<typeof vi.fn>;
    getFormData: ReturnType<typeof vi.fn>;
};

function createUIMock(): MockFincodeUI {
    return {
        create: vi.fn(),
        mount: vi.fn(),
        getFormData: vi.fn().mockResolvedValue({
            cardNo: "4111111111111111",
            expire: "3012",
            CVC: "123",
            holderName: "TARO TEST",
        }),
    };
}

function setupFincode(uiMocks: MockFincodeUI[]) {
    const fincodeInstance = {
        ui: vi.fn().mockImplementation(() => {
            const ui = uiMocks.shift();
            if (!ui) {
                throw new Error("ui mock exhausted");
            }
            return ui;
        }),
        tokens: vi.fn(),
    };

    const fincodeFactory = vi.fn().mockImplementation(() => fincodeInstance);
    (window as Window & { Fincode: unknown }).Fincode = fincodeFactory;

    const script = document.createElement("script");
    script.src = TEST_SCRIPT_URL;
    document.head.appendChild(script);

    return {
        fincodeFactory,
        fincodeInstance,
    };
}

describe("useFincode", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        document.head.innerHTML = "";
        document.body.innerHTML = "";
        (window as Window & { Fincode?: unknown }).Fincode = undefined;
    });

    it("initializes and mounts UI correctly", async () => {
        const ui = createUIMock();
        setupFincode([ui]);

        const { result } = renderHook(() =>
            useFincode({
                publicKey: "pk_test_123",
                isProduction: false,
            }),
        );

        await waitFor(() => {
            expect(result.current.isReady).toBe(true);
            expect(result.current.isLoading).toBe(false);
        });

        const container = document.createElement("div");
        container.id = "fincode-target";
        document.body.appendChild(container);

        act(() => {
            result.current.mountUI("fincode-target");
        });

        expect(ui.create).toHaveBeenCalledWith(
            "payments",
            expect.objectContaining({ layout: "vertical" }),
        );
        expect(ui.mount).toHaveBeenCalledWith("fincode-target", "100%");
        expect(result.current.error).toBeNull();
    });

    it("cleans up previous UI before remounting", async () => {
        const firstUI = createUIMock();
        const secondUI = createUIMock();

        setupFincode([firstUI, secondUI]);

        const { result } = renderHook(() =>
            useFincode({
                publicKey: "pk_test_123",
                isProduction: false,
            }),
        );

        await waitFor(() => {
            expect(result.current.isReady).toBe(true);
            expect(result.current.isLoading).toBe(false);
        });

        const container = document.createElement("div");
        container.id = "fincode-target";
        document.body.appendChild(container);

        act(() => {
            result.current.mountUI("fincode-target");
        });

        expect(firstUI.create).toHaveBeenCalledWith(
            "payments",
            expect.objectContaining({ layout: "vertical" }),
        );
        expect(firstUI.mount).toHaveBeenCalledWith("fincode-target", "100%");

        // 再マウント時にコンテナをクリーンアップして新しいUIを生成する
        act(() => {
            result.current.mountUI("fincode-target");
        });

        expect(secondUI.mount).toHaveBeenCalledWith("fincode-target", "100%");
        expect(result.current.error).toBeNull();
    });

    it("sets mount error when target container is not found", async () => {
        const ui = createUIMock();
        setupFincode([ui]);

        const { result } = renderHook(() =>
            useFincode({
                publicKey: "pk_test_123",
                isProduction: false,
            }),
        );

        await waitFor(() => {
            expect(result.current.isReady).toBe(true);
        });

        act(() => {
            result.current.mountUI("missing-target");
        });

        expect(result.current.error).toBe("カード入力フォームの表示に失敗しました");
        expect(ui.mount).not.toHaveBeenCalled();
    });

    it("cleans up UI on hook unmount", async () => {
        const ui = createUIMock();
        setupFincode([ui]);

        const { result, unmount } = renderHook(() =>
            useFincode({
                publicKey: "pk_test_123",
                isProduction: false,
            }),
        );

        await waitFor(() => {
            expect(result.current.isReady).toBe(true);
        });

        const container = document.createElement("div");
        container.id = "cleanup-target";
        container.innerHTML = "<p>placeholder</p>";
        document.body.appendChild(container);

        act(() => {
            result.current.mountUI("cleanup-target");
        });

        expect(ui.mount).toHaveBeenCalledWith("cleanup-target", "100%");

        act(() => {
            unmount();
        });

        // クリーンアップ時にコンテナの innerHTML は空になるはず
        expect(container.innerHTML).toBe("");
    });

    it("sets error when public key is missing", () => {
        const { result } = renderHook(() =>
            useFincode({
                publicKey: "",
                isProduction: false,
            }),
        );

        expect(result.current.error).toBe("fincode の公開キーが設定されていません");
        expect(result.current.isLoading).toBe(false);
    });
});
