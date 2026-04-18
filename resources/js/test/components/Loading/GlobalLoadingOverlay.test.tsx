import { act, fireEvent, render, screen } from "@testing-library/react";
import { beforeEach, afterEach, describe, expect, it, vi } from "vitest";
import GlobalLoadingOverlay from "@/Components/Loading/GlobalLoadingOverlay";
import { loadingStore } from "@/Utils/loadingStore";

const routerPostMock = vi.hoisted(() => vi.fn());
const routerVisitMock = vi.hoisted(() => vi.fn());

vi.mock("@inertiajs/react", () => ({
    router: {
        post: routerPostMock,
        visit: routerVisitMock,
    },
}));

function setHistoryRole(role: string | null): void {
    window.history.replaceState(
        {
            page: {
                props: {
                    auth: {
                        user: role ? { role } : null,
                    },
                },
            },
        },
        "",
        "/tenant/dashboard",
    );
}

function resetLoadingStore(): void {
    while (loadingStore.getCount() > 0) {
        loadingStore.decrement();
    }
}

function showTimedOutOverlay(): void {
    act(() => {
        loadingStore.increment();
    });

    act(() => {
        vi.advanceTimersByTime(150);
    });

    act(() => {
        vi.advanceTimersByTime(15_000);
    });
}

describe("GlobalLoadingOverlay", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.useFakeTimers();
        resetLoadingStore();
        setHistoryRole(null);
    });

    afterEach(() => {
        vi.clearAllTimers();
        vi.useRealTimers();
    });

    it("posts logout when timeout home button is clicked by tenant user", () => {
        setHistoryRole("tenant_admin");
        render(<GlobalLoadingOverlay />);

        showTimedOutOverlay();

        fireEvent.click(screen.getByRole("button", { name: "ホームに戻る" }));

        expect(routerPostMock).toHaveBeenCalledWith("/logout");
        expect(routerVisitMock).not.toHaveBeenCalled();
    });

    it("visits root when timeout home button is clicked by non-tenant user", () => {
        setHistoryRole("customer");
        render(<GlobalLoadingOverlay />);

        showTimedOutOverlay();

        fireEvent.click(screen.getByRole("button", { name: "ホームに戻る" }));

        expect(routerVisitMock).toHaveBeenCalledWith("/");
        expect(routerPostMock).not.toHaveBeenCalled();
    });

    it("does not trigger home navigation when retry button is clicked after timeout", () => {
        const consoleErrorSpy = vi.spyOn(console, "error").mockImplementation(() => {});
        render(<GlobalLoadingOverlay />);

        showTimedOutOverlay();

        fireEvent.click(screen.getByRole("button", { name: "再試行" }));

        expect(routerPostMock).not.toHaveBeenCalled();
        expect(routerVisitMock).not.toHaveBeenCalled();
        consoleErrorSpy.mockRestore();
    });
});
