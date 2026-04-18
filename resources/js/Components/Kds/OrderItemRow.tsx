import { KdsOrderItem } from "@/types";

interface OrderItemRowProps {
    item: KdsOrderItem;
}

export default function OrderItemRow({ item }: OrderItemRowProps) {
    return (
        <div className="text-sm text-ink-light">
            <div className="flex items-start justify-between">
                <span>
                    {item.name} <span className="text-sky-600">x{item.quantity}</span>
                </span>
            </div>
            {(item.options?.length ?? 0) > 0 && (
                <div className="ml-2 text-xs text-muted">{item.options?.map((opt) => opt?.name).join(", ")}</div>
            )}
        </div>
    );
}
