import ConfirmDialog from "@/Components/UI/ConfirmDialog";
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
        <ConfirmDialog
            show={show}
            onClose={onClose}
            onConfirm={handleLogout}
            title="ログアウトしますか？"
            confirmLabel="ログアウト"
            tone="danger"
            processing={processing}
        >
            <p className="mt-2 text-sm text-muted">現在のセッションからログアウトします。</p>
        </ConfirmDialog>
    );
}
