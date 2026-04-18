import { Link } from "@inertiajs/react";
import { NavigationItem } from "./navigationConfig";

interface SidebarProps {
    navigation: NavigationItem[];
    collapsed: boolean;
    onToggleCollapse: () => void;
}

export default function Sidebar({ navigation, collapsed, onToggleCollapse }: SidebarProps) {
    return (
        <aside
            className={`hidden md:fixed md:left-0 md:top-14 md:z-10 md:flex md:h-[calc(100vh-3.5rem)] md:flex-col border-r border-slate-200 bg-white/95 backdrop-blur-sm ${
                collapsed ? "md:w-16" : "md:w-56"
            }`}
        >
            <nav className={`flex-1 py-4 space-y-1 overflow-y-auto ${collapsed ? "px-2" : "px-3"}`}>
                {navigation.map((item) => (
                    <Link
                        key={item.name}
                        href={item.href}
                        className={`flex items-center text-sm font-medium ${
                            collapsed ? "justify-center px-2 py-2.5" : "px-3 py-2 gap-3"
                        } ${
                            item.current
                                ? "relative border-l-2 border-sky-500 bg-sky-50 text-sky-700 shadow-sm"
                                : "text-slate-700 geo-hover-sidebar hover:text-slate-900"
                        }`}
                        title={collapsed ? item.name : undefined}
                    >
                        <span className="flex-shrink-0">{item.icon}</span>
                        {!collapsed && <span>{item.name}</span>}
                    </Link>
                ))}
            </nav>
            <div className={`border-t py-3 ${collapsed ? "px-2" : "px-3"}`}>
                <button
                    onClick={onToggleCollapse}
                    className={`flex items-center w-full text-sm text-slate-500 geo-hover-sidebar hover:text-slate-700 ${
                        collapsed ? "justify-center px-2 py-2.5" : "px-3 py-2 gap-3"
                    }`}
                    title={collapsed ? "メニューを開く" : "メニューを閉じる"}
                >
                    <svg
                        className={`w-5 h-5 ${collapsed ? "rotate-180" : ""}`}
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M11 19l-7-7 7-7m8 14l-7-7 7-7"
                        />
                    </svg>
                    {!collapsed && <span>閉じる</span>}
                </button>
            </div>
        </aside>
    );
}
