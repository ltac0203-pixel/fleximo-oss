import ConfirmDialog from "@/Components/UI/ConfirmDialog";
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
        <ConfirmDialog
            show={show}
            onClose={onClose}
            onConfirm={handleConfirm}
            title="顧客を再有効化しますか？"
            confirmLabel="再有効化する"
            processing={processing}
            maxWidth="md"
        >
            <p className="mt-2 text-sm text-muted">
                この顧客のアカウントを再有効化します。再有効化すると、顧客はログインおよび注文が再びできるようになります。
            </p>
        </ConfirmDialog>
    );
}
