import { ToastType } from "@/Hooks/useToast";

interface ToastProps {
    type: ToastType;
    message: string;
    onClose?: () => void;
}

// 通知種別ごとに一目で意味を判別できるよう、見た目規則を集約する。
export default function Toast({ type, message, onClose }: ToastProps) {
    const styles: Record<ToastType, string> = {
        success: "bg-green-600 text-white",
        error: "bg-red-600 text-white",
        info: "bg-ink text-white",
    };

    return (
        <div
            role="status"
            aria-live="polite"
            className={`${styles[type]} rounded-lg shadow-lg px-4 py-2.5 min-w-[200px] max-w-[360px] flex items-center gap-2 animate-slide-in-right`}
        >
            <p className="text-sm flex-1">{message}</p>
            {onClose && (
                <button
                    type="button"
                    onClick={onClose}
                    className="text-white/70 hover:text-white flex-shrink-0 text-xs"
                    aria-label="閉じる"
                >
                    ✕
                </button>
            )}
        </div>
    );
}
