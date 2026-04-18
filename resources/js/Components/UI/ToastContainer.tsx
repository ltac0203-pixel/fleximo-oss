import { ToastItem } from "@/Hooks/useToast";
import Toast from "./Toast";

interface ToastContainerProps {
    toasts: ToastItem[];
    onClose: (id: string) => void;
}

// 通知表示位置を画面右上に固定し、どの画面でも同じ視線移動で結果を認識できるようにする。
// aria-live="polite" で支援技術にも通知し、視覚情報のみに依存しない設計とする。
export default function ToastContainer({ toasts, onClose }: ToastContainerProps) {
    return (
        <div
            className="fixed top-4 right-4 z-50 flex flex-col gap-2"
            role="status"
            aria-live="polite"
            aria-atomic="true"
        >
            {toasts.map((toast) => (
                <Toast key={toast.id} type={toast.type} message={toast.message} onClose={() => onClose(toast.id)} />
            ))}
        </div>
    );
}
