import { Link } from "@inertiajs/react";
import type { PaginatedData } from "@/types";
import { decodeHtmlEntities } from "@/Utils/decodeHtmlEntities";
import { getPaginationLinkBaseKey, withStableKeys } from "@/Utils/stableKeys";

interface AdminPaginationProps<T> {
    paginated: PaginatedData<T>;
    preserveState?: boolean;
}

export default function AdminPagination<T>({ paginated, preserveState = true }: AdminPaginationProps<T>) {
    if (paginated.last_page <= 1) {
        return null;
    }

    const links = withStableKeys(paginated.links, getPaginationLinkBaseKey);

    return (
        <div className="mt-6 flex items-center justify-between">
            <p className="text-sm text-ink-light">
                {paginated.from} - {paginated.to} / {paginated.total} 件
            </p>
            <div className="flex gap-2">
                {links.map(({ item: link, key }) => {
                    const label = decodeHtmlEntities(link.label);
                    const baseClasses = "px-3 py-2 text-sm";

                    if (!link.url) {
                        return (
                            <button
                                key={key}
                                disabled
                                className={`${baseClasses} bg-surface-dim text-muted-light cursor-not-allowed`}
                            >
                                {label}
                            </button>
                        );
                    }

                    const stateClasses = link.active
                        ? "bg-slate-800 text-white"
                        : "bg-white text-ink-light hover:bg-surface border border-edge-strong";

                    return (
                        <Link
                            key={key}
                            href={link.url}
                            preserveState={preserveState}
                            className={`${baseClasses} ${stateClasses}`}
                        >
                            {label}
                        </Link>
                    );
                })}
            </div>
        </div>
    );
}
