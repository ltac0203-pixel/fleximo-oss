import type { PageProps } from "./common";

export type TenantStatus = "active" | "inactive" | "suspended" | "pending" | "rejected";

export interface TenantBusinessHour {
    weekday: number;
    open_time: string;
    close_time: string;
    sort_order: number;
}

export interface BusinessHourRange {
    open_time: string;
    close_time: string;
}

export interface Tenant {
    id: number;
    name: string;
    slug: string;
    address: string | null;
    email: string | null;
    phone: string | null;
    fincode_shop_id: string | null;
    status: TenantStatus;
    is_active: boolean;
    is_approved?: boolean;
    is_open?: boolean;
    business_hours?: TenantBusinessHour[];
    today_business_hours?: BusinessHourRange[];
}

export interface TenantPageProps extends PageProps {
    tenant: Tenant;
}

export interface Staff {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    is_active: boolean;
    role: "admin" | "staff" | null;
    created_at: string;
}

export interface StaffIndexProps extends PageProps {
    staff: Staff[];
}
