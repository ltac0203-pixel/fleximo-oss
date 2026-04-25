import { useState } from "react";
import ConfirmDialog from "@/Components/UI/ConfirmDialog";

interface OrderPauseToggleProps {
    isOrderPaused: boolean;
    isToggling: boolean;
    onToggle: () => void;
}

export default function OrderPauseToggle({ isOrderPaused, isToggling, onToggle }: OrderPauseToggleProps) {
    const [showConfirm, setShowConfirm] = useState(false);

    const openConfirm = () => setShowConfirm(true);
    const closeConfirm = () => setShowConfirm(false);

    const handleConfirm = () => {
        setShowConfirm(false);
        onToggle();
    };

    const buttonClassName = isOrderPaused
        ? "px-3 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center justify-center"
        : "px-3 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center justify-center";

    const buttonLabel = isOrderPaused ? "注文受付 再開" : "注文受付 停止";

    return (
        <>
            <button
                type="button"
                onClick={openConfirm}
                disabled={isToggling}
                aria-busy={isToggling || undefined}
                className={buttonClassName}
            >
                {isToggling ? (
                    <>
                        <span
                            className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                            aria-hidden="true"
                        />
                        <span className="sr-only">処理中</span>
                    </>
                ) : (
                    buttonLabel
                )}
            </button>

            <ConfirmDialog
                show={showConfirm}
                onClose={closeConfirm}
                onConfirm={handleConfirm}
                title={isOrderPaused ? "注文受付を再開しますか？" : "注文受付を一時停止しますか？"}
                confirmLabel={isOrderPaused ? "再開する" : "停止する"}
                tone={isOrderPaused ? "default" : "danger"}
                processing={isToggling}
            >
                {!isOrderPaused && (
                    <p className="mt-4 text-sm text-ink-light">停止中は新規注文を受け付けません。</p>
                )}
            </ConfirmDialog>
        </>
    );
}
