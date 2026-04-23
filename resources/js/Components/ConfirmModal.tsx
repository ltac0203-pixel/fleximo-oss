import ConfirmDialog from "@/Components/UI/ConfirmDialog";

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
        <ConfirmDialog
            show={show}
            onClose={onClose}
            onConfirm={onConfirm}
            title={title}
            confirmLabel={confirmLabel}
            tone="danger"
            processing={processing}
        >
            <p className="mt-2 text-sm text-muted">{message}</p>
        </ConfirmDialog>
    );
}
