import { SavedCard } from "@/types";
import CardIcon from "./CardIcon";

interface SavedCardListProps {
    cards: SavedCard[];
    onDelete: (cardId: number) => void;
    deletingId: number | null;
}

// 既存カードを先に見せ、削除や選択の判断を誤らないようにする。
export default function SavedCardList({ cards, onDelete, deletingId }: SavedCardListProps) {
    if (cards.length === 0) {
        return (
            <div className="bg-white border p-6 text-center">
                <div className="text-muted-light mb-2">
                    <svg className="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={1.5}
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                        />
                    </svg>
                </div>
                <p className="text-muted text-sm">登録済みのカードはありません</p>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            <h2 className="text-sm font-medium text-ink-light">登録済みカード</h2>
            <div className="space-y-2">
                {cards.map((card) => (
                    <div key={card.id} className="bg-white border p-4 flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <CardIcon brand={card.brand} />
                            <div>
                                <div className="text-sm font-medium text-ink">{card.card_no_display}</div>
                                <div className="text-xs text-muted flex items-center gap-2">
                                    <span>有効期限: {card.expire}</span>
                                    {card.is_default && (
                                        <span className="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-sky-100 text-sky-700">
                                            メイン
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                        <button
                            onClick={() => onDelete(card.id)}
                            disabled={deletingId === card.id}
                            className={`
                                p-2
                                ${
                                    deletingId === card.id
                                        ? "text-muted-light cursor-not-allowed"
                                        : "text-muted-light hover:text-red-500 hover:bg-red-50"
                                }
                            `}
                            title="カードを削除"
                        >
                            {deletingId === card.id ? (
                                <svg className="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle
                                        className="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        strokeWidth="4"
                                    ></circle>
                                    <path
                                        className="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                            ) : (
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                            )}
                        </button>
                    </div>
                ))}
            </div>
        </div>
    );
}
