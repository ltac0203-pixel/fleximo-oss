import PrimaryButton from "@/Components/PrimaryButton";
import SecondaryButton from "@/Components/SecondaryButton";
import ItemForm from "@/Components/Tenant/Menu/ItemForm";
import { FormData, FormErrors } from "@/Components/Tenant/Menu/ItemForm/types";
import { useApiFormSubmission } from "@/Hooks/useApiFormSubmission";
import { useActiveToggle } from "@/Hooks/useActiveToggle";
import { useHelpPanel } from "@/Hooks/useHelpPanel";
import { api } from "@/api";
import TenantLayout from "@/Layouts/TenantLayout";
import { MenuItemEditProps } from "@/types";
import { Head, router } from "@inertiajs/react";
import { FormEvent, FormEventHandler, useState } from "react";
import ActiveToggleModal from "@/Components/Tenant/Menu/ActiveToggleModal";
import HelpButton from "@/Components/Common/Help/HelpButton";
import HelpPanel from "@/Components/Common/Help/HelpPanel";
import { tenantHelpContent } from "@/data/tenantHelpContent";

export default function Edit({ tenant, item, categories, optionGroups }: MenuItemEditProps) {
    const { processing, errors, generalError, submit } = useApiFormSubmission<FormErrors>();
    const { showHelp, openHelp, closeHelp } = useHelpPanel();
    const [formData, setFormData] = useState<FormData>({
        name: item.name,
        description: item.description || "",
        price: item.price,
        is_active: item.is_active,
        available_from: item.available_from,
        available_until: item.available_until,
        available_days: item.available_days,
        category_ids: item.categories?.map((c) => c.id) || [],
        option_group_ids: item.option_groups?.map((g) => g.id) || [],
        allergens: item.allergens ?? 0,
        allergen_advisories: item.allergen_advisories ?? 0,
        allergen_note: item.allergen_note ?? "",
        nutrition_info: {
            energy: item.nutrition_info?.energy ?? "",
            protein: item.nutrition_info?.protein ?? "",
            fat: item.nutrition_info?.fat ?? "",
            carbohydrate: item.nutrition_info?.carbohydrate ?? "",
            salt: item.nutrition_info?.salt ?? "",
        },
    });

    const { showActiveConfirm, pendingActiveValue, requestToggle, confirmToggle, cancelToggle } = useActiveToggle();

    const submitItem = async () => {
        await submit(
            () =>
                api.patch<unknown, { errors?: FormErrors }>(
                `/api/tenant/menu/items/${item.id}`,
                    {
                        ...formData,
                        allergen_note: formData.allergen_note || null,
                        nutrition_info: Object.values(formData.nutrition_info).some((v) => v !== "")
                            ? Object.fromEntries(
                                  Object.entries(formData.nutrition_info).map(([k, v]) => [k, v === "" ? null : v]),
                              )
                            : null,
                    },
                ),
            {
                logMessage: "Menu item update failed",
                logContext: {
                    itemId: item.id,
                },
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
        <TenantLayout title="商品編集">
            <Head title="商品編集" />

            <div className="mx-auto max-w-3xl">
                <div className="mb-3 flex items-center justify-between">
                    <h3 className="text-lg font-medium text-gray-900">商品編集</h3>
                    <HelpButton onClick={openHelp} />
                </div>
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

                        <div className="mt-8 flex justify-end gap-3">
                            <SecondaryButton type="button" onClick={handleCancel}>
                                キャンセル
                            </SecondaryButton>
                            <PrimaryButton type="submit" disabled={processing} isBusy={processing}>
                                更新
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>

            <ActiveToggleModal
                show={showActiveConfirm}
                isActivating={pendingActiveValue}
                onClose={cancelToggle}
                onConfirm={executeActiveToggle}
            />

            <HelpPanel open={showHelp} onClose={closeHelp} content={tenantHelpContent["menu-items-edit"]} />
        </TenantLayout>
    );
}
