import { OrderStatusValue } from "@/types";
import Badge from "@/Components/UI/Badge";
import { ORDER_STATUS_TONE_MAP } from "@/constants/statusColors";

interface StatusBadgeProps {
    status: OrderStatusValue;
    label: string;
    size?: "sm" | "md";
}

export default function StatusBadge({ status, label, size = "sm" }: StatusBadgeProps) {
    return (
        <Badge
            tone={ORDER_STATUS_TONE_MAP[status]}
            size={size === "sm" ? "xs" : "md"}
            shape="none"
            role="status"
            aria-label={`ステータス: ${label}`}
        >
            {label}
        </Badge>
    );
}
