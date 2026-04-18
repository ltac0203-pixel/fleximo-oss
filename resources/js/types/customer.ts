export type AccountStatus = "active" | "suspended" | "banned";

export interface CustomerListItem {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    account_status: AccountStatus;
    account_status_label: string;
    account_status_color: string;
    last_login_at: string | null;
    created_at: string;
    orders_count: number;
}

export interface CustomerDetail extends CustomerListItem {
    account_status_reason: string | null;
    account_status_changed_at: string | null;
    account_status_changed_by: { id: number; name: string } | null;
    total_orders: number;
    total_spent: number;
    favorite_tenants_count: number;
}

export interface CustomerOrderItem {
    id: number;
    order_code: string;
    tenant_name: string | null;
    status: string;
    status_label: string;
    total_amount: number;
    payment: {
        method: string | null;
        method_label: string | null;
        status: string | null;
    } | null;
    items: Array<{
        name: string;
        quantity: number;
        price: number;
    }> | null;
    created_at: string;
}
