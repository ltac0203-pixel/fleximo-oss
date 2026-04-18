import { InertiaLinkProps, Link } from "@inertiajs/react";

export default function ResponsiveNavLink({
    active = false,
    className = "",
    children,
    ...props
}: InertiaLinkProps & { active?: boolean }) {
    return (
        <Link
            {...props}
            className={`flex w-full items-start border-l-4 py-2 pe-4 ps-3 ${
                active
                    ? "border-sky-500 bg-sky-50 text-sky-700"
                    : "border-transparent text-ink-light hover:border-primary-light hover:bg-surface hover:text-ink"
            } text-base font-medium focus:outline-none ${className}`}
        >
            {children}
        </Link>
    );
}
