interface HelpButtonProps {
    onClick: () => void;
}

export default function HelpButton({ onClick }: HelpButtonProps) {
    return (
        <button
            type="button"
            onClick={onClick}
            className="inline-flex items-center justify-center w-8 h-8 rounded-full border border-edge-strong bg-white text-muted hover:bg-surface hover:text-ink-light transition-colors"
            aria-label="ヘルプを表示"
        >
            <span className="text-sm font-semibold">?</span>
        </button>
    );
}
