import { KdsOrderStatus } from "@/types";
import { KDS_STATUS_META } from "@/Utils/kdsHelpers";

interface StatusBadgeProps {
    status: KdsOrderStatus;
}

export default function StatusBadge({ status }: StatusBadgeProps) {
    const meta = KDS_STATUS_META[status];

    return (
        <span
            className={`inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium ${meta.badgeBgClass} ${meta.badgeTextClass}`}
        >
            <span className={`w-1.5 h-1.5 rounded-full ${meta.dotClass}`} />
            {meta.kdsHeading}
        </span>
    );
}
