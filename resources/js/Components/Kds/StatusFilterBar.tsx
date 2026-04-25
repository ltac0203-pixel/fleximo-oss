import { KdsOrderStatus } from "@/types";
import { KDS_STATUS_META, KDS_STATUSES } from "@/Utils/kdsHelpers";

interface StatusFilterBarProps {
    activeStatuses: Set<KdsOrderStatus>;
    orderCounts: Record<KdsOrderStatus, number>;
    onToggle: (status: KdsOrderStatus) => void;
}

export default function StatusFilterBar({ activeStatuses, orderCounts, onToggle }: StatusFilterBarProps) {
    return (
        <div
            className="grid grid-cols-2 sm:flex sm:flex-wrap gap-2"
            role="group"
            aria-label="ステータスフィルター"
        >
            {KDS_STATUSES.map((status) => {
                const meta = KDS_STATUS_META[status];
                const isActive = activeStatuses.has(status);
                const count = orderCounts[status];

                return (
                    <button
                        key={status}
                        role="checkbox"
                        aria-checked={isActive}
                        onClick={() => onToggle(status)}
                        className={`
                            flex items-center gap-2 px-4 py-2.5 rounded-lg border text-sm font-medium
                            transition-colors duration-150
                            ${isActive
                                ? "bg-white border-edge-strong text-ink shadow-sm"
                                : "bg-surface-dim border-transparent text-muted-light"
                            }
                        `}
                    >
                        <span
                            className={`w-2.5 h-2.5 rounded-full flex-shrink-0 ${
                                isActive ? meta.dotClass : "bg-edge-strong"
                            }`}
                        />
                        <span>{meta.kdsHeading}</span>
                        <span
                            className={`
                                ml-auto min-w-[1.5rem] text-center px-1.5 py-0.5 rounded-full text-xs font-bold
                                ${isActive
                                    ? `${meta.badgeBgClass} ${meta.badgeTextClass}`
                                    : "bg-edge text-muted-light"
                                }
                            `}
                        >
                            {count}
                        </span>
                    </button>
                );
            })}
        </div>
    );
}
