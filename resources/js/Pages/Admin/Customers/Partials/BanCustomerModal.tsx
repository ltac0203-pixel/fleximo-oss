import Modal from "@/Components/Modal";
import { BaseModalProps } from "@/types";
import { useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

interface BanCustomerModalProps extends BaseModalProps {
    customerId: number;
}

export default function BanCustomerModal({ show, onClose, customerId }: BanCustomerModalProps) {
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
        form.post(route("admin.customers.ban", customerId), {
            preserveScroll: true,
            onSuccess: handleClose,
        });
    };

    return (
        <Modal show={show} onClose={handleClose} maxWidth="md">
            <div className="p-6">
                <h3 className="text-lg font-medium text-red-900">顧客をBANする</h3>
                <div className="mt-2 border border-red-200 bg-red-50 p-3">
                    <p className="text-sm text-red-700">
                        この操作は顧客のアカウントを永久に停止します。BANされた顧客はログインおよび注文ができなくなります。
                    </p>
                </div>
                <form onSubmit={handleSubmit} className="mt-4">
                    <label className="block text-sm font-medium text-ink-light">BAN理由</label>
                    <textarea
                        autoFocus
                        value={form.data.reason}
                        onChange={(e) => form.setData("reason", e.target.value)}
                        rows={4}
                        required
                        className="mt-1 w-full rounded-md border border-edge-strong px-3 py-2 text-sm focus:border-primary focus:outline-none"
                        placeholder="BANの理由を入力してください..."
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
                            className="flex-1 bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50 inline-flex items-center justify-center"
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
                                "BANする"
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    );
}
