import { CustomerMenuOption } from "@/types";
import { formatPrice } from "@/Utils/formatPrice";

interface PriceCalculatorProps {
    basePrice: number;
    selectedOptions: CustomerMenuOption[];
    quantity: number;
}

export default function PriceCalculator({ basePrice, selectedOptions, quantity }: PriceCalculatorProps) {
    const optionTotal = selectedOptions.reduce((sum, opt) => sum + opt.price, 0);
    const unitPrice = basePrice + optionTotal;
    const total = unitPrice * quantity;

    return (
        <div className="space-y-2">
            {/* 単価の内訳 */}
            {selectedOptions.length > 0 && (
                <div className="text-sm text-muted space-y-1">
                    <div className="flex justify-between">
                        <span>基本価格</span>
                        <span>{formatPrice(basePrice)}</span>
                    </div>
                    {selectedOptions.map((option) => (
                        <div key={option.id} className="flex justify-between">
                            <span>{option.name}</span>
                            <span>+{formatPrice(option.price)}</span>
                        </div>
                    ))}
                    <div className="flex justify-between pt-1 border-t border-edge">
                        <span>単価</span>
                        <span>{formatPrice(unitPrice)}</span>
                    </div>
                </div>
            )}

            {/* 合計金額 */}
            <div className="flex justify-between items-center pt-2">
                <span className="text-ink-light">
                    {quantity > 1 ? `${formatPrice(unitPrice)} × ${quantity}` : "合計"}
                </span>
                <span className="text-xl font-bold text-ink">{formatPrice(total)}</span>
            </div>
        </div>
    );
}
