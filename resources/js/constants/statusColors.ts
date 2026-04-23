import type { OrderStatusValue } from "@/types";
import type { BadgeTone } from "@/Components/UI/Badge";

export const ORDER_STATUS_TONE_MAP: Record<OrderStatusValue, BadgeTone> = {
    pending_payment: "sky",
    paid: "cyan",
    accepted: "sky",
    in_progress: "sky",
    ready: "green",
    completed: "muted",
    cancelled: "red",
    payment_failed: "red",
    refunded: "muted",
};

export const ACCOUNT_STATUS_COLOR_TONE_MAP: Record<string, BadgeTone> = {
    green: "green",
    yellow: "yellow",
    red: "red",
};

export const ACCOUNT_STATUS_FALLBACK_TONE: BadgeTone = "neutral";

export function toAccountStatusTone(color: string): BadgeTone {
    return ACCOUNT_STATUS_COLOR_TONE_MAP[color] ?? ACCOUNT_STATUS_FALLBACK_TONE;
}
