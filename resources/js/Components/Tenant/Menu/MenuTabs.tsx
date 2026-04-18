import { Link } from "@inertiajs/react";

interface MenuTabsProps {
    activeTab: "categories" | "items" | "optionGroups";
}

export default function MenuTabs({ activeTab }: MenuTabsProps) {
    const tabs = [
        { key: "categories", label: "カテゴリ", route: "tenant.menu.categories.page" },
        { key: "items", label: "商品", route: "tenant.menu.items.page" },
        { key: "optionGroups", label: "オプション", route: "tenant.menu.option-groups.page" },
    ];

    return (
        <div className="mb-6 border-b border-edge">
            <nav className="-mb-px flex space-x-8" aria-label="Tabs">
                {tabs.map((tab) => (
                    <Link
                        key={tab.key}
                        href={route(tab.route)}
                        className={
                            activeTab === tab.key
                                ? "border-primary text-primary-dark whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium"
                                : "border-transparent text-muted hover:border-edge-strong hover:text-ink-light whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium"
                        }
                    >
                        {tab.label}
                    </Link>
                ))}
            </nav>
        </div>
    );
}
