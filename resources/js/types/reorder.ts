import type { Cart } from "./cart";

export type ReorderSkipReason =
    | "menu_item_deleted"
    | "inactive"
    | "sold_out"
    | "outside_time_window"
    | "option_constraints_changed";

export interface ReorderAddedItem {
    order_item_name: string;
    menu_item_id: number;
    quantity: number;
    original_unit_price: number;
    current_unit_price: number;
    price_changed: boolean;
    options_added: {
        name: string;
        original_price: number;
        current_price: number;
        price_changed: boolean;
    }[];
    options_skipped: string[];
}

export interface ReorderSkippedItem {
    order_item_name: string;
    menu_item_id: number | null;
    quantity: number;
    reason: ReorderSkipReason;
    reason_label: string;
}

export interface ReorderResponse {
    added_items: ReorderAddedItem[];
    skipped_items: ReorderSkippedItem[];
    summary: {
        total_items_in_order: number;
        items_added: number;
        items_skipped: number;
        had_existing_cart_items: boolean;
        tenant_id: number;
        tenant_name: string;
    };
    cart: Cart;
}
