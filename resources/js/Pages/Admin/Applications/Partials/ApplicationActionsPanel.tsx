import { TenantApplicationDetail } from "@/types";
import { FormEventHandler } from "react";
import { getActionButtonClass } from "@/Pages/Admin/Applications/Partials/applicationStyles";

interface NotesFormState {
    data: {
        internal_notes: string;
    };
    setData: (key: "internal_notes", value: string) => void;
    processing: boolean;
}

interface ApplicationActionsPanelProps {
    application: TenantApplicationDetail;
    notesForm: NotesFormState;
    onStartReview: () => void;
    onOpenApproveModal: () => void;
    onOpenRejectModal: () => void;
    onSubmitNotes: FormEventHandler;
}

export default function ApplicationActionsPanel({
    application,
    notesForm,
    onStartReview,
    onOpenApproveModal,
    onOpenRejectModal,
    onSubmitNotes,
}: ApplicationActionsPanelProps) {
    return (
        <div className="space-y-6">
            {/* アクションボタン を明示し、実装意図の誤読を防ぐ。 */}
            <div className="overflow-hidden bg-white border border-edge">
                <div className="border-b border-edge px-6 py-4">
                    <h3 className="text-lg font-medium text-ink">アクション</h3>
                </div>
                <div className="space-y-3 px-6 py-4">
                    <button
                        onClick={onStartReview}
                        disabled={!application.can_start_review}
                        className={getActionButtonClass(application.can_start_review, "blue")}
                    >
                        審査を開始
                    </button>
                    <button
                        onClick={onOpenApproveModal}
                        disabled={!application.can_be_approved}
                        className={getActionButtonClass(application.can_be_approved, "green")}
                    >
                        承認する
                    </button>
                    <button
                        onClick={onOpenRejectModal}
                        disabled={!application.can_be_rejected}
                        className={getActionButtonClass(application.can_be_rejected, "red")}
                    >
                        却下する
                    </button>
                </div>
            </div>

            {/* 審査情報 を明示し、実装意図の誤読を防ぐ。 */}
            {application.reviewer && (
                <div className="overflow-hidden bg-white border border-edge">
                    <div className="border-b border-edge px-6 py-4">
                        <h3 className="text-lg font-medium text-ink">審査情報</h3>
                    </div>
                    <div className="px-6 py-4">
                        <dl className="space-y-3">
                            <div>
                                <dt className="text-sm font-medium text-muted">審査者</dt>
                                <dd className="mt-1 text-sm text-ink">{application.reviewer.name}</dd>
                            </div>
                            {application.reviewed_at && (
                                <div>
                                    <dt className="text-sm font-medium text-muted">審査日時</dt>
                                    <dd className="mt-1 text-sm text-ink">
                                        {new Date(application.reviewed_at).toLocaleString("ja-JP")}
                                    </dd>
                                </div>
                            )}
                        </dl>
                    </div>
                </div>
            )}

            {/* 内部メモ を明示し、実装意図の誤読を防ぐ。 */}
            <div className="overflow-hidden bg-white border border-edge">
                <div className="border-b border-edge px-6 py-4">
                    <h3 className="text-lg font-medium text-ink">内部メモ</h3>
                </div>
                <form onSubmit={onSubmitNotes} className="p-6">
                    <textarea
                        value={notesForm.data.internal_notes}
                        onChange={(e) => notesForm.setData("internal_notes", e.target.value)}
                        rows={4}
                        className="w-full rounded-md border border-edge-strong px-3 py-2 text-sm focus:border-primary focus:outline-none"
                        placeholder="審査に関するメモを記録..."
                    />
                    <button
                        type="submit"
                        disabled={notesForm.processing}
                        aria-busy={notesForm.processing || undefined}
                        className="mt-3 w-full bg-slate-600 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-50 inline-flex items-center justify-center"
                    >
                        {notesForm.processing ? (
                            <>
                                <span
                                    className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                                    aria-hidden="true"
                                />
                                <span className="sr-only">処理中</span>
                            </>
                        ) : (
                            "メモを保存"
                        )}
                    </button>
                </form>
            </div>
        </div>
    );
}
