import React from "react";
import { fireEvent, render, screen } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import Dashboard from "@/Pages/Admin/Dashboard";
import { AdminDashboardProps } from "@/types";

const routerGetMock = vi.hoisted(() => vi.fn());

vi.mock("@/Layouts/AdminLayout", () => ({
    default: ({ children }: React.PropsWithChildren) => <div>{children}</div>,
}));

vi.mock("@inertiajs/react", () => ({
    Head: () => null,
    Link: ({ children, href, ...props }: React.PropsWithChildren<React.AnchorHTMLAttributes<HTMLAnchorElement>>) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
    router: {
        get: routerGetMock,
    },
}));

function createProps(): AdminDashboardProps {
    return {
        auth: {
            user: {
                id: 1,
                name: "Admin",
                email: "admin@example.com",
                role: "admin",
            },
        },
        stats: {
            pending_count: 3,
            under_review_count: 2,
            approved_count: 5,
            rejected_count: 1,
            total_count: 11,
            active_tenant_count: 6,
        },
        revenueFilters: {
            start_date: "2026-02-01",
            end_date: "2026-02-28",
            ranking_limit: 10,
        },
        revenueDashboard: {
            overview: {
                gmv_total: 300000,
                order_count_total: 120,
                avg_order_value: 2500,
                estimated_fee_total: 18000,
                active_tenant_count: 2,
            },
            trend: [
                {
                    date: "2026-02-27",
                    gmv: 100000,
                    order_count: 40,
                    estimated_fee: 6000,
                },
                {
                    date: "2026-02-28",
                    gmv: 200000,
                    order_count: 80,
                    estimated_fee: 12000,
                },
            ],
            ranking: [
                {
                    tenant_id: 10,
                    tenant_name: "Tenant A",
                    gmv: 200000,
                    order_count: 80,
                    estimated_fee: 12000,
                    fee_rate_bps: 600,
                    share_percent: 66.7,
                },
                {
                    tenant_id: 11,
                    tenant_name: "Tenant B",
                    gmv: 100000,
                    order_count: 40,
                    estimated_fee: 6000,
                    fee_rate_bps: 500,
                    share_percent: 33.3,
                },
            ],
        },
    };
}

describe("Admin Dashboard Page", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("GMVセクションとランキングを表示する", () => {
        render(<Dashboard {...createProps()} />);

        expect(screen.getByText("プラットフォーム売上（GMV）")).toBeInTheDocument();
        expect(screen.getByText("GMV合計")).toBeInTheDocument();
        expect(screen.getByText("テナント別売上ランキング")).toBeInTheDocument();
        expect(screen.getByText("Tenant A")).toBeInTheDocument();
        expect(screen.getByText("Tenant B")).toBeInTheDocument();
    });

    it("期間フィルタ送信時にadmin.dashboardへクエリ付きで遷移する", () => {
        render(<Dashboard {...createProps()} />);

        fireEvent.change(screen.getByLabelText("開始日"), { target: { value: "2026-02-10" } });
        fireEvent.change(screen.getByLabelText("終了日"), { target: { value: "2026-02-20" } });
        fireEvent.change(screen.getByLabelText("ランキング件数"), { target: { value: "5" } });
        fireEvent.click(screen.getByRole("button", { name: "適用" }));

        expect(routerGetMock).toHaveBeenCalledWith(
            "/admin.dashboard",
            {
                start_date: "2026-02-10",
                end_date: "2026-02-20",
                ranking_limit: 5,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    });
});
