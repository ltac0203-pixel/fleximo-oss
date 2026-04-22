export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    role?: "admin" | "customer" | "tenant_admin" | "tenant_staff";
    is_admin?: boolean;
    is_customer?: boolean;
    is_tenant_admin?: boolean;
    is_tenant_staff?: boolean;
    should_show_onboarding?: boolean;
}

export interface SiteConfig {
    name: string;
    baseUrl: string;
    contactEmail: string;
    supportEmail: string;
    logoUrl: string;
    defaultImageUrl: string;
}

export interface LegalConfig {
    companyName: string;
    representative: string;
    postalCode: string;
    address: string;
    addressExtra: string;
    contactEmail: string;
    businessHours: string;
    websiteUrl: string;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user?: User;
    };
    flash?: {
        success?: string;
        error?: string;
    };
    siteConfig: SiteConfig;
    legal: LegalConfig;
};

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
    from: number | null;
    to: number | null;
}

export interface ErrorPageProps extends PageProps {
    status: number;
    message?: string;
}
