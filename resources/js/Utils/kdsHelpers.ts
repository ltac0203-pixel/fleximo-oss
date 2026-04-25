import { KdsOrderStatus, KdsStatusMeta } from "@/types";

// ステータスごとの UI 文脈見出しと色・CSSクラスを一元管理。
// `kdsHeading` は KDS 端末向けの文言で、意味側ラベル (`ORDER_STATUS_LABELS`) とは別管理する。
// 例えば `paid` は意味としては「決済完了」だが、KDS では作業対象として「新規注文」と見せる。
export const KDS_STATUS_META: Record<KdsOrderStatus, KdsStatusMeta> = {
    paid: {
        kdsHeading: "新規注文",
        dotClass: "bg-amber-500",
        cardBorderClass: "border-l-amber-400",
        badgeBgClass: "bg-amber-100",
        badgeTextClass: "text-amber-800",
    },
    accepted: {
        kdsHeading: "受付済み",
        dotClass: "bg-sky-500",
        cardBorderClass: "border-l-sky-400",
        badgeBgClass: "bg-sky-100",
        badgeTextClass: "text-sky-800",
    },
    in_progress: {
        kdsHeading: "調理中",
        dotClass: "bg-cyan-500",
        cardBorderClass: "border-l-cyan-400",
        badgeBgClass: "bg-cyan-100",
        badgeTextClass: "text-cyan-800",
    },
    ready: {
        kdsHeading: "準備完了",
        dotClass: "bg-green-500",
        cardBorderClass: "border-l-green-400",
        badgeBgClass: "bg-green-100",
        badgeTextClass: "text-green-800",
    },
};

// ステータスの表示優先度（低い方が先に表示）
export const KDS_STATUS_PRIORITY: Record<KdsOrderStatus, number> = {
    paid: 0,
    accepted: 1,
    in_progress: 2,
    ready: 3,
};

// 全ステータスの順序付き配列
export const KDS_STATUSES: KdsOrderStatus[] = ["paid", "accepted", "in_progress", "ready"];

// 進行順序を単一点で管理し、画面側に状態遷移ルールを散らさない。
export function getNextStatus(currentStatus: KdsOrderStatus): KdsOrderStatus | null {
    switch (currentStatus) {
        case "paid":
            return null;
        case "accepted":
            return "in_progress";
        case "in_progress":
            return "ready";
        case "ready":
            return null;
        default:
            return null;
    }
}

// 操作文言を遷移先と結び付け、表示と業務フローの不整合を防ぐ。
export function getStatusButtonText(nextStatus: KdsOrderStatus | null): string {
    switch (nextStatus) {
        case "in_progress":
            return "調理開始";
        case "ready":
            return "準備完了";
        default:
            return "";
    }
}

// 経過時間の危険度を即判別できるよう、色だけで状態を伝える。
export function getElapsedTextClass(isWarning: boolean): string {
    return isWarning ? "text-red-600" : "text-slate-500";
}
