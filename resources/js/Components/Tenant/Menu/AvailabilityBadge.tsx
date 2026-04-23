import Badge from "@/Components/UI/Badge";

interface AvailabilityBadgeProps {
    isActive: boolean;
}

export default function AvailabilityBadge({ isActive }: AvailabilityBadgeProps) {
    return (
        <Badge tone={isActive ? "green" : "gray"} size="xs" shape="rounded" role="status">
            {isActive ? "販売中" : "非公開"}
        </Badge>
    );
}
