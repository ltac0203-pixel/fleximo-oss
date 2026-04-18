interface FavoriteButtonProps {
    isFavorited: boolean;
    isToggling?: boolean;
    onClick: () => void;
    className?: string;
}

export default function FavoriteButton({ isFavorited, isToggling = false, onClick, className = "" }: FavoriteButtonProps) {
    const handleClick = (e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();
        if (!isToggling) {
            onClick();
        }
    };

    return (
        <button
            type="button"
            onClick={handleClick}
            disabled={isToggling}
            aria-label={isFavorited ? "お気に入りから削除" : "お気に入りに追加"}
            className={`p-2.5 transition-colors ${isToggling ? "opacity-50" : ""} ${className}`}
        >
            <svg
                className={`w-5 h-5 ${isFavorited ? "text-red-500 fill-red-500" : "text-muted-light fill-none"}`}
                stroke="currentColor"
                viewBox="0 0 24 24"
                strokeWidth={2}
            >
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                />
            </svg>
        </button>
    );
}
