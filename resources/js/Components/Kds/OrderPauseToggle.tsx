interface OrderPauseToggleProps {
    isOrderPaused: boolean;
    isToggling: boolean;
    onToggle: () => void;
}

export default function OrderPauseToggle({ isOrderPaused, isToggling, onToggle }: OrderPauseToggleProps) {
    const handleClick = () => {
        const message = isOrderPaused
            ? "注文受付を再開しますか？"
            : "注文受付を一時停止しますか？\n停止中は新規注文を受け付けません。";

        if (window.confirm(message)) {
            onToggle();
        }
    };

    if (isOrderPaused) {
        return (
            <button
                type="button"
                onClick={handleClick}
                disabled={isToggling}
                aria-busy={isToggling || undefined}
                className="px-3 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center justify-center"
            >
                {isToggling ? (
                    <>
                        <span
                            className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                            aria-hidden="true"
                        />
                        <span className="sr-only">処理中</span>
                    </>
                ) : (
                    "注文受付 再開"
                )}
            </button>
        );
    }

    return (
        <button
            type="button"
            onClick={handleClick}
            disabled={isToggling}
            aria-busy={isToggling || undefined}
            className="px-3 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center justify-center"
        >
            {isToggling ? (
                <>
                    <span
                        className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                        aria-hidden="true"
                    />
                    <span className="sr-only">処理中</span>
                </>
            ) : (
                "注文受付 停止"
            )}
        </button>
    );
}
