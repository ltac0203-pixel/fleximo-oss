import Modal from "@/Components/Modal";
import { useItemDetailForm } from "@/Hooks/useItemDetailForm";
import { BaseModalProps, CartItemData, CustomerMenuItem } from "@/types";
import AllergenBadge from "./AllergenBadge";
import NutritionTable from "./NutritionTable";
import OptionSelector from "./OptionSelector";
import PriceCalculator from "./PriceCalculator";
import QuantitySelector from "./QuantitySelector";

interface ItemDetailModalProps extends BaseModalProps {
    item: CustomerMenuItem | null;
    onAddToCart: (data: CartItemData) => void;
    isLoading?: boolean;
}

export default function ItemDetailModal({ show, item, onClose, onAddToCart, isLoading }: ItemDetailModalProps) {
    const {
        quantity,
        setQuantity,
        selectedOptionsByGroup,

        selectedOptions,
        isValid,
        handleOptionChange,
        handleAddToCart,
    } = useItemDetailForm({ show, item, onAddToCart, onClose });

    if (!item) return null;

    return (
        <Modal show={show} onClose={onClose} maxWidth="md" variant="bottom-sheet">
            <div className="max-h-[90dvh] flex flex-col">
                {/* Header を明示し、実装意図の誤読を防ぐ。 */}
                <div className="flex items-center justify-between p-4 border-b">
                    <h2 className="text-lg font-semibold text-ink">{item.name}</h2>
                    <button
                        onClick={onClose}
                        className="p-2 -m-2 text-muted-light hover:text-muted"
                        aria-label="閉じる"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                {/* Scrollable Content を明示し、実装意図の誤読を防ぐ。 */}
                <div className="flex-1 overflow-y-auto p-4">
                    {item.description && <p className="text-ink-light mb-4">{item.description}</p>}

                    {(item.allergens > 0 || item.allergen_advisories > 0 || item.allergen_note) && (
                        <div className="mb-4 pb-4 border-b">
                            <AllergenBadge
                                allergens={item.allergens}
                                allergenAdvisories={item.allergen_advisories}
                                allergenNote={item.allergen_note}
                                mode="detail"
                            />
                        </div>
                    )}

                    <div className="mb-4 pb-4 border-b">
                        <span className="text-lg font-semibold text-sky-700">¥{item.price.toLocaleString()}</span>
                    </div>

                    {item.nutrition_info && (
                        <div className="mb-4">
                            <NutritionTable nutritionInfo={item.nutrition_info} />
                        </div>
                    )}

                    <OptionSelector
                        groups={item.option_groups}
                        selectedOptionsByGroup={selectedOptionsByGroup}
                        onChange={handleOptionChange}
                    />

                    <div className="flex items-center justify-between mb-4 pb-4 border-b">
                        <span className="font-medium text-ink-light">数量</span>
                        <QuantitySelector value={quantity} onChange={setQuantity} />
                    </div>

                    <PriceCalculator basePrice={item.price} selectedOptions={selectedOptions} quantity={quantity} />
                </div>

                {/* Footer を明示し、実装意図の誤読を防ぐ。 */}
                <div className="p-4 border-t bg-sky-50/50">
                    <button
                        onClick={handleAddToCart}
                        disabled={!isValid || isLoading}
                        className={`w-full px-5 py-2.5 font-medium rounded-lg transition-all duration-200 ${
                            isValid && !isLoading
                                ? "bg-sky-600 hover:bg-sky-700 hover:shadow-lg text-white shadow-md"
                                : "bg-surface-dim text-muted-light cursor-not-allowed"
                        }`}
                    >
                        {isLoading ? (
                            <span className="flex items-center justify-center">
                                <svg
                                    className="h-5 w-5 animate-spin text-muted-light"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span className="sr-only">処理中</span>
                            </span>
                        ) : isValid ? (
                            "カートに追加"
                        ) : (
                            "必須オプションを選択してください"
                        )}
                    </button>
                </div>
            </div>
        </Modal>
    );
}
