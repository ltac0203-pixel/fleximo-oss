import ConfirmDialog from "@/Components/UI/ConfirmDialog";
import { useDeleteAction } from "@/Hooks/useDeleteAction";

interface ConfirmDeleteModalProps {
    show: boolean;
    title: string;
    targetName?: string;
    message?: string;
    warningMessage?: string;
    apiEndpoint: string;
    reloadOnly: string[];
    successMessage: string;
    onClose: () => void;
    onSuccess?: (message: string) => void;
}

export default function ConfirmDeleteModal({
    show,
    title,
    targetName,
    message,
    warningMessage,
    apiEndpoint,
    reloadOnly,
    successMessage,
    onClose,
    onSuccess,
}: ConfirmDeleteModalProps) {
    const { executeDelete, processing, error } = useDeleteAction({
        apiEndpoint,
        reloadOnly,
        successMessage,
        onSuccess,
        onClose,
    });

    const defaultMessage = targetName
        ? `「${targetName}」を削除してもよろしいですか？この操作は取り消せません。`
        : "削除してもよろしいですか？この操作は取り消せません。";

    return (
        <ConfirmDialog
            show={show}
            onClose={onClose}
            onConfirm={() => {
                void executeDelete();
            }}
            title={title}
            confirmLabel="削除"
            tone="danger"
            processing={processing}
            maxWidth="md"
        >
            {error && <p className="mt-4 text-sm text-red-600">{error}</p>}
            <p className="mt-4 text-sm text-ink-light">{message ?? defaultMessage}</p>
            {warningMessage && <p className="mt-2 text-sm text-red-600">{warningMessage}</p>}
        </ConfirmDialog>
    );
}
