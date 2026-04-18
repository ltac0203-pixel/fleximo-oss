import { Link } from "@inertiajs/react";
import { NavigationItem } from "./navigationConfig";

interface MobileSidebarProps {
    navigation: NavigationItem[];
    tenantName: string;
    open: boolean;
    onClose: () => void;
}

export default function MobileSidebar({ navigation, tenantName, open, onClose }: MobileSidebarProps) {
    if (!open) return null;

    return (
        <div className="fixed inset-0 z-30 md:hidden">
            <div className="fixed inset-0 bg-slate-900/40" role="presentation" aria-hidden="true" onClick={onClose} />
            <aside className="fixed left-0 top-0 bottom-0 w-56 max-w-[85vw] bg-white">
                <div className="h-14 flex items-center px-4 border-b border-slate-200">
                    <h2 className="text-base font-semibold">{tenantName}</h2>
                </div>
                <nav className="px-3 py-4 space-y-1">
                    {navigation.map((item) => (
                        <Link
                            key={item.name}
                            href={item.href}
                            className={`flex items-center gap-3 px-3 py-2 text-sm font-medium ${
                                item.current
                                    ? "bg-sky-50 text-sky-700 border-l-2 border-sky-500"
                                    : "text-slate-700 hover:bg-slate-100"
                            }`}
                            onClick={onClose}
                        >
                            <span className="flex-shrink-0">{item.icon}</span>
                            <span>{item.name}</span>
                        </Link>
                    ))}
                </nav>
            </aside>
        </div>
    );
}
