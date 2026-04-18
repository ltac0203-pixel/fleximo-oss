import GeoSurface from "@/Components/GeoSurface";
import GradientBackground from "@/Components/GradientBackground";
import FavoriteButton from "@/Components/Customer/Common/FavoriteButton";
import { useFavorites } from "@/Hooks/useFavorites";
import { BusinessHourRange, PageProps } from "@/types";
import { Head, Link, usePage } from "@inertiajs/react";
import { useMemo, useState } from "react";

interface Tenant {
    id: number;
    name: string;
    slug: string;
    address: string | null;
    is_open: boolean;
    today_business_hours?: BusinessHourRange[];
}

interface CustomerHomeProps extends PageProps {
    tenants: Tenant[];
    favoriteTenantIds: number[];
}

function TenantCard({
    tenant,
    businessHours,
    isFavorited,
    isToggling,
    onToggleFavorite,
}: {
    tenant: Tenant;
    businessHours: string | null;
    isFavorited: boolean;
    isToggling: boolean;
    onToggleFavorite: (tenantId: number) => void;
}) {
    return (
        <GeoSurface
            as={Link}
            href={route("order.menu", { tenant: tenant.slug })}
            interactive
            topAccent
            className="block p-4 hover:border-sky-300"
        >
            <div className="flex items-start justify-between">
                <div className="flex-1">
                    <div className="flex items-center gap-2">
                        <h3 className="font-semibold text-slate-900">{tenant.name}</h3>
                        <span
                            className={`text-xs font-medium px-2 py-0.5 rounded-full ${
                                tenant.is_open ? "bg-cyan-100 text-cyan-700" : "bg-slate-100 text-slate-600"
                            }`}
                        >
                            {tenant.is_open ? "営業中" : "営業時間外"}
                        </span>
                    </div>
                    {tenant.address && <p className="text-sm text-slate-500 mt-1">{tenant.address}</p>}
                    {businessHours && <p className="text-sm text-slate-400 mt-1">{businessHours}</p>}
                </div>
                <div className="flex items-center gap-1 flex-shrink-0">
                    <FavoriteButton
                        isFavorited={isFavorited}
                        isToggling={isToggling}
                        onClick={() => onToggleFavorite(tenant.id)}
                    />
                    <svg className="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </div>
        </GeoSurface>
    );
}

export default function CustomerHome({ tenants, favoriteTenantIds }: CustomerHomeProps) {
    const user = usePage<PageProps>().props.auth?.user;
    const [searchQuery, setSearchQuery] = useState("");
    const { isFavorited, toggleFavorite, isToggling, favoriteIds } = useFavorites({
        initialFavoriteIds: favoriteTenantIds,
    });

    const filteredTenants = useMemo(() => {
        const data = tenants ?? [];
        if (!searchQuery.trim()) {
            return data;
        }
        const query = searchQuery.toLowerCase();
        return data.filter(
            (tenant) => tenant.name.toLowerCase().includes(query) || tenant.address?.toLowerCase().includes(query),
        );
    }, [tenants, searchQuery]);

    const favoriteTenants = useMemo(() => {
        const data = tenants ?? [];
        return data.filter((tenant) => favoriteIds.has(tenant.id));
    }, [tenants, favoriteIds]);

    const formatBusinessHours = (hours?: BusinessHourRange[]) => {
        if (!hours || hours.length === 0) {
            return null;
        }
        return hours.map((range) => `${range.open_time} - ${range.close_time}`).join(" / ");
    };

    const handleToggleFavorite = (tenantId: number) => {
        void toggleFavorite(tenantId);
    };

    return (
        <>
            <Head title="ホーム" />

            <div className="relative min-h-screen bg-slate-50">
                <GradientBackground variant="customer" />
                <header className="sticky top-0 z-10 border-b border-slate-200 bg-white/95 backdrop-blur-sm">
                    <div className="max-w-lg lg:max-w-5xl mx-auto px-4 py-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-xl font-bold text-slate-900">Fleximo</h1>
                                <p className="text-sm text-slate-500">こんにちは、{user?.name ?? "ゲスト"}さん</p>
                            </div>
                            <div className="flex items-center gap-3">
                                <Link
                                    href={route("order.orders.index")}
                                    className="text-slate-500 hover:text-slate-700"
                                >
                                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                        />
                                    </svg>
                                </Link>
                                <Link href={route("profile.edit")} className="text-slate-500 hover:text-slate-700">
                                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                        />
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </header>

                <main className="relative max-w-lg lg:max-w-5xl mx-auto px-4 py-6 space-y-6 geo-fade-in">
                    <GeoSurface tone="sky" elevated className="relative p-4">
                        <p className="text-xs font-semibold uppercase tracking-widest text-sky-600">Order Hub</p>
                        <h2 className="mt-1 text-lg font-semibold text-slate-900">今日の営業店舗を選択</h2>
                    </GeoSurface>

                    <GeoSurface className="relative p-3">
                        <input
                            type="text"
                            placeholder="店舗を検索..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="w-full border border-slate-200 px-4 py-3 pl-10 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-sky-500"
                        />
                        <svg
                            className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </GeoSurface>

                    {/* お気に入りセクション：検索未使用時かつお気に入りがある場合に表示 */}
                    {!searchQuery.trim() && favoriteTenants.length > 0 && (
                        <section>
                            <h2 className="text-lg font-semibold text-slate-900 mb-3">
                                お気に入り
                                <span className="text-sm font-normal text-slate-500 ml-2">
                                    ({favoriteTenants.length}件)
                                </span>
                            </h2>
                            <div className="grid grid-cols-1 gap-3 lg:grid-cols-2 lg:gap-4">
                                {favoriteTenants.map((tenant) => (
                                    <TenantCard
                                        key={tenant.id}
                                        tenant={tenant}
                                        businessHours={formatBusinessHours(tenant.today_business_hours)}
                                        isFavorited={true}
                                        isToggling={isToggling}
                                        onToggleFavorite={handleToggleFavorite}
                                    />
                                ))}
                            </div>
                        </section>
                    )}

                    <section>
                        <h2 className="text-lg font-semibold text-slate-900 mb-3">
                            店舗一覧
                            {searchQuery && (
                                <span className="text-sm font-normal text-slate-500 ml-2">
                                    ({filteredTenants.length}件)
                                </span>
                            )}
                        </h2>

                        {filteredTenants.length === 0 ? (
                            <GeoSurface className="p-8 text-center">
                                <svg
                                    className="w-12 h-12 text-slate-300 mx-auto mb-3"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                    />
                                </svg>
                                <p className="text-slate-500">
                                    {searchQuery ? "該当する店舗が見つかりません" : "利用可能な店舗がありません"}
                                </p>
                            </GeoSurface>
                        ) : (
                            <div className="grid grid-cols-1 gap-3 lg:grid-cols-2 lg:gap-4">
                                {filteredTenants.map((tenant) => (
                                    <TenantCard
                                        key={tenant.id}
                                        tenant={tenant}
                                        businessHours={formatBusinessHours(tenant.today_business_hours)}
                                        isFavorited={isFavorited(tenant.id)}
                                        isToggling={isToggling}
                                        onToggleFavorite={handleToggleFavorite}
                                    />
                                ))}
                            </div>
                        )}
                    </section>
                </main>
            </div>
        </>
    );
}
