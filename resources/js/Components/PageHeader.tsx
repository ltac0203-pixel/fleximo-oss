import { ReactNode } from "react";

interface PageHeaderProps {
    title: ReactNode;
    description?: ReactNode;
    help?: ReactNode;
    actions?: ReactNode;
    className?: string;
}

export default function PageHeader({ title, description, help, actions, className = "" }: PageHeaderProps) {
    return (
        <div className={`mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between ${className}`}>
            <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2">
                    <h2 className="text-lg font-semibold text-ink">{title}</h2>
                    {help}
                </div>
                {description ? <p className="mt-1 text-sm text-muted">{description}</p> : null}
            </div>
            {actions ? <div className="flex shrink-0 items-center gap-2">{actions}</div> : null}
        </div>
    );
}
