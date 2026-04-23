import ConfirmDialog from "@/Components/UI/ConfirmDialog";
import { useOptionActions } from "@/Hooks/useOptionActions";
import { Option } from "@/types";
import { useState } from "react";

interface OptionListProps {
    optionGroupId: number;
    options: Option[];
    onOptionsChange: () => void;
    onError?: (message: string) => void;
}

interface NewOption {
    name: string;
    price: number | "";
}

export default function OptionList({ optionGroupId, options, onOptionsChange, onError }: OptionListProps) {
    const [newOption, setNewOption] = useState<NewOption>({
        name: "",
        price: "",
    });
    const [editingId, setEditingId] = useState<number | null>(null);
    const [editingData, setEditingData] = useState<{
        name: string;
        price: number | "";
    }>({ name: "", price: 0 });
    const [confirmDeleteId, setConfirmDeleteId] = useState<number | null>(null);

    const { addOption, updateOption, deleteOption, processing, activeAction, activeOptionId } = useOptionActions({
        optionGroupId,
        onSuccess: onOptionsChange,
        onError,
    });

    const handleAddOption = async () => {
        const success = await addOption(newOption);
        if (success) {
            setNewOption({ name: "", price: "" });
        }
    };

    const handleStartEdit = (option: Option) => {
        setEditingId(option.id);
        setEditingData({ name: option.name, price: option.price });
    };

    const handleCancelEdit = () => {
        setEditingId(null);
        setEditingData({ name: "", price: 0 });
    };

    const handleSaveEdit = async (optionId: number) => {
        const success = await updateOption(optionId, editingData);
        if (success) {
            setEditingId(null);
            setEditingData({ name: "", price: 0 });
        }
    };

    const handleDeleteOption = async (optionId: number) => {
        setConfirmDeleteId(null);
        await deleteOption(optionId);
    };

    const formatPrice = (price: number) => {
        if (price === 0) return "±0円";
        return price > 0 ? `+${price}円` : `${price}円`;
    };

    return (
        <div className="space-y-4">
            <h4 className="text-md font-medium text-ink">オプション一覧</h4>

            {/* 既存オプション を明示し、実装意図の誤読を防ぐ。 */}
            <div className="space-y-2">
                {options.length === 0 ? (
                    <p className="text-sm text-muted">オプションがありません</p>
                ) : (
                    options.map((option) => (
                        <div key={option.id} className="flex items-center justify-between p-3 bg-surface">
                            {editingId === option.id ? (
                                <div className="flex-1 flex items-center gap-2">
                                    <input
                                        type="text"
                                        value={editingData.name}
                                        onChange={(e) =>
                                            setEditingData({
                                                ...editingData,
                                                name: e.target.value,
                                            })
                                        }
                                        className="flex-1 rounded-md border-edge-strong focus:border-primary focus:ring-primary sm:text-sm"
                                        placeholder="オプション名"
                                    />
                                    <input
                                        type="number"
                                        step="1"
                                        value={editingData.price}
                                        onChange={(e) =>
                                            setEditingData({
                                                ...editingData,
                                                price: e.target.value === "" ? "" : parseInt(e.target.value),
                                            })
                                        }
                                        className="w-24 rounded-md border-edge-strong focus:border-primary focus:ring-primary sm:text-sm"
                                        placeholder="価格"
                                    />
                                    <button
                                        onClick={() => {
                                            void handleSaveEdit(option.id);
                                        }}
                                        disabled={processing}
                                        aria-busy={
                                            (activeAction === "save" && activeOptionId === option.id) || undefined
                                        }
                                        className="text-sm text-green-600 hover:text-green-800 disabled:opacity-50 inline-flex items-center justify-center min-w-8"
                                    >
                                        {activeAction === "save" && activeOptionId === option.id ? (
                                            <>
                                                <span
                                                    className="h-3.5 w-3.5 animate-spin rounded-full border-2 border-green-600/40 border-t-green-600"
                                                    aria-hidden="true"
                                                />
                                                <span className="sr-only">処理中</span>
                                            </>
                                        ) : (
                                            "保存"
                                        )}
                                    </button>
                                    <button
                                        onClick={handleCancelEdit}
                                        className="text-sm text-ink-light hover:text-ink"
                                    >
                                        キャンセル
                                    </button>
                                </div>
                            ) : (
                                <>
                                    <div className="flex items-center gap-4">
                                        <span className="text-sm font-medium text-ink">{option.name}</span>
                                        <span className="text-sm text-muted">{formatPrice(option.price)}</span>
                                        {!option.is_active && <span className="text-xs text-muted-light">(無効)</span>}
                                    </div>
                                    <div className="flex gap-2">
                                        <button
                                            onClick={() => handleStartEdit(option)}
                                            className="text-sm text-primary-dark hover:text-primary-dark"
                                        >
                                            編集
                                        </button>
                                        <button
                                            onClick={() => setConfirmDeleteId(option.id)}
                                            disabled={processing}
                                            className="text-sm text-red-600 hover:text-red-800 disabled:opacity-50"
                                        >
                                            削除
                                        </button>
                                    </div>
                                </>
                            )}
                        </div>
                    ))
                )}
            </div>

            <ConfirmDialog
                show={confirmDeleteId !== null}
                onClose={() => setConfirmDeleteId(null)}
                onConfirm={() => {
                    if (confirmDeleteId !== null) {
                        void handleDeleteOption(confirmDeleteId);
                    }
                }}
                title="オプション削除"
                confirmLabel="削除"
                tone="danger"
                processing={processing && activeAction === "delete"}
            >
                <p className="mt-2 text-sm text-muted">このオプションを削除してもよろしいですか？</p>
            </ConfirmDialog>

            {/* 新規オプション追加 を明示し、実装意図の誤読を防ぐ。 */}
            <div className="border-t pt-4">
                <h5 className="text-sm font-medium text-ink-light mb-2">新しいオプションを追加</h5>
                <div className="flex items-center gap-2">
                    <input
                        type="text"
                        value={newOption.name}
                        onChange={(e) => setNewOption({ ...newOption, name: e.target.value })}
                        className="flex-1 rounded-md border-edge-strong focus:border-primary focus:ring-primary sm:text-sm"
                        placeholder="オプション名"
                    />
                    <input
                        type="number"
                        step="1"
                        value={newOption.price}
                        onChange={(e) =>
                            setNewOption({
                                ...newOption,
                                price: e.target.value === "" ? "" : parseInt(e.target.value),
                            })
                        }
                        className="w-24 rounded-md border-edge-strong focus:border-primary focus:ring-primary sm:text-sm"
                        placeholder="追加料金"
                    />
                    <button
                        onClick={() => {
                            void handleAddOption();
                        }}
                        disabled={processing || !newOption.name.trim()}
                        aria-busy={activeAction === "add" || undefined}
                        className="px-4 py-2 text-sm font-medium text-white bg-primary-dark hover:bg-primary-dark disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center justify-center min-w-14"
                    >
                        {activeAction === "add" ? (
                            <>
                                <span
                                    className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                                    aria-hidden="true"
                                />
                                <span className="sr-only">処理中</span>
                            </>
                        ) : (
                            "追加"
                        )}
                    </button>
                </div>
                <p className="mt-1 text-xs text-gray-500">追加料金は0円でも設定できます（+/-も可、1円単位）</p>
            </div>
        </div>
    );
}
