import PageHeader from "@/Components/PageHeader";
import PrimaryButton from "@/Components/PrimaryButton";
import AvailabilityBadge from "@/Components/Tenant/Menu/AvailabilityBadge";
import ConfirmDeleteModal from "@/Components/ConfirmDeleteModal";
import ToastContainer from "@/Components/UI/ToastContainer";
import { useToast } from "@/Hooks/useToast";
import { useHelpPanel } from "@/Hooks/useHelpPanel";
import TenantLayout from "@/Layouts/TenantLayout";
import { OptionGroup, OptionGroupsIndexProps, PageProps } from "@/types";
import { Head, Link, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";
import HelpButton from "@/Components/Tenant/HelpButton";
import HelpPanel from "@/Components/Tenant/HelpPanel";
import { tenantHelpContent } from "@/data/tenantHelpContent";
import MenuTabs from "@/Components/Tenant/Menu/MenuTabs";

export default function Index({ optionGroups }: OptionGroupsIndexProps) {
    const { auth, flash } = usePage<PageProps>().props;
    const canManage = auth.user!.is_tenant_admin;
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [selectedGroup, setSelectedGroup] = useState<OptionGroup | null>(null);
    const { showHelp, openHelp, closeHelp } = useHelpPanel();
    const { toasts, showToast, hideToast } = useToast();

    useEffect(() => {
        if (flash?.success) {
            showToast({ type: "success", message: flash.success });
        }
    }, [flash?.success, showToast]);

    const handleDelete = (group: OptionGroup) => {
        setSelectedGroup(group);
        setShowDeleteModal(true);
    };

    const closeDeleteModal = () => {
        setShowDeleteModal(false);
        setSelectedGroup(null);
    };

    return (
        <TenantLayout title="オプション管理">
            <Head title="オプション管理" />

            <MenuTabs activeTab="optionGroups" />

            <div className="overflow-hidden bg-white">
                <div className="p-6">
                    <PageHeader
                        title="オプショングループ一覧"
                        help={<HelpButton onClick={openHelp} />}
                        actions={
                            canManage ? (
                                <Link href={route("tenant.menu.option-groups.create")}>
                                    <PrimaryButton>グループを追加</PrimaryButton>
                                </Link>
                            ) : undefined
                        }
                    />

                    {optionGroups.length === 0 ? (
                        <div className="text-center py-8 text-gray-500">オプショングループがまだありません</div>
                    ) : (
                        <div className="space-y-4">
                            {optionGroups.map((group) => (
                                <div key={group.id} className="border p-4">
                                    <div className="flex items-center justify-between mb-3">
                                        <div className="flex items-center gap-3">
                                            <h4 className="text-lg font-medium text-gray-900">{group.name}</h4>
                                            <AvailabilityBadge isActive={group.is_active} />
                                            {group.required && (
                                                <span className="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-sky-100 text-sky-800">
                                                    必須
                                                </span>
                                            )}
                                        </div>
                                        {canManage && (
                                            <div className="flex gap-2">
                                                <Link
                                                    href={route("tenant.menu.option-groups.edit", {
                                                        optionGroup: group.id,
                                                    })}
                                                    className="text-sm text-indigo-600 hover:text-indigo-800"
                                                >
                                                    編集
                                                </Link>
                                                <button
                                                    onClick={() => handleDelete(group)}
                                                    className="text-sm text-red-600 hover:text-red-800"
                                                >
                                                    削除
                                                </button>
                                            </div>
                                        )}
                                    </div>

                                    <div className="text-sm text-gray-500 mb-2">
                                        選択数: {group.min_select}〜{group.max_select}個
                                    </div>

                                    {group.options && group.options.length > 0 ? (
                                        <div className="flex flex-wrap gap-2">
                                            {group.options.map((option) => (
                                                <span
                                                    key={option.id}
                                                    className={`inline-flex items-center px-2 py-1 text-sm ${
                                                        option.is_active
                                                            ? "bg-gray-100 text-gray-700"
                                                            : "bg-gray-50 text-gray-400"
                                                    }`}
                                                >
                                                    {option.name}
                                                    {option.price !== 0 && (
                                                        <span className="ml-1 text-xs">
                                                            ({option.price > 0 ? "+" : ""}
                                                            {option.price}
                                                            円)
                                                        </span>
                                                    )}
                                                </span>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-sm text-gray-400">オプションなし</p>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {canManage && (
                <ConfirmDeleteModal
                    show={showDeleteModal}
                    title="オプショングループを削除"
                    targetName={selectedGroup?.name}
                    message={`「${selectedGroup?.name}」を削除してもよろしいですか？`}
                    warningMessage="このグループに含まれる全てのオプションも削除されます。この操作は取り消せません。"
                    apiEndpoint={`/api/tenant/option-groups/${selectedGroup?.id}`}
                    reloadOnly={["optionGroups"]}
                    successMessage="オプショングループを削除しました"
                    onClose={closeDeleteModal}
                    onSuccess={(msg) => showToast({ type: "success", message: msg })}
                />
            )}

            <HelpPanel open={showHelp} onClose={closeHelp} content={tenantHelpContent["menu-option-groups"]} />

            <ToastContainer toasts={toasts} onClose={hideToast} />
        </TenantLayout>
    );
}
