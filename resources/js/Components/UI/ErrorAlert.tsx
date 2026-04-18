interface ErrorAlertProps {
    message: string;
    onRetry?: () => void;
    className?: string;
}

export default function ErrorAlert({ message, onRetry, className = "" }: ErrorAlertProps) {
    return (
        <div className={`bg-red-50 border border-red-200 rounded-md p-4 ${className}`} role="alert">
            <div className="flex items-center justify-between">
                <p className="text-sm text-red-600">{message}</p>
                {onRetry && (
                    <button
                        onClick={onRetry}
                        className="ml-4 shrink-0 text-sm font-medium text-red-600 hover:text-red-800 underline"
                    >
                        再試行
                    </button>
                )}
            </div>
        </div>
    );
}
