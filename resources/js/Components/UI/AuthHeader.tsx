import { Link } from "@inertiajs/react";

interface AuthHeaderProps {
    eyebrow?: string;
    title: string;
    description?: string;
    backHref?: string;
    backLabel?: string;
}

export default function AuthHeader({ eyebrow, title, description, backHref, backLabel }: AuthHeaderProps) {
    return (
        <div className="mb-6">
            {backHref && (
                <Link
                    href={backHref}
                    className="mb-3 inline-flex rounded-md text-sm text-ink-light underline hover:text-ink focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                >
                    {backLabel}
                </Link>
            )}
            {eyebrow && (
                <div className="flex items-center gap-2">
                    <div className="h-px w-8 bg-sky-400" />
                    <p className="text-xs font-medium uppercase tracking-widest text-sky-600">{eyebrow}</p>
                </div>
            )}
            <h2 className={`text-2xl font-bold text-ink${eyebrow ? " mt-2" : ""}`}>{title}</h2>
            {description && <p className="mt-2 text-sm text-ink-light">{description}</p>}
        </div>
    );
}
