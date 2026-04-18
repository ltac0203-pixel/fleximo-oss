import { router } from "@inertiajs/react";

type TenantRole = "tenant_admin" | "tenant_staff";
type SupportedRole = "admin" | "customer" | TenantRole;

interface InertiaPageLike {
    props?: {
        auth?: {
            user?: {
                role?: unknown;
            } | null;
        };
    };
}

interface InertiaHistoryLike {
    page?: InertiaPageLike;
}

const tenantRoles: ReadonlySet<SupportedRole> = new Set(["tenant_admin", "tenant_staff"]);

function toSupportedRole(role: unknown): SupportedRole | null {
    return role === "admin" || role === "customer" || role === "tenant_admin" || role === "tenant_staff" ? role : null;
}

function isTenantRole(role: SupportedRole | null): role is TenantRole {
    return role !== null && tenantRoles.has(role);
}

function resolveRoleFromHistoryState(): SupportedRole | null {
    const state = window.history.state as InertiaHistoryLike | null;
    return toSupportedRole(state?.page?.props?.auth?.user?.role);
}

function resolveRoleFromInitialPageData(): SupportedRole | null {
    const appElement = document.getElementById("app");
    const rawPage = appElement?.getAttribute("data-page");

    if (!rawPage) {
        return null;
    }

    try {
        const page = JSON.parse(rawPage) as InertiaPageLike;
        return toSupportedRole(page.props?.auth?.user?.role);
    } catch {
        return null;
    }
}

function resolveCurrentRole(explicitRole?: unknown): SupportedRole | null {
    return toSupportedRole(explicitRole) ?? resolveRoleFromHistoryState() ?? resolveRoleFromInitialPageData();
}

function resolveLogoutPath(): string {
    return typeof route === "function" ? route("logout") : "/logout";
}

export function navigateToSafeHome(explicitRole?: unknown): void {
    const role = resolveCurrentRole(explicitRole);

    if (isTenantRole(role)) {
        router.post(resolveLogoutPath());
        return;
    }

    router.visit("/");
}
