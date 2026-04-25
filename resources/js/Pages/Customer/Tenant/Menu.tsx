import FavoriteButton from "@/Components/Customer/Common/FavoriteButton";
import CategoryTabs from "@/Components/Customer/Menu/CategoryTabs";
import ItemDetailModal from "@/Components/Customer/Menu/ItemDetailModal";
import VirtualizedMenuList from "@/Components/Customer/Menu/VirtualizedMenuList";
import SeoHead from "@/Components/SeoHead";
import ToastContainer from "@/Components/UI/ToastContainer";
import { useCart } from "@/Hooks/useCart";
import { useFavorites } from "@/Hooks/useFavorites";
import { useMenuCategorySync } from "@/Hooks/useMenuCategorySync";
import { useSeo } from "@/Hooks/useSeo";
import { useToast } from "@/Hooks/useToast";
import CustomerLayout from "@/Layouts/CustomerLayout";
import { CartItemData, CustomerMenuItem, CustomerMenuPageProps, PageProps } from "@/types";
import type { SeoMetadata, StructuredData } from "@/types/seo";
import { Link, router, usePage } from "@inertiajs/react";
import { useCallback, useMemo, useState } from "react";

interface MenuPageProps extends CustomerMenuPageProps {
    tenant: CustomerMenuPageProps["tenant"] & {
        is_favorited?: boolean;
    };
    seo?: Partial<SeoMetadata>;
    structuredData?: StructuredData | StructuredData[];
}

export default function Menu({ tenant, menu, seo, structuredData }: MenuPageProps) {
    const { auth } = usePage<PageProps>().props;
    const { generateMetadata } = useSeo();
    const isAuthenticated = !!auth?.user;
    const categories = useMemo(() => menu.categories ?? [], [menu.categories]);

    const { isFavorited, toggleFavorite, isToggling } = useFavorites({
        initialFavoriteIds: tenant.is_favorited ? [tenant.id] : [],
    });

    const [selectedItem, setSelectedItem] = useState<CustomerMenuItem | null>(null);
    const [isAddingToCart, setIsAddingToCart] = useState(false);

    const { activeCategoryId, scrollToCategoryId, onCategoryTabChange, onActiveCategoryChange, onScrollComplete } =
        useMenuCategorySync(categories);

    // 成否通知を画面内で完結させ、モーダル閉鎖後も結果を伝えられるようにする。
    const { toasts, showToast, hideToast } = useToast();

    // ゲスト閲覧を許可しつつ、件数表示は認証時のみ正確に出すため条件分岐する。
    const { addToCart, getTotalItemCount } = useCart({ autoFetch: isAuthenticated });
    const cartItemCount = isAuthenticated ? getTotalItemCount() : 0;

    const handleCloseModal = useCallback(() => {
        setSelectedItem(null);
    }, []);

    const handleItemClick = useCallback((item: CustomerMenuItem) => {
        if (item.is_sold_out || !item.is_available) return;
        setSelectedItem(item);
    }, []);

    const handleAddToCart = useCallback(
        async (data: CartItemData) => {
            // 購入操作は認証必須のため、意図した遷移先を保持してログインへ送る。
            if (!isAuthenticated) {
                router.visit("/login", {
                    data: { redirect: window.location.pathname },
                });
                return;
            }

            setIsAddingToCart(true);
            try {
                const { cart, error: addError } = await addToCart(
                    tenant.id,
                    data.menuItemId,
                    data.quantity,
                    data.selectedOptions,
                );

                if (cart) {
                    // 追加成功後は入力文脈を閉じ、重複追加を防ぎつつ結果を通知する。
                    handleCloseModal();
                    showToast({
                        type: "success",
                        message: "カートに追加しました",
                    });
                } else {
                    // 失敗理由を即提示し、ユーザーが次の行動を判断できるようにする。
                    showToast({
                        type: "error",
                        message: addError ?? "カートへの追加に失敗しました",
                    });
                }
            } finally {
                setIsAddingToCart(false);
            }
        },
        [isAuthenticated, tenant.id, addToCart, handleCloseModal, showToast],
    );

    const handleCartClick = useCallback(() => {
        // カート閲覧も認証境界内に置き、注文データ漏えいを防ぐ。
        if (!isAuthenticated) {
            router.visit("/login", {
                data: { redirect: "/order/cart" },
            });
            return;
        }

        router.visit("/order/cart");
    }, [isAuthenticated]);

    const metadata = generateMetadata(
        seo ?? {
            title: `${tenant.name} メニュー`,
            description: `${tenant.name}のモバイルオーダーメニューです。`,
        },
    );

    return (
        <>
            <SeoHead metadata={metadata} structuredData={structuredData} />

            <CustomerLayout
                tenant={tenant}
                stickyHeader={
                    (menu.categories?.length ?? 0) > 0 && (
                        <CategoryTabs
                            categories={categories}
                            activeCategoryId={activeCategoryId}
                            onCategoryChange={onCategoryTabChange}
                        />
                    )
                }
                headerRightAction={
                    isAuthenticated ? (
                        <div className="flex items-center gap-2">
                            <Link
                                href={route("order.cards.index", { tenant: tenant.slug })}
                                className="text-slate-500 hover:text-slate-700 transition"
                                aria-label="カード管理"
                            >
                                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                                    />
                                </svg>
                            </Link>
                            <FavoriteButton
                                isFavorited={isFavorited(tenant.id)}
                                isToggling={isToggling}
                                onClick={() => void toggleFavorite(tenant.id)}
                            />
                        </div>
                    ) : undefined
                }
                showCartButton={true}
                cartItemCount={cartItemCount}
                onCartClick={handleCartClick}
            >
                {/* 空メニュー時に理由を明示し、表示不具合と誤解されるのを防ぐ。 */}
                {(menu.categories?.length ?? 0) === 0 && (
                    <div className="flex flex-col items-center justify-center py-16 px-4">
                        <svg
                            className="w-16 h-16 text-slate-300 mb-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={1.5}
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                            />
                        </svg>
                        <p className="text-slate-500 text-center">メニューがありません</p>
                    </div>
                )}

                {/* 件数増加時の描画コストを抑えるため、仮想化リストを使う。 */}
                {(menu.categories?.length ?? 0) > 0 && (
                    <VirtualizedMenuList
                        categories={categories}
                        activeCategoryId={activeCategoryId}
                        onActiveCategoryChange={onActiveCategoryChange}
                        onItemClick={handleItemClick}
                        scrollToCategoryId={scrollToCategoryId}
                        onScrollComplete={onScrollComplete}
                    />
                )}
            </CustomerLayout>

            {/* 一覧文脈を保ったまま詳細選択できるよう、同一画面上でモーダル表示する。 */}
            <ItemDetailModal
                show={selectedItem !== null}
                item={selectedItem}
                onClose={handleCloseModal}
                onAddToCart={(data) => {
                    void handleAddToCart(data);
                }}
                isLoading={isAddingToCart}
            />

            {/* 非同期結果をページ遷移なしで返し、操作の連続性を維持する。 */}
            <ToastContainer toasts={toasts} onClose={hideToast} />
        </>
    );
}
