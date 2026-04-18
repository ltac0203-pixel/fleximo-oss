import { stats } from "@/Components/Welcome/data";

const statGuides = ["日々の注文に浸透", "ピーク時の混雑を圧縮"] as const;

const snapshotPoints = [
    "列に並ぶ時間を短くする",
    "注文タイミングを自分で選べる",
    "登録から利用開始までが軽い",
] as const;

function StatsSection() {
    return (
        <section className="relative mt-24 sm:mt-28">
            <div className="geo-public-shell px-6 py-8 sm:px-8 sm:py-10">
                <div className="absolute inset-0 bg-grid-pattern opacity-[0.04]" />
                <div className="geo-public-orb-sky absolute -right-16 top-0 h-64 w-64 blur-3xl" />
                <div className="relative grid gap-8 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)] lg:items-end">
                    <div className="max-w-sm">
                        <p className="text-sm font-semibold uppercase tracking-[0.28em] text-sky-600">
                            Fast facts
                        </p>
                        <h2 className="mt-3 text-2xl font-bold text-slate-900 sm:text-3xl">
                            忙しいピーク時に必要な情報だけを、すばやく。
                        </h2>
                        <p className="mt-4 text-sm leading-7 text-slate-600 sm:text-base">
                            食事時の混雑を軽くするために大切なのは、操作を増やさずに流れを整えること。
                            Fleximoはそのための要点をコンパクトにまとめています。
                        </p>
                        <div className="geo-public-panel-soft mt-8 px-5 py-5">
                            <p className="text-xs font-semibold uppercase tracking-[0.28em] text-sky-600">
                                What matters
                            </p>
                            <div className="mt-4 space-y-3">
                                {snapshotPoints.map((point) => (
                                    <div key={point} className="flex items-start gap-3 text-sm text-slate-600">
                                        <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-sky-400" />
                                        <span>{point}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    <div className="grid flex-1 gap-4 md:grid-cols-2">
                        {stats.map((stat, index) => (
                            <article
                                key={stat.label}
                                className="geo-public-panel px-5 py-5"
                            >
                                <div className="inline-flex rounded-full border border-sky-100 bg-sky-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-sky-700">
                                    {statGuides[index]}
                                </div>
                                <div className="flex items-center justify-between gap-4">
                                    <p className="mt-5 text-4xl font-bold text-slate-900 sm:text-5xl">
                                        <span className="text-sky-500">{stat.value}</span>
                                    </p>
                                    <span className="text-sm font-semibold text-slate-300">
                                        0{index + 1}
                                    </span>
                                </div>
                                <p className="mt-3 text-sm font-semibold text-slate-700">{stat.label}</p>
                                <p className="mt-2 text-sm leading-7 text-slate-600">
                                    {stat.description}
                                </p>
                            </article>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}

export default StatsSection;
