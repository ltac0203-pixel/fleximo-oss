import ApplicationLogo from "@/Components/ApplicationLogo";
import Dropdown from "@/Components/Dropdown";
import ErrorBoundary, { FallbackProps } from "@/Components/ErrorBoundary";
import ErrorFallback from "@/Components/ErrorFallback";
import LogoutConfirmModal from "@/Components/LogoutConfirmModal";
import NavLink from "@/Components/NavLink";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink";
import GradientBackground from "@/Components/GradientBackground";
import SkipToContentLink, { MAIN_CONTENT_ID } from "@/Components/SkipToContentLink";
import { PageProps } from "@/types";
import { Link, usePage } from "@inertiajs/react";
import { PropsWithChildren, ReactNode, useState, useId } from "react";

function HeaderErrorFallback({ resetError }: FallbackProps) {
    return (
        <div className="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
            <div
                role="alert"
                className="flex flex-col gap-3 rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 sm:flex-row sm:items-center sm:justify-between"
            >
                <p>ヘッダーの表示に失敗しました。</p>
                <button
                    onClick={resetError}
                    className="inline-flex items-center justify-center border border-amber-300 bg-white px-3 py-1.5 font-medium text-amber-800 hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                >
                    再試行
                </button>
            </div>
        </div>
    );
}

export default function Authenticated({ header, children }: PropsWithChildren<{ header?: ReactNode }>) {
    const user = usePage<PageProps>().props.auth?.user;

    const [showLogoutModal, setShowLogoutModal] = useState(false);
    const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);
    const mobileMenuId = useId();

    return (
        <div className="relative min-h-screen bg-white">
            <SkipToContentLink />
            <GradientBackground />
            <nav className="relative border-b border-slate-200 bg-white">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link
                                    href="/"
                                    className="inline-flex items-center justify-center min-h-[44px] min-w-[44px] p-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:ring-offset-2"
                                >
                                    <ApplicationLogo className="block h-9 w-auto" />
                                </Link>
                            </div>

                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink href={route("dashboard")} active={route().current("dashboard")}>
                                    ダッシュボード
                                </NavLink>
                                {user?.is_tenant_admin && route().has("tenant.staff.page") && (
                                    <NavLink
                                        href={route("tenant.staff.page")}
                                        active={route().current("tenant.staff.*")}
                                    >
                                        スタッフ管理
                                    </NavLink>
                                )}
                            </div>
                        </div>

                        <div className="hidden sm:ms-6 sm:flex sm:items-center">
                            <div className="relative ms-3">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex">
                                            <button
                                                type="button"
                                                className="inline-flex items-center border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-slate-500 hover:text-slate-700 focus:outline-none"
                                            >
                                                <svg
                                                    className="me-2 h-4 w-4 text-sky-500"
                                                    viewBox="0 0 24 24"
                                                    fill="currentColor"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                >
                                                    <path d="M12 2l5.196 3v6L12 14 6.804 11V5z" opacity="0.3" />
                                                    <path d="M12 0L3.804 4.732v9.536L12 19l8.196-4.732V4.732L12 0zm0 2.144l6.196 3.577v7.558L12 16.856l-6.196-3.577V5.72L12 2.144z" />
                                                </svg>
                                                {user?.name ?? ""}

                                                <svg
                                                    className="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
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
                                            onClick={() => setShowLogoutModal(true)}
                                            className="flex w-full items-start border-l-2 border-transparent px-4 py-2 text-start text-sm text-slate-600 transition hover:bg-slate-50"
                                        >
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
                                        </button>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>

                        <div className="-me-2 flex items-center sm:hidden">
                            <button
                                onClick={() => setShowingNavigationDropdown((previousState) => !previousState)}
                                aria-label="メニュー"
                                aria-expanded={showingNavigationDropdown}
                                aria-controls={mobileMenuId}
                                aria-haspopup="menu"
                                className="inline-flex items-center justify-center p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-500 focus:bg-slate-100 focus:text-slate-500 focus:outline-none"
                            >
                                <svg className="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        className={!showingNavigationDropdown ? "inline-flex" : "hidden"}
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        className={showingNavigationDropdown ? "inline-flex" : "hidden"}
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    id={mobileMenuId}
                    role="navigation"
                    aria-label="モバイルメニュー"
                    className={(showingNavigationDropdown ? "block" : "hidden") + " sm:hidden"}
                >
                    <div className="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink href={route("dashboard")} active={route().current("dashboard")}>
                            ダッシュボード
                        </ResponsiveNavLink>
                        {user?.is_tenant_admin && route().has("tenant.staff.page") && (
                            <ResponsiveNavLink
                                href={route("tenant.staff.page")}
                                active={route().current("tenant.staff.*")}
                            >
                                スタッフ管理
                            </ResponsiveNavLink>
                        )}
                    </div>

                    <div className="border-t border-slate-200 pb-1 pt-4">
                        <div className="px-4">
                            <div className="text-base font-medium text-slate-900">{user?.name ?? ""}</div>
                            <div className="text-sm font-medium text-slate-500">{user?.email ?? ""}</div>
                        </div>

                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink href={route("profile.edit")}>プロフィール</ResponsiveNavLink>
                            <button
                                onClick={() => setShowLogoutModal(true)}
                                className="flex w-full items-start border-l-4 border-transparent py-2 pe-4 ps-3 text-base font-medium text-slate-600 transition duration-150 ease-in-out hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800 focus:border-slate-300 focus:bg-slate-50 focus:text-slate-800 focus:outline-none"
                            >
                                ログアウト
                            </button>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="relative border-b border-slate-200 bg-white">
                    <ErrorBoundary fallback={HeaderErrorFallback}>
                        <div className="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">{header}</div>
                    </ErrorBoundary>
                </header>
            )}

            <main id={MAIN_CONTENT_ID} tabIndex={-1} className="relative">
                <ErrorBoundary fallback={ErrorFallback}>{children}</ErrorBoundary>
            </main>

            <LogoutConfirmModal show={showLogoutModal} onClose={() => setShowLogoutModal(false)} />
        </div>
    );
}
