import Modal from "@/Components/Modal";
import { FormEventHandler } from "react";

interface RejectFormState {
    data: {
        rejection_reason: string;
    };
    setData: (key: "rejection_reason", value: string) => void;
    errors: {
        rejection_reason?: string;
    };
    processing: boolean;
}

interface RejectApplicationModalProps {
    show: boolean;
    onClose: () => void;
    onSubmit: FormEventHandler;
    rejectForm: RejectFormState;
}

export default function RejectApplicationModal({ show, onClose, onSubmit, rejectForm }: RejectApplicationModalProps) {
    return (
        <Modal show={show} onClose={onClose} maxWidth="md">
            <div className="p-6">
                <h3 className="text-lg font-medium text-ink">申し込みを却下</h3>
                <form onSubmit={onSubmit} className="mt-4">
                    <label className="block text-sm font-medium text-ink-light">却下理由</label>
                    <textarea
                        autoFocus
                        value={rejectForm.data.rejection_reason}
                        onChange={(e) => rejectForm.setData("rejection_reason", e.target.value)}
                        rows={4}
                        required
                        className="mt-1 w-full rounded-md border border-edge-strong px-3 py-2 text-sm focus:border-primary focus:outline-none"
                        placeholder="申請者に通知される却下理由を入力してください..."
                    />
                    {rejectForm.errors.rejection_reason && (
                        <p className="mt-1 text-sm text-red-600">{rejectForm.errors.rejection_reason}</p>
                    )}
                    <div className="mt-6 flex gap-3">
                        <button
                            type="button"
                            onClick={onClose}
                            className="flex-1 border border-edge-strong bg-white px-4 py-2 text-sm font-medium text-ink-light hover:bg-surface"
                        >
                            キャンセル
                        </button>
                        <button
                            type="submit"
                            disabled={rejectForm.processing}
                            aria-busy={rejectForm.processing || undefined}
                            className="flex-1 bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50 inline-flex items-center justify-center"
                        >
                            {rejectForm.processing ? (
                                <>
                                    <span
                                        className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                                        aria-hidden="true"
                                    />
                                    <span className="sr-only">処理中</span>
                                </>
                            ) : (
                                "却下する"
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    );
}
