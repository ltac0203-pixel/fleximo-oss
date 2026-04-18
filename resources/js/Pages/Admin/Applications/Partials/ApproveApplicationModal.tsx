import Modal from "@/Components/Modal";

interface ApproveApplicationModalProps {
    show: boolean;
    onClose: () => void;
    onConfirm: () => void;
}

export default function ApproveApplicationModal({ show, onClose, onConfirm }: ApproveApplicationModalProps) {
    return (
        <Modal show={show} onClose={onClose} maxWidth="md">
            <div className="p-6">
                <h3 className="text-lg font-medium text-ink">申し込みを承認しますか？</h3>
                <p className="mt-2 text-sm text-muted">
                    この操作を行うと、テナントとテナント管理者アカウントが作成され、申請者にパスワード設定メールが送信されます。
                </p>
                <div className="mt-6 flex gap-3">
                    <button
                        onClick={onClose}
                        className="flex-1 border border-edge-strong bg-white px-4 py-2 text-sm font-medium text-ink-light hover:bg-surface"
                    >
                        キャンセル
                    </button>
                    <button
                        onClick={onConfirm}
                        className="flex-1 bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                    >
                        承認する
                    </button>
                </div>
            </div>
        </Modal>
    );
}
