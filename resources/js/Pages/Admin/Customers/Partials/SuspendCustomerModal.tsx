import Modal from "@/Components/Modal";
import { useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

interface SuspendCustomerModalProps {
    show: boolean;
    onClose: () => void;
    customerId: number;
}

export default function SuspendCustomerModal({ show, onClose, customerId }: SuspendCustomerModalProps) {
    const form = useForm({
        reason: "",
    });

    const handleClose = () => {
        onClose();
        form.clearErrors();
        form.reset();
    };

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route("admin.customers.suspend", customerId), {
            preserveScroll: true,
            onSuccess: handleClose,
        });
    };

    return (
        <Modal show={show} onClose={handleClose} maxWidth="md">
            <div className="p-6">
                <h3 className="text-lg font-medium text-ink">顧客を一時停止</h3>
                <p className="mt-2 text-sm text-muted">
                    この顧客のアカウントを一時停止します。停止中はログインおよび注文ができなくなります。
                </p>
                <form onSubmit={handleSubmit} className="mt-4">
                    <label className="block text-sm font-medium text-ink-light">停止理由</label>
                    <textarea
                        autoFocus
                        value={form.data.reason}
                        onChange={(e) => form.setData("reason", e.target.value)}
                        rows={4}
                        required
                        className="mt-1 w-full rounded-md border border-edge-strong px-3 py-2 text-sm focus:border-primary focus:outline-none"
                        placeholder="一時停止の理由を入力してください..."
                    />
                    {form.errors.reason && (
                        <p className="mt-1 text-sm text-red-600">{form.errors.reason}</p>
                    )}
                    <div className="mt-6 flex gap-3">
                        <button
                            type="button"
                            onClick={handleClose}
                            className="flex-1 border border-edge-strong bg-white px-4 py-2 text-sm font-medium text-ink-light hover:bg-surface"
                        >
                            キャンセル
                        </button>
                        <button
                            type="submit"
                            disabled={form.processing}
                            aria-busy={form.processing || undefined}
                            className="flex-1 bg-yellow-600 px-4 py-2 text-sm font-medium text-white hover:bg-yellow-700 disabled:opacity-50 inline-flex items-center justify-center"
                        >
                            {form.processing ? (
                                <>
                                    <span
                                        className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                                        aria-hidden="true"
                                    />
                                    <span className="sr-only">処理中</span>
                                </>
                            ) : (
                                "一時停止する"
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    );
}
