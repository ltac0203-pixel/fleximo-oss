import { ReactNode } from "react";

type BackButtonVariant = "icon" | "text";

interface BackButtonProps {
    variant?: BackButtonVariant;
    onClick?: () => void;
    className?: string;
    children?: ReactNode;
    ariaLabel?: string;
}

const ChevronLeftIcon = () => (
    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
    </svg>
);

export default function BackButton({
    variant = "icon",
    onClick,
    className,
    children,
    ariaLabel = "前のページに戻る",
}: BackButtonProps) {
    const handleClick = onClick ?? (() => window.history.back());

    if (variant === "text") {
        return (
            <button
                type="button"
                onClick={handleClick}
                className={
                    className ??
                    "border border-sky-600 bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-geo-sky hover:border-sky-700 hover:bg-sky-700"
                }
            >
                {children ?? "前のページに戻る"}
            </button>
        );
    }

    return (
        <button
            type="button"
            onClick={handleClick}
            aria-label={ariaLabel}
            className={className ?? "text-slate-500 hover:text-sky-700"}
        >
            <ChevronLeftIcon />
        </button>
    );
}
