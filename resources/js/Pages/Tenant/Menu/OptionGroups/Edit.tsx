import Breadcrumb from "@/Components/Breadcrumb";
import FormActions from "@/Components/FormActions";
import PageHeader from "@/Components/PageHeader";
import Button from "@/Components/UI/Button";
import OptionGroupForm, { FormData, FormErrors } from "@/Components/Tenant/Menu/OptionGroupForm";
import OptionList from "@/Components/Tenant/Menu/OptionList";
import { useApiFormSubmission } from "@/Hooks/useApiFormSubmission";
import { useHelpPanel } from "@/Hooks/useHelpPanel";
import { api, ENDPOINTS } from "@/api";
import TenantLayout from "@/Layouts/TenantLayout";
import { OptionGroupEditProps } from "@/types";
import { Head, router } from "@inertiajs/react";
import { FormEvent, FormEventHandler, useState } from "react";
import HelpButton from "@/Components/Common/Help/HelpButton";
import HelpPanel from "@/Components/Common/Help/HelpPanel";
import { tenantHelpContent } from "@/data/tenantHelpContent";

export default function Edit({ optionGroup }: OptionGroupEditProps) {
    const { processing, errors, generalError, setGeneralError, submit } = useApiFormSubmission<FormErrors>();
    const { showHelp, openHelp, closeHelp } = useHelpPanel();
    const [formData, setFormData] = useState<FormData>({
        name: optionGroup.name,
        required: optionGroup.required,
        min_select: optionGroup.min_select,
        max_select: optionGroup.max_select,
        is_active: optionGroup.is_active,
    });

    const submitOptionGroup = async () => {
        await submit(
            () => api.patch<unknown, { errors?: FormErrors }>(ENDPOINTS.tenant.optionGroup(optionGroup.id), formData),
            {
                logMessage: "Option group update failed",
                logContext: {
                    optionGroupId: optionGroup.id,
                },
                onSuccess: () => {
                    router.visit(route("tenant.menu.option-groups.page"));
                },
            },
        );
    };

    const handleSubmit: FormEventHandler = (e: FormEvent) => {
        e.preventDefault();
        void submitOptionGroup();
    };

    const handleCancel = () => {
        router.visit(route("tenant.menu.option-groups.page"));
    };

    const handleOptionsChange = () => {
        // API更新後の最新並び順と状態を揃え、編集画面の表示齟齬を防ぐ。
        router.reload({ only: ["optionGroup"] });
    };

    return (
        <TenantLayout title="オプショングループ編集">
            <Head title="オプショングループ編集" />

            <div className="mx-auto max-w-2xl space-y-6">
                <Breadcrumb
                    items={[
                        { label: "メニュー", href: route("tenant.menu.items.page") },
                        { label: "オプショングループ", href: route("tenant.menu.option-groups.page") },
                        { label: optionGroup.name },
                    ]}
                />
                {/* グループ設定へ最短到達できるよう、主編集領域を先頭に置くため。 */}
                <div className="overflow-hidden bg-white">
                    <form onSubmit={handleSubmit} className="p-6">
                        {generalError && <p className="mb-4 text-sm text-red-600">{generalError}</p>}
                        <PageHeader title="グループ設定" help={<HelpButton onClick={openHelp} />} className="mb-4" />
                        <OptionGroupForm formData={formData} errors={errors} onChange={setFormData} />

                        <FormActions className="mt-6">
                            <Button variant="secondary" type="button" onClick={handleCancel}>
                                キャンセル
                            </Button>
                            <Button
                                variant="primary"
                                type="submit"
                                disabled={processing}
                                isBusy={processing}
                            >
                                更新
                            </Button>
                        </FormActions>
                    </form>
                </div>

                {/* 設定変更と選択肢編集を往復せず完了させるため、同一画面に集約する。 */}
                <div className="overflow-hidden bg-white">
                    <div className="p-6">
                        <OptionList
                            optionGroupId={optionGroup.id}
                            options={optionGroup.options || []}
                            onOptionsChange={handleOptionsChange}
                            onError={setGeneralError}
                        />
                    </div>
                </div>
            </div>

            <HelpPanel open={showHelp} onClose={closeHelp} content={tenantHelpContent["menu-option-groups-edit"]} />
        </TenantLayout>
    );
}
