import PrimaryButton from "@/Components/PrimaryButton";
import CreateStaffModal from "@/Components/Tenant/CreateStaffModal";
import ConfirmDeleteModal from "@/Components/ConfirmDeleteModal";
import EditStaffModal from "@/Components/Tenant/EditStaffModal";
import StaffTable from "@/Components/Tenant/StaffTable";
import ToastContainer from "@/Components/UI/ToastContainer";
import { useToast } from "@/Hooks/useToast";
import { useModalManager } from "@/Hooks/useModalManager";
import { useHelpPanel } from "@/Hooks/useHelpPanel";
import TenantLayout from "@/Layouts/TenantLayout";
import { PageProps, Staff, StaffIndexProps } from "@/types";
import { Head, usePage } from "@inertiajs/react";
import HelpButton from "@/Components/Tenant/HelpButton";
import HelpPanel from "@/Components/Tenant/HelpPanel";
import { tenantHelpContent } from "@/data/tenantHelpContent";

export default function Index({ staff }: StaffIndexProps) {
    const { auth } = usePage<PageProps>().props;
    const canManage = auth.user!.is_tenant_admin;
    const safeStaff = staff ?? [];
    const { showHelp, openHelp, closeHelp } = useHelpPanel();
    const { toasts, showToast, hideToast } = useToast();
    const {
        showCreateModal,
        showEditModal,
        showDeleteModal,
        selectedItem: selectedStaff,
        openCreate,
        openEdit,
        openDelete,
        closeCreate,
        closeEdit,
        closeDelete,
    } = useModalManager<Staff>();

    return (
        <TenantLayout title="スタッフ管理">
            <Head title="スタッフ管理" />

            <div className="overflow-hidden bg-white">
                <div className="p-6">
                    <div className="flex justify-between items-center mb-6">
                        <div className="flex items-center gap-2">
                            <h3 className="text-lg font-medium text-gray-900">スタッフ一覧</h3>
                            <HelpButton onClick={openHelp} />
                        </div>
                        {canManage && (
                            <PrimaryButton onClick={openCreate}>
                                スタッフを追加
                            </PrimaryButton>
                        )}
                    </div>

                    <StaffTable
                        staff={safeStaff}
                        onEdit={canManage ? openEdit : undefined}
                        onDelete={canManage ? openDelete : undefined}
                    />
                </div>
            </div>

            {canManage && (
                <>
                    <CreateStaffModal
                        show={showCreateModal}
                        onClose={closeCreate}
                        onSuccess={(msg) => showToast({ type: "success", message: msg })}
                    />

                    <EditStaffModal
                        show={showEditModal}
                        staff={selectedStaff}
                        onClose={closeEdit}
                        onSuccess={(msg) => showToast({ type: "success", message: msg })}
                    />

                    <ConfirmDeleteModal
                        show={showDeleteModal}
                        title="スタッフを削除"
                        targetName={selectedStaff?.name}
                        apiEndpoint={`/api/tenant/staff/${selectedStaff?.id}`}
                        reloadOnly={["staff"]}
                        successMessage="スタッフを削除しました"
                        onClose={closeDelete}
                        onSuccess={(msg) => showToast({ type: "success", message: msg })}
                    />
                </>
            )}

            <HelpPanel open={showHelp} onClose={closeHelp} content={tenantHelpContent["staff-index"]} />

            <ToastContainer toasts={toasts} onClose={hideToast} />
        </TenantLayout>
    );
}
