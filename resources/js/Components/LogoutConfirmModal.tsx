import Modal from "@/Components/Modal";
import SecondaryButton from "@/Components/SecondaryButton";
import DangerButton from "@/Components/DangerButton";
import { useCartStore } from "@/stores/cartStore";
import { router } from "@inertiajs/react";
import { useState } from "react";

export default function LogoutConfirmModal({ show, onClose }: { show: boolean; onClose: () => void }) {
    const [processing, setProcessing] = useState(false);

    const handleLogout = () => {
        setProcessing(true);
        useCartStore.getState().reset();
        router.post(route("logout"));
    };

    return (
        <Modal show={show} onClose={onClose} maxWidth="sm">
            <div className="p-6">
                <h2 className="text-lg font-medium text-ink">ログアウトしますか？</h2>
                <p className="mt-2 text-sm text-muted">現在のセッションからログアウトします。</p>
                <div className="mt-6 flex justify-end gap-3">
                    <SecondaryButton onClick={onClose}>キャンセル</SecondaryButton>
                    <DangerButton onClick={handleLogout} disabled={processing} isBusy={processing}>
                        ログアウト
                    </DangerButton>
                </div>
            </div>
        </Modal>
    );
}
