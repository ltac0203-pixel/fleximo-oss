import TenantLayout from "@/Layouts/TenantLayout";
import { Head, Link, usePage } from "@inertiajs/react";
import { PageProps, TenantBusinessHour, TenantPageProps } from "@/types";
import SecondaryButton from "@/Components/SecondaryButton";

export default function Index({ tenant }: TenantPageProps) {
    const { auth } = usePage<PageProps>().props;
    const canEdit = auth.user!.is_tenant_admin;
    const days = ["日", "月", "火", "水", "木", "金", "土"];

    const formatBusinessHours = (hours: TenantBusinessHour[]) => {
        if (hours.length === 0) {
            return "休業日";
        }

        return hours
            .toSorted((a, b) => a.sort_order - b.sort_order)
            .map((hour) => `${hour.open_time} 〜 ${hour.close_time}`)
            .join(" / ");
    };

    const businessHoursByDay = days.map((label, index) => ({
        label,
        hours: tenant.business_hours?.filter((hour) => hour.weekday === index) || [],
    }));

    return (
        <TenantLayout title="店舗設定">
            <Head title="店舗設定" />

            <div className="mb-5 flex items-center justify-between">
                <h2 className="text-xl font-semibold text-slate-900">店舗情報</h2>
                {canEdit && (
                    <Link href={route("tenant.profile.edit")}>
                        <SecondaryButton>編集</SecondaryButton>
                    </Link>
                )}
            </div>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1.1fr)_minmax(320px,0.9fr)]">
                <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 className="text-sm font-semibold text-slate-700">基本情報</h3>
                    <dl className="mt-4 grid grid-cols-1 gap-y-5 md:grid-cols-[170px_minmax(0,1fr)] md:gap-x-6">
                        <dt className="text-sm font-medium text-gray-500">店舗名</dt>
                        <dd className="text-sm text-gray-900 md:pt-0.5">{tenant.name}</dd>

                        <dt className="text-sm font-medium text-gray-500">住所</dt>
                        <dd className="text-sm text-gray-900 md:pt-0.5">{tenant.address || "未設定"}</dd>

                        <dt className="text-sm font-medium text-gray-500">連絡先メール</dt>
                        <dd className="text-sm text-gray-900 md:pt-0.5">{tenant.email || "未設定"}</dd>

                        <dt className="text-sm font-medium text-gray-500">連絡先電話番号</dt>
                        <dd className="text-sm text-gray-900 md:pt-0.5">{tenant.phone || "未設定"}</dd>

                        <dt className="text-sm font-medium text-gray-500">fincode ショップID</dt>
                        <dd className="text-sm font-mono text-gray-900 md:pt-0.5">
                            {tenant.fincode_shop_id || "未設定"}
                        </dd>
                    </dl>
                </section>

                <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 className="text-sm font-semibold text-slate-700">営業時間</h3>
                    <ul className="mt-4 space-y-2">
                        {businessHoursByDay.map((day) => (
                            <li
                                key={day.label}
                                className="grid grid-cols-[2rem_minmax(0,1fr)] items-center gap-3 rounded-md border border-slate-100 bg-slate-50 px-3 py-2"
                            >
                                <span className="text-sm font-medium text-slate-600">{day.label}</span>
                                <span className="text-sm text-slate-900">{formatBusinessHours(day.hours)}</span>
                            </li>
                        ))}
                    </ul>
                </section>
            </div>
        </TenantLayout>
    );
}
