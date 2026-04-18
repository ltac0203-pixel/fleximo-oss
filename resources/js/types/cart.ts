import type { PageProps } from "./common";
import type { BusinessHourRange } from "./tenant";

export interface CartItemData {
    menuItemId: number;
    quantity: number;
    selectedOptions: number[];
}

export interface CartOption {
    id: number;
    name: string;
    price: number;
}

export interface CartMenuItemInfo {
    id: number;
    name: string;
    description: string | null;
    price: number;
    is_sold_out: boolean;
}

export interface CartItem {
    id: number;
    menu_item: CartMenuItemInfo;
    quantity: number;
    options: CartOption[];
    subtotal: number;
}

export interface CartTenant {
    id: number;
    name: string;
    slug: string;
    is_open: boolean;
    is_order_paused?: boolean;
    today_business_hours: BusinessHourRange[];
}

export interface Cart {
    id: number;
    user_id: number;
    tenant_id: number;
    tenant?: CartTenant;
    items: CartItem[];
    total: number;
    item_count: number;
    is_empty: boolean;
}

export interface CartsResponse {
    data: Cart[];
}

export interface CartPageProps extends PageProps {}
