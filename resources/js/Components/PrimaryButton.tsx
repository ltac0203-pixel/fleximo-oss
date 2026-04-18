import { ButtonHTMLAttributes } from "react";
import { BUTTON_SIZE_CLASSES, type ButtonSize } from "@/constants/buttonStyles";

interface PrimaryButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    isBusy?: boolean;
    size?: ButtonSize;
    tone?: "solid" | "outline";
}

export default function PrimaryButton({
    className = "",
    disabled,
    isBusy = false,
    size = "md",
    tone = "solid",
    children,
    ...props
}: PrimaryButtonProps) {
    const isDisabled = disabled || isBusy;
    const sizeClass = BUTTON_SIZE_CLASSES[size];
    const toneClass =
        tone === "outline"
            ? "geo-hover-brackets border-sky-500 bg-white text-sky-700 hover:border-sky-600 hover:bg-sky-50"
            : "geo-hover-brackets geo-hover-brackets-light border-sky-600 bg-sky-600 text-white shadow-geo-sky hover:border-sky-700 hover:bg-sky-700";

    return (
        <button
            {...props}
            className={
                `inline-flex items-center justify-center gap-2 border font-medium tracking-wide transition duration-150 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed ${
                    isDisabled ? "opacity-40" : ""
                } ${sizeClass} ${toneClass} ` + className
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
