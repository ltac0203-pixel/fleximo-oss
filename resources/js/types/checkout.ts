import type { PageProps } from "./common";
import type { Cart } from "./cart";
import type { SavedCard } from "./cards";
import type { OrderDetail, OrderStatusValue, PaymentStatus } from "./order";

export interface CheckoutApiResponse {
    data: {
        order: {
            id: number;
            order_code: string;
            status: OrderStatusValue;
            status_label: string;
            total_amount: number;
        };
        payment: {
            id: number;
            requires_redirect: boolean;
            requires_token: boolean;
            redirect_url?: string;
            fincode_id?: string;
            access_id?: string;
        };
        cart_clear_failed?: boolean;
    };
}

export interface FinalizePaymentApiResponse {
    data: {
        order?: OrderDetail;
        requires_3ds_redirect?: boolean;
        redirect_url?: string;
        payment_id?: number;
        payment_pending?: boolean;
        order_id?: number;
    };
}

export interface CheckoutIndexProps extends PageProps {
    cart: Cart;
    fincodePublicKey: string;
    isProduction: boolean;
    savedCards: SavedCard[];
}

export interface CheckoutCompleteProps extends PageProps {
    order: OrderDetail;
}

export interface CheckoutFailedProps extends PageProps {
    order?: OrderDetail;
    errorMessage?: string;
}

export interface PayPayCallbackProps extends PageProps {
    payment: {
        id: number;
        status: PaymentStatus;
        order_id: number;
    };
    success: boolean;
}

export interface ThreeDsCallbackProps extends PageProps {
    payment: {
        id: number;
        status: PaymentStatus;
        order_id: number;
    };
    param: string;
    event?: string;
}

export interface ThreeDsCallbackApiResponse {
    data: {
        order?: OrderDetail;
        requires_3ds_redirect?: boolean;
        redirect_url?: string;
        payment_id?: number;
    };
    error?: {
        message: string;
    };
}
