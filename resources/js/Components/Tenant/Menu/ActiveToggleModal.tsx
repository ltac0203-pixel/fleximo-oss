import ConfirmModal from "@/Components/ConfirmModal";

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
    onConfirm
}: ActiveToggleModalProps) {
    return (
        <ConfirmModal
            show={show}
            onClose={onClose}
            onConfirm={onConfirm}
            title={isActivating ? "商品を公開しますか？" : "商品を非公開にしますか？"}
            message={
                isActivating
                    ? "この商品がメニューに表示されます。顧客が注文できるようになります。"
                    : "この商品はメニューに表示されなくなります。顧客は注文できなくなります。"
            }
            confirmLabel={isActivating ? "公開する" : "非公開にする"}
        />
    );
}
