import FormActions from "@/Components/FormActions";
import PageHeader from "@/Components/PageHeader";
import PrimaryButton from "@/Components/PrimaryButton";
import SecondaryButton from "@/Components/SecondaryButton";
import ItemForm from "@/Components/Tenant/Menu/ItemForm";
import { FormData, FormErrors } from "@/Components/Tenant/Menu/ItemForm/types";
import { useApiFormSubmission } from "@/Hooks/useApiFormSubmission";
import { useActiveToggle } from "@/Hooks/useActiveToggle";
import { useHelpPanel } from "@/Hooks/useHelpPanel";
import { api } from "@/api";
import TenantLayout from "@/Layouts/TenantLayout";
import { MenuItemCreateProps } from "@/types";
import { Head, router } from "@inertiajs/react";
import { FormEvent, FormEventHandler, useState } from "react";
import ActiveToggleModal from "@/Components/Tenant/Menu/ActiveToggleModal";
import HelpButton from "@/Components/Common/Help/HelpButton";
import HelpPanel from "@/Components/Common/Help/HelpPanel";
import { tenantHelpContent } from "@/data/tenantHelpContent";

const ALL_DAYS = 127;

export default function Create({ tenant, categories, optionGroups }: MenuItemCreateProps) {
    const { processing, errors, generalError, submit } = useApiFormSubmission<FormErrors>();
    const { showHelp, openHelp, closeHelp } = useHelpPanel();
    const [formData, setFormData] = useState<FormData>({
        name: "",
        description: "",
        price: "",
        is_active: true,
        available_from: null,
        available_until: null,
        available_days: ALL_DAYS,
        category_ids: [],
        option_group_ids: [],
        allergens: 0,
        allergen_advisories: 0,
        allergen_note: "",
        nutrition_info: {
            energy: "",
            protein: "",
            fat: "",
            carbohydrate: "",
            salt: "",
        },
    });

    const { showActiveConfirm, pendingActiveValue, requestToggle, confirmToggle, cancelToggle } = useActiveToggle();

    const submitItem = async () => {
        await submit(
            () =>
                api.post<unknown, { errors?: FormErrors }>("/api/tenant/menu/items", {
                    ...formData,
                    price: formData.price === "" ? null : formData.price,
                    allergen_note: formData.allergen_note || null,
                    nutrition_info: Object.values(formData.nutrition_info).some((v) => v !== "")
                        ? Object.fromEntries(
                              Object.entries(formData.nutrition_info).map(([k, v]) => [k, v === "" ? null : v]),
                          )
                        : null,
                }),
            {
                logMessage: "Menu item creation failed",
                onSuccess: () => {
                    router.visit(route("tenant.menu.items.page"));
                },
            },
        );
    };

    const handleSubmit: FormEventHandler = (e: FormEvent) => {
        e.preventDefault();
        void submitItem();
    };

    const handleCancel = () => {
        router.visit(route("tenant.menu.items.page"));
    };

    const handleActiveToggleRequest = (newValue: boolean) => {
        requestToggle(newValue);
    };

    const executeActiveToggle = () => {
        confirmToggle((value) => {
            setFormData({ ...formData, is_active: value });
        });
    };

    return (
        <TenantLayout title="商品追加">
            <Head title="商品追加" />

            <div className="mx-auto max-w-3xl">
                <PageHeader title="商品追加" help={<HelpButton onClick={openHelp} />} className="mb-3" />
                <div className="overflow-hidden bg-white">
                    <form onSubmit={handleSubmit} className="p-6">
                        {generalError && <p className="mb-4 text-sm text-red-600">{generalError}</p>}
                        <ItemForm
                            formData={formData}
                            errors={errors}
                            categories={categories}
                            optionGroups={optionGroups}
                            onChange={setFormData}
                            todayBusinessHours={tenant.today_business_hours}
                            businessHours={tenant.business_hours}
                            onActiveToggleRequest={handleActiveToggleRequest}
                        />

                        <FormActions>
                            <SecondaryButton onClick={handleCancel}>キャンセル</SecondaryButton>
                            <PrimaryButton type="submit" disabled={processing} isBusy={processing}>
                                作成
                            </PrimaryButton>
                        </FormActions>
                    </form>
                </div>
            </div>

            <ActiveToggleModal
                show={showActiveConfirm}
                isActivating={pendingActiveValue}
                onClose={cancelToggle}
                onConfirm={executeActiveToggle}
            />

            <HelpPanel open={showHelp} onClose={closeHelp} content={tenantHelpContent["menu-items-create"]} />
        </TenantLayout>
    );
}
