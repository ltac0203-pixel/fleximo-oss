import { act, render, screen } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import NavigationProgressBar from "@/Components/Loading/NavigationProgressBar";

type EventCallback = () => void;

const listeners: Record<string, EventCallback> = {};

vi.mock("@inertiajs/react", () => ({
    router: {
        on: (event: string, callback: EventCallback) => {
            listeners[event] = callback;
            return () => {
                delete listeners[event];
            };
        },
    },
}));

function simulateStart() {
    act(() => {
        listeners.start?.();
    });
}

function simulateFinish() {
    act(() => {
        listeners.finish?.();
    });
}

describe("NavigationProgressBar", () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.clearAllTimers();
        vi.useRealTimers();
    });

    it("does not show immediately on start (150ms delay)", () => {
        render(<NavigationProgressBar />);

        simulateStart();
        expect(screen.queryByRole("progressbar")).not.toBeInTheDocument();

        act(() => {
            vi.advanceTimersByTime(100);
        });
        expect(screen.queryByRole("progressbar")).not.toBeInTheDocument();
    });

    it("shows progress bar after 150ms delay", () => {
        render(<NavigationProgressBar />);

        simulateStart();

        act(() => {
            vi.advanceTimersByTime(150);
        });

        const bar = screen.getByRole("progressbar");
        expect(bar).toBeInTheDocument();
        expect(bar).toHaveAttribute("aria-label", "ページ遷移中");
        expect(bar).toHaveAttribute("aria-valuemin", "0");
        expect(bar).toHaveAttribute("aria-valuemax", "100");
    });

    it("does not show if navigation completes within 150ms", () => {
        render(<NavigationProgressBar />);

        simulateStart();

        act(() => {
            vi.advanceTimersByTime(100);
        });

        simulateFinish();

        act(() => {
            vi.advanceTimersByTime(200);
        });

        expect(screen.queryByRole("progressbar")).not.toBeInTheDocument();
    });

    it("has correct z-index and positioning", () => {
        render(<NavigationProgressBar />);

        simulateStart();
        act(() => {
            vi.advanceTimersByTime(150);
        });

        const bar = screen.getByRole("progressbar");
        expect(bar).toHaveClass("fixed", "top-0", "left-0", "right-0", "z-[60]");
    });

    it("shows growing animation during navigation", () => {
        render(<NavigationProgressBar />);

        simulateStart();
        act(() => {
            vi.advanceTimersByTime(150);
        });

        const inner = screen.getByRole("progressbar").firstElementChild;
        expect(inner).toHaveClass("animate-nav-progress-grow");
    });

    it("switches to complete animation before hiding after finish", () => {
        render(<NavigationProgressBar />);

        simulateStart();
        act(() => {
            vi.advanceTimersByTime(150);
        });

        simulateFinish();

        const inner = screen.getByRole("progressbar").firstElementChild;
        expect(inner).toHaveClass("animate-nav-progress-complete");

        act(() => {
            vi.advanceTimersByTime(300);
        });

        expect(screen.queryByRole("progressbar")).not.toBeInTheDocument();
    });
});
