import FormActions from "@/Components/FormActions";
import ToastContainer from "@/Components/UI/ToastContainer";
import {
    BusinessHourForm,
    toBusinessHourForm,
    toTenantBusinessHourPayload,
    useBusinessHours,
} from "@/Hooks/useBusinessHours";
import { useToast } from "@/Hooks/useToast";
import { useHelpPanel } from "@/Hooks/useHelpPanel";
import TenantLayout from "@/Layouts/TenantLayout";
import { Head, useForm } from "@inertiajs/react";
import { TenantPageProps } from "@/types";
import { FormEventHandler, useEffect } from "react";
import InputLabel from "@/Components/InputLabel";
import TextInput from "@/Components/TextInput";
import InputError from "@/Components/InputError";
import Button from "@/Components/UI/Button";
import HelpButton from "@/Components/Tenant/HelpButton";
import HelpPanel from "@/Components/Tenant/HelpPanel";
import { tenantHelpContent } from "@/data/tenantHelpContent";

const DAYS = [
    { label: "日", value: 0 },
    { label: "月", value: 1 },
    { label: "火", value: 2 },
    { label: "水", value: 3 },
    { label: "木", value: 4 },
    { label: "金", value: 5 },
    { label: "土", value: 6 },
];

export default function Edit({ tenant }: TenantPageProps) {
    const initialBusinessHours =
        tenant.business_hours
            ?.toSorted((a, b) => a.weekday - b.weekday || a.sort_order - b.sort_order)
            ?.map((hour) => ({
                ...toBusinessHourForm(hour),
            })) ?? [];

    const form = useForm<{
        business_hours: BusinessHourForm[];
    }>({
        business_hours: initialBusinessHours,
    });
    const { data, setData, processing, errors, recentlySuccessful } = form;

    const {
        copySourceDay,
        copyTargetDays,
        addTimeRange,
        updateTimeRange,
        removeTimeRange,
        getDayEntries,
        getError,
        toggleCopyTarget,
        selectWeekdays,
        selectAllDays,
        applyCopyToTargetDays,
        openCopyUI,
    } = useBusinessHours({
        businessHours: data.business_hours,
        errors: errors as Record<string, string | undefined>,
        onChange: (next) => setData("business_hours", next),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.transform((currentData) => ({
            ...currentData,
            business_hours: currentData.business_hours.map(toTenantBusinessHourPayload),
        }));
        form.patch(route("tenant.profile.update"));
    };

    const { showHelp, openHelp, closeHelp } = useHelpPanel();
    const { toasts, showToast, hideToast } = useToast();

    useEffect(() => {
        if (recentlySuccessful) {
            showToast({ type: "success", message: "プロフィールを更新しました" });
        }
    }, [recentlySuccessful, showToast]);

    return (
        <TenantLayout title="店舗設定編集">
            <Head title="店舗設定編集" />

            <div className="flex justify-end mb-2">
                <HelpButton onClick={openHelp} />
            </div>
            <div className="bg-white shadow p-6">
                <form onSubmit={submit} className="space-y-6">
                    <div>
                        <InputLabel value="店舗名" />
                        <p className="mt-1 text-sm text-gray-900 bg-gray-100 border border-gray-300 rounded-md px-3 py-2">
                            {tenant.name}
                        </p>
                    </div>

                    <div>
                        <InputLabel value="住所" />
                        <p className="mt-1 text-sm text-gray-900 bg-gray-100 border border-gray-300 rounded-md px-3 py-2">
                            {tenant.address || "未設定"}
                        </p>
                    </div>

                    <div>
                        <InputLabel value="連絡先メール" />
                        <p className="mt-1 text-sm text-gray-900 bg-gray-100 border border-gray-300 rounded-md px-3 py-2">
                            {tenant.email || "未設定"}
                        </p>
                    </div>

                    <div>
                        <InputLabel value="連絡先電話番号" />
                        <p className="mt-1 text-sm text-gray-900 bg-gray-100 border border-gray-300 rounded-md px-3 py-2">
                            {tenant.phone || "未設定"}
                        </p>
                    </div>

                    <div>
                        <InputLabel value="fincode ショップID" />
                        <p className="mt-1 text-sm text-gray-900 bg-gray-100 border border-gray-300 rounded-md px-3 py-2 font-mono">
                            {tenant.fincode_shop_id || "未設定"}
                        </p>
                    </div>

                    <div>
                        <InputLabel value="営業時間" />
                        <p className="mt-1 text-sm text-gray-500">
                            曜日ごとに営業時間を設定してください。時間帯を追加すると1日複数の営業時間が設定できます。
                        </p>
                        <div className="mt-4 space-y-4">
                            {DAYS.map((day) => {
                                const entries = getDayEntries(day.value);
                                const hasEntries = entries.length > 0;
                                const isCopySource = copySourceDay === day.value;
                                return (
                                    <div key={day.value} className="rounded-md border border-gray-200 p-4">
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm font-medium text-gray-900">{day.label}</span>
                                            <div className="flex items-center gap-3">
                                                {hasEntries && (
                                                    <button
                                                        type="button"
                                                        onClick={() => openCopyUI(day.value)}
                                                        className={`text-sm ${
                                                            isCopySource
                                                                ? "text-indigo-700 font-medium"
                                                                : "text-indigo-600 hover:text-indigo-800"
                                                        }`}
                                                    >
                                                        コピー
                                                    </button>
                                                )}
                                                <button
                                                    type="button"
                                                    onClick={() => addTimeRange(day.value)}
                                                    className="text-sm text-blue-600 hover:text-blue-800"
                                                >
                                                    時間帯追加
                                                </button>
                                            </div>
                                        </div>

                                        {entries.length === 0 ? (
                                            <p className="mt-2 text-sm text-gray-500">休業日</p>
                                        ) : (
                                            <div className="mt-3 space-y-4">
                                                {entries.map(({ hour, index }) => {
                                                    const openError = getError(index, "open_time");
                                                    const closeError = getError(index, "close_time");

                                                    return (
                                                        <div key={hour.client_id} className="space-y-2">
                                                            <div className="flex flex-wrap items-center gap-3">
                                                                <div className="flex items-center gap-2">
                                                                    <TextInput
                                                                        type="time"
                                                                        className="w-28 sm:w-32"
                                                                        value={hour.open_time || ""}
                                                                        aria-invalid={!!openError}
                                                                        onChange={(e) =>
                                                                            updateTimeRange(index, {
                                                                                open_time: e.target.value,
                                                                            })
                                                                        }
                                                                    />
                                                                    <span className="text-gray-500">〜</span>
                                                                    <TextInput
                                                                        type="time"
                                                                        className="w-28 sm:w-32"
                                                                        value={hour.close_time || ""}
                                                                        aria-invalid={!!closeError}
                                                                        onChange={(e) =>
                                                                            updateTimeRange(index, {
                                                                                close_time: e.target.value,
                                                                            })
                                                                        }
                                                                    />
                                                                </div>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => removeTimeRange(index)}
                                                                    className="text-sm text-gray-500 hover:text-gray-700"
                                                                >
                                                                    削除
                                                                </button>
                                                            </div>
                                                            <div className="space-y-1">
                                                                <InputError message={openError} />
                                                                <InputError message={closeError} />
                                                            </div>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        )}

                                        {isCopySource && (
                                            <div className="mt-3 rounded-md border border-indigo-200 bg-indigo-50 p-3">
                                                <p className="text-sm text-gray-700 mb-2">コピー先の曜日を選択:</p>
                                                <div className="flex flex-wrap gap-2">
                                                    {DAYS.filter((d) => d.value !== day.value).map((d) => (
                                                        <button
                                                            key={d.value}
                                                            type="button"
                                                            onClick={() => toggleCopyTarget(d.value)}
                                                            className={`w-10 h-10 rounded-full text-sm font-medium ${
                                                                copyTargetDays.includes(d.value)
                                                                    ? "bg-indigo-600 text-white"
                                                                    : "bg-white text-gray-700 border border-gray-300 hover:bg-gray-100"
                                                            }`}
                                                        >
                                                            {d.label}
                                                        </button>
                                                    ))}
                                                </div>
                                                <div className="mt-2 flex items-center gap-3">
                                                    <button
                                                        type="button"
                                                        onClick={selectWeekdays}
                                                        className="text-sm text-indigo-600 hover:text-indigo-800"
                                                    >
                                                        平日一括
                                                    </button>
                                                    <span className="text-gray-300">|</span>
                                                    <button
                                                        type="button"
                                                        onClick={selectAllDays}
                                                        className="text-sm text-indigo-600 hover:text-indigo-800"
                                                    >
                                                        全日一括
                                                    </button>
                                                    <div className="ml-auto">
                                                        <button
                                                            type="button"
                                                            onClick={applyCopyToTargetDays}
                                                            disabled={copyTargetDays.length === 0}
                                                            className="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                                        >
                                                            適用
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    <FormActions>
                        <Button variant="primary" disabled={processing} isBusy={processing}>
                            更新
                        </Button>
                    </FormActions>
                </form>
            </div>

            <HelpPanel open={showHelp} onClose={closeHelp} content={tenantHelpContent["profile-edit"]} />

            <ToastContainer toasts={toasts} onClose={hideToast} />
        </TenantLayout>
    );
}
