import Dropdown from "@/Components/Dropdown";
import ErrorBoundary from "@/Components/ErrorBoundary";
import ErrorFallback from "@/Components/ErrorFallback";
import SkipToContentLink, { MAIN_CONTENT_ID } from "@/Components/SkipToContentLink";
import { Link, usePage } from "@inertiajs/react";
import { PropsWithChildren, ReactNode, useState } from "react";
import { PageProps } from "@/types";

export default function AdminLayout({ header, children }: PropsWithChildren<{ header?: ReactNode }>) {
    const [showingSidebar, setShowingSidebar] = useState(false);
    const user = usePage<PageProps>().props.auth.user!;

    const navigation = [
        {
            name: "ダッシュボード",
            href: route("admin.dashboard"),
            current: route().current("admin.dashboard"),
        },
        {
            name: "テナント申し込み",
            href: route("admin.applications.index"),
            current: route().current("admin.applications.*"),
        },
        {
            name: "Shop ID管理",
            href: route("admin.tenant-shop-ids.index"),
            current: route().current("admin.tenant-shop-ids.*"),
        },
        {
            name: "顧客管理",
            href: route("admin.customers.index"),
            current: route().current("admin.customers.*"),
        },
    ];

    return (
        <div className="min-h-screen bg-slate-50">
            <SkipToContentLink />
            {/* Header: 固定トップバー を明示し、実装意図の誤読を防ぐ。 */}
            <header className="fixed top-0 left-0 right-0 z-20 h-16 bg-slate-800 border-b border-slate-700">
                <div className="h-full px-4 flex items-center justify-between">
                    {/* ハンバーガーメニュー（モバイル） を明示し、実装意図の誤読を防ぐ。 */}
                    <button
                        onClick={() => setShowingSidebar(!showingSidebar)}
                        className="md:hidden p-2 text-slate-300 hover:text-white hover:bg-slate-700"
                    >
                        <svg className="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path
                                className={showingSidebar ? "hidden" : "inline-flex"}
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                            <path
                                className={showingSidebar ? "inline-flex" : "hidden"}
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>

                    {/* ロゴ を明示し、実装意図の誤読を防ぐ。 */}
                    <div className="flex items-center">
                        <h1 className="text-lg font-semibold text-white">Fleximo 管理</h1>
                    </div>

                    {/* ユーザードロップダウン を明示し、実装意図の誤読を防ぐ。 */}
                    <Dropdown>
                        <Dropdown.Trigger>
                            <button className="flex items-center text-sm font-medium text-slate-300 hover:text-white">
                                <svg
                                    className="me-2 h-4 w-4 text-sky-400"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path d="M12 2l5.196 3v6L12 14 6.804 11V5z" opacity="0.3" />
                                    <path d="M12 0L3.804 4.732v9.536L12 19l8.196-4.732V4.732L12 0zm0 2.144l6.196 3.577v7.558L12 16.856l-6.196-3.577V5.72L12 2.144z" />
                                </svg>
                                {user.name}
                                <svg className="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fillRule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clipRule="evenodd"
                                    />
                                </svg>
                            </button>
                        </Dropdown.Trigger>
                        <Dropdown.Content>
                            <Dropdown.Link href={route("profile.edit")}>
                                <span className="inline-flex items-center gap-2">
                                    <svg
                                        className="h-4 w-4 text-sky-500"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        strokeWidth="2"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                        />
                                    </svg>
                                    プロフィール
                                </span>
                            </Dropdown.Link>
                            <Dropdown.Link href={route("logout")} method="post" as="button">
                                <span className="inline-flex items-center gap-2">
                                    <svg
                                        className="h-4 w-4 text-slate-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        strokeWidth="2"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                        />
                                    </svg>
                                    ログアウト
                                </span>
                            </Dropdown.Link>
                        </Dropdown.Content>
                    </Dropdown>
                </div>
            </header>

            {/* Sidebar: 固定左サイドバー（デスクトップ） を明示し、実装意図の誤読を防ぐ。 */}
            <aside className="hidden md:fixed md:left-0 md:top-16 md:h-[calc(100vh-4rem)] md:w-64 md:flex md:flex-col bg-slate-800">
                <nav className="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                    {navigation.map((item) => (
                        <Link
                            key={item.name}
                            href={item.href}
                            className={`block px-4 py-2 text-sm font-medium ${
                                item.current
                                    ? "bg-slate-700 text-white"
                                    : "text-slate-300 hover:bg-slate-700 hover:text-white"
                            }`}
                        >
                            {item.name}
                        </Link>
                    ))}
                </nav>
            </aside>

            {/* モバイルサイドバー（オーバーレイ） を明示し、実装意図の誤読を防ぐ。 */}
            {showingSidebar && (
                <div className="fixed inset-0 z-30 md:hidden">
                    {/* オーバーレイ背景 を明示し、実装意図の誤読を防ぐ。 */}
                    <div className="fixed inset-0 bg-slate-900/60" role="presentation" aria-hidden="true" onClick={() => setShowingSidebar(false)} />
                    {/* サイドバー を明示し、実装意図の誤読を防ぐ。 */}
                    <aside className="fixed left-0 top-0 bottom-0 w-64 max-w-[85vw] bg-slate-800">
                        <div className="h-16 flex items-center px-4 border-b border-slate-700">
                            <h2 className="text-lg font-semibold text-white">Fleximo 管理</h2>
                        </div>
                        <nav className="px-4 py-6 space-y-2">
                            {navigation.map((item) => (
                                <Link
                                    key={item.name}
                                    href={item.href}
                                    className={`block px-4 py-2 text-sm font-medium ${
                                        item.current
                                            ? "bg-slate-700 text-white"
                                            : "text-slate-300 hover:bg-slate-700 hover:text-white"
                                    }`}
                                    onClick={() => setShowingSidebar(false)}
                                >
                                    {item.name}
                                </Link>
                            ))}
                        </nav>
                    </aside>
                </div>
            )}

            {/* Main Content を明示し、実装意図の誤読を防ぐ。 */}
            <main id={MAIN_CONTENT_ID} tabIndex={-1} className="pt-16 md:ml-64">
                {header && (
                    <div className="bg-white border-b border-slate-200">
                        <div className="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8">{header}</div>
                    </div>
                )}
                <div className="mx-auto max-w-5xl py-6 px-4 sm:px-6 lg:px-8">
                    <ErrorBoundary fallback={ErrorFallback}>{children}</ErrorBoundary>
                </div>
            </main>
        </div>
    );
}
