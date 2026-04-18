import ApplicationLogo from "@/Components/ApplicationLogo";
import { Link } from "@inertiajs/react";
import { TEXT_LINK_BUTTON, HEADER_LOGIN_BUTTON } from "@/constants/buttonStyles";

interface WelcomeHeaderProps {
    isLoggedIn: boolean;
}

function WelcomeHeader({ isLoggedIn }: WelcomeHeaderProps) {
    return (
        <header className="pt-6 sm:pt-8">
            <div className="geo-public-shell px-4 py-4 sm:px-6">
                <div className="absolute inset-0 bg-grid-pattern opacity-[0.04]" />
                <div className="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-center">
                        <Link href={route("home")} className="flex items-center gap-4">
                            <div className="flex h-10 w-10 items-center justify-center">
                                <ApplicationLogo className="h-full w-full" />
                            </div>
                            <div>
                                <span className="text-xl font-semibold text-ink">Fleximo</span>
                                <p className="mt-1 text-[11px] font-semibold uppercase tracking-[0.28em] text-sky-600">
                                    Multi-tenant mobile order
                                </p>
                            </div>
                        </Link>
                        <div className="hidden h-10 w-px bg-sky-100 lg:block" />
                        <p className="hidden max-w-xs text-xs leading-6 text-slate-500 lg:block">
                            混み合うピーク時にちょうどいい、アプリ不要のモバイルオーダー。
                        </p>
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div className="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-white/80 px-3 py-2 text-xs font-semibold text-sky-700 shadow-sm">
                            <span className="flex h-2 w-2 rounded-full bg-sky-400" />
                            PayPay対応
                        </div>
                        <div className="flex items-center gap-4">
                            <Link
                                href={route("for-business.index")}
                                className={TEXT_LINK_BUTTON}
                            >
                                事業者の方はこちら
                            </Link>
                            {!isLoggedIn && (
                                <Link
                                    href={route("login")}
                                    className={HEADER_LOGIN_BUTTON}
                                >
                                    ログイン
                                </Link>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </header>
    );
}

export default WelcomeHeader;
