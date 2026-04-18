import { ButtonHTMLAttributes } from "react";
import { BUTTON_SIZE_CLASSES, type ButtonSize } from "@/constants/buttonStyles";

interface SecondaryButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    size?: ButtonSize;
}

export default function SecondaryButton({
    type = "button",
    className = "",
    disabled,
    size = "md",
    children,
    ...props
}: SecondaryButtonProps) {
    const sizeClass = BUTTON_SIZE_CLASSES[size];

    return (
        <button
            {...props}
            type={type}
            className={
                `inline-flex items-center justify-center border border-edge-strong bg-white font-medium text-ink-light shadow-sm transition duration-150 ease-out hover:border-primary-light hover:bg-surface hover:shadow-geo-sky hover:-translate-y-px focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:cursor-not-allowed ${
                    disabled ? "opacity-40" : ""
                } ${sizeClass} ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
