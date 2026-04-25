import type { OrderStatusValue } from "@/types";

export type StatusSummaryTone = "info" | "success" | "warning" | "danger";

export interface StatusSummary {
    headline: string;
    nextAction: string;
    tone: StatusSummaryTone;
}

export const ORDER_STATUS_SUMMARIES: Record<OrderStatusValue, StatusSummary> = {
    pending_payment: {
        headline: "現在、お支払い確認中です",
        nextAction: "決済完了後に注文受付へ進みます。",
        tone: "warning",
    },
    paid: {
        headline: "現在、注文を受け付けました",
        nextAction: "店舗で確認後、調理に進みます。",
        tone: "info",
    },
    accepted: {
        headline: "現在、店舗で注文内容を確認中です",
        nextAction: "確認が完了次第、商品の準備を開始します。",
        tone: "info",
    },
    in_progress: {
        headline: "現在、商品を準備中です",
        nextAction: "準備ができ次第、この画面でお知らせします。",
        tone: "info",
    },
    ready: {
        headline: "現在、商品の準備ができています",
        nextAction: "カウンターで注文番号をお伝えください。",
        tone: "success",
    },
    completed: {
        headline: "商品の受け取りが完了しました",
        nextAction: "ご利用ありがとうございました。",
        tone: "success",
    },
    cancelled: {
        headline: "この注文はキャンセルされました",
        nextAction: "詳細は店舗へお問い合わせください。",
        tone: "danger",
    },
    payment_failed: {
        headline: "決済に失敗しました",
        nextAction: "支払い方法を確認のうえ、再度お試しください。",
        tone: "danger",
    },
    refunded: {
        headline: "この注文は返金済みです",
        nextAction: "返金状況の詳細はご利用明細をご確認ください。",
        tone: "danger",
    },
};

export function getOrderStatusSummary(status: OrderStatusValue, fallbackLabel: string): StatusSummary {
    return (
        ORDER_STATUS_SUMMARIES[status] ?? {
            headline: `現在のステータス: ${fallbackLabel}`,
            nextAction: "注文状況をご確認ください。",
            tone: "info",
        }
    );
}
