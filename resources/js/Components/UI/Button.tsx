import { ButtonHTMLAttributes, forwardRef } from "react";
import { BUTTON_SIZE_CLASSES, type ButtonSize } from "@/constants/buttonStyles";

export type ButtonVariant = "primary" | "secondary" | "danger";
export type ButtonTone = "solid" | "outline";

export interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: ButtonVariant;
    tone?: ButtonTone;
    size?: ButtonSize;
    isBusy?: boolean;
}

const VARIANT_LAYOUT_CLASSES: Record<ButtonVariant, string> = {
    primary: "gap-2 tracking-wide",
    secondary: "",
    danger: "",
};

const VARIANT_TONE_CLASSES: Record<ButtonVariant, Record<ButtonTone, string>> = {
    primary: {
        solid: "geo-hover-brackets geo-hover-brackets-light border-sky-600 bg-sky-600 text-white shadow-geo-sky hover:border-sky-700 hover:bg-sky-700",
        outline: "geo-hover-brackets border-sky-500 bg-white text-sky-700 hover:border-sky-600 hover:bg-sky-50",
    },
    secondary: {
        solid: "border-edge-strong bg-white text-ink-light shadow-sm hover:border-primary-light hover:bg-surface hover:shadow-geo-sky hover:-translate-y-px",
        outline: "border-edge-strong bg-white text-ink-light shadow-sm hover:border-primary-light hover:bg-surface hover:shadow-geo-sky hover:-translate-y-px",
    },
    danger: {
        solid: "geo-hover-brackets geo-hover-brackets-light border-red-600 bg-red-600 text-white shadow-sm hover:border-red-700 hover:bg-red-700",
        outline: "geo-hover-brackets border-red-600 bg-white text-red-700 hover:border-red-700 hover:bg-red-50",
    },
};

const VARIANT_FOCUS_RING: Record<ButtonVariant, string> = {
    primary: "focus-visible:ring-sky-500",
    secondary: "focus-visible:ring-primary",
    danger: "focus-visible:ring-red-500",
};

const VARIANT_SUPPORTS_BUSY: Record<ButtonVariant, boolean> = {
    primary: true,
    secondary: false,
    danger: true,
};

const Button = forwardRef<HTMLButtonElement, ButtonProps>(function Button(
    {
        variant = "primary",
        tone = "solid",
        size = "md",
        isBusy = false,
        disabled,
        className = "",
        children,
        ...props
    },
    ref,
) {
    const supportsBusy = VARIANT_SUPPORTS_BUSY[variant];
    const showSpinner = supportsBusy && isBusy;
    const isDisabled = disabled || showSpinner;
    const sizeClass = BUTTON_SIZE_CLASSES[size];
    const layoutClass = VARIANT_LAYOUT_CLASSES[variant];
    const toneClass = VARIANT_TONE_CLASSES[variant][tone];
    const ringClass = VARIANT_FOCUS_RING[variant];

    const baseClass =
        "inline-flex items-center justify-center border font-medium transition duration-150 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed";

    const composed =
        `${baseClass} ${layoutClass} ${sizeClass} ${toneClass} ${ringClass} ${
            isDisabled ? "opacity-40" : ""
        } ` + className;

    return (
        <button
            {...props}
            ref={ref}
            className={composed}
            disabled={isDisabled}
            aria-busy={showSpinner || undefined}
        >
            {showSpinner ? (
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
});

export default Button;
