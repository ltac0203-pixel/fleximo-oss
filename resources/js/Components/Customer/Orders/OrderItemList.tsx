import { OrderItem } from "@/types";
import { formatPrice } from "@/Utils/formatPrice";

interface OrderItemListProps {
    items: OrderItem[];
    totalAmount: number;
}

export default function OrderItemList({ items, totalAmount }: OrderItemListProps) {
    return (
        <div className="bg-white border border-edge">
            <h3 className="text-base font-semibold text-ink px-4 pt-4 pb-2">注文内容</h3>

            <div className="divide-y divide-edge">
                {items.map((item) => (
                    <div key={item.id} className="px-4 py-3">
                        <div className="flex justify-between items-start">
                            <div className="flex-1 min-w-0">
                                <div className="flex items-center gap-2">
                                    <span className="text-sm font-medium text-ink">{item.name}</span>
                                    <span className="text-sm text-muted">x{item.quantity}</span>
                                </div>

                                {item.options.length > 0 && (
                                    <div className="mt-1 flex flex-wrap text-xs text-muted">
                                        {item.options.map((option) => (
                                            <span key={option.id} className="mr-2">
                                                +{option.name}
                                                {option.price > 0 && (
                                                    <span className="text-muted-light ml-1">
                                                        ({formatPrice(option.price)})
                                                    </span>
                                                )}
                                            </span>
                                        ))}
                                    </div>
                                )}
                            </div>

                            <div className="text-sm font-medium text-ink ml-4">{formatPrice(item.subtotal)}</div>
                        </div>
                    </div>
                ))}
            </div>

            <div className="border-t border-edge px-4 py-3">
                <div className="flex justify-between items-center">
                    <span className="text-base font-medium text-ink">合計</span>
                    <span className="text-lg font-bold text-ink">{formatPrice(totalAmount)}</span>
                </div>
            </div>
        </div>
    );
}
