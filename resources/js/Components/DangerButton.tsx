import { ButtonHTMLAttributes } from "react";
import { BUTTON_SIZE_CLASSES, type ButtonSize } from "@/constants/buttonStyles";

interface DangerButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    isBusy?: boolean;
    size?: ButtonSize;
}

export default function DangerButton({
    className = "",
    disabled,
    isBusy = false,
    size = "md",
    children,
    ...props
}: DangerButtonProps) {
    const isDisabled = disabled || isBusy;
    const sizeClass = BUTTON_SIZE_CLASSES[size];

    return (
        <button
            {...props}
            className={
                `geo-hover-brackets geo-hover-brackets-light inline-flex items-center justify-center border border-red-600 bg-red-600 font-medium text-white shadow-sm transition duration-150 ease-out hover:border-red-700 hover:bg-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed ${
                    isDisabled ? "opacity-40" : ""
                } ${sizeClass} ` + className
            }
            disabled={isDisabled}
            aria-busy={isBusy || undefined}
        >
            {isBusy ? (
                <>
                    <span
                        className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                        aria-hidden="true"
                    />
                    <span className="sr-only">処理中</span>
                </>
            ) : (
                children
            )}
        </button>
    );
}
