import { Link } from "@inertiajs/react";
import { Fragment } from "react";

export interface BreadcrumbItem {
    label: string;
    href?: string;
}

interface BreadcrumbProps {
    items: BreadcrumbItem[];
    className?: string;
}

export default function Breadcrumb({ items, className = "" }: BreadcrumbProps) {
    if (items.length === 0) return null;

    return (
        <nav aria-label="パンくずリスト" className={`mb-4 ${className}`}>
            <ol className="flex flex-wrap items-center gap-1 text-sm text-muted">
                {items.map((item, index) => {
                    const isLast = index === items.length - 1;
                    return (
                        <Fragment key={item.href ?? item.label}>
                            <li className="flex items-center">
                                {item.href && !isLast ? (
                                    <Link href={item.href} className="hover:text-ink-light hover:underline">
                                        {item.label}
                                    </Link>
                                ) : (
                                    <span
                                        className={isLast ? "font-medium text-ink" : ""}
                                        aria-current={isLast ? "page" : undefined}
                                    >
                                        {item.label}
                                    </span>
                                )}
                            </li>
                            {!isLast && (
                                <li aria-hidden="true" className="text-edge-strong">
                                    /
                                </li>
                            )}
                        </Fragment>
                    );
                })}
            </ol>
        </nav>
    );
}
