import Modal from "@/Components/Modal";
import { router } from "@inertiajs/react";
import { useState } from "react";

interface ReactivateCustomerModalProps {
    show: boolean;
    onClose: () => void;
    customerId: number;
}

export default function ReactivateCustomerModal({ show, onClose, customerId }: ReactivateCustomerModalProps) {
    const [processing, setProcessing] = useState(false);

    const handleConfirm = () => {
        setProcessing(true);
        router.post(
            route("admin.customers.reactivate", customerId),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    setProcessing(false);
                    onClose();
                },
                onError: () => {
                    setProcessing(false);
                },
            },
        );
    };

    return (
        <Modal show={show} onClose={onClose} maxWidth="md">
            <div className="p-6">
                <h3 className="text-lg font-medium text-ink">顧客を再有効化しますか？</h3>
                <p className="mt-2 text-sm text-muted">
                    この顧客のアカウントを再有効化します。再有効化すると、顧客はログインおよび注文が再びできるようになります。
                </p>
                <div className="mt-6 flex gap-3">
                    <button
                        onClick={onClose}
                        className="flex-1 border border-edge-strong bg-white px-4 py-2 text-sm font-medium text-ink-light hover:bg-surface"
                    >
                        キャンセル
                    </button>
                    <button
                        onClick={handleConfirm}
                        disabled={processing}
                        aria-busy={processing || undefined}
                        className="flex-1 bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50 inline-flex items-center justify-center"
                    >
                        {processing ? (
                            <>
                                <span
                                    className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                                    aria-hidden="true"
                                />
                                <span className="sr-only">処理中</span>
                            </>
                        ) : (
                            "再有効化する"
                        )}
                    </button>
                </div>
            </div>
        </Modal>
    );
}
