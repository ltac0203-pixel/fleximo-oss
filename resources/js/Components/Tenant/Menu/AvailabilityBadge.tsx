interface AvailabilityBadgeProps {
    isActive: boolean;
}

export default function AvailabilityBadge({ isActive }: AvailabilityBadgeProps) {
    return (
        <span
            role="status"
            className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${
                isActive ? "bg-green-100 text-green-800" : "bg-gray-100 text-gray-800"
            }`}
        >
            {isActive ? "販売中" : "非公開"}
        </span>
    );
}
