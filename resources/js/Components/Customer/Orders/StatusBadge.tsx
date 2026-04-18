import { OrderStatusValue } from "@/types";

interface StatusBadgeProps {
    status: OrderStatusValue;
    label: string;
    size?: "sm" | "md";
}

const statusStyles: Record<OrderStatusValue, string> = {
    pending_payment: "bg-sky-100 text-sky-700",
    paid: "bg-cyan-100 text-cyan-700",
    accepted: "bg-sky-100 text-sky-700",
    in_progress: "bg-sky-100 text-sky-700",
    ready: "bg-green-100 text-green-800",
    completed: "bg-surface-dim text-ink-light",
    cancelled: "bg-red-100 text-red-800",
    payment_failed: "bg-red-100 text-red-800",
    refunded: "bg-surface-dim text-ink-light",
};

export default function StatusBadge({ status, label, size = "sm" }: StatusBadgeProps) {
    const sizeClasses = size === "sm" ? "px-2 py-0.5 text-xs" : "px-3 py-1 text-sm";

    return (
        <span
            role="status"
            aria-label={`ステータス: ${label}`}
            className={`inline-flex items-center font-medium ${sizeClasses} ${statusStyles[status]}`}
        >
            {label}
        </span>
    );
}
