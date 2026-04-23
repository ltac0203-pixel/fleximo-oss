import ConfirmDialog from "@/Components/UI/ConfirmDialog";

interface ActiveToggleModalProps {
    show: boolean;
    isActivating: boolean | null;
    onClose: () => void;
    onConfirm: () => void;
}

export default function ActiveToggleModal({
    show,
    isActivating,
    onClose,
    onConfirm,
}: ActiveToggleModalProps) {
    return (
        <ConfirmDialog
            show={show}
            onClose={onClose}
            onConfirm={onConfirm}
            title={isActivating ? "商品を公開しますか？" : "商品を非公開にしますか？"}
            confirmLabel={isActivating ? "公開する" : "非公開にする"}
            tone="danger"
        >
            <p className="mt-2 text-sm text-muted">
                {isActivating
                    ? "この商品がメニューに表示されます。顧客が注文できるようになります。"
                    : "この商品はメニューに表示されなくなります。顧客は注文できなくなります。"}
            </p>
        </ConfirmDialog>
    );
}
