import FormActions from "@/Components/FormActions";
import PageHeader from "@/Components/PageHeader";
import PrimaryButton from "@/Components/PrimaryButton";
import SecondaryButton from "@/Components/SecondaryButton";
import OptionGroupForm, { FormData, FormErrors } from "@/Components/Tenant/Menu/OptionGroupForm";
import { useApiFormSubmission } from "@/Hooks/useApiFormSubmission";
import { useHelpPanel } from "@/Hooks/useHelpPanel";
import { api } from "@/api";
import TenantLayout from "@/Layouts/TenantLayout";
import { OptionGroupCreateProps } from "@/types";
import { Head, router } from "@inertiajs/react";
import { FormEvent, FormEventHandler, useState } from "react";
import HelpButton from "@/Components/Common/Help/HelpButton";
import HelpPanel from "@/Components/Common/Help/HelpPanel";
import { tenantHelpContent } from "@/data/tenantHelpContent";

export default function Create(_props: OptionGroupCreateProps) {
    const { processing, errors, generalError, setGeneralError, submit } = useApiFormSubmission<FormErrors>();
    const { showHelp, openHelp, closeHelp } = useHelpPanel();
    const [formData, setFormData] = useState<FormData>({
        name: "",
        required: false,
        min_select: 0,
        max_select: 1,
        is_active: true,
    });

    const submitOptionGroup = async () => {
        await submit(
            () => api.post<{ data: { id: number } }, { errors?: FormErrors }>("/api/tenant/option-groups", formData),
            {
                logMessage: "Option group creation failed",
                onSuccess: (response) => {
                    if (!response.data?.data?.id) {
                        setGeneralError("オプショングループの作成に失敗しました。");
                        return;
                    }

                    // 作成直後に詳細編集へ送って、連続して選択肢を登録できる導線にする。 を明示し、実装意図の誤読を防ぐ。
                    router.visit(
                        route("tenant.menu.option-groups.edit", {
                            optionGroup: response.data.data.id,
                        }),
                    );
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

    return (
        <TenantLayout title="オプショングループ追加">
            <Head title="オプショングループ追加" />

            <div className="mx-auto max-w-2xl">
                <PageHeader title="オプショングループ追加" help={<HelpButton onClick={openHelp} />} className="mb-3" />
                <div className="overflow-hidden bg-white">
                    <form onSubmit={handleSubmit} className="p-6">
                        {generalError && <p className="mb-4 text-sm text-red-600">{generalError}</p>}
                        <OptionGroupForm formData={formData} errors={errors} onChange={setFormData} />

                        <p className="mt-4 text-sm text-gray-500">グループを作成した後、オプションを追加できます。</p>

                        <FormActions>
                            <SecondaryButton onClick={handleCancel}>キャンセル</SecondaryButton>
                            <PrimaryButton type="submit" disabled={processing} isBusy={processing}>
                                作成
                            </PrimaryButton>
                        </FormActions>
                    </form>
                </div>
            </div>

            <HelpPanel open={showHelp} onClose={closeHelp} content={tenantHelpContent["menu-option-groups-create"]} />
        </TenantLayout>
    );
}
