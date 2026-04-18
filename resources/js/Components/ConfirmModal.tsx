import Modal from "@/Components/Modal";
import SecondaryButton from "@/Components/SecondaryButton";
import DangerButton from "@/Components/DangerButton";

export default function ConfirmModal({
    show,
    onClose,
    onConfirm,
    title,
    message,
    confirmLabel = "削除",
    processing = false,
}: {
    show: boolean;
    onClose: () => void;
    onConfirm: () => void;
    title: string;
    message: string;
    confirmLabel?: string;
    processing?: boolean;
}) {
    return (
        <Modal show={show} onClose={onClose} maxWidth="sm">
            <div className="p-6">
                <h2 className="text-lg font-medium text-ink">{title}</h2>
                <p className="mt-2 text-sm text-muted">{message}</p>
                <div className="mt-6 flex justify-end gap-3">
                    <SecondaryButton onClick={onClose}>キャンセル</SecondaryButton>
                    <DangerButton onClick={onConfirm} disabled={processing} isBusy={processing}>
                        {confirmLabel}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    );
}
