interface TenantStatusBadgeProps {
    isOpen: boolean;
    isOrderPaused?: boolean;
    size?: "sm" | "md";
    className?: string;
}

export default function TenantStatusBadge({ isOpen, isOrderPaused = false, size = "sm", className = "" }: TenantStatusBadgeProps) {
    const sizeClasses = size === "sm" ? "text-xs" : "text-sm";
    const dotSize = size === "sm" ? "w-1.5 h-1.5" : "w-2 h-2";

    // 一時停止中は営業時間外より優先して表示
    if (isOrderPaused) {
        return (
            <span
                role="status"
                aria-label="営業状態: 注文停止中"
                className={`${sizeClasses} inline-flex items-center gap-1 text-orange-600 ${className}`}
            >
                <span className={`inline-block ${dotSize} rounded-full bg-orange-500`} aria-hidden="true" />
                注文停止中
            </span>
        );
    }

    const textColor = isOpen ? "text-green-600" : "text-red-600";
    const bgColor = isOpen ? "bg-green-500" : "bg-red-500";
    const label = isOpen ? "営業中" : "営業時間外";

    return (
        <span
            role="status"
            aria-label={`営業状態: ${label}`}
            className={`${sizeClasses} inline-flex items-center gap-1 ${textColor} ${className}`}
        >
            <span className={`inline-block ${dotSize} rounded-full ${bgColor}`} aria-hidden="true" />
            {label}
        </span>
    );
}
