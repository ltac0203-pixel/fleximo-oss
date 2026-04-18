import { Link } from "@inertiajs/react";
import TenantStatusBadge from "@/Components/Customer/Common/TenantStatusBadge";
import { ReactNode } from "react";

interface FixedHeaderProps {
    tenant: {
        name: string;
        is_open?: boolean;
    };
    backHref?: string;
    rightAction?: ReactNode;
}

export default function FixedHeader({ tenant, backHref = "/", rightAction }: FixedHeaderProps) {
    return (
        <header className="safe-top fixed top-0 left-0 right-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur-sm">
            <div className="h-14 px-4 flex items-center justify-between max-w-lg lg:max-w-5xl mx-auto">
                <Link href={backHref} className="text-slate-500 hover:text-slate-700">
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div className="text-center">
                    <h1 className="text-lg font-semibold text-slate-900">{tenant.name}</h1>
                    {tenant.is_open !== undefined && (
                        <TenantStatusBadge isOpen={tenant.is_open} size="sm" />
                    )}
                </div>
                {rightAction ?? <div className="w-6" />}
            </div>
        </header>
    );
}
