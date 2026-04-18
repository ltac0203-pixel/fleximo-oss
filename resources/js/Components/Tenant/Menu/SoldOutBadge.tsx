interface SoldOutBadgeProps {
    isSoldOut: boolean;
}

export default function SoldOutBadge({ isSoldOut }: SoldOutBadgeProps) {
    if (!isSoldOut) return null;

    return (
        <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
            売り切れ
        </span>
    );
}
