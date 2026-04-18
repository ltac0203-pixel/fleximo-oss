import { features } from "@/Components/Welcome/data";

const featureSpans = ["md:col-span-2 xl:col-span-5", "xl:col-span-4", "xl:col-span-3"] as const;

const featureCaptions = [
    "席からでもすぐにオーダー",
    "出来上がりのタイミングを把握",
    "スマホで会計まで完了",
] as const;

function FeaturesSection() {
    return (
        <section className="relative mt-32">
            <div>
                <div className="text-center">
                    <div className="mx-auto flex items-center justify-center gap-3">
                        <div className="h-px w-8 bg-sky-400" />
                        <p className="text-sm font-semibold uppercase tracking-[0.28em] text-sky-600">
                            Features
                        </p>
                    </div>
                    <h2 className="mt-4 text-3xl font-bold text-slate-900 sm:text-4xl">
                        <span className="text-sky-500">Fleximo</span>
                        でランチが変わる
                    </h2>
                    <p className="mx-auto mt-4 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
                        注文、待ち時間の把握、キャッシュレス決済までをひとつにまとめて、
                        学食の混雑をもっと軽くします。
                    </p>
                </div>
            </div>

            <div className="mt-12 grid gap-6 md:grid-cols-2 xl:grid-cols-12">
                {features.map((card, index) => (
                    <div key={card.title} className={featureSpans[index]}>
                        <article
                            className={`geo-surface-interactive group h-full p-8 ${
                                index === 0 ? "geo-public-panel-accent" : "geo-public-panel"
                            }`}
                        >
                            <div className="relative">
                                <div className="flex items-center justify-end gap-4">
                                    <span className="text-sm font-semibold text-slate-300">0{index + 1}</span>
                                </div>
                                <p className="mt-6 text-xs font-semibold uppercase tracking-[0.28em] text-sky-600">
                                    {card.eyebrow}
                                </p>
                                <h3 className="mt-3 text-xl font-bold text-slate-900">{card.title}</h3>
                                <p className="mt-3 text-sm leading-7 text-slate-600">{card.body}</p>
                                <div className="mt-6 h-px w-full bg-gradient-to-r from-sky-200 via-cyan-200 to-transparent" />
                                <div className="mt-6 flex flex-wrap items-center justify-between gap-3">
                                    <span className="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">
                                        {card.meta}
                                    </span>
                                    <span className="text-xs font-medium text-slate-500">
                                        {featureCaptions[index]}
                                    </span>
                                </div>
                            </div>
                        </article>
                    </div>
                ))}
            </div>
        </section>
    );
}

export default FeaturesSection;
