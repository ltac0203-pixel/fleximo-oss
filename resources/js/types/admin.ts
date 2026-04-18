import type { PageProps, PaginatedData } from "./common";

export type BusinessType = "restaurant" | "cafe" | "izakaya" | "bakery" | "fast_food" | "other";

export type TenantApplicationStatus = "pending" | "under_review" | "approved" | "rejected";

export interface TenantApplicationListItem {
    id: number;
    application_code: string;
    applicant_name: string;
    applicant_email: string;
    tenant_name: string;
    business_type: BusinessType;
    business_type_label: string;
    status: TenantApplicationStatus;
    status_label: string;
    status_color: string;
    created_at: string;
}

export interface TenantApplicationDetail {
    id: number;
    application_code: string;
    applicant_name: string;
    applicant_email: string;
    applicant_phone: string;
    tenant_name: string;
    tenant_address: string | null;
    business_type: BusinessType;
    business_type_label: string;
    status: TenantApplicationStatus;
    status_label: string;
    status_color: string;
    rejection_reason: string | null;
    internal_notes: string | null;
    reviewed_at: string | null;
    reviewer?: {
        id: number;
        name: string;
    };
    created_tenant?: {
        id: number;
        name: string;
        slug: string;
    };
    can_start_review: boolean;
    can_be_approved: boolean;
    can_be_rejected: boolean;
    created_at: string;
    updated_at: string;
}

export interface AdminDashboardStats {
    pending_count: number;
    under_review_count: number;
    approved_count: number;
    rejected_count: number;
    total_count: number;
    active_tenant_count: number;
}

export interface AdminRevenueOverview {
    gmv_total: number;
    order_count_total: number;
    avg_order_value: number;
    estimated_fee_total: number;
    active_tenant_count: number;
}

export interface AdminRevenueTrendItem {
    date: string;
    gmv: number;
    order_count: number;
    estimated_fee: number;
}

export interface AdminRevenueRankingItem {
    tenant_id: number;
    tenant_name: string;
    gmv: number;
    order_count: number;
    estimated_fee: number;
    fee_rate_bps: number;
    share_percent: number;
}

export interface AdminRevenueDashboardData {
    overview: AdminRevenueOverview;
    trend: AdminRevenueTrendItem[];
    ranking: AdminRevenueRankingItem[];
}

export interface AdminRevenueFilters {
    start_date: string;
    end_date: string;
    ranking_limit: number;
}

export interface AdminDashboardProps extends PageProps {
    stats: AdminDashboardStats;
    revenueDashboard: AdminRevenueDashboardData;
    revenueFilters: AdminRevenueFilters;
}

export interface ApplicationsIndexProps extends PageProps {
    applications: PaginatedData<TenantApplicationListItem>;
    statusFilter: TenantApplicationStatus | null;
    searchQuery: string | null;
    statuses: Array<{ value: string; label: string }>;
}

export interface ApplicationShowProps extends PageProps {
    application: TenantApplicationDetail;
}
