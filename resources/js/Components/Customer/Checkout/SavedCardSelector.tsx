import { SavedCard } from "@/types";
import GeoSurface from "@/Components/GeoSurface";
import { Link } from "@inertiajs/react";

interface SavedCardSelectorProps {
    cards: SavedCard[];
    selectedCardId: number | null;
    onSelect: (cardId: number | null) => void;
    disabled?: boolean;
    tenantSlug?: string;
}

function getBrandLabel(brand: string | null): string {
    const brands: Record<string, string> = {
        VISA: "Visa",
        MASTER: "Mastercard",
        JCB: "JCB",
        AMEX: "AMEX",
        DINERS: "Diners",
        DISCOVER: "Discover",
    };
    return brand ? (brands[brand.toUpperCase()] ?? brand) : "カード";
}

export default function SavedCardSelector({
    cards,
    selectedCardId,
    onSelect,
    disabled = false,
    tenantSlug,
}: SavedCardSelectorProps) {
    if (cards.length === 0) {
        return (
            <GeoSurface topAccent elevated className="p-4">
                <h2 className="text-lg font-semibold text-ink mb-4">カードを選択</h2>
                <div className="flex flex-col items-center py-6 text-muted-light">
                    <svg className="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                        />
                    </svg>
                    <p className="text-sm text-muted">保存済みのカードはありません</p>
                    <p className="text-xs text-muted-light mt-1">「新しいカードで支払う」からカードを追加できます</p>
                </div>
            </GeoSurface>
        );
    }

    return (
        <GeoSurface topAccent elevated className="p-4">
            <div className="flex items-center justify-between mb-4">
                <h2 className="text-lg font-semibold text-ink">カードを選択</h2>
                {tenantSlug && (
                    <Link
                        href={route("order.cards.index", { tenant: tenantSlug })}
                        className="text-sm text-sky-600 hover:text-sky-700 transition"
                    >
                        管理
                    </Link>
                )}
            </div>
            <div className="space-y-4">
                {cards.map((card) => (
                    <label
                        key={card.id}
                        className={`geo-surface flex items-center gap-4 border-2 p-4 transition ${
                            selectedCardId === card.id
                                ? "border-sky-500 bg-sky-50 shadow-geo-sky"
                                : "border-edge hover:border-sky-300 hover:bg-surface"
                        } ${disabled ? "opacity-50 cursor-not-allowed" : ""}`}
                    >
                        <input
                            type="radio"
                            name="saved_card"
                            value={card.id}
                            checked={selectedCardId === card.id}
                            onChange={() => onSelect(card.id)}
                            disabled={disabled}
                            className="sr-only"
                        />
                        <div className="flex-1">
                            <div className="flex items-center gap-2">
                                <span
                                    className={`font-medium ${
                                        selectedCardId === card.id ? "text-sky-900" : "text-ink"
                                    }`}
                                >
                                    {getBrandLabel(card.brand)} {card.card_no_display}
                                </span>
                                {card.is_default && (
                                    <span className="text-xs bg-sky-100 text-sky-700 px-2 py-0.5 rounded-full">
                                        デフォルト
                                    </span>
                                )}
                            </div>
                            <div className="text-sm text-muted">有効期限: {card.expire}</div>
                        </div>
                        <div
                            className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${
                                selectedCardId === card.id ? "border-sky-500 bg-sky-500" : "border-edge-strong"
                            }`}
                        >
                            {selectedCardId === card.id && <div className="w-2 h-2 rounded-full bg-white" />}
                        </div>
                    </label>
                ))}
            </div>
        </GeoSurface>
    );
}
