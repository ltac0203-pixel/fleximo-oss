import { Link } from "@inertiajs/react";
import { PRIMARY_BUTTON_LARGE, SECONDARY_BUTTON_LARGE } from "@/constants/buttonStyles";

interface HeroSectionProps {
    userName?: string;
    isLoggedIn: boolean;
}

const quickHighlights = ["QRコードで即開始", "アプリ不要", "メニュー閲覧は登録不要", "キャッシュレス対応"] as const;

function HeroSection({ userName, isLoggedIn }: HeroSectionProps) {
    const ctaHref = isLoggedIn ? route("dashboard") : route("register");
    const ctaLabel = isLoggedIn ? "ダッシュボードへ" : "無料で会員登録";

    return (
        <section className="relative flex min-h-[calc(100vh-132px)] items-center py-12 sm:py-16 lg:py-24">
            <div className="grid w-full gap-14 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)] lg:items-center">
                <div className="max-w-2xl">
                    <div className="inline-flex items-center gap-3 rounded-full border border-sky-200 bg-white/80 px-4 py-2 text-xs font-semibold uppercase tracking-[0.26em] text-sky-700 shadow-sm backdrop-blur-sm">
                        <span className="flex h-2 w-2 rounded-full bg-sky-400" />
                        学食モバイルオーダー
                    </div>

                    <h1 className="mt-8 text-5xl font-bold leading-[1.03] text-slate-900 sm:text-6xl lg:text-7xl">
                        昼休みの行列、
                        <br />
                        <span className="mt-2 inline-block text-sky-500">まだ並んでる？</span>
                    </h1>

                    <p className="mt-8 max-w-xl text-base leading-8 text-slate-600 sm:text-lg">
                        お店のQRコードを読み取れば、その場でメニューが開きます。
                        アプリ不要・会員登録は約30秒。スマホひとつで注文から受け取りまで完結します。
                    </p>

                    {userName && (
                        <div className="mt-8 inline-flex items-center gap-3 rounded-full border border-sky-200 bg-white/80 px-4 py-2 shadow-sm backdrop-blur-sm">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-sky-400 text-sm font-medium text-white">
                                {userName.charAt(0)}
                            </div>
                            <span className="text-sm text-slate-700">
                                おかえりなさい、
                                <span className="font-medium text-sky-600">{userName}</span>
                                さん
                            </span>
                        </div>
                    )}

                    <div className="mt-10">
                        <div className="flex flex-col items-start gap-4 sm:flex-row">
                            <Link href={ctaHref} className={PRIMARY_BUTTON_LARGE}>
                                {ctaLabel}
                            </Link>
                            {!isLoggedIn && (
                                <Link href={route("login")} className={SECONDARY_BUTTON_LARGE}>
                                    ログインはこちら
                                </Link>
                            )}
                        </div>

                        <div className="geo-public-panel-soft mt-6 px-5 py-5">
                            <div className="flex items-start gap-4">
                                <svg
                                    className="h-8 w-8 shrink-0 text-sky-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    strokeWidth={1.5}
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"
                                    />
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75H16.5v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75H16.5v-.75z"
                                    />
                                </svg>
                                <div>
                                    <h3 className="text-sm font-bold text-slate-900">
                                        使い始めるには、お店のQRコードをスキャン
                                    </h3>
                                    <p className="mt-1 text-sm leading-7 text-slate-600">
                                        QRを読み取るだけでメニューが開きます。メニュー閲覧は会員登録なしでもOKです。
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="mt-6 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-slate-500">
                            {quickHighlights.map((text) => (
                                <span key={text} className="flex items-center gap-2">
                                    <span className="h-2 w-2 rounded-full bg-sky-400" />
                                    {text}
                                </span>
                            ))}
                        </div>
                    </div>

                    <div className="mt-16 hidden justify-start lg:flex">
                        <div className="flex h-10 w-6 items-start justify-center rounded-full border-2 border-sky-300/50 p-1">
                            <div className="h-2 w-1 animate-bounce rounded-full bg-sky-400" />
                        </div>
                    </div>
                </div>

                <div className="relative mx-auto w-full max-w-xl">
                    <div className="geo-public-orb-sky absolute -left-10 top-10 h-72 w-72 blur-3xl" />
                    <div className="geo-public-orb-cyan absolute right-0 top-20 h-56 w-56 blur-3xl" />

                    <div className="geo-public-panel-accent relative px-6 py-10 sm:px-10 sm:py-14">
                        <div className="absolute inset-0 bg-grid-pattern opacity-[0.05]" />
                        <div className="absolute right-0 top-0 h-28 w-28 bg-gradient-to-bl from-sky-100/80 to-transparent" />

                        <div className="relative flex items-start justify-between gap-4">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-sky-600">
                                    Campus order flow
                                </p>
                                <h2 className="mt-4 text-3xl font-bold leading-[1.15] text-slate-900 sm:text-4xl">
                                    席から注文できる
                                    <br />
                                    新しい昼休みへ
                                </h2>
                                <p className="mt-6 text-sm leading-7 text-slate-600 sm:text-base">
                                    テーブルや空いている席からQRを読み取って、自分のペースでメニューを選べます。
                                    行列に並ぶ時間をそのまま、食事の時間に戻せます。
                                </p>
                            </div>
                            <div className="shrink-0 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                                QRで開始
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

export default HeroSection;
