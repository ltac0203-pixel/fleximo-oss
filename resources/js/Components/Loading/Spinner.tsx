interface SpinnerProps {
    size?: "sm" | "md" | "lg";
    variant?: "primary" | "white" | "muted";
    label?: string;
    className?: string;
}

const sizeClasses = {
    sm: "h-5 w-5",
    md: "h-8 w-8",
    lg: "h-12 w-12",
} as const;

const variantClasses = {
    primary: "border-slate-200 border-t-sky-500",
    white: "border-white/40 border-t-white",
    muted: "border-edge-strong border-t-primary-dark",
} as const;

export default function Spinner({
    size = "md",
    variant = "primary",
    label = "読み込み中",
    className = "",
}: SpinnerProps) {
    return (
        <div
            className={`animate-spin rounded-full border-2 ${sizeClasses[size]} ${variantClasses[variant]} ${className}`}
            role="status"
            aria-label={label}
        >
            <span className="sr-only">{label}</span>
        </div>
    );
}
