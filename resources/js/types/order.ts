import type { PageProps, PaginatedData } from "./common";

export type OrderStatusValue =
    | "pending_payment"
    | "paid"
    | "accepted"
    | "in_progress"
    | "ready"
    | "completed"
    | "cancelled"
    | "payment_failed"
    | "refunded";

export type PaymentMethod = "new_card" | "saved_card" | "paypay";

export type ApiPaymentMethod = "card" | "paypay";

export type PaymentStatus = "pending" | "processing" | "completed" | "failed" | "cancelled" | "refunded";

export interface OrderItemOption {
    id: number;
    option_id: number | null;
    name: string;
    price: number;
    created_at: string;
}

export interface OrderItem {
    id: number;
    menu_item_id: number | null;
    name: string;
    price: number;
    quantity: number;
    options: OrderItemOption[];
    subtotal: number;
    created_at: string;
}

export interface OrderPayment {
    method: ApiPaymentMethod;
    method_label: string;
    status: PaymentStatus;
    status_label: string;
}

export interface OrderListTenant {
    id: number;
    name: string;
}

export interface OrderDetailTenant {
    id: number;
    name: string;
    slug: string;
    address: string | null;
}

export interface OrderListItem {
    id: number;
    order_code: string;
    tenant: OrderListTenant;
    status: OrderStatusValue;
    status_label: string;
    total_amount: number;
    created_at: string;
}

export interface OrderDetail {
    id: number;
    order_code: string;
    business_date: string;
    tenant: OrderDetailTenant;
    status: OrderStatusValue;
    status_label: string;
    can_be_cancelled: boolean;
    total_amount: number;
    items: OrderItem[];
    payment?: OrderPayment;
    paid_at: string | null;
    accepted_at: string | null;
    in_progress_at: string | null;
    ready_at: string | null;
    completed_at: string | null;
    cancelled_at: string | null;
    created_at: string;
}

export interface OrdersIndexPageProps extends PageProps {
    orders: PaginatedData<OrderListItem>;
}

export interface OrderShowPageProps extends PageProps {
    order: OrderDetail;
}
