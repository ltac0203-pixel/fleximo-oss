import { Dialog, DialogPanel, DialogTitle } from "@headlessui/react";
import { router } from "@inertiajs/react";
import { ReorderAddedItem, ReorderResponse, ReorderSkippedItem } from "@/types";
import { withStableKeys } from "@/Utils/stableKeys";

interface ReorderResultModalProps {
    result: ReorderResponse | null;
    onClose: () => void;
}

function formatPrice(price: number): string {
    return `¥${price.toLocaleString()}`;
}

type ReorderAddedOption = ReorderAddedItem["options_added"][number];

function getAddedItemBaseKey(item: ReorderAddedItem): string {
    return [
        item.menu_item_id,
        item.order_item_name,
        item.quantity,
        item.original_unit_price,
        item.current_unit_price,
        item.price_changed ? "changed" : "same",
        item.options_skipped.join(","),
    ].join("|");
}

function getAddedOptionBaseKey(option: ReorderAddedOption): string {
    return [
        option.name,
        option.original_price,
        option.current_price,
        option.price_changed ? "changed" : "same",
    ].join("|");
}

function getSkippedItemBaseKey(item: ReorderSkippedItem): string {
    return [item.menu_item_id ?? "missing", item.order_item_name, item.quantity, item.reason, item.reason_label].join("|");
}

export default function ReorderResultModal({ result, onClose }: ReorderResultModalProps) {
    if (!result) return null;

    const { added_items, skipped_items, summary } = result;
    const addedItemsWithKeys = withStableKeys(added_items, getAddedItemBaseKey);
    const skippedItemsWithKeys = withStableKeys(skipped_items, getSkippedItemBaseKey);

    const goToCart = () => {
        onClose();
        router.visit(route("order.cart.show"));
    };

    return (
        <Dialog open={result !== null} onClose={onClose} className="relative z-50">
            <div className="fixed inset-0 bg-black/30" aria-hidden="true" />

            <div className="fixed inset-0 flex items-center justify-center p-4">
                <DialogPanel className="mx-auto max-w-md w-full bg-white rounded-lg shadow-xl max-h-[80vh] flex flex-col">
                    <div className="p-6 flex-shrink-0">
                        <DialogTitle className="text-lg font-bold text-ink">
                            再注文結果
                        </DialogTitle>
                        <p className="mt-1 text-sm text-muted">
                            {summary.items_added}品追加
                            {summary.items_skipped > 0 && ` / ${summary.items_skipped}品スキップ`}
                        </p>
                    </div>

                    <div className="px-6 overflow-y-auto flex-1">
                        {/* 追加成功セクション */}
                        {added_items.length > 0 && (
                            <div className="mb-4">
                                <h3 className="text-sm font-medium text-green-800 bg-green-50 px-3 py-2 rounded-t">
                                    カートに追加しました
                                </h3>
                                <div className="border border-green-100 rounded-b divide-y divide-green-50">
                                    {addedItemsWithKeys.map(({ item, key: itemKey }) => (
                                        <div key={itemKey} className="px-3 py-2">
                                            <div className="flex justify-between items-start">
                                                <span className="text-sm font-medium text-ink">
                                                    {item.order_item_name} x{item.quantity}
                                                </span>
                                                <span className="text-sm text-ink-light">
                                                    {item.price_changed ? (
                                                        <>
                                                            <span className="line-through text-muted-light mr-1">
                                                                {formatPrice(item.original_unit_price)}
                                                            </span>
                                                            <span className="text-orange-600 font-medium">
                                                                {formatPrice(item.current_unit_price)}
                                                            </span>
                                                        </>
                                                    ) : (
                                                        formatPrice(item.current_unit_price)
                                                    )}
                                                </span>
                                            </div>
                                            {item.options_added.length > 0 && (
                                                <div className="mt-1 space-y-0.5">
                                                    {withStableKeys(item.options_added, getAddedOptionBaseKey).map(
                                                        ({ item: opt, key: optionKey }) => (
                                                            <div
                                                                key={`${itemKey}|${optionKey}`}
                                                                className="text-xs text-muted flex justify-between"
                                                            >
                                                            <span>+ {opt.name}</span>
                                                            <span>
                                                                {opt.price_changed ? (
                                                                    <>
                                                                        <span className="line-through mr-1">
                                                                            {formatPrice(opt.original_price)}
                                                                        </span>
                                                                        <span className="text-orange-600">
                                                                            {formatPrice(opt.current_price)}
                                                                        </span>
                                                                    </>
                                                                ) : (
                                                                    formatPrice(opt.current_price)
                                                                )}
                                                            </span>
                                                            </div>
                                                        ),
                                                    )}
                                                </div>
                                            )}
                                            {item.options_skipped.length > 0 && (
                                                <p className="mt-1 text-xs text-amber-600">
                                                    一部オプションは利用不可のためスキップ: {item.options_skipped.join(", ")}
                                                </p>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* スキップセクション */}
                        {skipped_items.length > 0 && (
                            <div className="mb-4">
                                <h3 className="text-sm font-medium text-amber-800 bg-amber-50 px-3 py-2 rounded-t">
                                    追加できませんでした
                                </h3>
                                <div className="border border-amber-100 rounded-b divide-y divide-amber-50">
                                    {skippedItemsWithKeys.map(({ item, key }) => (
                                        <div key={key} className="px-3 py-2">
                                            <div className="flex justify-between items-start">
                                                <span className="text-sm text-ink-light">
                                                    {item.order_item_name} x{item.quantity}
                                                </span>
                                            </div>
                                            <p className="text-xs text-amber-600 mt-0.5">
                                                {item.reason_label}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* 既存カート通知 */}
                        {summary.had_existing_cart_items && (
                            <p className="text-xs text-muted mb-4">
                                既存のカート内商品はそのまま保持されています。
                            </p>
                        )}
                    </div>

                    {/* ボタン */}
                    <div className="p-6 flex-shrink-0 border-t border-edge flex gap-3">
                        <button
                            type="button"
                            onClick={onClose}
                            className="flex-1 px-4 py-2.5 text-sm font-medium text-ink-light bg-surface-dim rounded-lg hover:bg-surface-dim/80 transition-colors"
                        >
                            閉じる
                        </button>
                        <button
                            type="button"
                            onClick={goToCart}
                            className="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-sky-500 rounded-lg hover:bg-sky-600 transition-colors"
                        >
                            カートを見る
                        </button>
                    </div>
                </DialogPanel>
            </div>
        </Dialog>
    );
}
