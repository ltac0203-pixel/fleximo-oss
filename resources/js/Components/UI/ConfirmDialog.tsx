import { ReactNode } from "react";
import Modal from "@/Components/Modal";
import Button from "@/Components/UI/Button";

type ConfirmTone = "default" | "danger";
type ConfirmMaxWidth = "sm" | "md" | "lg" | "xl" | "2xl";

interface ConfirmDialogProps {
    show: boolean;
    onClose: () => void;
    onConfirm: () => void;
    title: string;
    confirmLabel?: string;
    cancelLabel?: string;
    tone?: ConfirmTone;
    processing?: boolean;
    maxWidth?: ConfirmMaxWidth;
    children?: ReactNode;
}

export default function ConfirmDialog({
    show,
    onClose,
    onConfirm,
    title,
    confirmLabel = "OK",
    cancelLabel = "キャンセル",
    tone = "default",
    processing = false,
    maxWidth = "sm",
    children,
}: ConfirmDialogProps) {
    const confirmVariant = tone === "danger" ? "danger" : "primary";

    return (
        <Modal show={show} onClose={onClose} maxWidth={maxWidth}>
            <div className="p-6">
                <h2 className="text-lg font-medium text-ink">{title}</h2>
                {children}
                <div className="mt-6 flex justify-end gap-3">
                    <Button variant="secondary" type="button" onClick={onClose}>
                        {cancelLabel}
                    </Button>
                    <Button
                        variant={confirmVariant}
                        onClick={onConfirm}
                        disabled={processing}
                        isBusy={processing}
                    >
                        {confirmLabel}
                    </Button>
                </div>
            </div>
        </Modal>
    );
}
