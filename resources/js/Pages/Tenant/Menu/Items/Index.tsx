import PageHeader from "@/Components/PageHeader";
import Button from "@/Components/UI/Button";
import ConfirmDeleteModal from "@/Components/ConfirmDeleteModal";
import ItemCard from "@/Components/Tenant/Menu/ItemCard";
import ToastContainer from "@/Components/UI/ToastContainer";
import { useToast } from "@/Hooks/useToast";
import { useHelpPanel } from "@/Hooks/useHelpPanel";
import TenantLayout from "@/Layouts/TenantLayout";
import { MenuItem, MenuItemsIndexProps, PageProps } from "@/types";
import { Head, Link, usePage } from "@inertiajs/react";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useWindowVirtualizer } from "@tanstack/react-virtual";
import HelpButton from "@/Components/Tenant/HelpButton";
import HelpPanel from "@/Components/Tenant/HelpPanel";
import { tenantHelpContent } from "@/data/tenantHelpContent";
import MenuTabs from "@/Components/Tenant/Menu/MenuTabs";

export default function Index({ items, categories }: MenuItemsIndexProps) {
    const { auth, flash } = usePage<PageProps>().props;
    const canManage = auth.user!.is_tenant_admin;
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [selectedItem, setSelectedItem] = useState<MenuItem | null>(null);
    const [filterCategoryId, setFilterCategoryId] = useState<number | null>(null);
    const { showHelp, openHelp, closeHelp } = useHelpPanel();
    const { toasts, showToast, hideToast } = useToast();

    useEffect(() => {
        if (flash?.success) {
            showToast({ type: "success", message: flash.success });
        }
    }, [flash?.success, showToast]);

    const handleDelete = useCallback((item: MenuItem) => {
        setSelectedItem(item);
        setShowDeleteModal(true);
    }, []);

    const closeDeleteModal = useCallback(() => {
        setShowDeleteModal(false);
        setSelectedItem(null);
    }, []);

    const handleSoldOutToggle = useCallback((msg: string) => showToast({ type: "success", message: msg }), [showToast]);

    const safeItems = useMemo(() => items ?? [], [items]);
    const safeCategories = useMemo(() => categories ?? [], [categories]);

    const filteredItems = useMemo(
        () =>
            filterCategoryId
                ? safeItems.filter((item) => item.categories?.some((cat) => cat.id === filterCategoryId))
                : safeItems,
        [safeItems, filterCategoryId],
    );

    const listRef = useRef<HTMLDivElement>(null);

    const virtualizer = useWindowVirtualizer({
        count: filteredItems.length,
        estimateSize: () => 120,
        overscan: 5,
        gap: 16,
        scrollMargin: listRef.current?.offsetTop ?? 0,
    });

    return (
        <TenantLayout title="商品管理">
            <Head title="商品管理" />

            <MenuTabs activeTab="items" />

            <div className="overflow-hidden bg-white">
                <div className="p-6">
                    <PageHeader
                        title="商品一覧"
                        help={<HelpButton onClick={openHelp} />}
                        actions={
                            canManage ? (
                                <Link href={route("tenant.menu.items.create")}>
                                    <Button variant="primary">商品を追加</Button>
                                </Link>
                            ) : undefined
                        }
                    />

                    {/* カテゴリフィルター */}
                    {safeCategories.length > 0 && (
                        <div className="mb-4">
                            <div className="flex flex-wrap gap-2">
                                <button
                                    onClick={() => setFilterCategoryId(null)}
                                    className={`px-3 py-1 text-sm rounded-full border ${
                                        filterCategoryId === null
                                            ? "bg-indigo-600 text-white border-indigo-600"
                                            : "bg-white text-gray-700 border-gray-300 hover:bg-gray-50"
                                    }`}
                                >
                                    すべて
                                </button>
                                {safeCategories.map((category) => (
                                    <button
                                        key={category.id}
                                        onClick={() => setFilterCategoryId(category.id)}
                                        className={`px-3 py-1 text-sm rounded-full border ${
                                            filterCategoryId === category.id
                                                ? "bg-indigo-600 text-white border-indigo-600"
                                                : "bg-white text-gray-700 border-gray-300 hover:bg-gray-50"
                                        }`}
                                    >
                                        {category.name}
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

                    {filteredItems.length === 0 ? (
                        <div className="text-center py-8 text-gray-500">
                            {filterCategoryId ? "このカテゴリには商品がありません" : "商品がまだありません"}
                        </div>
                    ) : (
                        <div
                            ref={listRef}
                            style={{
                                height: virtualizer.getTotalSize(),
                                position: "relative",
                            }}
                        >
                            {virtualizer.getVirtualItems().map((virtualItem) => (
                                <div
                                    key={filteredItems[virtualItem.index].id}
                                    ref={virtualizer.measureElement}
                                    data-index={virtualItem.index}
                                    style={{
                                        position: "absolute",
                                        top: 0,
                                        left: 0,
                                        width: "100%",
                                        transform: `translateY(${virtualItem.start - (listRef.current?.offsetTop ?? 0)}px)`,
                                    }}
                                >
                                    <ItemCard
                                        item={filteredItems[virtualItem.index]}
                                        onDelete={canManage ? handleDelete : undefined}
                                        canManage={canManage}
                                        onSoldOutToggle={handleSoldOutToggle}
                                    />
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {canManage && (
                <ConfirmDeleteModal
                    show={showDeleteModal}
                    title="商品を削除"
                    targetName={selectedItem?.name}
                    apiEndpoint={`/api/tenant/menu/items/${selectedItem?.id}`}
                    reloadOnly={["items"]}
                    successMessage="商品を削除しました"
                    onClose={closeDeleteModal}
                    onSuccess={(msg) => showToast({ type: "success", message: msg })}
                />
            )}

            <HelpPanel open={showHelp} onClose={closeHelp} content={tenantHelpContent["menu-items"]} />

            <ToastContainer toasts={toasts} onClose={hideToast} />
        </TenantLayout>
    );
}
