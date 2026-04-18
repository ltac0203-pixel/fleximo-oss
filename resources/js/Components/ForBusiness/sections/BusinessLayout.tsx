import ApplicationLogo from "@/Components/ApplicationLogo";
import { PRIMARY_BUTTON, TEXT_LINK_BUTTON } from "@/constants/buttonStyles";
import { Link } from "@inertiajs/react";

export function BusinessHeader() {
    return (
        <header className="pt-8">
            <div className="geo-public-shell px-4 py-4 sm:px-6">
                <div className="absolute inset-0 bg-grid-pattern opacity-[0.04]" />
                <div className="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-center">
                        <Link href={route("home")} className="flex items-center gap-4">
                            <div className="flex h-10 w-10 items-center justify-center">
                                <ApplicationLogo className="h-full w-full" />
                            </div>
                            <div className="flex items-center gap-3">
                                <div>
                                    <span className="text-xl font-semibold text-slate-800">Fleximo</span>
                                    <p className="mt-1 text-[11px] font-semibold uppercase tracking-[0.28em] text-sky-600">
                                        Store operation support
                                    </p>
                                </div>
                                <span className="border border-sky-200 bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-600">
                                    for Business
                                </span>
                            </div>
                        </Link>
                        <div className="hidden h-10 w-px bg-sky-100 lg:block" />
                        <p className="hidden max-w-sm text-xs leading-6 text-slate-500 lg:block">
                            飲食店・学食運営向けに、注文から決済までの流れを軽くする導入ページです。
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <Link href={route("home")} className={TEXT_LINK_BUTTON}>
                            一般の方はこちら
                        </Link>
                        <Link href={route("contact.index")} className={TEXT_LINK_BUTTON}>
                            お問い合わせ
                        </Link>
                        <Link href={route("for-business.login")} className={PRIMARY_BUTTON}>
                            事業者ログイン
                        </Link>
                    </div>
                </div>
            </div>
        </header>
    );
}

export function BusinessFooter() {
    return (
        <footer className="relative mt-20">
            <div className="geo-public-divider absolute left-0 right-0 top-0 h-px" />
            <div className="geo-public-shell-soft mt-8 px-6 py-8">
                <div className="geo-public-orb-cyan absolute -right-12 top-0 h-44 w-44 blur-3xl" />
                <div className="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="flex items-center gap-2 font-medium text-slate-700">
                            <ApplicationLogo className="h-5 w-5" />
                            <span>Fleximo</span>
                        </div>
                        <p className="mt-3 max-w-md text-sm leading-7 text-slate-600">
                            店舗運営に合わせて、注文導線・決済導線・受け取り導線を軽く整えるモバイルオーダー。
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-6 text-sm text-slate-600">
                        <Link href={route("home")} className={TEXT_LINK_BUTTON}>
                            トップページ
                        </Link>
                        <Link href={route("contact.index")} className={TEXT_LINK_BUTTON}>
                            お問い合わせ
                        </Link>
                        <Link href={route("tenant-application.create")} className={TEXT_LINK_BUTTON}>
                            テナント登録
                        </Link>
                        <Link href={route("legal.tenant-terms")} className={TEXT_LINK_BUTTON}>
                            テナント利用規約
                        </Link>
                        <Link href={route("legal.transactions")} className={TEXT_LINK_BUTTON}>
                            特定商取引法
                        </Link>
                    </div>
                </div>
                <div className="relative mt-6 flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-wrap gap-2">
                        {["初期費用0円", "月額0円", "キャッシュレス対応"].map((item) => (
                            <span
                                key={item}
                                className="rounded-full border border-sky-200 bg-white px-3 py-1.5 text-xs font-semibold text-sky-700"
                            >
                                {item}
                            </span>
                        ))}
                    </div>
                    <p className="text-sm text-slate-500">&copy; 2026 Fleximo. All rights reserved.</p>
                </div>
            </div>
        </footer>
    );
}
