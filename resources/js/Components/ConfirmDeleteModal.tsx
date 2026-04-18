import DangerButton from "@/Components/DangerButton";
import Modal from "@/Components/Modal";
import SecondaryButton from "@/Components/SecondaryButton";
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
        <Modal show={show} onClose={onClose} maxWidth="md">
            <div className="p-6">
                <h2 className="text-lg font-medium text-ink">{title}</h2>

                {error && <p className="mt-4 text-sm text-red-600">{error}</p>}

                <p className="mt-4 text-sm text-ink-light">{message ?? defaultMessage}</p>

                {warningMessage && <p className="mt-2 text-sm text-red-600">{warningMessage}</p>}

                <div className="mt-6 flex justify-end">
                    <SecondaryButton onClick={onClose}>キャンセル</SecondaryButton>

                    <DangerButton
                        className="ms-3"
                        onClick={() => {
                            void executeDelete();
                        }}
                        disabled={processing}
                        isBusy={processing}
                    >
                        削除
                    </DangerButton>
                </div>
            </div>
        </Modal>
    );
}
