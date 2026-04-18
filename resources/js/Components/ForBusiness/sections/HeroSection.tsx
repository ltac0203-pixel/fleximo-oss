import { impactStats } from "@/Components/ForBusiness/data";
import { PRIMARY_BUTTON_LARGE } from "@/constants/buttonStyles";
import { Link } from "@inertiajs/react";

const heroHighlights = ["初期費用0円", "月額0円", "専用端末不要"] as const;

const heroMetrics = [
    { label: "初期費用", value: "0円" },
    { label: "専用端末", value: "不要" },
    { label: "導入目安", value: "数日" },
] as const;

const operationCards = [
    {
        title: "注文受付を分散",
        body: "お客様のスマホから注文を受けることで、レジ前の混雑を抑えやすくなります。",
        status: "Order",
    },
    {
        title: "キッチンへ即時反映",
        body: "注文内容はそのままKDSへ。伝票の読み違いや口頭伝達のズレを防ぎます。",
        status: "Kitchen",
    },
    {
        title: "決済導線もスマートに",
        body: "会計待ちを作りにくくしながら、キャッシュレスでオペレーションを軽くします。",
        status: "Payment",
    },
] as const;

function HeroSection() {
    return (
        <section className="mt-16 lg:mt-20">
            <div className="grid gap-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.92fr)] lg:items-center">
                <div>
                    <div className="inline-flex items-center gap-3 rounded-full border border-sky-200 bg-white/80 px-4 py-2 text-xs font-semibold uppercase tracking-[0.26em] text-sky-700 shadow-sm backdrop-blur-sm">
                        <span className="flex h-2 w-2 rounded-full bg-sky-400" />
                        飲食店オーナー様向け
                    </div>
                    <h1 className="mt-6 max-w-4xl text-4xl font-bold leading-tight text-slate-900 sm:text-5xl lg:text-6xl">
                        毎日の行列が、<span className="text-sky-500">売上機会の損失</span>になっていませんか？
                    </h1>
                    <p className="mt-6 max-w-2xl text-base leading-relaxed text-slate-600 sm:text-lg">
                        Fleximoなら、初期費用0円・月額0円でモバイルオーダーを導入できます。
                        お客様のスマホが注文端末に変わり、回転率アップと人件費削減を同時に支えます。
                    </p>
                    <div className="mt-10 flex flex-wrap gap-4">
                        <Link href={route("tenant-application.create")} className={PRIMARY_BUTTON_LARGE}>
                            無料でテナント申請
                        </Link>
                    </div>
                    <div className="mt-6 flex flex-wrap gap-3">
                        {heroHighlights.map((highlight) => (
                            <span
                                key={highlight}
                                className="rounded-full border border-sky-200 bg-white/80 px-4 py-2 text-sm font-medium text-sky-700 shadow-sm"
                            >
                                {highlight}
                            </span>
                        ))}
                    </div>
                    <div className="mt-10 grid gap-4 sm:grid-cols-2">
                        <div className="geo-public-panel-soft px-5 py-5">
                            <p className="text-xs font-semibold uppercase tracking-[0.28em] text-sky-600">
                                For peak hours
                            </p>
                            <h2 className="mt-3 text-lg font-bold text-slate-900">注文の受け口を増やす</h2>
                            <p className="mt-3 text-sm leading-7 text-slate-600">
                                レジ前だけに注文が集中しない導線をつくり、ピーク時の負荷をやわらげます。
                            </p>
                        </div>
                        <div className="geo-public-panel-soft px-5 py-5">
                            <p className="text-xs font-semibold uppercase tracking-[0.28em] text-sky-600">
                                For small teams
                            </p>
                            <h2 className="mt-3 text-lg font-bold text-slate-900">オペレーションを軽くする</h2>
                            <p className="mt-3 text-sm leading-7 text-slate-600">
                                注文確認、会計、受け渡しの流れを整理し、少人数でも回しやすい形を目指せます。
                            </p>
                        </div>
                    </div>
                </div>

                <div className="relative mx-auto w-full max-w-xl">
                    <div className="geo-public-orb-sky absolute -left-8 top-8 h-72 w-72 blur-3xl" />
                    <div className="geo-public-orb-cyan absolute right-[-2rem] top-6 h-64 w-64 blur-3xl" />
                    <div className="absolute -left-4 top-20 hidden rounded-full border border-sky-200 bg-white/85 px-4 py-2 text-sm font-semibold text-slate-700 shadow-md backdrop-blur-sm xl:block">
                        Store flow
                    </div>
                    <div className="geo-public-panel-accent relative px-6 py-6 sm:px-8 sm:py-8">
                        <div className="absolute inset-0 bg-grid-pattern opacity-[0.05]" />
                        <div className="relative flex items-start justify-between gap-4">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-sky-600">
                                    Fleximo flow
                                </p>
                                <h2 className="mt-3 text-2xl font-bold text-slate-900 sm:text-3xl">
                                    注文から受け渡しまで、
                                    <br />
                                    導線をひとつに
                                </h2>
                            </div>
                            <div className="rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                                導入しやすい
                            </div>
                        </div>

                        <div className="relative mt-6 grid gap-3 sm:grid-cols-3">
                            {heroMetrics.map((metric) => (
                                <div key={metric.label} className="geo-public-panel-soft px-4 py-4">
                                    <p className="text-xs font-semibold uppercase tracking-[0.26em] text-slate-500">
                                        {metric.label}
                                    </p>
                                    <p className="mt-2 text-3xl font-bold text-slate-900">{metric.value}</p>
                                </div>
                            ))}
                        </div>

                        <div className="relative mt-6 space-y-3">
                            {operationCards.map((card, index) => (
                                <div key={card.title} className="geo-public-panel-soft px-4 py-4">
                                    <div className="flex items-start gap-4">
                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sky-500 text-xs font-semibold text-white shadow-geo-sky">
                                            0{index + 1}
                                        </div>
                                        <div className="flex-1">
                                            <div className="flex flex-wrap items-center justify-between gap-3">
                                                <h3 className="text-base font-bold text-slate-900">{card.title}</h3>
                                                <span className="rounded-full border border-sky-200 bg-white px-3 py-1 text-xs font-semibold text-sky-700">
                                                    {card.status}
                                                </span>
                                            </div>
                                            <p className="mt-3 text-sm leading-7 text-slate-600">{card.body}</p>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

export function ImpactStatsSection() {
    return (
        <div className="mt-24">
            <div className="geo-public-shell px-8 py-10">
                <div className="absolute inset-0 bg-grid-pattern opacity-[0.04]" />
                <div className="relative grid gap-8 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:items-center">
                    <div className="max-w-sm">
                        <p className="text-sm font-semibold uppercase tracking-[0.28em] text-sky-600">
                            Impact snapshot
                        </p>
                        <h2 className="mt-3 text-3xl font-bold text-slate-900">導入後の変化を、現場視点で。</h2>
                        <p className="mt-4 text-sm leading-7 text-slate-600 sm:text-base">
                            ピーク時の回転、注文ミス、導入負担。まず確認したいポイントをひと目で把握できます。
                        </p>
                    </div>
                    <div className="grid gap-4 text-center sm:grid-cols-3">
                        {impactStats.map((stat, index) => (
                            <div
                                key={stat.label}
                                className={`geo-public-panel px-5 py-6 ${index === 1 ? "sm:-translate-y-4" : ""}`}
                            >
                                <p className="text-4xl font-bold sm:text-5xl">
                                    <span className="text-sky-500">{stat.value}</span>
                                </p>
                                <p className="mt-2 text-sm font-medium text-slate-600">{stat.label}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}

export default HeroSection;
