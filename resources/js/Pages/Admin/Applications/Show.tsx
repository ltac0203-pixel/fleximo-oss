import AdminLayout from "@/Layouts/AdminLayout";
import ApplicationActionsPanel from "@/Pages/Admin/Applications/Partials/ApplicationActionsPanel";
import ApproveApplicationModal from "@/Pages/Admin/Applications/Partials/ApproveApplicationModal";
import RejectApplicationModal from "@/Pages/Admin/Applications/Partials/RejectApplicationModal";
import { Head, Link, router, useForm, usePage } from "@inertiajs/react";
import { ApplicationShowProps, PageProps } from "@/types";
import { FormEventHandler, useState } from "react";

export default function Show({ application }: ApplicationShowProps) {
    const { flash } = usePage<PageProps>().props;
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [showApproveConfirm, setShowApproveConfirm] = useState(false);

    const rejectForm = useForm({
        rejection_reason: "",
    });

    const notesForm = useForm({
        internal_notes: application.internal_notes || "",
    });

    const handleStartReview = () => {
        router.post(route("admin.applications.start-review", application.id), {}, { preserveScroll: true });
    };

    const handleApprove = () => {
        router.post(
            route("admin.applications.approve", application.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => setShowApproveConfirm(false),
            },
        );
    };

    const handleRejectModalClose = () => {
        setShowRejectModal(false);
        rejectForm.clearErrors();
    };

    const handleReject: FormEventHandler = (e) => {
        e.preventDefault();
        rejectForm.post(route("admin.applications.reject", application.id), {
            preserveScroll: true,
            onSuccess: handleRejectModalClose,
        });
    };

    const handleUpdateNotes: FormEventHandler = (e) => {
        e.preventDefault();
        notesForm.patch(route("admin.applications.update-notes", application.id), { preserveScroll: true });
    };

    const getStatusBadgeClass = (color: string) => {
        const colorMap: Record<string, string> = {
            yellow: "bg-cyan-100 text-cyan-800",
            blue: "bg-sky-100 text-sky-800",
            green: "bg-green-100 text-green-800",
            red: "bg-red-100 text-red-800",
        };
        return colorMap[color] || "bg-surface-dim text-ink";
    };

    return (
        <AdminLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <Link
                            href={route("admin.applications.index")}
                            className="text-sm text-muted hover:text-ink-light"
                        >
                            &larr; 一覧に戻る
                        </Link>
                        <h2 className="mt-1 text-xl font-semibold leading-tight text-ink">申し込み詳細</h2>
                    </div>
                    <span
                        className={`inline-flex rounded-full px-3 py-1 text-sm font-medium ${getStatusBadgeClass(application.status_color)}`}
                    >
                        {application.status_label}
                    </span>
                </div>
            }
        >
            <Head title={`申し込み詳細 - ${application.application_code}`} />

            <div>
                {/* フラッシュメッセージ を明示し、実装意図の誤読を防ぐ。 */}
                {flash?.success && (
                    <div className="mb-6 border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="mb-6 border border-red-200 bg-red-50 p-4 text-sm text-red-700">{flash.error}</div>
                )}

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {/* 左カラム: 申し込み情報 を明示し、実装意図の誤読を防ぐ。 */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* 申し込み情報 を明示し、実装意図の誤読を防ぐ。 */}
                        <div className="overflow-hidden bg-white border border-edge">
                            <div className="border-b border-edge px-6 py-4">
                                <h3 className="text-lg font-medium text-ink">申し込み情報</h3>
                            </div>
                            <div className="px-6 py-4">
                                <dl className="divide-y divide-edge">
                                    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                        <dt className="text-sm font-medium text-muted">申し込み番号</dt>
                                        <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0 font-mono">
                                            {application.application_code}
                                        </dd>
                                    </div>
                                    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                        <dt className="text-sm font-medium text-muted">申し込み日時</dt>
                                        <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                            {new Date(application.created_at).toLocaleString("ja-JP")}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        {/* 店舗情報 を明示し、実装意図の誤読を防ぐ。 */}
                        <div className="overflow-hidden bg-white border border-edge">
                            <div className="border-b border-edge px-6 py-4">
                                <h3 className="text-lg font-medium text-ink">店舗情報</h3>
                            </div>
                            <div className="px-6 py-4">
                                <dl className="divide-y divide-edge">
                                    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                        <dt className="text-sm font-medium text-muted">店舗名</dt>
                                        <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                            {application.tenant_name}
                                        </dd>
                                    </div>
                                    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                        <dt className="text-sm font-medium text-muted">業種</dt>
                                        <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                            {application.business_type_label}
                                        </dd>
                                    </div>
                                    {application.tenant_address && (
                                        <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                            <dt className="text-sm font-medium text-muted">住所</dt>
                                            <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                                {application.tenant_address}
                                            </dd>
                                        </div>
                                    )}
                                </dl>
                            </div>
                        </div>

                        {/* 申請者情報 を明示し、実装意図の誤読を防ぐ。 */}
                        <div className="overflow-hidden bg-white border border-edge">
                            <div className="border-b border-edge px-6 py-4">
                                <h3 className="text-lg font-medium text-ink">申請者情報</h3>
                            </div>
                            <div className="px-6 py-4">
                                <dl className="divide-y divide-edge">
                                    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                        <dt className="text-sm font-medium text-muted">お名前</dt>
                                        <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                            {application.applicant_name}
                                        </dd>
                                    </div>
                                    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                        <dt className="text-sm font-medium text-muted">メールアドレス</dt>
                                        <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                            <a
                                                href={`mailto:${application.applicant_email}`}
                                                className="text-primary hover:text-primary-light"
                                            >
                                                {application.applicant_email}
                                            </a>
                                        </dd>
                                    </div>
                                    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                        <dt className="text-sm font-medium text-muted">電話番号</dt>
                                        <dd className="mt-1 text-sm text-ink sm:col-span-2 sm:mt-0">
                                            {application.applicant_phone}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        {/* 却下理由（却下された場合のみ表示） を明示し、実装意図の誤読を防ぐ。 */}
                        {application.rejection_reason && (
                            <div className="overflow-hidden bg-red-50 border border-red-200">
                                <div className="border-b border-red-200 px-6 py-4">
                                    <h3 className="text-lg font-medium text-red-900">却下理由</h3>
                                </div>
                                <div className="px-6 py-4">
                                    <p className="text-sm text-red-800 whitespace-pre-wrap">
                                        {application.rejection_reason}
                                    </p>
                                </div>
                            </div>
                        )}

                        {/* 作成されたテナント情報（承認済みの場合のみ表示） を明示し、実装意図の誤読を防ぐ。 */}
                        {application.created_tenant && (
                            <div className="overflow-hidden bg-green-50 border border-green-200">
                                <div className="border-b border-green-200 px-6 py-4">
                                    <h3 className="text-lg font-medium text-green-900">作成されたテナント</h3>
                                </div>
                                <div className="px-6 py-4">
                                    <dl className="divide-y divide-green-200">
                                        <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                            <dt className="text-sm font-medium text-green-700">テナント名</dt>
                                            <dd className="mt-1 text-sm text-green-900 sm:col-span-2 sm:mt-0">
                                                {application.created_tenant.name}
                                            </dd>
                                        </div>
                                        <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                            <dt className="text-sm font-medium text-green-700">スラッグ</dt>
                                            <dd className="mt-1 text-sm text-green-900 sm:col-span-2 sm:mt-0 font-mono">
                                                {application.created_tenant.slug}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* 右カラム: アクション＆メモ を明示し、実装意図の誤読を防ぐ。 */}
                    <ApplicationActionsPanel
                        application={application}
                        notesForm={{
                            data: { internal_notes: notesForm.data.internal_notes },
                            setData: (key, value) => notesForm.setData(key, value),
                            processing: notesForm.processing,
                        }}
                        onStartReview={handleStartReview}
                        onOpenApproveModal={() => setShowApproveConfirm(true)}
                        onOpenRejectModal={() => setShowRejectModal(true)}
                        onSubmitNotes={handleUpdateNotes}
                    />
                </div>
            </div>

            {/* 承認確認モーダル を明示し、実装意図の誤読を防ぐ。 */}
            <ApproveApplicationModal
                show={showApproveConfirm}
                onClose={() => setShowApproveConfirm(false)}
                onConfirm={handleApprove}
            />

            {/* 却下モーダル を明示し、実装意図の誤読を防ぐ。 */}
            <RejectApplicationModal
                show={showRejectModal}
                onClose={handleRejectModalClose}
                onSubmit={handleReject}
                rejectForm={{
                    data: { rejection_reason: rejectForm.data.rejection_reason },
                    setData: (key, value) => rejectForm.setData(key, value),
                    errors: { rejection_reason: rejectForm.errors.rejection_reason },
                    processing: rejectForm.processing,
                }}
            />
        </AdminLayout>
    );
}
