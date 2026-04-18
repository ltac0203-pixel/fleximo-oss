import { Link } from "@inertiajs/react";
import {
    INVERTED_BUTTON_PRIMARY,
    INVERTED_BUTTON_SECONDARY,
} from "@/constants/buttonStyles";

interface FinalCtaSectionProps {
    isLoggedIn: boolean;
}

const reassuranceItems = ["すぐ利用開始", "キャッシュレス対応"] as const;

const ctaSteps = ["登録", "注文", "受け取り"] as const;

function FinalCtaSection({ isLoggedIn }: FinalCtaSectionProps) {
    const ctaHref = isLoggedIn ? route("dashboard") : route("register");
    const ctaLabel = isLoggedIn ? "ダッシュボードへ" : "無料で会員登録";

    return (
        <div className="mt-32">
            <div className="geo-public-band p-8 md:p-12 lg:p-14">
                <div className="absolute inset-0 bg-grid-pattern opacity-10" />
                <div className="geo-public-orb-ice absolute -left-8 top-8 h-48 w-48 blur-3xl" />
                <div className="geo-public-orb-cyan absolute right-[-5rem] top-0 h-72 w-72 blur-3xl" />
                <div className="relative grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.92fr)] lg:items-center">
                    <div>
                        <div className="inline-flex items-center gap-2 rounded-full bg-white/20 px-4 py-1.5">
                            <span className="text-sm font-medium text-white">登録30秒 ・ 完全無料</span>
                        </div>
                        <h2 className="mt-6 text-3xl font-bold leading-tight text-white sm:text-4xl lg:text-5xl">
                            今日の注文から使える
                        </h2>
                        <p className="mt-6 max-w-lg text-base leading-relaxed text-white/80">
                            次のピーク時から、行列とサヨナラしませんか？
                            30秒で登録して、すぐに注文を始められます。
                        </p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            {reassuranceItems.map((item) => (
                                <span
                                    key={item}
                                    className="rounded-full border border-white/25 bg-white/10 px-4 py-2 text-sm font-medium text-white/85"
                                >
                                    {item}
                                </span>
                            ))}
                        </div>
                    </div>

                    <div className="border border-white/15 bg-white/10 p-6 backdrop-blur-sm sm:p-8">
                        <p className="text-xs font-semibold uppercase tracking-[0.28em] text-white/70">
                            Next order flow
                        </p>
                        <div className="mt-5 flex items-center gap-3">
                            {ctaSteps.map((step, index) => (
                                <div key={step} className="flex min-w-0 flex-1 items-center gap-3">
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-white/25 bg-white/10 text-sm font-semibold text-white">
                                        {index + 1}
                                    </div>
                                    <span className="truncate text-sm font-medium text-white/85">{step}</span>
                                </div>
                            ))}
                        </div>

                        <div className="mt-8 flex flex-wrap gap-4">
                            <Link
                                href={ctaHref}
                                className={INVERTED_BUTTON_PRIMARY}
                            >
                                {ctaLabel}
                            </Link>
                            {!isLoggedIn && (
                                <Link
                                    href={route("login")}
                                    className={INVERTED_BUTTON_SECONDARY}
                                >
                                    ログイン
                                </Link>
                            )}
                        </div>

                        <p className="mt-5 text-sm leading-7 text-white/75">
                            会員登録後すぐに利用できます。次のご注文から、そのまま始められます。
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default FinalCtaSection;
