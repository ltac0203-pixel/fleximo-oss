import { fireEvent, render, screen } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import Error403 from "@/Pages/Errors/Error403";
import { ErrorPageProps } from "@/types";

const routerPostMock = vi.hoisted(() => vi.fn());
const routerVisitMock = vi.hoisted(() => vi.fn());

vi.mock("@inertiajs/react", () => ({
    Head: () => null,
    router: {
        post: routerPostMock,
        visit: routerVisitMock,
    },
}));

function createProps(role?: ErrorPageProps["auth"]["user"]["role"]): ErrorPageProps {
    if (!role) {
        return {
            status: 403,
            auth: { user: undefined as never },
            flash: {},
        };
    }
    return {
        status: 403,
        auth: {
            user: {
                id: 1,
                name: "テストユーザー",
                email: "user@example.com",
                role,
            },
        },
        flash: {},
    };
}

describe("Error403", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it.each(["tenant_admin", "tenant_staff"] as const)(
        "shows logout button and posts logout when clicked by %s",
        (role) => {
            render(<Error403 {...createProps(role)} />);

            const logoutButton = screen.getByRole("button", { name: "ログアウト" });
            fireEvent.click(logoutButton);

            expect(routerPostMock).toHaveBeenCalledWith("/logout");
            expect(routerVisitMock).not.toHaveBeenCalled();
        },
    );

    it.each(["tenant_admin", "tenant_staff"] as const)(
        "does not show home button for %s",
        (role) => {
            render(<Error403 {...createProps(role)} />);

            expect(screen.queryByRole("button", { name: "ホームに戻る" })).toBeNull();
        },
    );

    it("shows home and logout buttons for non-tenant authenticated user", () => {
        render(<Error403 {...createProps("customer")} />);

        expect(screen.getByRole("button", { name: "ホームに戻る" })).toBeDefined();
        expect(screen.getByRole("button", { name: "ログアウト" })).toBeDefined();
        expect(screen.queryByRole("button", { name: "前のページへ" })).toBeNull();
    });

    it("visits root when home button is clicked by non-tenant authenticated user", () => {
        render(<Error403 {...createProps("customer")} />);

        fireEvent.click(screen.getByRole("button", { name: "ホームに戻る" }));

        expect(routerVisitMock).toHaveBeenCalledWith("/");
        expect(routerPostMock).not.toHaveBeenCalled();
    });

    it("posts logout when logout button is clicked by non-tenant authenticated user", () => {
        render(<Error403 {...createProps("customer")} />);

        fireEvent.click(screen.getByRole("button", { name: "ログアウト" }));

        expect(routerPostMock).toHaveBeenCalledWith("/logout");
    });

    it("shows home and back buttons for unauthenticated user", () => {
        render(<Error403 {...createProps()} />);

        expect(screen.getByRole("button", { name: "ホームに戻る" })).toBeDefined();
        expect(screen.getByRole("button", { name: "前のページへ" })).toBeDefined();
        expect(screen.queryByRole("button", { name: "ログアウト" })).toBeNull();
        expect(screen.queryByRole("button", { name: "ログアウトする" })).toBeNull();
    });
});
