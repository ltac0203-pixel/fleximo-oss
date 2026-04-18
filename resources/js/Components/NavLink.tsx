import { InertiaLinkProps, Link } from "@inertiajs/react";

export default function NavLink({
    active = false,
    className = "",
    children,
    ...props
}: InertiaLinkProps & { active: boolean }) {
    return (
        <Link
            {...props}
            className={
                "inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium leading-5 focus:outline-none " +
                (active
                    ? "border-sky-500 text-ink"
                    : "border-transparent text-ink-light hover:border-sky-400 hover:text-ink") +
                " " +
                className
            }
        >
            {children}
        </Link>
    );
}
