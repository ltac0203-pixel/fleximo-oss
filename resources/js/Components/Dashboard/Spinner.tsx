interface SpinnerProps {
    size?: "sm" | "md" | "lg";
    className?: string;
}

const sizeClasses = {
    sm: "h-4 w-4",
    md: "h-8 w-8",
    lg: "h-12 w-12",
};

export default function Spinner({ size = "md", className = "" }: SpinnerProps) {
    return (
        <div
            className={`animate-spin rounded-full border-2 border-edge-strong border-t-primary-dark ${sizeClasses[size]} ${className}`}
            role="status"
            aria-label="読み込み中"
        >
            <span className="sr-only">読み込み中...</span>
        </div>
    );
}
