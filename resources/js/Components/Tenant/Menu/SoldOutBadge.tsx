import Badge from "@/Components/UI/Badge";

interface SoldOutBadgeProps {
    isSoldOut: boolean;
}

export default function SoldOutBadge({ isSoldOut }: SoldOutBadgeProps) {
    if (!isSoldOut) return null;

    return (
        <Badge tone="red" size="xs" shape="rounded">
            売り切れ
        </Badge>
    );
}
