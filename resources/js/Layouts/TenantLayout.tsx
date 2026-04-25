import Dropdown from "@/Components/Dropdown";
import ErrorBoundary from "@/Components/ErrorBoundary";
import ErrorFallback from "@/Components/ErrorFallback";
import GradientBackground from "@/Components/GradientBackground";
import LogoutConfirmModal from "@/Components/LogoutConfirmModal";
import SkipToContentLink, { MAIN_CONTENT_ID } from "@/Components/SkipToContentLink";
import { useOnboardingStore } from "@/stores/onboardingStore";
import { usePage } from "@inertiajs/react";
import { PropsWithChildren, useState } from "react";
import { TenantPageProps } from "@/types";
import { getNavigation } from "./TenantLayout/navigationConfig";
import Sidebar from "./TenantLayout/Sidebar";
import MobileSidebar from "./TenantLayout/MobileSidebar";

export default function TenantLayout({ title, children }: PropsWithChildren<{ title?: string }>) {
    const [showingMobileSidebar, setShowingMobileSidebar] = useState(false);
    const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
    const [showLogoutModal, setShowLogoutModal] = useState(false);
    const pageProps = usePage<TenantPageProps>().props;
    const user = pageProps.auth.user!;
    const tenant = pageProps.tenant;
    const openOnboarding = useOnboardingStore((state) => state.openManual);

    const isApproved = tenant.is_approved !== false;
    const navigation = getNavigation(isApproved);

    return (
        <div className="relative min-h-screen bg-surface">
            <GradientBackground variant="dashboard" />
            <SkipToContentLink />
            {/* Header */}
            <header className="fixed top-0 left-0 right-0 z-20 h-14 border-b border-edge bg-white/95 backdrop-blur-sm">
                <div className="h-full px-4 flex items-center gap-3">
                    {/* サイドバートグル（デスクトップ） */}
                    <button
                        onClick={() => setSidebarCollapsed(!sidebarCollapsed)}
                        className="hidden md:flex p-2 text-muted-light hover:text-muted hover:bg-surface-dim"
                        title={sidebarCollapsed ? "メニューを開く" : "メニューを閉じる"}
                    >
                        <svg className="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>

                    {/* ハンバーガーメニュー（モバイル） */}
                    <button
                        onClick={() => setShowingMobileSidebar(!showingMobileSidebar)}
                        className="md:hidden p-2 text-muted-light hover:text-muted hover:bg-surface-dim"
                    >
                        <svg className="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path
                                className={showingMobileSidebar ? "hidden" : "inline-flex"}
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                            <path
                                className={showingMobileSidebar ? "inline-flex" : "hidden"}
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>

                    {/* テナント名 & ページタイトル */}
                    <div className="flex items-center gap-2 min-w-0 flex-1">
                        <span className="text-sm font-medium text-muted truncate">{tenant.name}</span>
                        {title && (
                            <>
                                <span className="text-edge-strong">/</span>
                                <h1 className="text-base font-semibold text-ink truncate">{title}</h1>
                            </>
                        )}
                    </div>

                    {/* ユーザードロップダウン */}
                    <Dropdown>
                        <Dropdown.Trigger>
                            <button className="flex items-center text-sm font-medium text-muted hover:text-ink-light">
                                <svg
                                    className="me-2 h-4 w-4 text-sky-500"
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
                            <button
                                type="button"
                                onClick={openOnboarding}
                                className="flex w-full items-start border-l-2 border-transparent px-4 py-2 text-start text-sm text-ink-light transition hover:bg-surface"
                            >
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
                                            d="M9.663 17h4.673M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                                        />
                                    </svg>
                                    オンボーディングを見る
                                </span>
                            </button>
                            <button
                                onClick={() => setShowLogoutModal(true)}
                                className="flex w-full items-start border-l-2 border-transparent px-4 py-2 text-start text-sm text-ink-light transition hover:bg-surface"
                            >
                                <span className="inline-flex items-center gap-2">
                                    <svg
                                        className="h-4 w-4 text-muted-light"
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
                                    ログアウト{" "}
                                </span>
                            </button>
                        </Dropdown.Content>
                    </Dropdown>
                </div>
            </header>

            <Sidebar
                navigation={navigation}
                collapsed={sidebarCollapsed}
                onToggleCollapse={() => setSidebarCollapsed(!sidebarCollapsed)}
            />

            <MobileSidebar
                navigation={navigation}
                tenantName={tenant.name}
                open={showingMobileSidebar}
                onClose={() => setShowingMobileSidebar(false)}
            />

            {/* Main Content */}
            <main
                id={MAIN_CONTENT_ID}
                tabIndex={-1}
                className={`relative pt-14 ${sidebarCollapsed ? "md:ml-16" : "md:ml-56"}`}
            >
                <div className="geo-fade-in mx-auto max-w-5xl py-6 px-4 sm:px-6 lg:px-8">
                    <ErrorBoundary fallback={ErrorFallback}>{children}</ErrorBoundary>
                </div>
            </main>

            <LogoutConfirmModal show={showLogoutModal} onClose={() => setShowLogoutModal(false)} />
        </div>
    );
}
