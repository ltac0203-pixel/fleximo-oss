import PrimaryButton from "@/Components/PrimaryButton";
import CategoryList from "@/Components/Tenant/Menu/CategoryList";
import { api } from "@/api";
import { ENDPOINTS } from "@/api/endpoints";
import CreateCategoryModal from "@/Components/Tenant/Menu/CreateCategoryModal";
import ConfirmDeleteModal from "@/Components/ConfirmDeleteModal";
import EditCategoryModal from "@/Components/Tenant/Menu/EditCategoryModal";
import ErrorAlert from "@/Components/UI/ErrorAlert";
import ToastContainer from "@/Components/UI/ToastContainer";
import { useToast } from "@/Hooks/useToast";
import { useModalManager } from "@/Hooks/useModalManager";
import { useHelpPanel } from "@/Hooks/useHelpPanel";
import TenantLayout from "@/Layouts/TenantLayout";
import { MenuCategoriesIndexProps, MenuCategory, PageProps } from "@/types";
import { logger } from "@/Utils/logger";
import { Head, router, usePage } from "@inertiajs/react";
import { useState } from "react";
import HelpButton from "@/Components/Tenant/HelpButton";
import HelpPanel from "@/Components/Tenant/HelpPanel";
import { tenantHelpContent } from "@/data/tenantHelpContent";
import MenuTabs from "@/Components/Tenant/Menu/MenuTabs";

export default function Index({ categories }: MenuCategoriesIndexProps) {
    const { auth } = usePage<PageProps>().props;
    const canManage = auth.user!.is_tenant_admin;
    const [generalError, setGeneralError] = useState("");
    const { showHelp, openHelp, closeHelp } = useHelpPanel();
    const { toasts, showToast, hideToast } = useToast();
    const {
        showCreateModal,
        showEditModal,
        showDeleteModal,
        selectedItem: selectedCategory,
        openCreate,
        openEdit,
        openDelete,
        closeCreate,
        closeEdit,
        closeDelete,
    } = useModalManager<MenuCategory>();

    const handleReorder = async (orderedIds: number[]) => {
        try {
            const { error } = await api.post(ENDPOINTS.tenant.menu.categoriesReorder, { ordered_ids: orderedIds });
            if (error) {
                throw new Error(error);
            }

            router.reload({ only: ["categories"] });
        } catch (error) {
            logger.error("Category reorder failed", error, {
                orderedIdsCount: orderedIds.length,
            });
            setGeneralError("並び替えに失敗しました。もう一度お試しください。");
        }
    };

    return (
        <TenantLayout title="カテゴリ管理">
            <Head title="カテゴリ管理" />

            <MenuTabs activeTab="categories" />

            <div className="overflow-hidden bg-white">
                <div className="p-6">
                    {generalError && (
                        <ErrorAlert
                            message={generalError}
                            onRetry={() => {
                                setGeneralError("");
                                router.reload({ only: ["categories"] });
                            }}
                            className="mb-4"
                        />
                    )}
                    <div className="flex justify-between items-center mb-6">
                        <div className="flex items-center gap-2">
                            <h3 className="text-lg font-medium text-gray-900">カテゴリ一覧</h3>
                            <HelpButton onClick={openHelp} />
                        </div>
                        {canManage && (
                            <PrimaryButton onClick={openCreate}>
                                カテゴリを追加
                            </PrimaryButton>
                        )}
                    </div>

                    {canManage && (
                        <p className="text-sm text-gray-500 mb-4">ドラッグ&ドロップで並び順を変更できます</p>
                    )}

                    <CategoryList
                        categories={categories}
                        onEdit={canManage ? openEdit : undefined}
                        onDelete={canManage ? openDelete : undefined}
                        onReorder={
                            canManage
                                ? (orderedIds) => {
                                      void handleReorder(orderedIds);
                                  }
                                : undefined
                        }
                    />
                </div>
            </div>

            {canManage && (
                <>
                    <CreateCategoryModal
                        show={showCreateModal}
                        onClose={closeCreate}
                        onSuccess={(msg) => showToast({ type: "success", message: msg })}
                    />

                    <EditCategoryModal
                        show={showEditModal}
                        category={selectedCategory}
                        onClose={closeEdit}
                        onSuccess={(msg) => showToast({ type: "success", message: msg })}
                    />

                    <ConfirmDeleteModal
                        show={showDeleteModal}
                        title="カテゴリを削除"
                        targetName={selectedCategory?.name}
                        apiEndpoint={`/api/tenant/menu/categories/${selectedCategory?.id}`}
                        reloadOnly={["categories"]}
                        successMessage="カテゴリを削除しました"
                        onClose={closeDelete}
                        onSuccess={(msg) => showToast({ type: "success", message: msg })}
                    />
                </>
            )}

            <HelpPanel
                open={showHelp}
                onClose={closeHelp}
                content={tenantHelpContent["menu-categories"]}
            />

            <ToastContainer toasts={toasts} onClose={hideToast} />
        </TenantLayout>
    );
}
