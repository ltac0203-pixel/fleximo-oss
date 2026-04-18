interface SkipToContentLinkProps {
    targetId?: string;
    label?: string;
}

export const MAIN_CONTENT_ID = "main-content";

export default function SkipToContentLink({
    targetId = MAIN_CONTENT_ID,
    label = "メインコンテンツへスキップ",
}: SkipToContentLinkProps) {
    return (
        <a
            href={`#${targetId}`}
            className="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-sky-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white focus:outline-none focus:ring-2 focus:ring-sky-300"
        >
            {label}
        </a>
    );
}
